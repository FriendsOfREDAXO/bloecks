<?php
/**
 * bloecks_dragndrop class - Drag & Drop functionality integrated into the main addon.
 */
class bloecks_dragndrop extends bloecks_abstract
{
    /**
     * Initializes the drag & drop functionality.
     */
    public static function init(rex_extension_point $ep)
    {
        if (rex::isBackend() && rex::getUser()) {
            // call the backend functions
            bloecks_dragndrop_backend::init($ep);
        }
    }
}
