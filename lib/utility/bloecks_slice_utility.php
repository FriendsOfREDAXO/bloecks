<?php

namespace FriendsOfRedaxo\Bloecks;

use rex;
use rex_article_revision;
use rex_article_slice;
use rex_clang;
use rex_content_service;
use rex_sql;
use rex_sql_util;

use function class_exists;
use function sprintf;

/**
 * Utility class for slice operations in the BLOECKS addon.
 */
class SliceUtility
{
    /**
     * Create a new slice using REDAXO core services.
     * @param array<string, mixed> $data
     * @api
     */
    public static function createSlice(int $articleId, int $clangId, int $ctypeId, int $moduleId, array $data = []): string
    {
        // Get revision from Version plugin if available
        if (class_exists('rex_article_revision')) {
            $data['revision'] ??= rex_article_revision::getSessionArticleRevision($articleId);
        } else {
            $data['revision'] ??= 0;
        }

        // Use REDAXO core service for slice creation
        return rex_content_service::addSlice($articleId, $clangId, $ctypeId, $moduleId, $data);
    }

    /**
     * Calculate priority for slice insertion at specific position.
     * @api
     */
    public static function calculateInsertionPriority(int $targetSlice, int $articleId, int $clang, string $position = 'after'): int
    {
        // Get revision from Version plugin if available
        $revision = 0;
        if (class_exists('rex_article_revision')) {
            $revision = rex_article_revision::getSessionArticleRevision($articleId);
        }

        if ($targetSlice) {
            $targetSliceObj = rex_article_slice::getArticleSliceById($targetSlice);
            if ($targetSliceObj) {
                return 'before' === $position ?
                    $targetSliceObj->getPriority() :
                    $targetSliceObj->getPriority() + 1;
            }
        }

        // Default: Insert at end using REDAXO core method
        $sql = rex_sql::factory();
        $result = $sql->getArray(
            'SELECT IFNULL(MAX(priority),0)+1 as priority FROM ' . rex::getTable('article_slice') .
            ' WHERE article_id=? AND clang_id=? AND revision=?',
            [$articleId, $clang, $revision],
        );

        return (int) $result[0]['priority'];
    }

    /**
     * Reorganize slice priorities using REDAXO core utility.
     * @api
     */
    public static function organizePriorities(int $articleId, int $clang, int $ctype, int $revision = 0): void
    {
        $whereCondition = sprintf(
            'article_id=%d AND clang_id=%d AND ctype_id=%d AND revision=%d',
            $articleId,
            $clang,
            $ctype,
            $revision,
        );

        rex_sql_util::organizePriorities(
            rex::getTable('article_slice'),
            'priority',
            $whereCondition,
            'priority',
        );
    }

    /**
     * Get slice data by ID using REDAXO core method.
     * @return array<string, mixed>|null
     * @api
     */
    public static function getSliceData(int $sliceId): ?array
    {
        $slice = rex_article_slice::getArticleSliceById($sliceId);
        if (!$slice) {
            return null;
        }

        $data = [];
        $data['id'] = $slice->getId();
        $data['article_id'] = $slice->getArticleId();
        $data['clang_id'] = $slice->getClangId();
        $data['ctype_id'] = $slice->getCtype();
        $data['module_id'] = $slice->getModuleId();
        $data['priority'] = $slice->getPriority();
        $data['revision'] = $slice->getRevision();

        // Get all value fields
        for ($i = 1; $i <= 20; ++$i) {
            $value = $slice->getValue($i);
            if (null !== $value) {
                $data["value$i"] = $value;
            }
        }

        // Get media and link fields
        for ($i = 1; $i <= 10; ++$i) {
            $media = $slice->getMedia($i);
            if (null !== $media) {
                $data["media$i"] = $media;
            }

            $link = $slice->getLink($i);
            if (null !== $link) {
                $data["link$i"] = $link;
            }

            $linkList = $slice->getLinkList($i);
            if (null !== $linkList) {
                $data["linklist$i"] = $linkList;
            }
        }

        return $data;
    }

    /**
     * Get slice value by ID and field name using REDAXO core method.
     * @api
     */
    public static function getSliceValue(int $sliceId, string $key, mixed $default = null): mixed
    {
        $slice = rex_article_slice::getArticleSliceById($sliceId);
        if (!$slice) {
            return $default;
        }

        // Use appropriate getter method based on field type
        if (preg_match('/^value(\d+)$/', $key, $matches)) {
            return $slice->getValue((int) $matches[1]);
        }
        if (preg_match('/^media(\d+)$/', $key, $matches)) {
            return $slice->getMedia((int) $matches[1]);
        }
        if (preg_match('/^link(\d+)$/', $key, $matches)) {
            return $slice->getLink((int) $matches[1]);
        }
        if (preg_match('/^linklist(\d+)$/', $key, $matches)) {
            return $slice->getLinkList((int) $matches[1]);
        }

        // Fallback for other properties
        $data = self::getSliceData($sliceId);
        return $data[$key] ?? $default;
    }

    /**
     * Get all slices for an article using REDAXO core method.
     * @return array<rex_article_slice>
     * @api
     */
    public static function getSlicesForArticle(int $articleId, ?int $clang = null, int $revision = 0): array
    {
        if (null === $clang) {
            $clang = rex_clang::getCurrentId();
        }

        return rex_article_slice::getSlicesForArticle($articleId, $clang, $revision) ?: [];
    }
}
