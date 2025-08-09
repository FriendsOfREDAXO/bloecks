<?php

namespace FriendsOfRedaxo\Bloecks;

/**
 * BlOecksDragNDrop class - Drag & Drop functionality integrated into the main addon.
 */
class BlOecksDragNDrop extends BlOecksAbstract
{
    /**
     * Initializes the drag & drop functionality.
     */
    public static function init(\rex_extension_point $ep)
    {
        if (\rex::isBackend() && \rex::getUser()) {
            // call the backend functions
            BlOecksDragNDropBackend::init($ep);
        }
    }
}
