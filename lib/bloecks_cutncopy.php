<?php

namespace FriendsOfRedaxo\Bloecks;

/**
 * BlOecksCutNCopy class - Cut & Copy functionality integrated into the main addon.
 */
class BlOecksCutNCopy extends BlOecksAbstract
{
    /**
     * Initializes the cut & copy functionality.
     */
    public static function init(\rex_extension_point $ep)
    {
        if (\rex::isBackend()) {
            // call the backend functions
            BlOecksCutNCopyBackend::init($ep);
        }
    }
}
