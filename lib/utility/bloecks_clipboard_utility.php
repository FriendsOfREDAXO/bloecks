<?php

namespace FriendsOfRedaxo\Bloecks;

use rex;
use rex_article;
use rex_i18n;
use rex_sql;

use function is_array;
use function is_string;

/**
 * Utility class for clipboard management in the BLOECKS addon.
 */
class ClipboardUtility
{
    /**
     * Clear clipboard on logout for security.
     * @api
     */
    public static function clearClipboardOnSessionStart(): void
    {
        // Clear clipboard on explicit logout
        if (rex_request('logout', 'bool')) {
            self::clearClipboard();
        }
    }

    /**
     * Clear all clipboard data from session.
     * @api
     */
    public static function clearClipboard(): void
    {
        rex_unset_session('bloecks_clipboard');
        rex_unset_session('bloecks_multi_clipboard');
    }

    /**
     * Store slice data in clipboard session.
     * @param array<string, mixed> $sliceData
     * @api
     */
    public static function storeInClipboard(int $sliceId, array $sliceData, string $action): void
    {
        // Get slice fields to store
        /** @var array<string> $fields */
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

        /** @var array<string, mixed> $data */
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $sliceData[$field] ?? null;
        }

        // Get source information
        $sourceInfo = self::getSourceInfo($sliceData);

        rex_set_session('bloecks_clipboard', [
            'data' => $data,
            'source_slice_id' => $sliceId,
            'source_revision' => $sliceData['revision'] ?? 0,
            'action' => $action,
            'timestamp' => time(),
            'source_info' => $sourceInfo,
        ]);
    }

    /**
     * Get source information for clipboard display.
     * @param array<string, mixed> $sliceData
     * @return array<string, mixed>
     * @api
     */
    public static function getSourceInfo(array $sliceData): array
    {
        $articleId = isset($sliceData['article_id']) && is_numeric($sliceData['article_id']) ? (int) $sliceData['article_id'] : 0;
        $clangId = isset($sliceData['clang_id']) && is_numeric($sliceData['clang_id']) ? (int) $sliceData['clang_id'] : 0;
        $sourceArticle = rex_article::get($articleId, $clangId);

        $moduleSql = rex_sql::factory();
        $moduleId = isset($sliceData['module_id']) && is_numeric($sliceData['module_id']) ? (int) $sliceData['module_id'] : 0;
        $moduleResult = $moduleSql->getArray('SELECT name FROM ' . rex::getTablePrefix() . 'module WHERE id=?', [$moduleId]);
        $moduleRow = count($moduleResult) === 0 ? null : $moduleResult[0];
        $moduleName = $moduleRow !== null
            ? $moduleRow['name']
            : rex_i18n::msg('bloecks_error_unknown_module');

        return [
            'article_name' => $sourceArticle !== null ? $sourceArticle->getName() : rex_i18n::msg('bloecks_error_unknown_article'),
            'module_name' => $moduleName,
            'article_id' => $articleId,
            'clang_id' => $clangId,
        ];
    }

    /**
     * Get clipboard content.
     * @return array<string, mixed>|null
     * @api
     */
    public static function getClipboard(): ?array
    {
        $result = rex_session('bloecks_clipboard', 'array', null);
        /** @var array<string, mixed>|null $result */
        return is_array($result) ? $result : null;
    }

    /**
     * Check if clipboard has content.
     * @api
     */
    public static function hasClipboardContent(): bool
    {
        $clipboard = self::getClipboard();
        return $clipboard !== null && isset($clipboard['data']);
    }

    /**
     * Get multi-clipboard content.
     * @return array<mixed>
     * @api
     */
    public static function getMultiClipboard(): array
    {
        return rex_session('bloecks_multi_clipboard', 'array', []);
    }

    /**
     * Check if slice is the current source in clipboard.
     */
    public static function isClipboardSource(int $sliceId): bool
    {
        $clipboard = self::getClipboard();
        $sourceSliceId = is_array($clipboard) && isset($clipboard['source_slice_id']) && is_numeric($clipboard['source_slice_id']) ? (int) $clipboard['source_slice_id'] : 0;
        return $sourceSliceId === $sliceId;
    }

    /**
     * Get clipboard action (copy or cut).
     */
    public static function getClipboardAction(): ?string
    {
        $clipboard = self::getClipboard();
        if (is_array($clipboard) && isset($clipboard['action']) && is_string($clipboard['action'])) {
            return $clipboard['action'];
        }
        return null;
    }

    /**
     * Get clipboard source info.
     * @return array<string, mixed>|null
     * @api
     */
    public static function getClipboardSourceInfo(): ?array
    {
        $clipboard = self::getClipboard();
        if (is_array($clipboard) && isset($clipboard['source_info']) && is_array($clipboard['source_info'])) {
            /** @var array<string, mixed> $sourceInfo */
            $sourceInfo = $clipboard['source_info'];
            return $sourceInfo;
        }
        return null;
    }
}
