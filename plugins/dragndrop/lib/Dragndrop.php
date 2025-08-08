<?php

namespace FriendsOfRedaxo\Bloecks\Dragndrop;

use FriendsOfRedaxo\Bloecks\AbstractBase;
use FriendsOfRedaxo\Bloecks\Dragndrop\Backend;

/**
 * bloecks_dragndrop class - basic functions for the plugin.
 */
class Dragndrop extends AbstractBase
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
            Backend::init($ep);
        }
    }
}
