<?php

// REDAXO <5.10 only
if (rex_string::versionCompare(rex::getVersion(), '5.10.0-dev', '<')) {
    /*
     * Initialize the plugin
     */
    rex_extension::register('PACKAGES_INCLUDED', ['bloecks_status', 'init'], rex_extension::EARLY);
}
