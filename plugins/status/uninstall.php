<?php
    /**
     * Let's remove the STATUS column in the database
     */
    $addon_cols = [
        'status'
    ];

    $db_cols = rex_sql::showColumns(rex::getTablePrefix().'article_slice');
    foreach($addon_cols as $addon_col)
    {
        $found = false;
        foreach($db_cols as $db_col)
        {
            if($db_col['name'] === $addon_col)
            {
                $found = true;
                break;
            }
        }

        if($found)
        {
            $sql = rex_sql::factory();
            $sql->setQuery("ALTER TABLE `".rex::getTablePrefix().'article_slice'."` DROP ".$addon_col, array());
        }
    }

    unset($addon_cols, $addon_col, $db_cols, $db_col, $found, $sql);
