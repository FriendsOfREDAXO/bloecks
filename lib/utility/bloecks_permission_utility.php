<?php

namespace FriendsOfRedaxo\Bloecks;

use rex;
use rex_addon;
use rex_article;
use function array_map;
use function explode;
use function in_array;
use function trim;

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
        if (is_string($modulesExclude) && $modulesExclude && $moduleId) {
            $excludedModules = array_map('trim', explode(',', $modulesExclude));
            if (in_array((string) $moduleId, $excludedModules)) {
                return true;
            }
        }

        // Check template exclusions
        $templatesExclude = $addon->getConfig('templates_exclude', '');
        if (is_string($templatesExclude) && $templatesExclude && $articleId && $clang) {
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
     * Check if user has content edit permissions for article/template/module.
     */
    public static function hasContentEditPermission(int $articleId, int $clang, ?int $moduleId = null): bool
    {
        $user = rex::getUser();
        if (!$user) {
            return false;
        }

        // Admin can do everything
        if ($user->isAdmin()) {
            return true;
        }

        // Get article to check
        $article = rex_article::get($articleId, $clang);
        if (!$article) {
            return false;
        }

        $structurePerm = $user->getComplexPerm('structure');
        if ($structurePerm) {
            // Check category permission (articles inherit from their category)
            $categoryId = $article->getCategoryId();
            if ($categoryId && method_exists($structurePerm, 'hasCategoryPerm') && !$structurePerm->hasCategoryPerm($categoryId)) {
                return false;
            }
        }

        // Check module permissions if module is specified
        if ($moduleId) {
            $modulePerm = $user->getComplexPerm('modules');
            if ($modulePerm && method_exists($modulePerm, 'hasPerm') && !$modulePerm->hasPerm($moduleId)) {
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
        if (!$addon->getConfig('enable_copy_paste', true)) {
            return false;
        }

        $user = rex::getUser();
        if (!$user) {
            return false;
        }

        return $user->hasPerm('bloecks[]') || $user->hasPerm('bloecks[copy]');
    }

    /**
     * Check if drag & drop should be shown.
     */
    public static function shouldShowDragDrop(): bool
    {
        $addon = rex_addon::get('bloecks');
        if (!$addon->getConfig('enable_drag_drop', false)) {
            return false;
        }

        $user = rex::getUser();
        if (!$user) {
            return false;
        }

        return $user->hasPerm('bloecks[]') || $user->hasPerm('bloecks[order]');
    }

    /**
     * Check if user has drag & drop permissions.
     * @api
     */
    public static function hasDragDropPermission(): bool
    {
        $user = rex::getUser();
        if (!$user) {
            return false;
        }

        return $user->hasPerm('bloecks[]') || $user->hasPerm('bloecks[order]');
    }

    /**
     * Check if user has multi-clipboard permissions.
     * @api
     */
    public static function hasMultiClipboardPermission(): bool
    {
        $user = rex::getUser();

        // Check if user is logged in
        if (!$user) {
            return false;
        }

        // Admin can always use multi-clipboard
        if ($user->isAdmin()) {
            return true;
        }

        // Check specific permission
        return $user->hasPerm('bloecks[]') || $user->hasPerm('bloecks[multi]');
    }

    /**
     * Check if multi-clipboard is available (setting enabled AND user has permission).
     */
    public static function isMultiClipboardAvailable(): bool
    {
        $addon = rex_addon::get('bloecks');
        
        // Check if multi-clipboard is enabled in settings
        if (!$addon->getConfig('enable_multi_clipboard', false)) {
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
     * Check if user can access BLOECKS functionality at all.
     * @api
     */
    public static function hasAnyBloecksPermission(): bool
    {
        $user = rex::getUser();
        if (!$user) {
            return false;
        }

        return $user->hasPerm('bloecks[]') || 
               $user->hasPerm('bloecks[copy]') || 
               $user->hasPerm('bloecks[order]') || 
               $user->hasPerm('bloecks[multi]');
    }
}
