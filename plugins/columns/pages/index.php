<?php
    $plugin = BloecksBackend::getPlugin('columns');
    $addon_dir = rex_addon::get('bloecks')->getPath('pages');

    $message = '';
    $error = '';

    $isSaved = BloecksBackend::saveSettings($plugin->getName());

    if($isSaved !== null)
    {
        if($isSaved == false)
        {
            $error = $plugin->i18n('config_not_saved');
        }
        else
        {
            $message = $plugin->i18n('config_saved_successful');
        }
    }

    $settings = $plugin->getProperty('grids', []);
    $options = [
        'grid',
        'default',
        'min',
        'max'
    ];

    $content = '';

    foreach($settings as $setting)
    {
        $inputs = [];
        $values = $plugin->getConfig($setting);

        foreach($options as $option)
        {
            $id = $plugin->getName() . '-' . $setting . '-' . $option;
            $inputs[] = [
                'label' => '<label for="' . $id . '">' . $plugin->i18n('bloecks_columns_'.$option) . '</label>',
                'field' => '<input class="form-control" id="' . $id . '" type="number" min="1" name="bloecks[' . $plugin->getName() . '][' . $setting . '][' . $option . ']" value="' . (isset($values[$option]) ? (string) $values[$option] : '') . '" />'
            ];
            unset($id);
        }

        unset($values, $option);

        $fragment = new rex_fragment();
        $fragment->setVar('elements', $inputs, false);

        $content .= '<fieldset><legend>' . $plugin->i18n('bloecks_columns_'.$setting) . '</legend>';
        $content .= $fragment->parse('core/form/form.php');

        unset($fragment, $inputs);
    }
    unset($settings, $setting, $options);

    $setting = 'advanced';
    $id = $plugin->getName() . '-' . $setting;
    $values = $plugin->getConfig($setting);
    $inputs = [];
    $inputs[] = [
        'label' => '<label for="' . $id . '">' . $plugin->i18n('bloecks_columns_'.$setting) . '</label>',
        'field' => '<textarea class="form-control bloecks--code" id="' . $id . '" name="bloecks[' . $plugin->getName() . '][' . $setting . ']">' . $values . '</textarea>',
        'note' => $plugin->i18n('bloecks_columns_advanced_info')
    ];
    unset($id, $values);

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $inputs, false);
    unset($inputs);

    $content .= '<fieldset><legend>' . $plugin->i18n('bloecks_columns_'.$setting) . '</legend>';
    $content .= $fragment->parse('core/form/form.php');
    unset($fragment, $setting);


    $inputs = [];
    $inputs[] = [
        'field' => '<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="' . $plugin->i18n('save') . '">' . $plugin->i18n('save') . '</button>'
    ];
    $inputs[] = [
        'field' => '<button class="btn btn-reset" type="reset" name="btn_reset" value="' . $plugin->i18n('reset') . '" data-confirm="' . $plugin->i18n('reset_info') . '">' . $plugin->i18n('reset') . '</button>'
    ];

    $fragment = new rex_fragment();
    $fragment->setVar('flush', true);
    $fragment->setVar('elements', $inputs, false);
    $buttons = $fragment->parse('core/form/submit.php');
    unset($inputs, $fragment);

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    $content = $fragment->parse('core/page/section.php');
    unset($fragment, $buttons);

?><form action="<?php echo rex_url::currentBackendPage();?>" method="post">
    <?php if($message) echo rex_view::success($message); ?>
    <?php if($error) echo rex_view::success($error); ?>
    <?php echo $content;?>
</form>
