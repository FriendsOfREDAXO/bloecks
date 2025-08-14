<?php

/**
 * @deprecated cutncopy functionality is now integrated into FriendsOfRedaxo\Bloecks\Backend
 */
class bloecks_cutncopy extends bloecks_abstract
{
    /**
     * @deprecated This functionality is now integrated into the main Backend class
     */
    protected static $plugin_name = 'cutncopy';

    /**
     * @deprecated Use FriendsOfRedaxo\Bloecks\Backend::init() instead
     */
    public static function init($ep = null)
    {
        // Cut/copy functionality is now integrated
        return \FriendsOfRedaxo\Bloecks\Backend::init();
    }
}
