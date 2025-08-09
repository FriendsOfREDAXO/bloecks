<?php
/**
 * bloecks_cutncopy class - Cut & Copy functionality integrated into the main addon.
 */
class bloecks_cutncopy extends bloecks_abstract
{
    /**
     * Initializes the cut & copy functionality.
     */
    public static function init(rex_extension_point $ep)
    {
        if (rex::isBackend()) {
            // call the backend functions
            bloecks_cutncopy_backend::init($ep);
        }
    }
}
