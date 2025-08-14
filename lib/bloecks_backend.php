<?php

namespace FriendsOfRedaxo\Bloecks;

use rex;
use rex_addon;
use rex_extension;
use rex_extension_point;
use rex_be_controller;
use rex_view;
use rex_csrf_token;
use rex_url;
use rex_request;
use rex_sql;
use rex_article;
use rex_sql_exception;
use rex_article_cache;
use rex_content_service;
use rex_exception;



/**
 * Backend functionality for BLOECKS addon
 */
class Backend
{
    /**
     * Initialize the backend functionality
     */
    public static function init(): void
    {
        if (!rex::getUser()) return;

        $addon = rex_addon::get('bloecks');
        $copyPasteEnabled = (bool)$addon->getConfig('enable_copy_paste', false);
        $dragDropEnabled = (bool)$addon->getConfig('enable_drag_drop', false);
        
        // Only register extension points if features are enabled
        if ($copyPasteEnabled) {
            // Register slice menu extensions for copy/paste
            rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', self::addButtons(...));
            
            // Add paste button to module select menu when no slices exist
            rex_extension::register('STRUCTURE_CONTENT_MODULE_SELECT', self::addPasteToModuleSelect(...));
            
            // Process copy/cut/paste actions 
            rex_extension::register('STRUCTURE_CONTENT_BEFORE_SLICES', self::process(...));
        }
        
        // Load assets on content edit pages ONLY if features are enabled
        if (rex_be_controller::getCurrentPagePart(1) === 'content') {
            // Only load assets if at least one feature is enabled
            if ($copyPasteEnabled || $dragDropEnabled) {
                
                // JS config for drag & drop ordering
                rex_view::setJsProperty('bloecks', [
                    'token' => rex_csrf_token::factory('bloecks')->getValue(),
                    'perm_order' => rex::getUser()->hasPerm('bloecks[]') || rex::getUser()->hasPerm('bloecks[order]'),
                    'enable_copy_paste' => $copyPasteEnabled,
                    'enable_drag_drop' => $dragDropEnabled
                ]);
                
                // Load SortableJS for drag & drop only if drag & drop is enabled
                if ($dragDropEnabled) {
                    rex_view::addJsFile($addon->getAssetsUrl('js/sortable.min.js'));
                }
                
                rex_view::addJsFile($addon->getAssetsUrl('js/bloecks.js') . '?v=' . time());
                rex_view::addCssFile($addon->getAssetsUrl('css/bloecks.css') . '?v=' . time());
            }
        }
    }

    /**
     * Add copy/cut/paste buttons to slice menu
     */
    public static function addButtons(rex_extension_point $ep): array
    {
        $addon = rex_addon::get('bloecks');
        $user = rex::getUser();
        
        if (!$addon->getConfig('enable_copy_paste', false)) return $ep->getSubject();
        if (!$user->hasPerm('bloecks[]') && !$user->hasPerm('bloecks[copy]')) return $ep->getSubject();
        if (!$ep->getParam('perm')) return $ep->getSubject(); // No module permissions
        
        $sliceId = $ep->getParam('slice_id');
        $articleId = $ep->getParam('article_id');
        $clang = $ep->getParam('clang');
        $ctype = $ep->getParam('ctype');
        $moduleId = $ep->getParam('module_id');
        
        // Check for template/module exclusions
        if (self::isExcluded($articleId, $clang, $moduleId)) {
            return $ep->getSubject();
        }
        
        // Check if user has content edit permissions for this article
        if (!self::hasContentEditPermission($articleId, $clang, $moduleId)) {
            return $ep->getSubject();
        }
        
        // Get category_id for proper URL construction
        $categoryId = 0;
        if ($articleId) {
            $article = rex_article::get($articleId, $clang);
            if ($article) {
                $categoryId = $article->getCategoryId();
            }
        }
        
        try {
            $clipboard = rex_request::session('bloecks_clipboard', 'array', null);
        } catch (rex_exception $e) {
            // Session not active yet, skip clipboard functionality
            $clipboard = null;
        }
        $isSource = $clipboard && (int)$clipboard['source_slice_id'] === (int)$sliceId;
        
        $baseParams = [
            'page' => 'content/edit',
            'article_id' => $articleId,
            'category_id' => $categoryId,
            'clang' => $clang,
            'ctype' => $ctype,
            'slice_id' => $sliceId,
            'module_id' => $moduleId,
            'revision' => 0
        ];
        
        $buttons = [];
        
        // Copy button
        $buttons[] = [
            'hidden_label' => 'Copy Slice',
            'url' => rex_url::backendController($baseParams + ['bloecks_action' => 'copy']),
            'icon' => 'copy',
            'attributes' => [
                'class' => ['btn', 'btn-default', $isSource && $clipboard['action'] === 'copy' ? 'is-copied' : ''],
                'title' => 'Slice kopieren',
                'data-pjax-no-history' => 'true'
            ]
        ];
        
        // Cut button
        $buttons[] = [
            'hidden_label' => 'Cut Slice',
            'url' => rex_url::backendController($baseParams + ['bloecks_action' => 'cut']),
            'icon' => 'cut',
            'attributes' => [
                'class' => ['btn', 'btn-default', $isSource && $clipboard['action'] === 'cut' ? 'is-cut' : ''],
                'title' => 'Slice ausschneiden',
                'data-pjax-no-history' => 'true'
            ]
        ];
        
        // Paste button - always available in slice menu 
        if ($clipboard) {
            $sourceInfo = $clipboard['source_info'] ?? null;
            $tooltipText = 'Slice einfügen (nach diesem)';
            
            if ($sourceInfo) {
                $actionText = $clipboard['action'] === 'cut' ? 'Ausgeschnitten' : 'Kopiert';
                $tooltipText = sprintf(
                    '%s: "%s" aus "%s" (ID: %d)',
                    $actionText,
                    $sourceInfo['module_name'],
                    $sourceInfo['article_name'],
                    $sourceInfo['article_id']
                );
            }
            
            $buttons[] = [
                'hidden_label' => 'Paste after',
                'url' => rex_url::backendController($baseParams + [
                    'bloecks_action' => 'paste',
                    'bloecks_target' => $sliceId
                ]),
                'icon' => 'paste',
                'attributes' => [
                    'class' => ['btn', 'btn-default'],
                    'title' => $tooltipText,
                    'data-pjax-no-history' => 'true'
                ]
            ];
        }
        
        return array_merge((array)$ep->getSubject(), $buttons);
    }

    /**
     * Add paste button to module select menu when no slices exist yet
     */
    public static function addPasteToModuleSelect(rex_extension_point $ep): string
    {
        $addon = rex_addon::get('bloecks');
        $user = rex::getUser();
        
        if (!$addon->getConfig('enable_copy_paste', false)) return $ep->getSubject();
        if (!$user->hasPerm('bloecks[]') && !$user->hasPerm('bloecks[copy]')) return $ep->getSubject();
        
        // Only show paste button if there's something in clipboard
        try {
            $clipboard = rex_request::session('bloecks_clipboard', 'array', null);
        } catch (rex_exception $e) {
            // Session not active yet, no clipboard available
            $clipboard = null;
        }
        if (!$clipboard) return $ep->getSubject();
        
        $articleId = rex_request('article_id', 'int');
        $clang = rex_request('clang', 'int');
        $ctype = rex_request('ctype', 'int', 1);
        
        if (!$articleId || !$clang) return $ep->getSubject();
        
        // Check for template exclusions (we can't check module here since it's the module select page)
        if (self::isExcluded($articleId, $clang, null)) {
            return $ep->getSubject();
        }
        
        // Check if user has content edit permissions for this article
        if (!self::hasContentEditPermission($articleId, $clang, null)) {
            return $ep->getSubject();
        }
        
        // Check if there are already slices in this ctype - if yes, don't show button
        $sql = rex_sql::factory();
        $existingSlices = $sql->getArray(
            'SELECT id FROM ' . rex::getTablePrefix() . 'article_slice 
             WHERE article_id=? AND clang_id=? AND ctype_id=?',
            [$articleId, $clang, $ctype]
        );
        
        if (count($existingSlices) > 0) {
            // There are already slices, don't show paste button here
            return $ep->getSubject();
        }
        
        // Get category_id for proper URL construction
        $categoryId = 0;
        if ($articleId) {
            $article = rex_article::get($articleId, $clang);
            if ($article) {
                $categoryId = $article->getCategoryId();
            }
        }
        
        $baseParams = [
            'page' => 'content/edit',
            'article_id' => $articleId,
            'category_id' => $categoryId,
            'clang' => $clang,
            'ctype' => $ctype,
            'revision' => 0
        ];
        
        // Add paste button before module selection
        $sourceInfo = $clipboard['source_info'] ?? null;
        $tooltipText = 'Slice aus Zwischenablage einfügen';
        $buttonText = 'Einfügen';
        
        if ($sourceInfo) {
            $actionText = $clipboard['action'] === 'cut' ? 'Ausgeschnitten' : 'Kopiert';
            $tooltipText = sprintf(
                '%s: "%s" aus "%s" (ID: %d)',
                $actionText,
                $sourceInfo['module_name'],
                $sourceInfo['article_name'],
                $sourceInfo['article_id']
            );
            $buttonText = sprintf('Einfügen: %s', $sourceInfo['module_name']);
        }
        
        $pasteButton = sprintf(
            '<div class="rex-toolbar"><div class="btn-toolbar"><a href="%s" class="btn btn-success" title="%s"><i class="rex-icon rex-icon-paste"></i> %s</a></div></div>',
            rex_url::backendController($baseParams + [
                'bloecks_action' => 'paste',
                'bloecks_target' => 0  // Insert at beginning (for empty pages this is correct)
            ]),
            htmlspecialchars($tooltipText),
            htmlspecialchars($buttonText)
        );
        
        return $pasteButton . $ep->getSubject();
    }

    /**
     * Process copy/cut/paste actions
     */
    public static function process(rex_extension_point $ep): void
    {
        $action = rex_request('bloecks_action', 'string');
        if (!$action) return;
        
        $user = rex::getUser();
        if (!$user->hasPerm('bloecks[]') && !$user->hasPerm('bloecks[copy]')) return;
        
        $msg = '';
        
        switch ($action) {
            case 'copy':
            case 'cut':
                $sliceId = rex_request('slice_id', 'int');
                if (!$sliceId) break;
                
                $sql = rex_sql::factory();
                $row = $sql->getArray('SELECT * FROM ' . rex::getTablePrefix() . 'article_slice WHERE id=?', [$sliceId]);
                
                if (!$row) {
                    $msg = rex_view::warning('Slice nicht gefunden');
                    break;
                }
                
                $row = $row[0];
                
                if (!$user->getComplexPerm('modules')->hasPerm($row['module_id'])) {
                    $msg = rex_view::warning('Keine Berechtigung für dieses Modul');
                    break;
                }
                
                // Check if user has content edit permissions for this slice
                if (!self::hasContentEditPermission($row['article_id'], $row['clang_id'], $row['module_id'])) {
                    $msg = rex_view::warning('Keine Berechtigung zum Bearbeiten dieses Inhalts');
                    break;
                }
                
                // Store slice data in session
                $fields = ['module_id'];
                for ($i = 1; $i <= 20; $i++) $fields[] = 'value' . $i;
                for ($i = 1; $i <= 5; $i++) {
                    $fields[] = 'media' . $i;
                    $fields[] = 'medialist' . $i;
                }
                for ($i = 1; $i <= 5; $i++) {
                    $fields[] = 'link' . $i;
                    $fields[] = 'linklist' . $i;
                }
                
                $data = [];
                foreach ($fields as $field) {
                    $data[$field] = $row[$field];
                }
                
                // Get source slice info for tooltips
                $sourceArticle = rex_article::get($row['article_id'], $row['clang_id']);
                
                // Get module name
                $moduleSql = rex_sql::factory();
                $moduleRow = $moduleSql->getArray('SELECT name FROM ' . rex::getTablePrefix() . 'module WHERE id=?', [$row['module_id']]);
                $moduleName = $moduleRow ? $moduleRow[0]['name'] : 'Unbekanntes Modul';
                
                try {
                    rex_request::setSession('bloecks_clipboard', [
                        'data' => $data,
                        'source_slice_id' => $sliceId,
                        'action' => $action,
                        'timestamp' => time(),
                        'source_info' => [
                            'article_name' => $sourceArticle ? $sourceArticle->getName() : 'Unbekannter Artikel',
                            'module_name' => $moduleName,
                            'article_id' => $row['article_id'],
                            'clang_id' => $row['clang_id']
                        ]
                    ]);
                } catch (rex_exception $e) {
                    // Session not active, cannot save clipboard
                    $msg = rex_view::warning('Session nicht aktiv - Zwischenablage kann nicht gespeichert werden');
                    break;
                }
                
                $msg = rex_view::success('Slice ' . ($action === 'cut' ? 'ausgeschnitten' : 'kopiert'));
                break;
                
            case 'paste':
                try {
                    $clipboard = rex_request::session('bloecks_clipboard', 'array', null);
                } catch (rex_exception $e) {
                    // Session not active, no clipboard available
                    $msg = rex_view::warning('Session nicht aktiv - Zwischenablage ist nicht verfügbar');
                    break;
                }
                if (!$clipboard || !isset($clipboard['data'])) {
                    $msg = rex_view::warning('Zwischenablage ist leer');
                    break;
                }
                
                $targetSlice = rex_request('bloecks_target', 'int');
                $articleId = rex_request('article_id', 'int');
                $clang = rex_request('clang', 'int');
                $ctype = rex_request('ctype', 'int', 1);
                
                if (!$articleId || !$clang) {
                    $msg = rex_view::warning('Fehlende Parameter');
                    break;
                }
                
                $data = $clipboard['data'];
                
                // Check if user has content edit permissions for target article
                if (!self::hasContentEditPermission($articleId, $clang, $data['module_id'])) {
                    $msg = rex_view::warning('Keine Berechtigung zum Bearbeiten dieses Inhalts');
                    break;
                }
                
                // Determine priority for insertion
                $priority = 1;
                $sql = rex_sql::factory();
                
                if ($targetSlice) {
                    $sql->setQuery('SELECT priority FROM ' . rex::getTablePrefix() . 'article_slice WHERE id=?', [$targetSlice]);
                    if ($sql->getRows()) {
                        $priority = (int)$sql->getValue('priority') + 1; // Insert AFTER target slice
                        // Shift existing slices down
                        $shift = rex_sql::factory();
                        $shift->setQuery(
                            'UPDATE ' . rex::getTablePrefix() . 'article_slice 
                             SET priority = priority + 1 
                             WHERE article_id=? AND clang_id=? AND priority>=?',
                            [$articleId, $clang, $priority]
                        );
                    }
                } else {
                    $priority = (int)($sql->getArray(
                        'SELECT MAX(priority) p FROM ' . rex::getTablePrefix() . 'article_slice 
                         WHERE article_id=? AND clang_id=?',
                        [$articleId, $clang]
                    )[0]['p'] ?? 0) + 1;
                }
                
                // Insert new slice
                $ins = rex_sql::factory();
                $ins->setTable(rex::getTablePrefix() . 'article_slice');
                $ins->setValue('article_id', $articleId);
                $ins->setValue('clang_id', $clang);
                $ins->setValue('ctype_id', $ctype);
                $ins->setValue('module_id', $data['module_id']);
                $ins->setValue('priority', $priority);
                $ins->setValue('status', 1);
                
                foreach ($data as $k => $v) {
                    if ($k !== 'module_id') {
                        $ins->setValue($k, $v);
                    }
                }
                
                $ins->addGlobalCreateFields();
                $ins->addGlobalUpdateFields();
                
                try {
                    $ins->insert();
                    $newSliceId = $ins->getLastId();
                    
                    // If cut, delete original slice
                    if ($clipboard['action'] === 'cut') {
                        $srcId = (int)$clipboard['source_slice_id'];
                        if ($srcId) {
                            rex_content_service::deleteSlice($srcId);
                        }
                        try {
                            rex_request::unsetSession('bloecks_clipboard');
                        } catch (rex_exception $e) {
                            // Session not active, cannot clear clipboard
                        }
                    }
                    
                    rex_article_cache::delete($articleId, $clang);
                    
                    $msg = rex_view::success('Slice eingefügt');
                    
                } catch (rex_sql_exception $e) {
                    $msg = rex_view::warning('Fehler beim Einfügen: ' . $e->getMessage());
                }
                break;
        }
        
        if ($msg) {
            $subject = $ep->getSubject();
            $ep->setSubject($msg . $subject);
        }
    }
    
    /**
     * Check if template or module is excluded from BLOECKS functionality
     */
    public static function isExcluded($articleId, $clang, $moduleId): bool
    {
        $addon = rex_addon::get('bloecks');
        
        // Check module exclusions
        $modulesExclude = $addon->getConfig('modules_exclude', '');
        if ($modulesExclude && $moduleId) {
            $excludedModules = array_map('trim', explode(',', $modulesExclude));
            if (in_array((string)$moduleId, $excludedModules)) {
                return true;
            }
        }
        
        // Check template exclusions
        $templatesExclude = $addon->getConfig('templates_exclude', '');
        if ($templatesExclude && $articleId && $clang) {
            $article = rex_article::get($articleId, $clang);
            if ($article && $article->getTemplateId()) {
                $excludedTemplates = array_map('trim', explode(',', $templatesExclude));
                if (in_array((string)$article->getTemplateId(), $excludedTemplates)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Clear clipboard at session start for security
     * This ensures clipboard is cleared on login/logout/session restart
     */
    public static function clearClipboardOnSessionStart(): void
    {
        try {
            // Check if this is a fresh session or no user logged in
            $sessionStarted = rex_request::session('bloecks_session_started', 'bool', false);
            if (!rex::getUser() || $sessionStarted === false) {
                self::clearClipboard();
                rex_request::setSession('bloecks_session_started', true);
            }
            
            // Also clear on logout detection
            if (rex_request('logout') || rex_be_controller::getCurrentPagePart(1) === 'login') {
                self::clearClipboard();
                rex_request::unsetSession('bloecks_session_started');
            }
        } catch (rex_exception $e) {
            // Session not started yet, nothing to clear
            return;
        }
    }
    
    /**
     * Clear all clipboard data from session
     */
    public static function clearClipboard(): void
    {
        try {
            rex_request::unsetSession('bloecks_clipboard');
        } catch (rex_exception $e) {
            // Session not active, nothing to clear
        }
    }
    
    /**
     * Check if user has content edit permissions for article/template/module
     * Based on REDAXO structure content permissions
     */
    public static function hasContentEditPermission($articleId, $clang, $moduleId = null): bool
    {
        $user = rex::getUser();
        
        if (!$user) return false;
        
        // Admin users always have permission
        if ($user->isAdmin()) return true;
               
        // Check if user has structure permission for this article/category
        $article = rex_article::get($articleId, $clang);
        if (!$article) return false;
        
        $structurePerm = $user->getComplexPerm('structure');
        if ($structurePerm) {
            // Check category permission (articles inherit from their category)
            $categoryId = $article->getCategoryId();
            if ($categoryId && !$structurePerm->hasCategoryPerm($categoryId)) {
                return false;
            }
        }
        
        // Check module permissions if module is specified
        if ($moduleId) {
            $modulePerm = $user->getComplexPerm('modules');
            if ($modulePerm && !$modulePerm->hasPerm($moduleId)) {
                return false;
            }
        }
        
        return true;
    }
}
