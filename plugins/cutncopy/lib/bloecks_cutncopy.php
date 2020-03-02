<?php
/**
 * bloecks_status class - basic functions for the plugin.
 */
class bloecks_cutncopy extends bloecks_abstract
{
    /**
     * The name of the plugin.
     *
     * @var string
     */
    protected static $plugin_name = 'cutncopy';

    /**
     * Initializes the plugin.
     */
    public static function init(rex_extension_point $ep)
    {
        if (rex::isBackend()) {
            // call the backend functions
            bloecks_cutncopy_backend::init($ep);
        }
    }
}
