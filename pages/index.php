<?php
/**
 * @package redaxo5
 */

echo rex_view::title(rex_i18n::msg('bloecks_title'));

if($subpage = rex_be_controller::getCurrentPagePart(2))
{
    include rex_be_controller::getCurrentPageObject()->getSubPath();
}
else
{
    echo '<p>' . $this->i18n('bloecks_no_plugins_installed') . '</p>';
}
