<?php

    /**
     * Make sure we have a STATUS column in the database
     */

     if(rex_sql_table::get(rex::getTablePrefix().'article_slice')->hasColumn('bloecks_status'))
     {
         $qry = "ALTER TABLE `" . rex::getTablePrefix()."article_slice` CHANGE `bloecks_status` `status` TINYINT;";
         rex_sql::factory()->setQuery($qry);
     }
