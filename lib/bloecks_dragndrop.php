<?php
/**
 * bloecks_dragndrop class - Drag and drop functionality for slices.
 */
class bloecks_dragndrop extends bloecks_backend
{
    /**
     * Initialize the dragndrop functionality in the backend.
     */
    public static function init(rex_extension_point $ep)
    {
        if (rex::getUser()) {
            // register action for display of the slice
            rex_extension::register('SLICE_SHOW_BLOECKS_BE', ['bloecks_dragndrop', 'showSlice']);
        }
    }

    /**
     * Wraps a LI.rex-slice-draggable around both the block selector and the block itself.
     *
     * @param rex_extension_point $ep [description]
     *
     * @return string the slice content
     */
    public static function showSlice(rex_extension_point $ep)
    {
        if (rex::getUser()->hasPerm('bloecks[]')) {
            $subject = $ep->getSubject();

            if (preg_match('/class="rex-slice rex-slice-output/', $subject) && preg_match('/class="rex-slice rex-slice-select"/', $subject)) {
                // get setting 'display sort buttons' ?
                $sortbuttons = static::settings('display_sort_buttons', false) ? '' : ' has--no-sortbuttons';

                // get setting 'display in compact mode' ?
                $compactmode = static::settings('display_compact', true) ? ' is--compact' : '';

                $csrfToken = rex_csrf_token::factory(rex_api_content_move_slice_to::class)->getValue();

                $subject = '<li class="rex-slice rex-slice-draggable' . $sortbuttons . $compactmode . '" data-csrf-token="' . $csrfToken . '"><ul class="rex-slices is--undraggable">' . $subject . '</ul></li>';

                $ep->setSubject($subject);
            }
        }
    }
}