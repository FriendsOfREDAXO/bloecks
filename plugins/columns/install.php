<?php

rex_sql_table::get(rex::getTablePrefix().'article_slice')
  ->ensureColumn(new rex_sql_column('bloecks_format', 'varchar(255)'))
  ->alter();


  if (!$this->hasConfig()) {
      $config = [
          'columns' => [
              'grid' => 4,
              'default' => 4,
              'min' => 1,
              'max' => 4
          ]
      ];
      $this->setConfig($config);
  }
