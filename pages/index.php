<?php
/**
 * bloecks
 *
 * @var rex_addon $this
 */

echo rex_view::title($this->i18n('name'));

if($subpage = rex_be_controller::getCurrentPagePart(2))
{
    rex_be_controller::includeCurrentPageSubPath();
}
else
{
    echo '<p>' . $this->i18n('bloecks_no_plugin_settings_available') . ' <a href="' . rex_url::backendController(['page' => 'packages']) . '">' . $this->i18n('switch_to_addons_page') . '</a></p>';
}
