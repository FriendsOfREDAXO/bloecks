<?php
  
  //remove table
  rex_sql_table::get(rex::getTable('bloecks_statusswitch'))
    ->drop();