<?php
/**
 * bloecks_status class - basic functions for the plugin.
 */
class bloecks_status extends bloecks_abstract
{
    /**
     * The name of the plugin.
     *
     * @var string
     */
    protected static $plugin_name = 'status';

    /**
     * Initializes the plugin.
     */
    public static function init(rex_extension_point $ep)
    {
        if (rex::isBackend() && rex::getUser()) {
            // call the backend functions
            bloecks_status_backend::init($ep);
        } elseif (!rex::isBackend()) {
            // add our slice_show extension whenever a sliceis displayed
            rex_extension::register('SLICE_SHOW_BLOECKS_FE', ['bloecks_status', 'showSlice']);
        }
    }

    /**
     * Checks the status of a slice and empties its content when displayed if it is set to OFFLINE.
     *
     * @return string the slice content
     */
    public static function showSlice(rex_extension_point $ep)
    {
        if ($ep->hasParam('sql')) {
            /** @var rex_sql $sql */
            $sql = $ep->getParam('sql');
            $status = (bool) $sql->getValue('status');
        } else {
            $status = (bool) static::getValueOfSlice($ep->getParam('slice_id'), 'status', 1);
        }
        if (false === $status) {
            // slice is not active - don't show anything!
            return '';
        }
    }
}
