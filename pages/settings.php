<?php
/** @var rex_addon $this */

if(!rex::getUser()->hasPerm('bloecks[settings]')) {
    echo rex_view::error(rex_i18n::msg('bloecks_no_permission'));
    return;
}

$addon = rex_addon::get('bloecks');
$form = rex_config_form::factory('bloecks');

$field = $form->addCheckboxField('enable_copy_paste');
$field->setLabel(rex_i18n::msg('bloecks_enable_copy_paste'));
$field->addOption(rex_i18n::msg('bloecks_active'), 1);

$field = $form->addCheckboxField('enable_drag_drop');
$field->setLabel(rex_i18n::msg('bloecks_enable_drag_drop'));
$field->addOption(rex_i18n::msg('bloecks_active'), 1);

$field = $form->addTextField('templates_exclude');
$field->setLabel(rex_i18n::msg('bloecks_templates_exclude'));
$field->setNotice(rex_i18n::msg('bloecks_csv_notice'));

$field = $form->addTextField('modules_exclude');
$field->setLabel(rex_i18n::msg('bloecks_modules_exclude'));
$field->setNotice(rex_i18n::msg('bloecks_csv_notice'));

$content = '';
$content .= $form->getMessage();
$content .= $form->get();

$fragment = new rex_fragment();
$fragment->setVar('class','info', false);
$fragment->setVar('title', rex_i18n::msg('bloecks_settings'), false);
$fragment->setVar('body', $content, false);

echo $fragment->parse('core/page/section.php');
