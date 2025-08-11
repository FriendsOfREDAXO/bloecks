<?php
/**
 * BLOECKS - Einfaches Drag & Drop + Copy/Paste für REDAXO
 */



// Register permissions
rex_perm::register('bloecks[]');
rex_perm::register('bloecks[settings]');
rex_perm::register('bloecks[copy]');
rex_perm::register('bloecks[order]');

// Backend functionality
if (rex::isBackend()) {
    // Clear clipboard on login/logout and session start for security
    bloecks_backend::clearClipboardOnSessionStart();
    
    bloecks_backend::init();
    
    // Register wrapper for slice_columns-style drag & drop only if enabled
    $addon = rex_addon::get('bloecks');
    if ($addon->getConfig('enable_drag_drop', false)) {
        rex_extension::register('SLICE_SHOW', bloecks_wrapper::addDragDropWrapper(...), rex_extension::EARLY);
        rex_extension::register('SLICE_MENU', bloecks_wrapper::addDragHandle(...));
        # error_log("BLOECKS DEBUG: Drag & Drop extension points registered");
    } else {
        # error_log("BLOECKS DEBUG: Drag & Drop disabled, no wrapper extension points registered");
    }
}
