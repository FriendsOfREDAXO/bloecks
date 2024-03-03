<?php
/**
 * bloecks class - basic functions for the addon and its plugins.
 */
class bloecks extends bloecks_abstract
{
    /**
     * Initializes the addon.
     */
    public static function init(rex_extension_point $ep)
    {
        if (rex::isBackend() && rex::getUser()) {
            // initialize the backend functions
            bloecks_backend::init($ep);
        } elseif (!rex::isBackend()) {
            // things to do in frontend
            rex_extension::register('SLICE_SHOW', ['bloecks', 'showSlice'], rex_extension::EARLY);
        }
    }

    /**
     * Creates our own extension point to use in all our plugins.
     *
     * @return string slice content
     */
    public static function showSlice(rex_extension_point $ep)
    {
        // get subject
        $slice_content = $ep->getSubject();

        // add our own extension point
        $slice_content = rex_extension::registerPoint(new rex_extension_point(
            'SLICE_SHOW_BLOECKS_FE',
            $slice_content,
            $ep->getParams()
        ));

        // return
        return $slice_content;
    }
}
