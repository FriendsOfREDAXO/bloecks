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
        
        // Clean up clipboard on each page load to remove invalid slice IDs
        Backend::cleanupClipboard();
        
        Backend::init();
    });
    
    // Prevent slice status links with invalid slice_id from being generated
    rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', static function (rex_extension_point $ep) {
        $sliceId = $ep->getParam('slice_id');
        if (!$sliceId || $sliceId <= 0) {
            error_log('BLOECKS WARNING: Invalid slice_id in SLICE_MENU: ' . var_export($sliceId, true));
            // Don't modify the menu if slice_id is invalid - let the default behavior handle it
            return $ep->getSubject();
        }
        
        // Continue with normal processing
        return $ep->getSubject();
    }, rex_extension::EARLY);
    
    // Override the content slice status API to prevent slice_id=0 errors
    rex_extension::register('STRUCTURE_CONTENT_ARTICLE_UPDATED', static function (rex_extension_point $ep) {
        // This extension point is called when slice status is changed
        $sliceId = rex_request('slice_id', 'int');
        if ($sliceId === 0) {
            error_log('BLOECKS WARNING: Blocked slice status change for slice_id=0');
            // Prevent the status change by returning early
            return false;
        }
    }, rex_extension::EARLY);
    
        // Additional safety check for slice status operations - prevent slice_id=0 errors
    rex_extension::register('PAGE_CHECKED', static function (rex_extension_point $ep) {
        // Only intercept on content pages
        if (rex_be_controller::getCurrentPage() === 'content/edit') {
            // Check for problematic API calls before they execute
            $apiCall = rex_request('rex-api-call', 'string');
            if ($apiCall === 'content_slice_status') {
                $sliceId = rex_request('slice_id', 'int');
                if (!$sliceId || $sliceId <= 0) {
                    // Log the problematic request  
                    error_log('BLOECKS WARNING: Blocked invalid slice_id in content_slice_status: ' . 
                              var_export($sliceId, true) . ' Full Request: ' . var_export($_REQUEST, true));
                    
                    // Redirect back to the content page to avoid the error
                    $articleId = rex_request('article_id', 'int');
                    $clang = rex_request('clang', 'int', rex_clang::getCurrentId());
                    $ctype = rex_request('ctype', 'int', 1);
                    
                    if ($articleId) {
                        // Create a clean redirect URL
                        $redirectUrl = rex_url::backendPage('content/edit', [
                            'article_id' => $articleId,
                            'clang' => $clang, 
                            'ctype' => $ctype
                        ]);
                        
                        // Ensure proper URL encoding (no &amp; entities)
                        $redirectUrl = html_entity_decode($redirectUrl, ENT_QUOTES, 'UTF-8');
                        
                        rex_response::sendRedirect($redirectUrl);
                        exit;
                    }
                    
                    throw new rex_api_exception('Invalid slice_id: ' . $sliceId);
                }
            }
        }
    }, rex_extension::EARLY);
    
    // Prevent slice status links with invalid slice_id from being generated - only on content pages
    rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', static function (rex_extension_point $ep) {
        // Only act on content pages
        if (rex_be_controller::getCurrentPage() !== 'content/edit') {
            return $ep->getSubject();
        }
        
        $sliceId = $ep->getParam('slice_id');
        if (!$sliceId || $sliceId <= 0) {
            error_log('BLOECKS WARNING: Invalid slice_id in SLICE_MENU: ' . var_export($sliceId, true));
            // Don't modify the menu if slice_id is invalid - let the default behavior handle it
            return $ep->getSubject();
        }
        
        // Continue with normal processing
        return $ep->getSubject();
    }, rex_extension::EARLY);

    // Override OUTPUT_FILTER to fix any remaining slice_id=0 issues in HTML
    rex_extension::register('OUTPUT_FILTER', static function (rex_extension_point $ep) {
        $content = $ep->getSubject();

        // Only process backend pages
        if (rex::isBackend()) {
            // Only fix slice-related issues on content pages to avoid interfering with other pages
            if (rex_be_controller::getCurrentPage() === 'content/edit') {
                // Fix any remaining slice_id=0 in content_slice_status URLs
                $pattern = '/href="([^"]*rex-api-call=content_slice_status[^"]*slice_id=0[^"]*)"/';
                if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $fullUrl = $match[1];
                        error_log('BLOECKS WARNING: Found problematic URL in output: ' . $fullUrl);
                        // Remove the problematic link by making it non-functional
                        $content = str_replace($match[0], 'href="#" class="disabled" style="opacity:0.5;pointer-events:none;" title="Slice-ID ungültig"', $content);
                    }
                }
                
                // Fix HTML entities in URLs - convert &amp; back to & in href attributes
                $content = preg_replace_callback('/href="([^"]*&amp;[^"]*)"/', function($matches) {
                    $cleanUrl = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                    return 'href="' . htmlspecialchars($cleanUrl, ENT_QUOTES, 'UTF-8') . '"';
                }, $content);
            }
            
            // Add JS config for multi-clipboard and paste position on all backend pages
            if (str_contains($content, '</head>')) {
                $jsConfig = '<script type="text/javascript">';

                // Multi-clipboard is available only if:
                // 1. Setting is globally enabled AND
                // 2. User has permission (admin or bloecks[multi])
                $multiClipboardAvailable = Backend::isMultiClipboardAvailable();

                // Paste position setting
                $addon = rex_addon::get('bloecks');
                $pastePosition = $addon->getConfig('paste_position', 'after');

                $jsConfig .= 'var BLOECKS_MULTI_CLIPBOARD = ' . ($multiClipboardAvailable ? 'true' : 'false') . ';';
                $jsConfig .= 'var BLOECKS_PASTE_POSITION = ' . json_encode($pastePosition) . ';';
                $jsConfig .= '</script></head>';

                $content = str_replace('</head>', $jsConfig, $content);
            }
        }

        return $content;
    });
}
