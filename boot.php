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
if (rex::isBackend() && PHP_SAPI !== 'cli') {
    // Only run session-dependent code when not in CLI context
    rex_extension::register('PACKAGES_INCLUDED', static function () {
        // Clear clipboard on login/logout and session start for security
        Backend::clearClipboardOnSessionStart();
        Backend::init();
    });
    
    // Add JavaScript configuration to backend pages
    rex_extension::register('OUTPUT_FILTER', static function (rex_extension_point $ep) {
        $content = $ep->getSubject();
        
        // Only add JS config on backend pages
        if (rex::isBackend() && strpos($content, '</head>') !== false) {
            $jsConfig = '<script type="text/javascript">';
            
            // Multi-clipboard is available only if:
            // 1. Setting is globally enabled AND
            // 2. User has permission (admin or bloecks[multi])
            $multiClipboardAvailable = Backend::isMultiClipboardAvailable();
            
            $jsConfig .= 'var BLOECKS_MULTI_CLIPBOARD = ' . ($multiClipboardAvailable ? 'true' : 'false') . ';';
            $jsConfig .= '</script></head>';
            
            $content = str_replace('</head>', $jsConfig, $content);
            $ep->setSubject($content);
        }
        
        return $content;
    });
}
