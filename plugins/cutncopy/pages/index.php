<?php
    $plugin = BloecksBackend::getPlugin('cutncopy');
    $addon_dir = rex_addon::get('bloecks')->getPath('pages');

    $content = '<p>' . $plugin->i18n('bloecks_plugin_missing') . '</p>';

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('body', $content, false);
    $sections = $fragment->parse('core/page/section.php');
    unset($fragment, $buttons, $content);

    ?><form action="<?php echo rex_url::currentBackendPage();?>" method="post">
    <?php echo $sections;?>
    </form>
