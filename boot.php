<?php

/**
 * BLOECKS - Simple Drag & Drop + Copy/Paste for REDAXO.
 */

use FriendsOfRedaxo\Bloecks\Api;
use FriendsOfRedaxo\Bloecks\Backend;

// Register API explicitly
rex_api_function::register('bloecks', Api::class);

// Register permissions
rex_perm::register('bloecks[]');
rex_perm::register('bloecks[copy]');
rex_perm::register('bloecks[order]');

// Backend functionality
if (rex::isBackend() && PHP_SAPI !== 'cli') {
    // Only run session-dependent code when not in CLI context
    rex_extension::register('PACKAGES_INCLUDED', static function () {
        // Clear clipboard on login/logout and session start for security
        Backend::clearClipboardOnSessionStart();
        Backend::init();
    });
}
