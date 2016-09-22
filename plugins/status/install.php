<?php

    /**
     * Make sure we have a STATUS column in the database
     */
    rex_sql_table::get(rex::getTablePrefix().'article_slice')
      ->ensureColumn(new rex_sql_column('status', 'tinyint(1)', false, 1, 'unsigned'))
      ->alter();
