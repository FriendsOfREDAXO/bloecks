<?php

namespace FriendsOfRedaxo\Bloecks;

use rex;
use rex_addon;
use rex_api_function;
use rex_article;
use rex_article_cache;
use rex_article_revision;
use rex_article_slice_history;
use rex_content_service;
use rex_i18n;
use rex_plugin;
use rex_sql;
use rex_sql_exception;

use function in_array;
use function sprintf;

/**
 * API endpoint for drag & drop ordering (exactly like slice_columns sorter.php).
 */
class Api extends rex_api_function
{
    protected $published = false;  // Backend calls only

    public function execute(): never
    {
        $function = rex_request('function', 'string', '');

        // Handle copy/paste AJAX operations
        if (in_array($function, ['copy', 'cut', 'paste', 'clear_clipboard', 'multi_paste', 'get_clipboard_status'])) {
            $this->handleCopyPasteAjax($function);
            exit;
        }

        if ('update_order' === $function) {
            $article_id = rex_request('article', 'int', 0);
            $article_clang = rex_request('clang', 'int', 1);
            $order = rex_request('order', 'string', '');

            $order = json_decode($order);

            if (!$order || !$article_id) {
                echo json_encode(['error' => rex_i18n::msg('bloecks_api_error_missing_parameters')]);
                exit;
            }

            $sql = rex_sql::factory();
            foreach ($order as $key => $value) {
                if ($value) { // Skip empty values
                    $sql->setQuery('UPDATE ' . rex::getTablePrefix() . 'article_slice SET priority = :prio WHERE id = :id', ['prio' => $key + 1, 'id' => $value]);
                }
            }

            if (rex_plugin::get('structure', 'history')->isAvailable()) {
                rex_article_slice_history::makeSnapshot($article_id, $article_clang, 'bloecks_updateorder');
            }
            rex_article_cache::delete($article_id, $article_clang);

            echo json_encode([$function, $order, $article_id]);
            exit;
        }

        echo json_encode(['error' => rex_i18n::msg('bloecks_api_error_unknown_function')]);
        exit;
    }

    private function handleCopyPasteAjax($action)
    {
        $user = rex::getUser();
        if (!$user->hasPerm('bloecks[]') && !$user->hasPerm('bloecks[copy]')) {
            echo json_encode(['success' => false, 'message' => rex_i18n::msg('bloecks_error_no_permission')]);
            return;
        }

        switch ($action) {
            case 'copy':
            case 'cut':
                $this->handleCopyOrCut($action);
                break;

            case 'paste':
                $this->handlePaste();
                break;
                
            case 'clear_clipboard':
                $this->handleClearClipboard();
                break;
                
            case 'multi_paste':
                $this->handleMultiPaste();
                break;
                
            case 'get_clipboard_status':
                $this->handleGetClipboardStatus();
                break;
        }
    }

    private function handleCopyOrCut($action)
    {
        $sliceId = rex_request('slice_id', 'int');
        if (!$sliceId) {
            echo json_encode(['success' => false, 'message' => rex_i18n::msg('bloecks_error_slice_id_missing')]);
            return;
        }

        $sql = rex_sql::factory();
        $row = $sql->getArray('SELECT * FROM ' . rex::getTablePrefix() . 'article_slice WHERE id=?', [$sliceId]);

        if (!$row) {
            echo json_encode(['success' => false, 'message' => rex_i18n::msg('bloecks_error_slice_not_found')]);
            return;
        }

        $row = $row[0];
        $user = rex::getUser();

        if (!$user->getComplexPerm('modules')->hasPerm($row['module_id'])) {
            echo json_encode(['success' => false, 'message' => rex_i18n::msg('bloecks_error_no_module_permission')]);
            return;
        }

        // Check if user has content edit permissions for this slice
        if (!Backend::hasContentEditPermission($row['article_id'], $row['clang_id'], $row['module_id'])) {
            echo json_encode(['success' => false, 'message' => rex_i18n::msg('bloecks_error_no_content_permission')]);
            return;
        }

        // Store slice data in session (same logic as backend)
        $fields = ['module_id', 'status'];
        for ($i = 1; $i <= 20; ++$i) {
            $fields[] = 'value' . $i;
        }
        for ($i = 1; $i <= 5; ++$i) {
            $fields[] = 'media' . $i;
            $fields[] = 'medialist' . $i;
        }
        for ($i = 1; $i <= 5; ++$i) {
            $fields[] = 'link' . $i;
            $fields[] = 'linklist' . $i;
        }

        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $row[$field];
        }

        // Get source slice info
        $sourceArticle = rex_article::get($row['article_id'], $row['clang_id']);

        // Get module name
        $moduleSql = rex_sql::factory();
        $moduleRow = $moduleSql->getArray('SELECT name FROM ' . rex::getTablePrefix() . 'module WHERE id=?', [$row['module_id']]);
        $moduleName = $moduleRow ? $moduleRow[0]['name'] : rex_i18n::msg('bloecks_error_unknown_module');

        // Create clipboard item
        $clipboardItem = [
            'data' => $data,
            'source_slice_id' => $sliceId,
            'source_revision' => $row['revision'] ?? 0,
            'action' => $action,
            'timestamp' => time(),
            'source_info' => [
                'article_name' => $sourceArticle ? $sourceArticle->getName() : rex_i18n::msg('bloecks_error_unknown_article'),
                'module_name' => $moduleName,
                'article_id' => $row['article_id'],
                'clang_id' => $row['clang_id'],
            ],
        ];

        // Store in regular clipboard (backward compatibility)
        rex_set_session('bloecks_clipboard', $clipboardItem);

        // Always use multi-clipboard system now
        $multiClipboard = rex_session('bloecks_multi_clipboard', 'array', []);
        
        // Check if item already exists (by slice_id)
        $existingIndex = -1;
        foreach ($multiClipboard as $index => $item) {
            if ($item['source_slice_id'] == $sliceId) {
                $existingIndex = $index;
                break;
            }
        }
        
        if ($existingIndex !== -1) {
            // Update existing item
            $multiClipboard[$existingIndex] = $clipboardItem;
        } else {
            // Add new item or replace if single-clipboard mode
            // Multi-clipboard is available if setting is enabled AND user has permission
            $isMultiClipboardAvailable = Backend::isMultiClipboardAvailable();
            
            if (!$isMultiClipboardAvailable) {
                // Single clipboard mode - replace all items
                $multiClipboard = [$clipboardItem];
            } else {
                // Multi clipboard mode - add to existing
                $multiClipboard[] = $clipboardItem;
            }
        }
        
        rex_set_session('bloecks_multi_clipboard', $multiClipboard);

        $message = 'cut' === $action ? rex_i18n::msg('bloecks_slice_cut') : rex_i18n::msg('bloecks_slice_copied');

        // Return clipboard item for JavaScript multi-clipboard
        echo json_encode([
            'success' => true, 
            'message' => $message, 
            'reload_needed' => false,
            'clipboard_item' => $clipboardItem
        ]);
    }

    private function handlePaste()
    {
        $clipboard = rex_session('bloecks_clipboard', 'array', null);
        if (!$clipboard || !isset($clipboard['data'])) {
            echo json_encode(['success' => false, 'message' => rex_i18n::msg('bloecks_error_clipboard_empty')]);
            return;
        }

        $targetSlice = rex_request('bloecks_target', 'int');
        $articleId = rex_request('article_id', 'int');
        $clang = rex_request('clang', 'int');
        $ctype = rex_request('ctype', 'int', 1);

        if (!$articleId || !$clang) {
            echo json_encode(['success' => false, 'message' => rex_i18n::msg('bloecks_error_missing_parameters')]);
            return;
        }

        $data = $clipboard['data'];
        $user = rex::getUser();

        // Check if user has content edit permissions for target article
        if (!Backend::hasContentEditPermission($articleId, $clang, $data['module_id'])) {
            echo json_encode(['success' => false, 'message' => rex_i18n::msg('bloecks_error_no_content_permission')]);
            return;
        }

        // Determine priority for insertion
        $priority = 1;
        $sql = rex_sql::factory();

        // Get revision from Version plugin if available
        $revision = 0; // Default revision (LIVE)
        if (class_exists('rex_article_revision')) {
            $revision = rex_article_revision::getSessionArticleRevision($articleId);
        }

        // Get paste position from config
        $addon = rex_addon::get('bloecks');
        $pastePosition = $addon->getConfig('paste_position', 'after');

        if ($targetSlice) {
            $sql->setQuery('SELECT priority FROM ' . rex::getTablePrefix() . 'article_slice WHERE id=?', [$targetSlice]);
            if ($sql->getRows()) {
                $currentPriority = (int) $sql->getValue('priority');

                if ('before' === $pastePosition) {
                    $priority = $currentPriority; // Insert at current priority
                    // Shift target slice and all following slices down
                    $shift = rex_sql::factory();
                    $shift->setQuery(
                        'UPDATE ' . rex::getTablePrefix() . 'article_slice
                         SET priority = priority + 1
                         WHERE article_id=? AND clang_id=? AND revision=? AND priority>=?',
                        [$articleId, $clang, $revision, $priority],
                    );
                } else {
                    $priority = $currentPriority + 1; // Insert AFTER target slice
                    // Shift existing slices down (only in current revision)
                    $shift = rex_sql::factory();
                    $shift->setQuery(
                        'UPDATE ' . rex::getTablePrefix() . 'article_slice
                         SET priority = priority + 1
                         WHERE article_id=? AND clang_id=? AND revision=? AND priority>=?',
                        [$articleId, $clang, $revision, $priority],
                    );
                }
            }
        } else {
            $priority = (int) ($sql->getArray(
                'SELECT MAX(priority) p FROM ' . rex::getTablePrefix() . 'article_slice
                 WHERE article_id=? AND clang_id=? AND revision=?',
                [$articleId, $clang, $revision],
            )[0]['p'] ?? 0) + 1;
        }

        // Insert new slice
        $ins = rex_sql::factory();
        $ins->setTable(rex::getTablePrefix() . 'article_slice');
        $ins->setValue('article_id', $articleId);
        $ins->setValue('clang_id', $clang);
        $ins->setValue('ctype_id', $ctype);
        $ins->setValue('priority', $priority);
        $ins->setValue('revision', $revision);

        foreach ($data as $k => $v) {
            $ins->setValue($k, $v);
        }

        $ins->addGlobalCreateFields();
        $ins->addGlobalUpdateFields();

        try {
            $ins->insert();
            $newSliceId = $ins->getLastId();

            // If cut, delete original slice
            if ('cut' === $clipboard['action']) {
                $srcId = (int) $clipboard['source_slice_id'];
                if ($srcId) {
                    rex_content_service::deleteSlice($srcId);
                }
                rex_unset_session('bloecks_clipboard');
            }

            rex_article_cache::delete($articleId, $clang);

            // Create detailed success message
            $sourceInfo = $clipboard['source_info'] ?? null;
            $moduleName = $sourceInfo ? $sourceInfo['module_name'] : rex_i18n::msg('bloecks_unknown_module');
            $actionText = 'cut' === $clipboard['action'] ? rex_i18n::msg('bloecks_action_cut') : rex_i18n::msg('bloecks_action_copy');

            $message = rex_i18n::msg('bloecks_slice_inserted', $moduleName, $actionText);

            echo json_encode([
                'success' => true,
                'message' => $message,
                'reload_needed' => true,
                'new_slice_id' => $newSliceId,
                'scroll_to_slice' => true,
            ]);
        } catch (rex_sql_exception $e) {
            echo json_encode(['success' => false, 'message' => sprintf(rex_i18n::msg('bloecks_error_insert_failed'), $e->getMessage())]);
        }
    }

    private function handleClearClipboard()
    {
        // Clear both regular and multi clipboard
        rex_unset_session('bloecks_clipboard');
        rex_unset_session('bloecks_multi_clipboard');
        
        echo json_encode([
            'success' => true,
            'message' => rex_i18n::msg('bloecks_clear_clipboard'),
            'reload_needed' => false
        ]);
    }

    private function handleMultiPaste()
    {
        // Check if multi-clipboard is available (setting enabled AND user has permission)
        if (!Backend::isMultiClipboardAvailable()) {
            echo json_encode(['success' => false, 'message' => 'Multi-Clipboard nicht verfügbar']);
            return;
        }
        
        $selectedItems = rex_request('selected_items', 'string', '');
        $targetSlice = rex_request('bloecks_target', 'int');
        $articleId = rex_request('article_id', 'int');
        $clang = rex_request('clang', 'int');
        $ctype = rex_request('ctype', 'int', 1);

        if (!$articleId || !$clang) {
            echo json_encode(['success' => false, 'message' => rex_i18n::msg('bloecks_error_missing_parameters')]);
            return;
        }

        $multiClipboard = rex_session('bloecks_multi_clipboard', 'array', []);
        if (empty($multiClipboard)) {
            echo json_encode(['success' => false, 'message' => rex_i18n::msg('bloecks_error_clipboard_empty')]);
            return;
        }

        // Parse selected items (JSON array of indices)
        $selectedIndices = json_decode($selectedItems, true);
        if (!is_array($selectedIndices)) {
            $selectedIndices = array_keys($multiClipboard); // Paste all if no selection
        }

        $insertedCount = 0;
        $newSliceIds = [];

        try {
            foreach ($selectedIndices as $index) {
                if (!isset($multiClipboard[$index])) continue;
                
                $clipboard = $multiClipboard[$index];
                
                // Use same paste logic as single paste
                $result = $this->pasteSingleItem($clipboard, $targetSlice, $articleId, $clang, $ctype);
                
                if ($result['success']) {
                    $insertedCount++;
                    $newSliceIds[] = $result['new_slice_id'];
                    
                    // If cut, remove from multi-clipboard
                    if ('cut' === $clipboard['action']) {
                        unset($multiClipboard[$index]);
                    }
                }
            }

            // Update multi-clipboard after cuts
            rex_set_session('bloecks_multi_clipboard', array_values($multiClipboard));
            rex_article_cache::delete($articleId, $clang);

            echo json_encode([
                'success' => true,
                'message' => sprintf('%d von %d Elementen eingefügt', $insertedCount, count($selectedIndices)),
                'reload_needed' => true,
                'inserted_count' => $insertedCount,
                'new_slice_ids' => $newSliceIds
            ]);

        } catch (rex_sql_exception $e) {
            echo json_encode(['success' => false, 'message' => sprintf(rex_i18n::msg('bloecks_error_insert_failed'), $e->getMessage())]);
        }
    }

    private function pasteSingleItem($clipboard, $targetSlice, $articleId, $clang, $ctype)
    {
        $data = $clipboard['data'];
        $user = rex::getUser();

        // Check permissions
        if (!Backend::hasContentEditPermission($articleId, $clang, $data['module_id'])) {
            return ['success' => false, 'message' => rex_i18n::msg('bloecks_error_no_content_permission')];
        }

        // Determine priority for insertion
        $priority = 1;
        $sql = rex_sql::factory();

        // Get revision from Version plugin if available
        $revision = 0; // Default revision (LIVE)
        if (class_exists('rex_article_revision')) {
            $revision = rex_article_revision::getSessionArticleRevision($articleId);
        }

        // Get paste position from config
        $addon = rex_addon::get('bloecks');
        $pastePosition = $addon->getConfig('paste_position', 'after');

        if ($targetSlice) {
            $sql->setQuery('SELECT priority FROM ' . rex::getTablePrefix() . 'article_slice WHERE id=?', [$targetSlice]);
            if ($sql->getRows()) {
                $currentPriority = (int) $sql->getValue('priority');
                
                if ($pastePosition === 'before') {
                    $priority = $currentPriority; // Insert at current priority
                    // Shift target slice and all following slices down
                    $shift = rex_sql::factory();
                    $shift->setQuery(
                        'UPDATE ' . rex::getTablePrefix() . 'article_slice
                         SET priority = priority + 1
                         WHERE article_id=? AND clang_id=? AND revision=? AND priority>=?',
                        [$articleId, $clang, $revision, $priority],
                    );
                } else {
                    $priority = $currentPriority + 1; // Insert AFTER target slice
                    // Shift existing slices down (only in current revision)
                    $shift = rex_sql::factory();
                    $shift->setQuery(
                        'UPDATE ' . rex::getTablePrefix() . 'article_slice
                         SET priority = priority + 1
                         WHERE article_id=? AND clang_id=? AND revision=? AND priority>=?',
                        [$articleId, $clang, $revision, $priority],
                    );
                }
            }
        } else {
            $priority = (int) ($sql->getArray(
                'SELECT MAX(priority) p FROM ' . rex::getTablePrefix() . 'article_slice
                 WHERE article_id=? AND clang_id=? AND revision=?',
                [$articleId, $clang, $revision],
            )[0]['p'] ?? 0) + 1;
        }

        // Insert new slice
        $ins = rex_sql::factory();
        $ins->setTable(rex::getTablePrefix() . 'article_slice');
        $ins->setValue('article_id', $articleId);
        $ins->setValue('clang_id', $clang);
        $ins->setValue('ctype_id', $ctype);
        $ins->setValue('priority', $priority);
        $ins->setValue('revision', $revision);

        foreach ($data as $k => $v) {
            $ins->setValue($k, $v);
        }

        $ins->addGlobalCreateFields();
        $ins->addGlobalUpdateFields();

        $ins->insert();
        $newSliceId = $ins->getLastId();

        // If cut, delete original slice
        if ('cut' === $clipboard['action']) {
            $srcId = (int) $clipboard['source_slice_id'];
            if ($srcId) {
                rex_content_service::deleteSlice($srcId);
            }
        }

        return ['success' => true, 'new_slice_id' => $newSliceId];
    }

    private function handleGetClipboardStatus()
    {
        $clipboard = rex_session('bloecks_clipboard', 'array', null);
        $multiClipboard = rex_session('bloecks_multi_clipboard', 'array', []);
        
        // Multi-clipboard is available if setting is enabled AND user has permission
        $isMultiClipboardAvailable = Backend::isMultiClipboardAvailable();
        
        echo json_encode([
            'success' => true,
            'has_clipboard' => !empty($clipboard),
            'multi_clipboard_enabled' => $isMultiClipboardAvailable,
            'multi_clipboard_count' => count($multiClipboard),
            'multi_clipboard_items' => $multiClipboard
        ]);
    }
}
