<?php
/**
 * rex_api_bloecks class - API endpoint for drag & drop ordering (exactly like slice_columns sorter.php)
 */
class rex_api_bloecks extends rex_api_function
{
    protected $published = false;  // Aufruf nur aus dem Backend

    public function execute(): never
    {
        $function = rex_request('function', 'string', '');

        if ($function === 'update_order') {
            $article_id = rex_request('article', 'int', 0);
            $article_clang = rex_request('clang', 'int', 1);
            $order = rex_request('order', 'string', '');

            $order = json_decode($order);
            
            if (!$order || !$article_id) {
                echo json_encode(['error' => 'Missing parameters']);
                exit;
            }

            $sql = rex_sql::factory();
            foreach ($order as $key => $value) {
                if ($value) { // Skip empty values
                    $sql->setQuery('UPDATE ' . rex::getTablePrefix() . 'article_slice SET priority = :prio WHERE id = :id', ['prio' => $key + 1, 'id' => $value]);
                }
            }
            
            if (rex_plugin::get('structure','history')->isAvailable()) {
                rex_article_slice_history::makeSnapshot($article_id, $article_clang,'bloecks_updateorder');
            }
            rex_article_cache::delete($article_id, $article_clang);

            echo json_encode([$function, $order, $article_id]);
            exit;
        }

        echo json_encode(['error' => 'Unknown function']);
        exit;
    }
}
