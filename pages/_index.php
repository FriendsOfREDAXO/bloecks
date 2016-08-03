<?php
    if(!is_object($plugin))
    {
        return;
    }

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
    // set up sections variable
    $sections = '';

    // get all modules
    $sql = rex_sql::factory();
    $modules = $sql->getArray("SELECT `id`, `name` FROM `".rex::getTablePrefix()."module` ORDER BY `name`");

    /**
     * Let's display the modules section
     */
    $content = '';

    // get config values
    $values = $plugin->getConfig('modules');

    // create all modules checkbox
    $fragment = new rex_fragment();
    $fragment->setVar('name', 'bloecks[' . $plugin->getName() . '][modules][]', false);
    $fragment->setVar('checked', $values === null || (is_array($values) && in_array('all', $values)), false);
    $fragment->setVar('value', 'all', false);
    $fragment->setVar('label', $plugin->i18n('bloecks_modules_available_all'), false);
    $fragment->setVar('toggleFields','.allmodules',false);
    $content .= $fragment->parse('form/checkbox.php');

    // create modules select list
    $fragment = new rex_fragment();
    $fragment->setVar('group', 'allmodules', false);
    $fragment->setVar('name', 'bloecks[' . $plugin->getName() . '][modules][]', false);
    $fragment->setVar('min', count($modules), false);
    $fragment->setVar('size', 5, false);
    $fragment->setVar('multiple', true, false);
    $fragment->setVar('selected',$values,false);
    $fragment->setVar('label', rex_i18n::msg('bloecks_modules_available'), false);
    $fragment->setVar('options',$modules,false);
    $fragment->setVar('info',rex_i18n::msg('ctrl'),false);
    $content .= $fragment->parse('form/select.php');

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', rex_i18n::msg('bloecks_modules_title'));
    $fragment->setVar('body', $content, false);
    $sections .= $fragment->parse('core/page/section.php');

    unset($content, $fragment, $modules);

    /**
     * Let's create CTYPE sections for templates
     */
    $content = '';

    // foreach template create a select ctype block
    $templates = array();

    $sql = rex_sql::factory();
    foreach($sql->getArray("SELECT `id`, `name`, `attributes` FROM `" . rex::getTablePrefix() . "template` WHERE `active` = 1") as $key => $attributes)
    {
        $_attributes = json_decode($attributes['attributes'],1);
        if(isset($_attributes['ctype']))
        {
            $templates[$attributes['id']] = array(
                'name' => $attributes['name'],
                'data' => $_attributes['ctype']
            );
        }
        unset($_attributes);
    }
    unset($sql, $key, $attributes);

    // CTypes
    if(!empty($templates))
    {
        $values = BloecksBackend::getPlugin('' . $plugin->getName() . '')->getConfig('templates');

        foreach($templates as $tid => $template)
        {
            $content = '';

            $fragment = new rex_fragment();
            $fragment->setVar('name', 'bloecks[' . $plugin->getName() . '][templates]['.$tid.'][]', false);
            $fragment->setVar('checked', !is_array($values) || !isset($values[$tid]) || (!empty($values[$tid]) && in_array('all', $values[$tid])), false);
            $fragment->setVar('value', 'all', false);
            $fragment->setVar('toggleFields','.alltemplates_'.$tid,false);
            if(empty($template['data']))
            {
                $content .= '<input type="hidden" name="bloecks[' . $plugin->getName() . '][templates]['.$tid.'][all]" value="false" />';
                $fragment->setVar('label', $plugin->i18n('bloecks_templates_available_no_ctypes'), false);
            }
            else
            {
                $fragment->setVar('label', $plugin->i18n('bloecks_templates_available_all'), false);
            }
            $content .= $fragment->parse('form/checkbox.php');

            if(!empty($template['data']))
            {
                $fragment = new rex_fragment();
                $fragment->setVar('group', 'alltemplates_'.$tid, false);
                $fragment->setVar('name', 'bloecks[' . $plugin->getName() . '][templates]['.$tid.'][]', false);
                $fragment->setVar('min', count($template['data']), false);
                $fragment->setVar('size', 5, false);
                $fragment->setVar('multiple', true, false);
                $fragment->setVar('selected', (isset($values[$tid]) ? $values[$tid] : false), false);
                $fragment->setVar('label', $plugin->i18n('bloecks_templates_available'), false);
                $fragment->setVar('options',$template['data'],false);
                $fragment->setVar('info',rex_i18n::msg('ctrl'),false);
                $content .= $fragment->parse('form/select.php');
            }



            $fragment = new rex_fragment();
            $fragment->setVar('class', 'edit', false);
            $fragment->setVar('title', $plugin->i18n('bloecks_templates_title').' '.$template['name'] . ' (ID: ' . $tid . ')');
            $fragment->setVar('body', $content, false);
            $sections .= $fragment->parse('core/page/section.php');
        }
        unset($values, $tid, $template);
    }
    unset($templates);

    /**
     * Now the buttons
     */

    $content = '';

    $formElements = [];
    $n = [];
    $n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="' . $plugin->i18n('save') . '">' . $plugin->i18n('save') . '</button>';
    $formElements[] = $n;
    $n = [];
    $n['field'] = '<button class="btn btn-reset" type="reset" name="btn_reset" value="' . $plugin->i18n('reset') . '" data-confirm="' . $plugin->i18n('reset_info') . '">' . $plugin->i18n('reset') . '</button>';
    $formElements[] = $n;
    unset($n);

    $fragment = new rex_fragment();
    $fragment->setVar('flush', true);
    $fragment->setVar('elements', $formElements, false);
    $buttons = $fragment->parse('core/form/submit.php');
    unset($formElements, $fragment);

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    $sections .= $fragment->parse('core/page/section.php');
    unset($fragment, $buttons, $content);

?><form action="<?php echo rex_url::currentBackendPage();?>" method="post">
    <?php if($message) echo rex_view::success($message); ?>
    <?php if($error) echo rex_view::success($error); ?>
    <?php echo $sections;?>
</form>
