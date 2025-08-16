<?php

namespace FriendsOfRedaxo\Bloecks;

use rex;
use rex_addon_interface;
use rex_article;
use rex_article_revision;
use rex_extension_point;
use rex_i18n;
use rex_sql;

use function count;
use function htmlspecialchars;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Utility class for creating buttons and UI elements in the BLOECKS addon.
 */
class ButtonUtility
{
    /**
     * Create copy button for slice menu.
     * @param array<string, mixed> $params
     * @param array<string, mixed>|null $clipboard
     * @return array<string, mixed>
     * @api
     */
    public static function createCopyButton(array $params, ?array $clipboard, bool $isSource): array
    {
        $isActive = $isSource && 'copy' === ClipboardUtility::getClipboardAction();

        return [
            'hidden_label' => 'Copy Slice',
            'url' => '#',
            'icon' => 'copy',
            'attributes' => [
                'class' => ['btn', 'btn-default', 'bloecks-copy', $isActive ? 'is-copied' : ''],
                'title' => rex_i18n::msg('bloecks_copy_slice'),
                'data-slice-id' => $params['slice_id'],
                'data-article-id' => $params['article_id'],
                'data-clang-id' => $params['clang'],
                'data-ctype-id' => $params['ctype'],
            ],
        ];
    }

    /**
     * Create cut button for slice menu.
     * @param array<string, mixed> $params
     * @param array<string, mixed>|null $clipboard
     * @return array<string, mixed>
     * @api
     */
    public static function createCutButton(array $params, ?array $clipboard, bool $isSource): array
    {
        $isActive = $isSource && 'cut' === ClipboardUtility::getClipboardAction();

        return [
            'hidden_label' => 'Cut Slice',
            'url' => '#',
            'icon' => 'cut',
            'attributes' => [
                'class' => ['btn', 'btn-default', 'bloecks-cut', $isActive ? 'is-cut' : ''],
                'title' => rex_i18n::msg('bloecks_cut_slice'),
                'data-slice-id' => $params['slice_id'],
                'data-article-id' => $params['article_id'],
                'data-clang-id' => $params['clang'],
                'data-ctype-id' => $params['ctype'],
            ],
        ];
    }

    /**
     * Create paste button for slice menu.
     * @param array<string, mixed> $params
     * @param array<string, mixed> $clipboard
     * @return array<string, mixed>
     * @api
     */
    public static function createPasteButton(array $params, array $clipboard, rex_addon_interface $addon): array
    {
        $sourceInfo = ClipboardUtility::getClipboardSourceInfo();
        $pastePosition = $addon->getConfig('paste_position', 'after');
        $isPasteBefore = 'before' === $pastePosition;

        // Check multi-clipboard status
        $multiClipboard = ClipboardUtility::getMultiClipboard();
        $clipboardCount = count($multiClipboard);

        // Build tooltip based on clipboard content
        if ($clipboardCount > 1) {
            // Multiple items
            $tooltipText = sprintf('Zwischenablage (%d Elemente) - %s',
                $clipboardCount,
                $isPasteBefore ? 'vor dem Slice einfügen' : 'nach dem Slice einfügen',
            );
            $buttonClass = ['btn', 'btn-default', 'bloecks-paste', 'has-multiple'];
        } elseif (1 === $clipboardCount && $sourceInfo) {
            // Single item with detailed info
            $actionText = 'cut' === ClipboardUtility::getClipboardAction() ? rex_i18n::msg('bloecks_action_cut') : rex_i18n::msg('bloecks_action_copied');
            $positionText = $isPasteBefore ? rex_i18n::msg('paste_position_before') : rex_i18n::msg('paste_position_after');
            $tooltipText = sprintf(
                '%s: "%s" aus "%s" (ID: %s) - %s',
                $actionText,
                is_string($sourceInfo['module_name']) ? $sourceInfo['module_name'] : '',
                is_string($sourceInfo['article_name']) ? $sourceInfo['article_name'] : '',
                is_numeric($sourceInfo['article_id']) ? (string) $sourceInfo['article_id'] : '',
                $positionText,
            );
            $buttonClass = ['btn', 'btn-default', 'bloecks-paste'];
        } else {
            // Fallback
            $tooltipText = $isPasteBefore ? rex_i18n::msg('bloecks_paste_slice_before') : rex_i18n::msg('bloecks_paste_slice_after');
            $buttonClass = ['btn', 'btn-default', 'bloecks-paste'];
        }

        $hiddenLabel = $isPasteBefore ? 'Paste before' : 'Paste after';

        return [
            'hidden_label' => $hiddenLabel,
            'url' => '#',
            'icon' => 'paste',
            'attributes' => [
                'class' => $buttonClass,
                'title' => $tooltipText,
                'data-target-slice' => $params['slice_id'],
                'data-article-id' => $params['article_id'],
                'data-clang-id' => $params['clang'],
                'data-ctype-id' => $params['ctype'],
                'data-paste-position' => $pastePosition,
            ],
        ];
    }

    /**
     * Create paste button for module select menu.
     * @api
     */
    public static function createModuleSelectPasteButton(int $articleId, int $clang, int $ctype, string $pastePosition): string
    {
        $clipboard = ClipboardUtility::getClipboard();
        $sourceInfo = ClipboardUtility::getClipboardSourceInfo();

        // Determine module name for button text
        $moduleName = '';
        if ($sourceInfo && !empty($sourceInfo['module_name'])) {
            $moduleName = $sourceInfo['module_name'];
            $tooltipText = sprintf(
                'Fügt ein: "%s" aus "%s" (ID: %s)',
                is_string($sourceInfo['module_name']) ? $sourceInfo['module_name'] : '',
                is_string($sourceInfo['article_name']) ? $sourceInfo['article_name'] : '',
                is_numeric($sourceInfo['article_id']) ? (string) $sourceInfo['article_id'] : '',
            );
        } else {
            // Fallback: Get module name from clipboard data
            $clipboardData = $clipboard['data'] ?? [];
            $moduleId = null;
            if (is_array($clipboardData) && isset($clipboardData['module_id']) && is_numeric($clipboardData['module_id'])) {
                $moduleId = (int) $clipboardData['module_id'];
            }
            if ($moduleId) {
                $moduleSql = rex_sql::factory();
                $moduleResult = $moduleSql->getArray('SELECT name FROM ' . rex::getTablePrefix() . 'module WHERE id=?', [$moduleId]);
                $moduleRow = !empty($moduleResult) ? $moduleResult[0] : null;
                $moduleName = $moduleRow ? $moduleRow['name'] : rex_i18n::msg('bloecks_error_unknown_module');
            }
            $displayName = !empty($moduleName) ? $moduleName : '-noname-';
            $tooltipText = rex_i18n::msg('bloecks_paste_from_clipboard', $displayName);
        }

        // Button text with REDAXO language system
        if (!empty($moduleName) && is_string($moduleName)) {
            $buttonText = rex_i18n::msg('bloecks_paste_module', $moduleName);
        } else {
            $buttonText = rex_i18n::msg('bloecks_action_paste');
        }

        // Tooltip should always be "Fügt ein: ...", not "Kopiert: ..."
        if (!empty($moduleName)) {
            $tooltipText = sprintf('Fügt ein: "%s"', is_string($moduleName) ? $moduleName : '');
            if ($sourceInfo) {
                $tooltipText = sprintf(
                    'Fügt ein: "%s" aus "%s" (ID: %s)',
                    is_string($sourceInfo['module_name']) ? $sourceInfo['module_name'] : '',
                    is_string($sourceInfo['article_name']) ? $sourceInfo['article_name'] : '',
                    is_numeric($sourceInfo['article_id']) ? (string) $sourceInfo['article_id'] : '',
                );
            }
        } else {
            $tooltipText = rex_i18n::msg('bloecks_paste_from_clipboard');
        }

        return sprintf(
            '<div class="rex-toolbar"><div class="btn-toolbar"><a href="#" class="btn btn-success bloecks-paste" title="%s" data-target-slice="0" data-article-id="%d" data-clang-id="%d" data-ctype-id="%d" data-paste-position="%s"><i class="rex-icon rex-icon-paste"></i> %s</a></div></div>',
            htmlspecialchars($tooltipText),
            $articleId,
            $clang,
            $ctype,
            $pastePosition,
            htmlspecialchars($buttonText),
        );
    }

    /**
     * Extract button parameters from extension point.
     * @param rex_extension_point<mixed> $ep
     * @return array<string, mixed>
     * @api
     */
    public static function extractButtonParams(rex_extension_point $ep): array
    {
        $sliceId = $ep->getParam('slice_id');
        $articleId = $ep->getParam('article_id');
        $clang = $ep->getParam('clang');
        $ctype = $ep->getParam('ctype');
        $moduleId = $ep->getParam('module_id');

        // Get revision from Version plugin if available
        $revision = 0; // Default revision (LIVE)
        if (class_exists('rex_article_revision')) {
            $articleIdInt = is_numeric($articleId) ? (int) $articleId : 0;
            $revision = rex_article_revision::getSessionArticleRevision($articleIdInt);
        }

        // Get category_id for proper URL construction
        $categoryId = 0;
        if ($articleId && is_numeric($articleId)) {
            $articleIdInt = (int) $articleId;
            $clangInt = is_numeric($clang) ? (int) $clang : null;
            $article = rex_article::get($articleIdInt, $clangInt);
            if ($article) {
                $categoryId = $article->getCategoryId();
            }
        }

        return [
            'slice_id' => $sliceId,
            'article_id' => $articleId,
            'clang' => $clang,
            'ctype' => $ctype,
            'module_id' => $moduleId,
            'revision' => $revision,
            'category_id' => $categoryId,
        ];
    }

    /**
     * Check if there are existing slices in a ctype.
     */
    public static function hasExistingSlicesInCtype(int $articleId, int $clang, int $ctype): bool
    {
        // Get revision from Version plugin if available
        $revision = 0; // Default revision (LIVE)
        if (class_exists('rex_article_revision')) {
            $revision = rex_article_revision::getSessionArticleRevision($articleId);
        }

        $sql = rex_sql::factory();
        $existingSlices = $sql->getArray(
            'SELECT id FROM ' . rex::getTablePrefix() . 'article_slice
             WHERE article_id=? AND clang_id=? AND ctype_id=? AND revision=?',
            [$articleId, $clang, $ctype, $revision],
        );

        return count($existingSlices) > 0;
    }
}
