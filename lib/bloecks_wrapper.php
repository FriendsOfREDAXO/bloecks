<?php

namespace FriendsOfRedaxo\Bloecks;

use rex_addon;
use rex_article_slice;
use rex_extension_point;
use rex_i18n;

use function is_array;
use function is_string;
use function rex_request;
use function sprintf;

class Wrapper
{
    /**
     * Add drag-drop wrapper around slice content - similar to slice_columns.
     * @param rex_extension_point<mixed> $ep
     */
    public static function addDragDropWrapper(rex_extension_point $ep): string
    {
        $subject = $ep->getSubject();
        $slice_id = $ep->getParam('slice_id');
        $article_id = $ep->getParam('article_id');
        $clang_id = $ep->getParam('clang_id');
        $module_id = $ep->getParam('module_id');

        // Get the slice object to ensure correct clang_id and module_id
        if ($slice_id && is_numeric($slice_id)) {
            $slice = rex_article_slice::getArticleSliceById((int) $slice_id);
            if ($slice) {
                $clang_id = $slice->getClang();
                $article_id = $slice->getArticleId();
                $module_id = $slice->getModuleId();
            }
        }

        // If still no valid clang_id, use current request clang
        if (!$clang_id || $clang_id <= 0) {
            $clang_id = rex_request('clang', 'int', 1);
        }

        // Check for exclusions using the permission utility
        if (PermissionUtility::isExcluded($article_id, $clang_id, $module_id)) {
            $subject = $ep->getSubject();
            return is_string($subject) ? $subject : '';
        }

        // Check if drag & drop is enabled
        $addon = rex_addon::get('bloecks');
        if (!$addon->getConfig('enable_drag_drop', false)) {
            $subject = $ep->getSubject();
            return is_string($subject) ? $subject : '';
        }

        $subject = $ep->getSubject();
        $subjectString = is_string($subject) ? $subject : '';

        // Wrap ALL slices, not just slice-output
        if (str_contains($subjectString, 'rex-slice')) {
            // Beautiful drag handle with FontAwesome 6 grip icon - optimized position at 6px (Regular variant)
            $dragHandle = '<div class="bloecks-drag-handle" title="' . rex_i18n::msg('bloecks_drag_move') . '"><i class="fa fa-grip-vertical"></i></div>';

            // Create wrapper similar to slice_columns - remove border completely
            $sliceIdInt = is_numeric($slice_id) ? (int) $slice_id : 0;
            $articleIdInt = is_numeric($article_id) ? (int) $article_id : 0;
            $clangIdInt = is_numeric($clang_id) ? (int) $clang_id : 1;

            $wrapper = sprintf(
                '<li class="bloecks-dragdrop" data-slice-id="%d" data-article-id="%d" data-clang-id="%d">
                    %s
                    <ul class="bloecks-slice-container">%s</ul>
                </li>',
                $sliceIdInt,
                $articleIdInt,
                $clangIdInt,
                $dragHandle,
                $subjectString,
            );

            return $wrapper;
        }

        return $subjectString;
    }

    /**
     * Add drag handle to slice menu.
     * @param rex_extension_point<mixed> $ep
     */
    public static function addDragHandle(rex_extension_point $ep): mixed
    {
        // Check if drag & drop is enabled
        $addon = rex_addon::get('bloecks');
        if (!$addon->getConfig('enable_drag_drop', false)) {
            return $ep->getSubject();
        }

        $menu_items = $ep->getSubject();

        // Simple drag handle - just add HTML string for now
        if (is_string($menu_items)) {
            $drag_handle = '<a href="#" class="bloecks-drag-handle-link">' . rex_i18n::msg('bloecks_drag_handle') . '</a>';
            return $drag_handle . $menu_items;
        }

        // If it's an array, try to add drag handle
        if (is_array($menu_items)) {
            $drag_handle = [
                'label' => '<span class="bloecks-drag-handle-label">' . rex_i18n::msg('bloecks_drag_handle') . '</span>',
                'url' => '#',
                'attributes' => [
                    'class' => ['bloecks-drag-handle'],
                    'title' => rex_i18n::msg('bloecks_drag_move'),
                ],
            ];

            array_unshift($menu_items, $drag_handle);
            return $menu_items;
        }

        return $menu_items;
    }
}
