<?php

use FriendsOfRedaxo\Bloecks\Backend;

/**
 * @deprecated cutncopy backend functionality is now integrated into FriendsOfRedaxo\Bloecks\Backend
 */
class bloecks_cutncopy_backend extends bloecks_backend
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
        return Backend::init();
    }

    /**
     * @deprecated Cookie-based clipboard replaced with session-based system
     */
    protected static function getCookieName()
    {
        return 'rex_bloecks_cutncopy';
    }

    /**
     * @deprecated Cookie-based clipboard replaced with session-based system
     */
    public static function deleteCookie()
    {
        // Cookies are no longer used - session-based clipboard now
        Backend::clearClipboard();
    }

    /**
     * @deprecated Cookie-based clipboard replaced with session-based system
     */
    protected static function setCookie($value)
    {
        // Cookies are no longer used
    }

    /**
     * @deprecated Cookie-based clipboard replaced with session-based system
     */
    protected static function getCookie($key = null, $vartype = null, $default = null)
    {
        // Cookies are no longer used
        return $default;
    }

    /**
     * @deprecated Button adding is now handled by the integrated Backend class
     */
    public static function addButtons($ep)
    {
        // This functionality is now integrated into the Backend class
        return $ep->getSubject();
    }

    /**
     * @deprecated Process handling is now integrated into the Backend class
     */
    public static function process($ep)
    {
        // This functionality is now integrated into the Backend class
        return $ep->getSubject();
    }

    /**
     * @deprecated Slice copying is now handled by the integrated Backend class
     */
    public static function copySlice($slice)
    {
        // This functionality is now integrated into the Backend class
        return '';
    }

    /**
     * @deprecated Slice cutting is now handled by the integrated Backend class
     */
    public static function cutSlice($slice)
    {
        // This functionality is now integrated into the Backend class
        return '';
    }

    /**
     * @deprecated This is now handled by the integrated Backend class
     */
    public static function prepareClipboardSliceForAdding()
    {
        // This functionality is now integrated into the Backend class
    }

    /**
     * @deprecated This is now handled by the integrated Backend class
     */
    public static function postProcessClipboard($ep)
    {
        // This functionality is now integrated into the Backend class
        return '';
    }

    /**
     * @deprecated This is now handled by the integrated Backend class
     */
    public static function addBlockToDropdown($ep)
    {
        // This functionality is now integrated into the Backend class
        return $ep->getSubject();
    }
}
