<?php

// CRONJOB REGISTER
if (rex_addon::get('cronjob')->isAvailable()) {

  rex_cronjob_manager::registerType('rex_cronjob_bloecks_statusswitch');

}

// LOAD ASSETS
if (rex::isBackend() && rex_be_controller::getCurrentPagePart(2) == 'statusswitch') {

  if (!rex_plugin::get('ui_tools', 'bootstrap-datetimepicker')->isAvailable()) {

    rex_view::addCssFile($this->getAssetsUrl('vendor/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css'));
    rex_view::addJsFile($this->getAssetsUrl('vendor/bootstrap-datetimepicker/js/moment-with-locales.js'));
    rex_view::addJsFile($this->getAssetsUrl('vendor/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js'));

  }

  rex_view::addJsFile($this->getAssetsUrl('main.js'));

}