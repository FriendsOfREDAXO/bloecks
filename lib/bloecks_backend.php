<?php

namespace FriendsOfRedaxo\Bloecks;

use rex;
use rex_addon;
use rex_article_cache;
use rex_be_controller;
use rex_content_service;
use rex_csrf_token;
use rex_extension;
use rex_extension_point;
use rex_i18n;
use rex_sql;
use rex_sql_exception;
use rex_url;
use rex_view;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;

/**
 * Backend functionality for BLOECKS addon.
 * Reduced complexity - main logic moved to utility classes.
 */
class Backend
{
    /**
     * Initialize the backend functionality.
     */
    public static function init(): void
    {
        $user = rex::getUser();
        if ($user === null) {
            return;
        }

        self::registerCopyPasteExtensions();
        self::registerDragDropExtensions();
        self::loadAssets();
    }

    /**
     * Register copy/paste extension points.
     */
    private static function registerCopyPasteExtensions(): void
    {
        if (!PermissionUtility::shouldShowCopyPasteButtons()) {
            return;
        }

        rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', self::addButtons(...));
        rex_extension::register('STRUCTURE_CONTENT_MODULE_SELECT', self::addPasteToModuleSelect(...));
        rex_extension::register('STRUCTURE_CONTENT_BEFORE_SLICES', self::process(...));
    }

    /**
     * Register drag & drop extension points.
     */
    private static function registerDragDropExtensions(): void
    {
        if (!PermissionUtility::shouldShowDragDrop()) {
            return;
        }

        rex_extension::register('SLICE_SHOW', Wrapper::addDragDropWrapper(...), rex_extension::EARLY);
        rex_extension::register('SLICE_MENU', Wrapper::addDragHandle(...));
    }

    /**
     * Load assets if needed.
     */
    private static function loadAssets(): void
    {
        if (rex_be_controller::getCurrentPagePart(1) !== 'content') {
            return;
        }

        $loadCopyPasteAssets = PermissionUtility::shouldShowCopyPasteButtons();
        $loadDragDropAssets = PermissionUtility::shouldShowDragDrop();

        if (!$loadCopyPasteAssets && !$loadDragDropAssets) {
            return;
        }

        self::setJavaScriptConfig($loadCopyPasteAssets, $loadDragDropAssets);
        self::loadJavaScriptAndCSS($loadDragDropAssets);
    }

    /**
     * Set JavaScript configuration via rex_view::setJsProperty.
     */
    private static function setJavaScriptConfig(bool $copyPasteEnabled, bool $dragDropEnabled): void
    {
        $user = rex::getUser();
        if ($user === null) {
            return;
        }

        rex_view::setJsProperty('bloecks', [
            'token' => rex_csrf_token::factory('bloecks')->getValue(),
            'perm_order' => $user->hasPerm('bloecks[]') || $user->hasPerm('bloecks[order]'),
            'perm_copy' => $user->hasPerm('bloecks[]') || $user->hasPerm('bloecks[copy]'),
            'api_url' => rex_url::backendController(['rex-api-call' => 'bloecks_api']),
            'drag_drop_enabled' => $dragDropEnabled,
            'copy_paste_enabled' => $copyPasteEnabled,
            'multiClipboard' => rex_addon::get('bloecks')->getConfig('enable_multi_clipboard', false),
            'pastePosition' => rex_addon::get('bloecks')->getConfig('paste_position', 'after'),
            'messages' => [
                'success' => rex_i18n::msg('bloecks_success'),
                'error' => rex_i18n::msg('bloecks_error'),
                'copy_success' => rex_i18n::msg('bloecks_copy_success'),
                'paste_success' => rex_i18n::msg('bloecks_paste_success'),
                'order_success' => rex_i18n::msg('bloecks_order_success'),
                'confirm_clear_clipboard' => rex_i18n::msg('bloecks_confirm_clear_clipboard'),
            ],
        ]);
    }

    /**
     * Load JavaScript and CSS assets.
     */
    private static function loadJavaScriptAndCSS(bool $loadDragDropAssets): void
    {
        $addon = rex_addon::get('bloecks');

        // Load SortableJS for drag & drop only if needed
        if ($loadDragDropAssets) {
            rex_view::addJsFile($addon->getAssetsUrl('js/sortable.min.js'));
        }

        rex_view::addJsFile($addon->getAssetsUrl('js/bloecks.js') . '?v=' . time());
        rex_view::addCssFile($addon->getAssetsUrl('css/bloecks.css') . '?v=' . time());
    }

    /**
     * Add copy/cut/paste buttons to slice menu.
     * @param rex_extension_point<mixed> $ep
     * @return array<mixed>
     * @api Extension point callback for STRUCTURE_CONTENT_SLICE_MENU
     */
    public static function addButtons(rex_extension_point $ep): array
    {
        if (!self::shouldShowButtons($ep)) {
            $subject = $ep->getSubject();
            return is_array($subject) ? $subject : [];
        }

        $params = ButtonUtility::extractButtonParams($ep);
        $clipboard = ClipboardUtility::getClipboard();
        $sliceId = is_numeric($params['slice_id']) ? (int) $params['slice_id'] : 0;
        $isSource = ClipboardUtility::isClipboardSource($sliceId);

        $buttons = [];
        $buttons[] = ButtonUtility::createCopyButton($params, $clipboard, $isSource);
        $buttons[] = ButtonUtility::createCutButton($params, $clipboard, $isSource);

        if (ClipboardUtility::hasClipboardContent()) {
            $addon = rex_addon::get('bloecks');
            if ($clipboard !== null) {
                $buttons[] = ButtonUtility::createPasteButton($params, $clipboard, $addon);
            }
        }

        $subject = $ep->getSubject();
        $subjectArray = is_array($subject) ? $subject : [];
        return array_merge($subjectArray, $buttons);
    }

    /**
     * Check if buttons should be shown for this slice.
     * @param rex_extension_point<mixed> $ep
     */
    private static function shouldShowButtons(rex_extension_point $ep): bool
    {
        $perm = (bool) $ep->getParam('perm');
        if (!$perm) {
            return false; // No module permissions
        }

        $sliceId = $ep->getParam('slice_id');
        $articleId = is_numeric($ep->getParam('article_id')) ? (int) $ep->getParam('article_id') : 0;
        $clang = is_numeric($ep->getParam('clang')) ? (int) $ep->getParam('clang') : 1;
        $moduleId = is_numeric($ep->getParam('module_id')) ? (int) $ep->getParam('module_id') : null;

        // Check for template/module exclusions
        if (PermissionUtility::isExcluded($articleId, $clang, $moduleId)) {
            return false;
        }

        // Check if user has content edit permissions for this article
        return PermissionUtility::hasContentEditPermission($articleId, $clang, $moduleId);
    }

    /**
     * Add paste button to module select menu when no slices exist yet.
     * @param rex_extension_point<mixed> $ep
     * @api Extension point callback for STRUCTURE_CONTENT_MODULE_SELECT
     */
    public static function addPasteToModuleSelect(rex_extension_point $ep): string
    {
        if (!ClipboardUtility::hasClipboardContent()) {
            $subject = $ep->getSubject();
            return is_string($subject) ? $subject : '';
        }

        $articleId = rex_request('article_id', 'int');
        $clang = rex_request('clang', 'int');
        $ctype = rex_request('ctype', 'int', 1);

        if ($articleId <= 0 || $clang <= 0) {
            $subject = $ep->getSubject();
            return is_string($subject) ? $subject : '';
        }

        // Check for template exclusions
        if (PermissionUtility::isExcluded($articleId, $clang, null)) {
            $subject = $ep->getSubject();
            return is_string($subject) ? $subject : '';
        }

        // Check if user has content edit permissions
        if (!PermissionUtility::hasContentEditPermission($articleId, $clang, null)) {
            $subject = $ep->getSubject();
            return is_string($subject) ? $subject : '';
        }

        // Don't show button if there are already slices
        if (ButtonUtility::hasExistingSlicesInCtype($articleId, $clang, $ctype)) {
            $subject = $ep->getSubject();
            return is_string($subject) ? $subject : '';
        }

        $addon = rex_addon::get('bloecks');
        $pastePosition = $addon->getConfig('paste_position', 'after');
        $pastePositionString = is_string($pastePosition) ? $pastePosition : 'after';
        $pasteButton = ButtonUtility::createModuleSelectPasteButton($articleId, $clang, $ctype, $pastePositionString);

        $subject = $ep->getSubject();
        $subjectString = is_string($subject) ? $subject : '';
        return $pasteButton . $subjectString;
    }

    /**
     * Process copy/cut/paste actions.
     * @param rex_extension_point<mixed> $ep
     * @api Extension point callback for STRUCTURE_CONTENT_BEFORE_SLICES
     */
    public static function process(rex_extension_point $ep): void
    {
        $action = rex_request('bloecks_action', 'string');
        if ($action === '') {
            return;
        }

        if (!PermissionUtility::shouldShowCopyPasteButtons()) {
            return;
        }

        $msg = self::processAction($action);

        if ($msg !== '' && $msg !== '0') {
            $subject = $ep->getSubject();
            $subjectString = is_string($subject) ? $subject : '';
            $ep->setSubject($msg . $subjectString);
        }
    }

    /**
     * Process a specific action.
     * @api
     */
    private static function processAction(string $action): string
    {
        switch ($action) {
            case 'copy':
            case 'cut':
                return self::processCopyOrCut($action);
            case 'paste':
                return self::processPaste();
            default:
                return '';
        }
    }

    /**
     * Process copy or cut action.
     * @api
     */
    private static function processCopyOrCut(string $action): string
    {
        $sliceId = rex_request('slice_id', 'int');
        if ($sliceId <= 0) {
            return '';
        }

        $sql = rex_sql::factory();
        $result = $sql->getArray('SELECT * FROM ' . rex::getTablePrefix() . 'article_slice WHERE id=?', [$sliceId]);
        $row = count($result) === 0 ? null : $result[0];

        if (!is_array($row)) {
            return rex_view::warning(rex_i18n::msg('bloecks_error_slice_not_found'));
        }

        if (!self::validateSlicePermissions($row)) {
            return rex_view::warning(rex_i18n::msg('bloecks_error_no_permission'));
        }

        ClipboardUtility::storeInClipboard($sliceId, $row, $action);

        $successMsg = $action === 'cut' ? rex_i18n::msg('bloecks_slice_cut') : rex_i18n::msg('bloecks_slice_copied');
        return rex_view::success($successMsg);
    }

    /**
     * Validate permissions for a slice.
     * @param array<string, mixed> $row
     */
    private static function validateSlicePermissions(array $row): bool
    {
        $user = rex::getUser();
        if ($user === null) {
            return false;
        }

        $modulePerm = $user->getComplexPerm('modules');
        if ($modulePerm === null || !method_exists($modulePerm, 'hasPerm')) {
            return false;
        }

        $moduleId = is_numeric($row['module_id']) ? (int) $row['module_id'] : 0;
        if (!$modulePerm->hasPerm($moduleId)) {
            return false;
        }

        return PermissionUtility::hasContentEditPermission(
            is_numeric($row['article_id']) ? (int) $row['article_id'] : 0,
            is_numeric($row['clang_id']) ? (int) $row['clang_id'] : 0,
            is_numeric($row['module_id']) ? (int) $row['module_id'] : null,
        );
    }

    /**
     * Process paste action.
     * @api
     */
    private static function processPaste(): string
    {
        $clipboard = ClipboardUtility::getClipboard();
        if (!is_array($clipboard) || !isset($clipboard['data']) || !is_array($clipboard['data'])) {
            return rex_view::warning(rex_i18n::msg('bloecks_error_clipboard_empty'));
        }

        $targetSlice = rex_request('bloecks_target', 'int');
        $articleId = rex_request('article_id', 'int');
        $clang = rex_request('clang', 'int');
        $ctype = rex_request('ctype', 'int', 1);

        if ($articleId <= 0 || $clang <= 0) {
            return rex_view::warning(rex_i18n::msg('bloecks_error_missing_parameters'));
        }

        $data = $clipboard['data'];

        $moduleId = isset($data['module_id']) && is_numeric($data['module_id']) ? (int) $data['module_id'] : null;
        if (!PermissionUtility::hasContentEditPermission($articleId, $clang, $moduleId)) {
            return rex_view::warning(rex_i18n::msg('bloecks_error_no_content_permission'));
        }

        /** @var array<string, mixed> $dataArray */
        $dataArray = $data;
        return self::insertSlice($targetSlice, $articleId, $clang, $ctype, $dataArray, $clipboard);
    }

    /**
     * Insert a slice and handle cleanup.
     * @param array<string, mixed> $data
     * @param array<string, mixed> $clipboard
     */
    private static function insertSlice(int $targetSlice, int $articleId, int $clang, int $ctype, array $data, array $clipboard): string
    {
        $priority = SliceUtility::calculateInsertionPriority($targetSlice, $articleId, $clang);

        $ins = rex_sql::factory();
        $ins->setTable(rex::getTablePrefix() . 'article_slice');
        $ins->setValue('article_id', $articleId);
        $ins->setValue('clang_id', $clang);
        $ins->setValue('ctype_id', $ctype);
        $ins->setValue('priority', $priority);
        $ins->setValue('revision', 0); // Default revision (LIVE)

        foreach ($data as $k => $v) {
            if (is_string($v) || is_int($v) || is_float($v) || is_bool($v) || $v === null) {
                $ins->setValue($k, $v);
            }
        }

        $ins->addGlobalCreateFields();
        $ins->addGlobalUpdateFields();

        try {
            $ins->insert();

            if ($clipboard['action'] === 'cut') {
                $srcId = isset($clipboard['source_slice_id']) && is_numeric($clipboard['source_slice_id']) ? (int) $clipboard['source_slice_id'] : 0;
                if ($srcId !== 0) {
                    rex_content_service::deleteSlice($srcId);
                }
                rex_unset_session('bloecks_clipboard');
            }

            rex_article_cache::delete($articleId, $clang);
            return rex_view::success(rex_i18n::msg('bloecks_slice_inserted'));
        } catch (rex_sql_exception $e) {
            return rex_view::warning(sprintf(rex_i18n::msg('bloecks_error_insert_failed'), $e->getMessage()));
        }
    }

    /**
     * Output JavaScript configuration variables.
     * @deprecated Use rex_view::setJsProperty instead
     * @api
     */
    public static function outputJavaScriptConfig(): void
    {
        $multiClipboardEnabled = PermissionUtility::isMultiClipboardAvailable();

        echo '<script type="text/javascript">';
        echo 'var BLOECKS_MULTI_CLIPBOARD = ' . ($multiClipboardEnabled ? 'true' : 'false') . ';';
        echo '</script>';
    }

    /**
     * Clear clipboard at session start for security.
     * Delegated to ClipboardUtility.
     * @api
     */
    public static function clearClipboardOnSessionStart(): void
    {
        ClipboardUtility::clearClipboardOnSessionStart();
    }

    /**
     * Clear all clipboard data from session.
     * Delegated to ClipboardUtility.
     * @api
     */
    public static function clearClipboard(): void
    {
        ClipboardUtility::clearClipboard();
    }

    /**
     * Check if multi-clipboard is available for current user.
     * Delegated to PermissionUtility.
     */
    public static function isMultiClipboardAvailable(): bool
    {
        return PermissionUtility::isMultiClipboardAvailable();
    }
}
