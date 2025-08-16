<?php

namespace FriendsOfRedaxo\Bloecks;

use rex;
use rex_addon;
use rex_article;
use rex_module_perm;
use rex_structure_perm;

use function array_map;
use function explode;
use function in_array;
use function is_string;

/**
 * Utility class for permission checks and validations in the BLOECKS addon.
 */
class PermissionUtility
{
    /**
     * Check if template or module is excluded from BLOECKS functionality.
     */
    public static function isExcluded(int $articleId, int $clang, ?int $moduleId): bool
    {
        $addon = rex_addon::get('bloecks');

        // Check module exclusions
        $modulesExclude = $addon->getConfig('modules_exclude', '');
        if (is_string($modulesExclude) && $modulesExclude !== '' && $moduleId !== null) {
            $excludedModules = array_map('trim', explode(',', $modulesExclude));
            if (in_array((string) $moduleId, $excludedModules, true)) {
                return true;
            }
        }

        // Check template exclusions
        $templatesExclude = $addon->getConfig('templates_exclude', '');
        if (is_string($templatesExclude) && $templatesExclude !== '' && $articleId > 0 && $clang > 0) {
            $article = rex_article::get($articleId, $clang);
            if ($article !== null && $article->getTemplateId() > 0) {
                $excludedTemplates = array_map('trim', explode(',', $templatesExclude));
                if (in_array((string) $article->getTemplateId(), $excludedTemplates, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has content edit permissions for article/template/module.
     */
    public static function hasContentEditPermission(int $articleId, int $clang, ?int $moduleId = null): bool
    {
        $user = rex::getUser();
        if ($user === null) {
            return false;
        }

        // Admin can do everything
        if ($user->isAdmin()) {
            return true;
        }

        // Get article to check
        $article = rex_article::get($articleId, $clang);
        if ($article === null) {
            return false;
        }

        // Check category permission using REDAXO core method
        $structurePerm = $user->getComplexPerm('structure');
        if ($structurePerm instanceof rex_structure_perm && !$structurePerm->hasCategoryPerm($article->getCategoryId())) {
            return false;
        }

        // Check module permissions if module is specified using REDAXO core method
        if ($moduleId !== null) {
            $modulePerm = $user->getComplexPerm('modules');
            if ($modulePerm instanceof rex_module_perm && !$modulePerm->hasPerm($moduleId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if copy/paste buttons should be shown.
     */
    public static function shouldShowCopyPasteButtons(): bool
    {
        $addon = rex_addon::get('bloecks');
        $enableCopyPaste = (bool) $addon->getConfig('enable_copy_paste', true);
        if (!$enableCopyPaste) {
            return false;
        }

        return self::getUserPermissions()['copy'];
    }

    /**
     * Check if drag & drop should be shown.
     */
    public static function shouldShowDragDrop(): bool
    {
        $addon = rex_addon::get('bloecks');
        $enableDragDrop = (bool) $addon->getConfig('enable_drag_drop', false);
        if (!$enableDragDrop) {
            return false;
        }

        return self::getUserPermissions()['order'];
    }

    /**
     * Check if user has drag & drop permissions.
     * @api
     */
    public static function hasDragDropPermission(): bool
    {
        return self::getUserPermissions()['order'];
    }

    /**
     * Check if user has multi-clipboard permissions.
     * @api
     */
    public static function hasMultiClipboardPermission(): bool
    {
        return self::getUserPermissions()['multi'];
    }

    /**
     * Check if multi-clipboard is available (setting enabled AND user has permission).
     */
    public static function isMultiClipboardAvailable(): bool
    {
        $addon = rex_addon::get('bloecks');

        // Check if multi-clipboard is enabled in settings
        if ($addon->getConfig('enable_multi_clipboard', false) !== true) {
            return false;
        }

        // Check if user has permission
        return self::hasMultiClipboardPermission();
    }

    /**
     * Check if slice operations should be allowed based on exclusions.
     * @api
     */
    public static function isSliceAllowed(int $articleId, int $clang, ?int $moduleId): bool
    {
        // Check if template or module is excluded
        if (self::isExcluded($articleId, $clang, $moduleId)) {
            return false;
        }

        // Check content edit permissions
        return self::hasContentEditPermission($articleId, $clang, $moduleId);
    }

    /**
     * Get user's current permissions as a structured array.
     * @return array{
     *   copy: bool,
     *   order: bool,
     *   multi: bool,
     *   any: bool,
     *   admin: bool
     * }
     * @api
     */
    public static function getUserPermissions(): array
    {
        $user = rex::getUser();
        if ($user === null) {
            return [
                'copy' => false,
                'order' => false,
                'multi' => false,
                'any' => false,
                'admin' => false,
            ];
        }

        $isAdmin = $user->isAdmin();
        $hasBase = $user->hasPerm('bloecks[]');

        return [
            'copy' => $isAdmin || $hasBase || $user->hasPerm('bloecks[copy]'),
            'order' => $isAdmin || $hasBase || $user->hasPerm('bloecks[order]'),
            'multi' => $isAdmin || $hasBase || $user->hasPerm('bloecks[multi]'),
            'any' => $isAdmin || $hasBase || $user->hasPerm('bloecks[copy]') || $user->hasPerm('bloecks[order]') || $user->hasPerm('bloecks[multi]'),
            'admin' => $isAdmin,
        ];
    }

    /**
     * Check if user can access BLOECKS functionality at all.
     * @api
     */
    public static function hasAnyBloecksPermission(): bool
    {
        return self::getUserPermissions()['any'];
    }
}
