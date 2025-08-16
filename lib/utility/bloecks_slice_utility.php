<?php

namespace FriendsOfRedaxo\Bloecks;

use rex_article_revision;
use rex_sql;
use rex;
use function class_exists;

/**
 * Utility class for slice operations in the BLOECKS addon.
 */
class SliceUtility
{
    /**
     * Calculate priority for slice insertion.
     * @api
     */
    public static function calculateInsertionPriority(int $targetSlice, int $articleId, int $clang): int
    {
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
                return $priority;
            }
        }

        // Default: Insert at end
        $result = $sql->getArray(
            'SELECT MAX(priority) p FROM ' . rex::getTablePrefix() . 'article_slice
             WHERE article_id=? AND clang_id=? AND revision=?',
            [$articleId, $clang, $revision],
        );

        return (int) ($result[0]['p'] ?? 0) + 1;
    }

    /**
     * Get slice value by ID and field name.
     * @api
     */
    public static function getSliceValue(int $sliceId, string $key, mixed $default = null): mixed
    {
        if ($sliceId <= 0) {
            return $default;
        }

        $sql = rex_sql::factory();
        $sql->setQuery('SELECT ' . $sql->escapeIdentifier($key) . ' FROM ' . rex::getTablePrefix() . 'article_slice WHERE id=?', [$sliceId]);
        
        if ($sql->getRows() > 0 && $sql->hasValue($key)) {
            return $sql->getValue($key);
        }

        return $default;
    }
}
