<?php

rex_sql_table::get(rex::getTablePrefix().'article_slice')
  ->ensureColumn(new rex_sql_column('bloecks_status', 'tinyint(1)', false, 1, 'unsigned'))
  ->alter();
