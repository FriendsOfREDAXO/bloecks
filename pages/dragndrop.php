<?php
/** @var rex_addon $this */

if (rex_post('config-submit', 'boolean')) {
    $this->setConfig(rex_post('config', [
        ['display_sort_buttons', 'bool']
    ]));

    echo rex_view::success($this->i18n('saved'));
}

$content = '<fieldset>';

$formElements = [];

$n = [];
$n['label'] = '<label for="rex-bloecks-dragndrop-display_sort_buttons">' . $this->i18n('display_sort_buttons') . '</label>';
$n['field'] = '<input type="checkbox" id="rex-bloecks-dragndrop-display_sort_buttons" name="config[display_sort_buttons]" value="1" ' . ($this->getConfig('display_sort_buttons') ? ' checked="checked"' : '') . ' />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

$formElements = [];

$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="config-submit" value="1" ' . rex::getAccesskey($this->i18n('save'), 'save') . '>' . $this->i18n('save') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('flush', true);
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', $this->i18n('settings'));
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');

echo '
    <form action="' . rex_url::currentBackendPage() . '" method="post">
        ' . $content . '
    </form>';


/* package info from README.md */

$package = rex_package::get($this->getProperty('package'));

$content = '';
if (is_readable($package->getPath('README.'. rex_i18n::getLanguage() .'.md'))) {
    $fragment = new rex_fragment();
    $fragment->setVar('content', rex_markdown::factory()->parse(rex_file::get($package->getPath('README.'. rex_i18n::getLanguage() .'.md'))), false);
    $content .= $fragment->parse('core/page/docs.php');
} elseif (is_readable($package->getPath('README.md'))) {
    $fragment = new rex_fragment();
    $fragment->setVar('content', rex_markdown::factory()->parse(rex_file::get($package->getPath('README.md'))), false);
    $content .= $fragment->parse('core/page/docs.php');
}

if (!empty($content)) {
    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('package_help') . ' ' . $package->getPackageId(), false);
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
}
