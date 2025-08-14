<?php

use FriendsOfRedaxo\Bloecks\Backend;

/**
 * @deprecated Use FriendsOfRedaxo\Bloecks\Backend instead
 */
class bloecks_backend extends bloecks_abstract
{
    /**
     * @deprecated Use FriendsOfRedaxo\Bloecks\Backend::init() instead
     */
    public static function init($ep = null)
    {
        return Backend::init();
    }

    /**
     * @deprecated Permission system has changed in bloecks 5.x
     */
    public static function getPermName()
    {
        return 'bloecks[]';
    }

    /**
     * @deprecated Permission registration is now handled automatically
     */
    public static function addPerm()
    {
        // Permissions are registered automatically in boot.php
        return 'bloecks[]';
    }

    /**
     * @deprecated Use rex::getUser()->hasPerm() and getComplexPerm() directly
     */
    public static function hasModulePerm(rex_user $user, $module_id)
    {
        if (!$user->hasPerm('admin[]')) {
            if (!$user->hasPerm('bloecks[]')) {
                return false;
            }
        }

        return $user->getComplexPerm('modules')->hasPerm($module_id);
    }

    /**
     * @deprecated Extension point handling has changed in bloecks 5.x
     */
    public static function showSlice($ep)
    {
        // This is now handled by the new Backend class
        return $ep->getSubject();
    }

    /**
     * @deprecated Button adding is now handled differently
     */
    public static function addButton($ep, array $btn)
    {
        $items = (array) $ep->getSubject();
        $items[] = $btn;
        $ep->setSubject($items);
    }
}
