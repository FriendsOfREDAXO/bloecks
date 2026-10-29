<?php

// Favoriten: gemeinsame Slice-Vorlagen
rex_sql_table::get(rex::getTable('bloecks_favorite'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('module_id', 'int(10) unsigned'))
    ->ensureColumn(new rex_sql_column('data', 'mediumtext'))
    ->ensureGlobalColumns()
    ->ensure();
