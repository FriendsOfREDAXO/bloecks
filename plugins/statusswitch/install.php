<?php

// Create tables
rex_sql_table::get(rex::getTable('bloecks_statusswitch'))
  ->ensurePrimaryIdColumn()
  ->ensureColumn(new rex_sql_column('slice_id', 'int(10)', true))
  ->ensureColumn(new rex_sql_column('slice_status', 'int(10)', true))
  ->ensureColumn(new rex_sql_column('switchdate', 'datetime'))
  ->ensureColumn(new rex_sql_column('exec', 'int(10)'))
  ->ensureColumn(new rex_sql_column('createdate', 'datetime'))
  ->ensureColumn(new rex_sql_column('createuser', 'varchar(255)'))
  ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
  ->ensureColumn(new rex_sql_column('updateuser', 'varchar(255)'))
  ->ensure();

// Create cronjob
$now       = new DateTime();
$startdate = date('Y-m-d 00:00:00', strtotime("tomorrow"));

$cronjob = rex_sql::factory();
$cronjob->setDebug(false);
$cronjob->setQuery('SELECT id FROM ' . rex::getTable('cronjob') . ' WHERE type LIKE "rex_cronjob_bloecks_statusswitch"');

if ($cronjob->getRows() == 0) {

  $cronjob = rex_sql::factory();
  $cronjob->setDebug(false);
  $cronjob->setTable(rex::getTable('cronjob'));
  $cronjob->setValue('name', 'Bloecks: Status Switch');
  $cronjob->setValue('description', '');
  $cronjob->setValue('type', 'rex_cronjob_bloecks_statusswitch');
  $cronjob->setValue('interval', '{"minutes":[0],"hours":[0],"days":"all","weekdays":"all","months":"all"}');
  $cronjob->setValue('environment', '|backend|');
  $cronjob->setValue('execution_start', '1970-01-01 01:00:00');
  $cronjob->setValue('status', '1');
  $cronjob->setValue('parameters', '[]');
  $cronjob->setValue('nexttime', $startdate);
  $cronjob->setValue('createdate', $now->format('Y-m-d H:i:s'));
  $cronjob->setValue('updatedate', $now->format('Y-m-d H:i:s'));
  $cronjob->setValue('createuser', rex::getUser()->getLogin());
  $cronjob->setValue('updateuser', rex::getUser()->getLogin());

  try {
    $cronjob->insertOrUpdate();
    echo rex_view::success('Der Cronjob "Bloecks: Status switch" wurde angelegt. ');
  } catch (rex_sql_exception $e) {
    echo rex_view::warning('Der Cronjob "Bloecks: Status Switch" wurde nicht angelegt.<br/>Wahrscheinlich existiert er schon.');
  }
}
