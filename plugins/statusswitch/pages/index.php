<?php

$content = '';

$func    = rex_request('func', 'string');
$data_id = rex_request('id', 'int', 0);

if ($func == 'delete' && $data_id > 0) {

  $sql = rex_sql::factory();
  // $sql->setDebug();

  try {
    $sql->transactional(static function () use ($sql, $data_id) {
      $sql->setTable(rex::getTable('bloecks_statusswitch'));
      $sql->setWhere(['id' => $data_id]);
      $sql->delete();
    });

    $success = rex_i18n::msg('bloecks_statusswitch_deleted');
  } catch (rex_sql_exception $e) {
    $error = $sql->getError();
  }
  $func = '';
}

if ($success != '') {
  echo rex_view::success($success);
}

if ($error != '') {
  echo rex_view::error($error);
}

if ($func == '') {

  $query = 'SELECT `id`, `slice_id`, `slice_status`, `switchdate` FROM `' . rex::getTable('bloecks_statusswitch') . '` ORDER BY `id` ASC';

  $list = rex_list::factory($query, 30, 'statusswitch');
  $list->addTableAttribute('class', 'table-striped');
  $list->setNoRowsMessage($this->i18n('bloecks_statusswitch_norowsmessage'));

  $list->removeColumn('id');

  $list->setColumnLabel('slice_id', $this->i18n('bloecks_statusswitch_slice_id'), '', 1);

  $list->addColumn($this->i18n('bloecks_statusswitch_slice_status_now'), '', 2);
  $list->setColumnLabel($this->i18n('bloecks_statusswitch_slice_status_now'), $this->i18n('bloecks_statusswitch_slice_status') . ' ist');
  $list->setColumnFormat($this->i18n('bloecks_statusswitch_slice_status_now'), 'custom', function ($params) {

    $slice_id = $params['list']->getValue('slice_id');
    $slice    = rex_sql::factory()->getArray('SELECT `id`, `status` FROM rex_article_slice WHERE id=' . $slice_id);

    if ($slice[0]['status'] == "1") {
      return '<span class="rex-online"><i class="rex-icon rex-icon-online"></i> online</span>';
    } else if ($slice[0]['status'] == "0") {
      return '<span class="rex-offline"><i class="rex-icon rex-icon-offline"></i> offline</span>';
    } else if ($slice[0]['status'] == "2") {
      return '<span><i class="rex-icon fa-info-circle"></i> ausgeführt</span>';
    }

  });

  $list->setColumnLabel('slice_status', $this->i18n('bloecks_statusswitch_slice_status') . ' wird', '', 3);
  $list->setColumnFormat('slice_status', 'custom', function ($params) {

    if ($params['list']->getValue('slice_status') == "1") {
      return '<span class="rex-online"><i class="rex-icon rex-icon-online"></i> online</span>';
    } else if ($params['list']->getValue('slice_status') == "0") {
      return '<span class="rex-offline"><i class="rex-icon rex-icon-offline"></i> offline</span>';
    } else if ($params['list']->getValue('slice_status') == "2") {
      return '<span><i class="rex-icon fa-info-circle"></i> ausgeführt</span>';
    }

  });

  $list->setColumnLabel('switchdate', $this->i18n('bloecks_statusswitch_switchdate'));
  $list->setColumnFormat('switchdate', 'custom', function ($params) {

    $value = $params['list']->getValue('switchdate');
    return rex_formatter::strftime($value, 'datetime');

  });

  // icon column
  $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="' . $this->i18n('column_hashtag') . ' ' . rex_i18n::msg('add') . '"><i class="rex-icon rex-icon-add-action"></i></a>';
  $tdIcon = '<i class="rex-icon fa-file-text-o"></i>';
  $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
  $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

  $list->addColumn('edit', '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('edit'), -1, ['', '<td class="rex-table-action">###VALUE###</td>']);
  $list->setColumnParams('edit', ['id' => '###id###', 'func' => 'edit']);

  // delete
  $list->addColumn('delete', '', -1, ['', '<td class="rex-table-action">###VALUE###</td>']);
  $list->setColumnParams('delete', ['id' => '###id###', 'func' => 'delete']);
  $list->addLinkAttribute('delete', 'data-confirm', rex_i18n::msg('delete') . ' ?');
  $list->setColumnFormat('delete', 'custom', static function ($params) {
    $list = $params['list'];
    return $list->getColumnLink('delete', '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('media_manager_type_delete'));
  });

  $fragment = new rex_fragment();
  $fragment->setVar('title', $this->i18n('bloecks_statusswitch'));
  $fragment->setVar('content', $list->get(), false);
  $content .= $fragment->parse('core/page/section.php');

  echo $content;

} else if ($func == 'add' || $func == 'edit') {

  $form = rex_form::factory(rex::getTable('bloecks_statusswitch'), '', 'id=' . $data_id, 'post', false);

  //SLICE ID
  $form->addParam('id', rex_request('id', 'int', 0));
  $field = $form->addInputField('number', 'slice_id');
  $field->setAttribute('class', 'form-control');
  $field->setLabel($this->i18n('bloecks_statusswitch_slice_id'));

  //SWITCH STATUS
  $field = $form->addSelectField('slice_status');
  $field->setLabel($this->i18n('bloecks_statusswitch_slice_status'));
  $select = $field->getSelect();
  $select->setSize(1);
  $select->addOptions([
    0 => 'offline',
    1 => 'online',
    2 => $this->i18n('bloecks_statusswitch_executed'),
  ]);

  //SWITCH DATE
  $field = $form->addInputField('text', 'switchdate');
  $field->setAttribute('class', 'form-control datetimepicker');
  $field->setLabel($this->i18n('bloecks_statusswitch_switchdate'));

  $fragment = new rex_fragment();
  $fragment->setVar('class', 'edit');
  $fragment->setVar('title', $this->i18n('bloecks_statusswitch'));
  $fragment->setVar('body', $form->get(), false);
  $content .= $fragment->parse('core/page/section.php');

  echo $content;
}