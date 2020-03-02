<?php
/**
 * bloecks_dragndrop class - basic functions for the plugin.
 */
class bloecks_dragndrop extends bloecks_abstract
{
    /**
     * The name of the plugin.
     *
     * @var string
     */
    protected static $plugin_name = 'dragndrop';

    /**
     * Initializes the plugin.
     */
    public static function init(rex_extension_point $ep)
    {
        if (rex::isBackend() && rex::getUser()) {
            // call the backend functions
            bloecks_dragndrop_backend::init($ep);
        }
    }
}
