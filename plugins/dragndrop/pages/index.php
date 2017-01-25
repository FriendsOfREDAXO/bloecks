<?php
$plugin = $this->getPlugin('dragndrop');

if (rex_post('config-submit', 'boolean')) {
    $plugin->setConfig(rex_post('config', [
        ['display_sort_buttons', 'bool']
    ]));

    echo rex_view::success($plugin->i18n('saved'));
}

$content = '<fieldset>';

$formElements = [];

$n = [];
$n['label'] = '<label for="rex-bloecks-dragndrop-display_sort_buttons">' . $plugin->i18n('display_sort_buttons') . '</label>';
$n['field'] = '<input type="checkbox" id="rex-bloecks-dragndrop-display_sort_buttons" name="config[display_sort_buttons]" value="1" ' . ($plugin->getConfig('display_sort_buttons') ? ' checked="checked"' : '') . ' />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

$formElements = [];

$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="config-submit" value="1" ' . rex::getAccesskey($plugin->i18n('save'), 'save') . '>' . $plugin->i18n('save') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('flush', true);
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', $plugin->i18n('settings'));
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');

echo '
    <form action="' . rex_url::currentBackendPage() . '" method="post">
        ' . $content . '
    </form>';
