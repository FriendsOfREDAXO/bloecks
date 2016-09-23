<?php
/**
 * bloecks_dragndrop_backend class - basic backend functions for the plugin
 */
class bloecks_dragndrop_backend extends bloecks_backend
{
    /**
     * The name of the plugin
     * @var string
     */
    protected static $plugin_name = 'dragndrop';

    protected static $is_moving = false;

    /**
     * Initialize the plugin in the backend
     */
    public static function init(rex_extension_point $ep)
    {
        // register button to slice menu
        // rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', array('bloecks_dragndrop_backend', 'addButtons'));

        // register action for toggling the slice status
        # rex_extension::register('SLICE_MOVE', array('bloecks_dragndrop_backend', 'process'));

        // register action for display of the slice
        rex_extension::register('SLICE_SHOW_BLOECKS_BE', array('bloecks_dragndrop_backend', 'showSlice'));

        // call the addon init function - see blocks_backend:init() class
        parent::init($ep);
    }

    /**
     * Processes the request: Looks if there is a variable named BLOECKS is set and if it matches
     * the plugins name. If so, try to change the status of the slice depending on the
     * submitted "status" parameter
     * @param  rex_extension_point $ep
     * @return string                  a message if the slice could be toggled
     */
    public static function process(rex_extension_point $ep)
    {
        // let's mark that we are already moving the slice (otherwise this would be called each time the slice is moved)
        if(!empty(static::$is_moving))
        {
            return;
        }
        static::$is_moving = true;

        $direction = $ep->getParam('direction');
        $slice_id = $ep->getParam('slice_id');
        $clang_id = $ep->getParam('clang_id');
        $slice_revision = $ep->getParam('slice_revision');
        $insertafter = rex_request('insertafter', 'int', null);
        $insertafter_prio = null;


        if($insertafter !== null && ($direction == 'moveup' || $direction == 'movedown'))
        {
            $slice = rex_article_slice::getArticleSlicebyId($slice_id, $clang_id, $slice_revision);
            if($slice)
            {
                $slice_priority = (int) $slice->getValue('priority');
                // slice is valid
                if($insertafter > 0)
                {
                    // insertafter is given, let's get it
                    $insertafter_slice = rex_article_slice::getArticleSlicebyId($insertafter, $clang_id, $slice_revision);
                    if($insertafter_slice && ($insertafter_slice->getArticleId() == $slice->getArticleId()) && ($insertafter_slice->getCtype() == $slice->getCtype()))
                    {
                        // insertafter_slice exists and is within the same article and is within the same ctype,
                        // let's get its priority
                        $insertafter_prio = (int) $insertafter_slice->getValue('priority');
                    }
                }
                else
                {
                    // insert after is 0 so the new priority is 0
                    $insertafter_prio = 0;
                }

                if($insertafter_prio !== null)
                {
                    // we could define a new priority
                    //
                    $steps = 0;
                    if($direction == 'movedown')
                    {
                        $steps = $insertafter_prio - $slice_priority - 1;
                    }
                    else if($direction == 'moveup')
                    {
                        $steps = $slice_priority - $insertafter_prio - 2;
                    }

                    if($steps > 0)
                    {
                        for($i = 0; $i < $steps; $i++)
                        {
                            // execute the move $step times (the last one is made by rex_content_service::moveSlice itself)
                            rex_content_service::moveSlice($slice_id, $clang_id, $direction);
                        }
                    }
                }
            }
        }

        static::$is_moving = false;
        die();
    }

    /**
     * Changes the status of a slice. Before the slice status is changed it calls a
     * SLICE_UPDATE_STATUS extension point. After successful changing of the status
     * it calls the SLICE_STATUS_UPDATED extension point.
     * @param int $slice_id     the id of the slice
     * @param int $status       the status (1 for online, 0 for offline)
     */
    public static function setSliceStatus($slice_id, $status = null)
    {
        $slice = rex_article_slice::getArticleSlicebyId($slice_id);
        if($slice)
        {
            // the slice exists...
            //
            if(static::hasModulePerm(rex::getUser(), $slice->getModuleId()) && $status !== null)
            {
                // the user can edit the module AND has the rights to use this plugin

                // define the new status - make sure it is a 0 or a 1
                $new_status = max(0,min(1,(int) $status));

                // get the old status
                $old_status = (int) static::getValueOfSlice($slice->getId(), 'status', 1);

                if($old_status !== null)
                {
                    // there is a former status set...

                    if($old_status != $new_status)
                    {
                        // the new status is different from the old one
                        // call our extension point BEFORE the update
                        rex_extension::registerPoint(new rex_extension_point('SLICE_UPDATE_STATUS', '', [
                            'slice_id' => $slice->getId(),
                            'article_id' => $slice->getArticleId(),
                            'clang_id' => $slice->getClang(),
                            'slice_revision' => $slice->getRevision(),
                        ]));

                        // set the new status via SQL query
                        $sql = rex_sql::factory();
                        $sql->setTable(rex::getTablePrefix().'article_slice');
                        $sql->setWhere(array('id' => $slice_id));
                        $sql->setValue('status', $new_status);
                        if(!$sql->update())
                        {
                            // something went wrong
                            return false;
                        }
                        else
                        {
                            // all went well, we call our UPDATED extension point
                            rex_extension::registerPoint(new rex_extension_point('SLICE_STATUS_UPDATED', '', [
                                'article_id' =>  $slice->getArticleId(),
                                'clang' =>  $slice->getClang(),
                                'slice_id' => $slice->getId(),
                                'page' => rex_be_controller::getCurrentPage(),
                                'ctype' => $slice->getCtype(),
                                'module_id' =>  $slice->getModuleId(),
                                'status' => $new_status,
                                'old_status' => $old_status
                            ]));

                            // recreate caches!
                            rex_article_cache::delete(static::getValueOfSlice($slice_id, 'article_id'), static::getValueOfSlice($slice_id, 'clang_id'));
                        }
                    }
                    return true;
                }
            }
        }

        return null;
    }

    /**
     * Wrap a LI.rex-slice-draggable around both the block selector and the block itself
     * @param  rex_extension_point $ep [description]
     * @return string                  the slice content
     */
    public static function showSlice(rex_extension_point $ep)
    {
        $subject = $ep->getSubject();

        // get setting 'display sort buttons' ?
        $sortbuttons = static::settings('hide_sort_buttons', true) ? ' has--no-sortbuttons' : '';

        // get setting 'display in compact mode' ?
        $compactmode = static::settings('display_compact', true) ? ' is--compact' : '';

        $subject = '<li class="rex-slice rex-slice-draggable' . $sortbuttons . $compactmode . '"><ul class="rex-slices is--undraggable">' . $subject . '</ul></li>';

        return $subject;
    }

}
