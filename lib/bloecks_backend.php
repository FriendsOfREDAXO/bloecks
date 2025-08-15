<?php

namespace FriendsOfRedaxo\Bloecks;

use rex;
use rex_addon;
use rex_article;
use rex_article_cache;
use rex_article_revision;
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

use function count;
use function in_array;
use function rex_session;
use function rex_set_session;
use function rex_unset_session;
use function sprintf;

/**
 * Backend functionality for BLOECKS addon.
 */
class Backend
{
    /**
     * In                    rex_article_cache::delete($articleId, $clang);.
     *
     * // Create success message with hidden new slice ID embedded in the message
     * $successMessage = rex_i18n::msg('bloecks_slice_inserted') .
     * '<span style="display: none;" data-bloecks-new-slice-id="' . $newSliceId . '"></span>';
     * $msg = rex_view::success($successMessage);ze the backend functionality.
     */
    public static function init(): void
    {
        if (!rex::getUser()) {
            return;
        }

        $addon = rex_addon::get('bloecks');
        $copyPasteEnabled = (bool) $addon->getConfig('enable_copy_paste', false);
        $dragDropEnabled = (bool) $addon->getConfig('enable_drag_drop', false);

        // Only register extension points if features are enabled AND user has permissions
        if ($copyPasteEnabled
            && (rex::getUser()->hasPerm('bloecks[]') || rex::getUser()->hasPerm('bloecks[copy]'))) {
            // Register slice menu extensions for copy/paste
            rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', self::addButtons(...));

            // Add paste button to module select menu when no slices exist
            rex_extension::register('STRUCTURE_CONTENT_MODULE_SELECT', self::addPasteToModuleSelect(...));

            // Process copy/cut/paste actions
            rex_extension::register('STRUCTURE_CONTENT_BEFORE_SLICES', self::process(...));
        }

        // Register drag & drop extension points if enabled AND user has permissions
        if ($dragDropEnabled
            && (rex::getUser()->hasPerm('bloecks[]') || rex::getUser()->hasPerm('bloecks[order]'))) {
            rex_extension::register('SLICE_SHOW', Wrapper::addDragDropWrapper(...), rex_extension::EARLY);
            rex_extension::register('SLICE_MENU', Wrapper::addDragHandle(...));
        }

        // Load assets on content edit pages ONLY if features are enabled
        if ('content' === rex_be_controller::getCurrentPagePart(1)) {
            // Only load assets if at least one feature is enabled and user has permissions
            $loadCopyPasteAssets = $copyPasteEnabled
                && (rex::getUser()->hasPerm('bloecks[]') || rex::getUser()->hasPerm('bloecks[copy]'));
            $loadDragDropAssets = $dragDropEnabled
                && (rex::getUser()->hasPerm('bloecks[]') || rex::getUser()->hasPerm('bloecks[order]'));

            if ($loadCopyPasteAssets || $loadDragDropAssets) {
                // JS config for drag & drop ordering
                rex_view::setJsProperty('bloecks', [
                    'token' => rex_csrf_token::factory('bloecks')->getValue(),
                    'perm_order' => rex::getUser()->hasPerm('bloecks[]') || rex::getUser()->hasPerm('bloecks[order]'),
                    'enable_copy_paste' => $loadCopyPasteAssets,
                    'enable_drag_drop' => $loadDragDropAssets,
                ]);

                // Load SortableJS for drag & drop only if drag & drop is enabled and user has permissions
                if ($loadDragDropAssets) {
                    rex_view::addJsFile($addon->getAssetsUrl('js/sortable.min.js'));
                }

                rex_view::addJsFile($addon->getAssetsUrl('js/bloecks.js') . '?v=' . time());
                rex_view::addCssFile($addon->getAssetsUrl('css/bloecks.css') . '?v=' . time());
            }
        }
    }

    /**
     * Add copy/cut/paste buttons to slice menu.
     */
    public static function addButtons(rex_extension_point $ep): array
    {
        $addon = rex_addon::get('bloecks');
        $user = rex::getUser();

        if (!$addon->getConfig('enable_copy_paste', false)) {
            return $ep->getSubject();
        }
        if (!$user->hasPerm('bloecks[]') && !$user->hasPerm('bloecks[copy]')) {
            return $ep->getSubject();
        }
        if (!$ep->getParam('perm')) {
            return $ep->getSubject();
        } // No module permissions

        $sliceId = $ep->getParam('slice_id');
        $articleId = $ep->getParam('article_id');
        $clang = $ep->getParam('clang');
        $ctype = $ep->getParam('ctype');
        $moduleId = $ep->getParam('module_id');

        // Check for template/module exclusions
        if (self::isExcluded($articleId, $clang, $moduleId)) {
            return $ep->getSubject();
        }

        // Check if user has content edit permissions for this article
        if (!self::hasContentEditPermission($articleId, $clang, $moduleId)) {
            return $ep->getSubject();
        }

        // Get revision from Version plugin if available
        $revision = 0; // Default revision (LIVE)
        if (class_exists('rex_article_revision')) {
            $revision = rex_article_revision::getSessionArticleRevision($articleId);
        }

        // Get category_id for proper URL construction
        $categoryId = 0;
        if ($articleId) {
            $article = rex_article::get($articleId, $clang);
            if ($article) {
                $categoryId = $article->getCategoryId();
            }
        }

        $clipboard = rex_session('bloecks_clipboard', 'array', null);
        $isSource = $clipboard && (int) $clipboard['source_slice_id'] === (int) $sliceId;

        $baseParams = [
            'page' => 'content/edit',
            'article_id' => $articleId,
            'category_id' => $categoryId,
            'clang' => $clang,
            'ctype' => $ctype,
            'slice_id' => $sliceId,
            'module_id' => $moduleId,
            'revision' => $revision,
        ];

        $buttons = [];

        // Copy button
        $buttons[] = [
            'hidden_label' => 'Copy Slice',
            'url' => rex_url::backendController($baseParams + ['bloecks_action' => 'copy']),
            'icon' => 'copy',
            'attributes' => [
                'class' => ['btn', 'btn-default', $isSource && 'copy' === $clipboard['action'] ? 'is-copied' : ''],
                'title' => rex_i18n::msg('bloecks_copy_slice'),
                'data-pjax-no-history' => 'true',
            ],
        ];

        // Cut button
        $buttons[] = [
            'hidden_label' => 'Cut Slice',
            'url' => rex_url::backendController($baseParams + ['bloecks_action' => 'cut']),
            'icon' => 'cut',
            'attributes' => [
                'class' => ['btn', 'btn-default', $isSource && 'cut' === $clipboard['action'] ? 'is-cut' : ''],
                'title' => rex_i18n::msg('bloecks_cut_slice'),
                'data-pjax-no-history' => 'true',
            ],
        ];

        // Paste button - always available in slice menu
        if ($clipboard) {
            $sourceInfo = $clipboard['source_info'] ?? null;
            $tooltipText = rex_i18n::msg('bloecks_paste_slice');

            if ($sourceInfo) {
                $actionText = 'cut' === $clipboard['action'] ? rex_i18n::msg('bloecks_action_cut') : rex_i18n::msg('bloecks_action_copied');
                $tooltipText = sprintf(
                    '%s: "%s" aus "%s" (ID: %d)',
                    $actionText,
                    $sourceInfo['module_name'],
                    $sourceInfo['article_name'],
                    $sourceInfo['article_id'],
                );
            }

            $buttons[] = [
                'hidden_label' => 'Paste after',
                'url' => rex_url::backendController($baseParams + [
                    'bloecks_action' => 'paste',
                    'bloecks_target' => $sliceId,
                ]),
                'icon' => 'paste',
                'attributes' => [
                    'class' => ['btn', 'btn-default'],
                    'title' => $tooltipText,
                    'data-pjax-no-history' => 'true',
                ],
            ];
        }

        return array_merge((array) $ep->getSubject(), $buttons);
    }

    /**
     * Add paste button to module select menu when no slices exist yet.
     */
    public static function addPasteToModuleSelect(rex_extension_point $ep): string
    {
        $addon = rex_addon::get('bloecks');
        $user = rex::getUser();

        if (!$addon->getConfig('enable_copy_paste', false)) {
            return $ep->getSubject();
        }
        if (!$user->hasPerm('bloecks[]') && !$user->hasPerm('bloecks[copy]')) {
            return $ep->getSubject();
        }

        // Only show paste button if there's something in clipboard
        $clipboard = rex_session('bloecks_clipboard', 'array', null);
        if (!$clipboard) {
            return $ep->getSubject();
        }

        $articleId = rex_request('article_id', 'int');
        $clang = rex_request('clang', 'int');
        $ctype = rex_request('ctype', 'int', 1);

        if (!$articleId || !$clang) {
            return $ep->getSubject();
        }

        // Check for template exclusions (we can't check module here since it's the module select page)
        if (self::isExcluded($articleId, $clang, null)) {
            return $ep->getSubject();
        }

        // Check if user has content edit permissions for this article
        if (!self::hasContentEditPermission($articleId, $clang, null)) {
            return $ep->getSubject();
        }

        // Get revision from Version plugin if available
        $revision = 0; // Default revision (LIVE)
        if (class_exists('rex_article_revision')) {
            $revision = rex_article_revision::getSessionArticleRevision($articleId);
        }

        // Check if there are already slices in this ctype - if yes, don't show button
        $sql = rex_sql::factory();
        $existingSlices = $sql->getArray(
            'SELECT id FROM ' . rex::getTablePrefix() . 'article_slice
             WHERE article_id=? AND clang_id=? AND ctype_id=? AND revision=?',
            [$articleId, $clang, $ctype, $revision],
        );

        if (count($existingSlices) > 0) {
            // There are already slices, don't show paste button here
            return $ep->getSubject();
        }

        // Get category_id for proper URL construction
        $categoryId = 0;
        if ($articleId) {
            $article = rex_article::get($articleId, $clang);
            if ($article) {
                $categoryId = $article->getCategoryId();
            }
        }

        $baseParams = [
            'page' => 'content/edit',
            'article_id' => $articleId,
            'category_id' => $categoryId,
            'clang' => $clang,
            'ctype' => $ctype,
            'revision' => $revision,
        ];

        // Add paste button before module selection
        $sourceInfo = $clipboard['source_info'] ?? null;

        // Bestimme den Modulnamen für den Button-Text
        $moduleName = '';

        if ($sourceInfo && !empty($sourceInfo['module_name'])) {
            // Verwende module_name aus source_info wenn verfügbar
            $moduleName = $sourceInfo['module_name'];
            $tooltipText = sprintf(
                'Fügt ein: "%s" aus "%s" (ID: %d)',
                $sourceInfo['module_name'],
                $sourceInfo['article_name'],
                $sourceInfo['article_id'],
            );
        } else {
            // Fallback: Hole Modulnamen aus clipboard data
            $moduleId = $clipboard['data']['module_id'] ?? null;
            if ($moduleId) {
                $moduleSql = rex_sql::factory();
                $moduleRow = $moduleSql->getArray('SELECT name FROM ' . rex::getTablePrefix() . 'module WHERE id=?', [$moduleId]);
                $moduleName = $moduleRow ? $moduleRow[0]['name'] : rex_i18n::msg('bloecks_error_unknown_module');
            }
            $tooltipText = rex_i18n::msg('bloecks_paste_from_clipboard');
        }

        // Button-Text mit REDAXO-Sprachsystem (Parameter wird automatisch in {0} eingesetzt)
        if (!empty($moduleName)) {
            $buttonText = rex_i18n::msg('bloecks_paste_module', $moduleName);
        } else {
            $buttonText = rex_i18n::msg('bloecks_action_paste');
        }

        // Tooltip - sollte immer "Fügt ein: ..." sein, nicht "Kopiert: ..."
        if (!empty($moduleName)) {
            $tooltipText = sprintf('Fügt ein: "%s"', $moduleName);
            if ($sourceInfo) {
                $tooltipText = sprintf(
                    'Fügt ein: "%s" aus "%s" (ID: %d)',
                    $sourceInfo['module_name'],
                    $sourceInfo['article_name'],
                    $sourceInfo['article_id'],
                );
            }
        } else {
            $tooltipText = rex_i18n::msg('bloecks_paste_from_clipboard');
        }

        $pasteButton = sprintf(
            '<div class="rex-toolbar"><div class="btn-toolbar"><a href="%s" class="btn btn-success" title="%s"><i class="rex-icon rex-icon-paste"></i> %s</a></div></div>',
            rex_url::backendController($baseParams + [
                'bloecks_action' => 'paste',
                'bloecks_target' => 0,  // Insert at beginning (for empty pages this is correct)
            ]),
            htmlspecialchars($tooltipText),
            htmlspecialchars($buttonText),
        );

        return $pasteButton . $ep->getSubject();
    }

    /**
     * Process copy/cut/paste actions.
     */
    public static function process(rex_extension_point $ep): void
    {
        $action = rex_request('bloecks_action', 'string');
        if (!$action) {
            return;
        }

        $user = rex::getUser();
        if (!$user->hasPerm('bloecks[]') && !$user->hasPerm('bloecks[copy]')) {
            return;
        }

        $msg = '';

        switch ($action) {
            case 'copy':
            case 'cut':
                $sliceId = rex_request('slice_id', 'int');
                if (!$sliceId) {
                    break;
                }

                $sql = rex_sql::factory();
                $row = $sql->getArray('SELECT * FROM ' . rex::getTablePrefix() . 'article_slice WHERE id=?', [$sliceId]);

                if (!$row) {
                    $msg = rex_view::warning(rex_i18n::msg('bloecks_error_slice_not_found'));
                    break;
                }

                $row = $row[0];

                if (!$user->getComplexPerm('modules')->hasPerm($row['module_id'])) {
                    $msg = rex_view::warning(rex_i18n::msg('bloecks_error_no_module_permission'));
                    break;
                }

                // Check if user has content edit permissions for this slice
                if (!self::hasContentEditPermission($row['article_id'], $row['clang_id'], $row['module_id'])) {
                    $msg = rex_view::warning(rex_i18n::msg('bloecks_error_no_content_permission'));
                    break;
                }

                // Store slice data in session
                $fields = ['module_id', 'status'];
                for ($i = 1; $i <= 20; ++$i) {
                    $fields[] = 'value' . $i;
                }
                for ($i = 1; $i <= 5; ++$i) {
                    $fields[] = 'media' . $i;
                    $fields[] = 'medialist' . $i;
                }
                for ($i = 1; $i <= 5; ++$i) {
                    $fields[] = 'link' . $i;
                    $fields[] = 'linklist' . $i;
                }

                $data = [];
                foreach ($fields as $field) {
                    $data[$field] = $row[$field];
                }

                // Get source slice info for tooltips
                $sourceArticle = rex_article::get($row['article_id'], $row['clang_id']);

                // Get module name
                $moduleSql = rex_sql::factory();
                $moduleRow = $moduleSql->getArray('SELECT name FROM ' . rex::getTablePrefix() . 'module WHERE id=?', [$row['module_id']]);
                $moduleName = $moduleRow ? $moduleRow[0]['name'] : rex_i18n::msg('bloecks_error_unknown_module');

                rex_set_session('bloecks_clipboard', [
                    'data' => $data,
                    'source_slice_id' => $sliceId,
                    'source_revision' => $row['revision'] ?? 0, // Store source revision
                    'action' => $action,
                    'timestamp' => time(),
                    'source_info' => [
                        'article_name' => $sourceArticle ? $sourceArticle->getName() : rex_i18n::msg('bloecks_error_unknown_article'),
                        'module_name' => $moduleName,
                        'article_id' => $row['article_id'],
                        'clang_id' => $row['clang_id'],
                    ],
                ]);

                $successMsg = 'cut' === $action ? rex_i18n::msg('bloecks_slice_cut') : rex_i18n::msg('bloecks_slice_copied');
                $msg = rex_view::success($successMsg);
                break;

            case 'paste':
                $clipboard = rex_session('bloecks_clipboard', 'array', null);
                if (!$clipboard || !isset($clipboard['data'])) {
                    $msg = rex_view::warning(rex_i18n::msg('bloecks_error_clipboard_empty'));
                    break;
                }

                $targetSlice = rex_request('bloecks_target', 'int');
                $articleId = rex_request('article_id', 'int');
                $clang = rex_request('clang', 'int');
                $ctype = rex_request('ctype', 'int', 1);

                if (!$articleId || !$clang) {
                    $msg = rex_view::warning(rex_i18n::msg('bloecks_error_missing_parameters'));
                    break;
                }

                $data = $clipboard['data'];

                // Check if user has content edit permissions for target article
                if (!self::hasContentEditPermission($articleId, $clang, $data['module_id'])) {
                    $msg = rex_view::warning(rex_i18n::msg('bloecks_error_no_content_permission'));
                    break;
                }

                // Determine priority for insertion
                $priority = 1;
                $sql = rex_sql::factory();

                // Get revision from Version plugin if available
                $revision = 0; // Default revision (LIVE)
                if (class_exists('rex_article_revision')) {
                    $revision = rex_article_revision::getSessionArticleRevision($articleId);
                }

                if ($targetSlice) {
                    $sql->setQuery('SELECT priority FROM ' . rex::getTablePrefix() . 'article_slice WHERE id=?', [$targetSlice]);
                    if ($sql->getRows()) {
                        $priority = (int) $sql->getValue('priority') + 1; // Insert AFTER target slice
                        // Shift existing slices down (only in current revision)
                        $shift = rex_sql::factory();
                        $shift->setQuery(
                            'UPDATE ' . rex::getTablePrefix() . 'article_slice
                             SET priority = priority + 1
                             WHERE article_id=? AND clang_id=? AND revision=? AND priority>=?',
                            [$articleId, $clang, $revision, $priority],
                        );
                    }
                } else {
                    $priority = (int) ($sql->getArray(
                        'SELECT MAX(priority) p FROM ' . rex::getTablePrefix() . 'article_slice
                         WHERE article_id=? AND clang_id=? AND revision=?',
                        [$articleId, $clang, $revision],
                    )[0]['p'] ?? 0) + 1;
                }

                // Insert new slice
                $ins = rex_sql::factory();
                $ins->setTable(rex::getTablePrefix() . 'article_slice');
                $ins->setValue('article_id', $articleId);
                $ins->setValue('clang_id', $clang);
                $ins->setValue('ctype_id', $ctype);
                $ins->setValue('priority', $priority);
                $ins->setValue('revision', $revision);

                foreach ($data as $k => $v) {
                    $ins->setValue($k, $v);
                }

                $ins->addGlobalCreateFields();
                $ins->addGlobalUpdateFields();

                try {
                    $ins->insert();
                    $newSliceId = $ins->getLastId();

                    // If cut, delete original slice
                    if ('cut' === $clipboard['action']) {
                        $srcId = (int) $clipboard['source_slice_id'];
                        if ($srcId) {
                            rex_content_service::deleteSlice($srcId);
                        }
                        rex_unset_session('bloecks_clipboard');
                    }

                    rex_article_cache::delete($articleId, $clang);

                    $msg = rex_view::success(rex_i18n::msg('bloecks_slice_inserted'));
                } catch (rex_sql_exception $e) {
                    $msg = rex_view::warning(sprintf(rex_i18n::msg('bloecks_error_insert_failed'), $e->getMessage()));
                }
                break;
        }

        if ($msg) {
            $subject = $ep->getSubject();
            $ep->setSubject($msg . $subject);
        }
    }

    /**
     * Check if template or module is excluded from BLOECKS functionality.
     */
    public static function isExcluded($articleId, $clang, $moduleId): bool
    {
        $addon = rex_addon::get('bloecks');

        // Check module exclusions
        $modulesExclude = $addon->getConfig('modules_exclude', '');
        if ($modulesExclude && $moduleId) {
            $excludedModules = array_map('trim', explode(',', $modulesExclude));
            if (in_array((string) $moduleId, $excludedModules)) {
                return true;
            }
        }

        // Check template exclusions
        $templatesExclude = $addon->getConfig('templates_exclude', '');
        if ($templatesExclude && $articleId && $clang) {
            $article = rex_article::get($articleId, $clang);
            if ($article && $article->getTemplateId()) {
                $excludedTemplates = array_map('trim', explode(',', $templatesExclude));
                if (in_array((string) $article->getTemplateId(), $excludedTemplates)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Clear clipboard at session start for security
     * This ensures clipboard is cleared on login/logout/session restart.
     */
    public static function clearClipboardOnSessionStart(): void
    {
        // Check if this is a fresh session or no user logged in
        if (!rex::getUser() || false === rex_session('bloecks_session_started', 'bool', false)) {
            self::clearClipboard();
            rex_set_session('bloecks_session_started', true);
        }

        // Also clear on logout detection
        if (rex_request('logout') || 'login' === rex_be_controller::getCurrentPagePart(1)) {
            self::clearClipboard();
            rex_unset_session('bloecks_session_started');
        }
    }

    /**
     * Clear all clipboard data from session.
     */
    public static function clearClipboard(): void
    {
        rex_unset_session('bloecks_clipboard');
    }

    /**
     * Check if user has content edit permissions for article/template/module
     * Based on REDAXO structure content permissions.
     */
    public static function hasContentEditPermission($articleId, $clang, $moduleId = null): bool
    {
        $user = rex::getUser();

        if (!$user) {
            return false;
        }

        // Admin users always have permission
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has structure permission for this article/category
        $article = rex_article::get($articleId, $clang);
        if (!$article) {
            return false;
        }

        $structurePerm = $user->getComplexPerm('structure');
        if ($structurePerm) {
            // Check category permission (articles inherit from their category)
            $categoryId = $article->getCategoryId();
            if ($categoryId && !$structurePerm->hasCategoryPerm($categoryId)) {
                return false;
            }
        }

        // Check module permissions if module is specified
        if ($moduleId) {
            $modulePerm = $user->getComplexPerm('modules');
            if ($modulePerm && !$modulePerm->hasPerm($moduleId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Selects a value of a slice from the database.
     * Helper function similar to the old bloecks version.
     *
     * @param int $sliceId ID of the slice
     * @param string $key name of the value
     * @param mixed $default if the value is not contained in the database or set to NULL return this value (default is NULL)
     *
     * @return mixed The slice's value
     */
    protected static function getValueOfSlice($sliceId, $key, $default = null)
    {
        $sliceId = (int) $sliceId;
        $value = $default;

        if ($sliceId > 0) {
            $sql = rex_sql::factory();
            $sql->setQuery('SELECT ' . $key . ' FROM ' . rex::getTablePrefix() . 'article_slice WHERE id=?', [$sliceId]);
            if ($sql->getRows() > 0 && $sql->hasValue($key)) {
                $value = $sql->getValue($key);
            }
        }

        return $value;
    }
}
