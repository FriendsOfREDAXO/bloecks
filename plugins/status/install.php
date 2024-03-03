<?php

// REDAXO <5.10 only
if (rex_string::versionCompare(rex::getVersion(), '5.10.0-dev', '<')) {
    /*
     * Make sure we have a STATUS column in the database
     */

    if (rex_sql_table::get(rex::getTablePrefix().'article_slice')->hasColumn('bloecks_status')) {
        $qry = 'ALTER TABLE `'.rex::getTablePrefix().'article_slice` CHANGE `bloecks_status` `status` TINYINT;';
        rex_sql::factory()->setQuery($qry);
    } else {
        rex_sql_table::get(rex::getTablePrefix().'article_slice')
            ->ensureColumn(new rex_sql_column('status', 'tinyint(1) unsigned', false, 1))
            ->alter();
    }
}
