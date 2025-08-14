<?php
/**
 * BLOECKS - Einfaches Drag & Drop + Copy/Paste für REDAXO
 */

use FriendsOfRedaxo\Bloecks\Api;
use FriendsOfRedaxo\Bloecks\Backend;
use FriendsOfRedaxo\Bloecks\Wrapper;

// Register API explicitly
rex_api_function::register('bloecks', Api::class);

// Register permissions
rex_perm::register('bloecks[]');
rex_perm::register('bloecks[settings]');
rex_perm::register('bloecks[copy]');
rex_perm::register('bloecks[order]');

// Backend functionality
if (rex::isBackend() && PHP_SAPI !== 'cli') {
    // Only run session-dependent code when not in CLI context
    rex_extension::register('PACKAGES_INCLUDED', function () {
        // Clear clipboard on login/logout and session start for security
        Backend::clearClipboardOnSessionStart();
        Backend::init();

        // Register wrapper for slice_columns-style drag & drop only if enabled
        $addon = rex_addon::get('bloecks');
        if ($addon->getConfig('enable_drag_drop', false)) {
            rex_extension::register('SLICE_SHOW', Wrapper::addDragDropWrapper(...), rex_extension::EARLY);
            rex_extension::register('SLICE_MENU', Wrapper::addDragHandle(...));
            # error_log("BLOECKS DEBUG: Drag & Drop extension points registered");
        } else {
            # error_log("BLOECKS DEBUG: Drag & Drop disabled, no wrapper extension points registered");
        }
    }
}
