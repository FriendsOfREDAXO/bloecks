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
        if (in_array($function, ['copy', 'cut', 'paste'])) {
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

        rex_set_session('bloecks_clipboard', [
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
        ]);

        $message = 'cut' === $action ? rex_i18n::msg('bloecks_slice_cut') : rex_i18n::msg('bloecks_slice_copied');

        echo json_encode(['success' => true, 'message' => $message, 'reload_needed' => false]);
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
}
