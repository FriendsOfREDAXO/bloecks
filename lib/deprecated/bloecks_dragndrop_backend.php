<?php

/**
 * @deprecated dragndrop backend functionality is now integrated into FriendsOfRedaxo\Bloecks\Backend
 */
class bloecks_dragndrop_backend extends bloecks_backend
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
        return \FriendsOfRedaxo\Bloecks\Backend::init();
    }

    /**
     * @deprecated Slice wrapping is now handled by FriendsOfRedaxo\Bloecks\Wrapper
     */
    public static function showSlice($ep)
    {
        // This functionality is now handled by the Wrapper class
        return $ep->getSubject();
    }
}
