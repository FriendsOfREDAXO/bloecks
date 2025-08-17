<?php

use FriendsOfRedaxo\Bloecks\Backend;

/**
 * @deprecated Use FriendsOfRedaxo\Bloecks\Backend instead
 */
class bloecks
{
    /**
     * @deprecated Use FriendsOfRedaxo\Bloecks\Backend::init() instead
     */
    public static function init($ep = null)
    {
        return Backend::init();
    }
}
