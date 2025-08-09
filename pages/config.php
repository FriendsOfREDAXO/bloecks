<?php
/** @var rex_addon $this */

if (rex_post('config-submit', 'boolean')) {
    $this->setConfig(rex_post('config', [
        ['cutncopy_active', 'bool'],
        ['dragndrop_active', 'bool'],
        ['display_sort_buttons', 'bool']
    ]));

    echo rex_view::success($this->i18n('saved'));
}

$content = '<fieldset>';

$formElements = [];

// Cut & Copy activation
$n = [];
$n['label'] = '<label for="rex-bloecks-cutncopy-active">' . $this->i18n('cutncopy_active') . '</label>';
$n['field'] = '<input type="checkbox" id="rex-bloecks-cutncopy-active" name="config[cutncopy_active]" value="1" ' . ($this->getConfig('cutncopy_active', true) ? ' checked="checked"' : '') . ' />';
$formElements[] = $n;

// Drag & Drop activation
$n = [];
$n['label'] = '<label for="rex-bloecks-dragndrop-active">' . $this->i18n('dragndrop_active') . '</label>';
$n['field'] = '<input type="checkbox" id="rex-bloecks-dragndrop-active" name="config[dragndrop_active]" value="1" ' . ($this->getConfig('dragndrop_active', true) ? ' checked="checked"' : '') . ' />';
$formElements[] = $n;

// Display sort buttons option
$n = [];
$n['label'] = '<label for="rex-bloecks-dragndrop-display_sort_buttons">' . $this->i18n('display_sort_buttons') . '</label>';
$n['field'] = '<input type="checkbox" id="rex-bloecks-dragndrop-display_sort_buttons" name="config[display_sort_buttons]" value="1" ' . ($this->getConfig('display_sort_buttons', true) ? ' checked="checked"' : '') . ' />';
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