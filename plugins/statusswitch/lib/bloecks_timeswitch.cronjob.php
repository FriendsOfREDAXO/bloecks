<?php

class rex_cronjob_bloecks_statusswitch extends rex_cronjob {

  public function execute() {

    $bloecks_status_backend = new bloecks_status_backend();
    $slices                 = rex_sql::factory()->setDebug(0)->setQuery('SELECT * FROM ' . rex::getTable('bloecks_statusswitch') . ' WHERE `exec` != 1 ORDER BY updatedate asc');

    foreach ($slices as $slice) {

      $switchOnline = false;
      $switchOnline = $this->switchOnline(strtotime($slice->getValue('switchdate')));

      if ($switchOnline == true) {
        $bloecks_status_backend->setSliceStatus($slice->getValue('slice_id'), rex_clang::getCurrentId(), $revision = 0, $slice->getValue('slice_status')); // the status (1 for online, 0 for offline)
        rex_sql::factory()->setDebug(0)->setQuery('UPDATE ' . rex::getTable('bloecks_statusswitch') . ' SET `exec` = 1 WHERE `slice_id` = ' . $slice->getValue('slice_id'));
      }
    }
    return true;
  }

  public function switchOnline($switchdate = 0) {
    $now = time();
    return (
      ($switchdate > 0 && $now > $switchdate)
    ) ? 1 : 0;
  }

  public function getTypeName() {
    return rex_i18n::msg('rex_cronjob_bloecks_statusswitch');
  }

  public function getParamFields() {
    return [];
  }
}
?>