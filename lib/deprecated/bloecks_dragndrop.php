<?php

use FriendsOfRedaxo\Bloecks\Backend;

/**
 * @deprecated dragndrop functionality is now integrated into FriendsOfRedaxo\Bloecks\Backend
 */
class bloecks_dragndrop extends bloecks_abstract
{
    /**
     * @deprecated This functionality is now integrated into the main Backend class
     */
    protected static $plugin_name = 'dragndrop';

    /**
     * @deprecated Use FriendsOfRedaxo\Bloecks\Backend::init() instead
     */
    public static function init($ep = null)
    {
        // Drag/drop functionality is now integrated
        return Backend::init();
    }
}
