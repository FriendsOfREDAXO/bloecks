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
rex_perm::register('bloecks[multi]');

// Backend functionality
if (rex::isBackend() && PHP_SAPI !== 'cli' && is_object(rex::getUser())) {
    
    // Set JavaScript properties for bloecks configuration
    $addon = rex_addon::get('bloecks');
    
    // Multi-clipboard is available only if:
    // 1. Setting is globally enabled AND
    // 2. User has permission (admin or bloecks[multi])
    $multiClipboardAvailable = Backend::isMultiClipboardAvailable();
    
    // Basic configuration
    rex_view::setJsProperty('bloecks', [
        'enabled' => $addon->getConfig('enable_copy_paste', true),
        'dragDropEnabled' => $addon->getConfig('enable_drag_drop', false),
        'multiClipboard' => $multiClipboardAvailable,
        'pastePosition' => $addon->getConfig('paste_position', 'after'),
        'apiUrl' => rex_url::backendController([
            'page' => 'bloecks',
            'rex-api-call' => 'bloecks_api'
        ])
    ]);

    // Translations for JavaScript
    rex_view::setJsProperty('bloecks_i18n', [
        'copy' => rex_i18n::msg('bloecks_copy'),
        'cut' => rex_i18n::msg('bloecks_cut'), 
        'paste' => rex_i18n::msg('bloecks_paste'),
        'clear_clipboard' => rex_i18n::msg('bloecks_clear_clipboard'),
        'confirm_clear' => rex_i18n::msg('bloecks_confirm_clear_clipboard'),
        'drag_move' => rex_i18n::msg('bloecks_drag_move'),
        'success_copied' => rex_i18n::msg('bloecks_slice_copied'),
        'success_cut' => rex_i18n::msg('bloecks_slice_cut'),
        'success_pasted' => rex_i18n::msg('bloecks_slice_pasted'),
        'error_permission' => rex_i18n::msg('bloecks_error_no_permission'),
        'error_clipboard_empty' => rex_i18n::msg('bloecks_error_clipboard_empty')
    ]);

    // Only run session-dependent code when not in CLI context
    rex_extension::register('PACKAGES_INCLUDED', static function () {
        // Clear clipboard on login/logout and session start for security
        Backend::clearClipboardOnSessionStart();
        Backend::init();
    });
}
