<?php

namespace FriendsOfRedaxo\Bloecks\Cutncopy;

use FriendsOfRedaxo\Bloecks\AbstractBase;
use FriendsOfRedaxo\Bloecks\Cutncopy\Backend;

/**
 * bloecks_status class - basic functions for the plugin.
 */
class Cutncopy extends AbstractBase
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
            Backend::init($ep);
        }
    }
}
