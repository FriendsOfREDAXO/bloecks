<?php
/**
 * bloecks_status_backend class - basic backend functions for the plugin.
 */
class bloecks_status_backend extends bloecks_backend
{
    /**
     * The name of the plugin.
     *
     * @var string
     */
    protected static $plugin_name = 'status';

    /**
     * Initialize the plugin in the backend.
     */
    public static function init(rex_extension_point $ep)
    {
        // register button to slice menu
        rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', ['bloecks_status_backend', 'addButtons']);

        // register action for toggling the slice status
        rex_extension::register('STRUCTURE_CONTENT_BEFORE_SLICES', ['bloecks_status_backend', 'process']);

        // register action for display of the slice
        rex_extension::register('SLICE_SHOW_BLOECKS_BE', ['bloecks_status_backend', 'showSlice']);

        // register action for updating an inserted slice status
        rex_extension::register('SLICE_INSERTED', ['bloecks_status_backend', 'updateSliceStatus']);

        // call the addon init function - see blocks_backend:init() class
        parent::init($ep);
    }

    /**
     * Adds a toggle button to the slice menu.
     *
     * @param rex_extension_point $ep [description]
     *
     * @return array $items
     */
    public static function addButtons(rex_extension_point $ep)
    {
        if (rex::getUser()->hasPerm(static::getPermName()) && true === $ep->getParam('perm')) {
            $status = (bool) static::getValueOfSlice($ep->getParam('slice_id'), 'status', 1);
            $mode = $status ? 'visible' : 'invisible';

            static::addButton($ep, [
                'hidden_label' => static::package()->i18n('toggle_status_'.$mode),
                'url' => rex_url::backendController([
                    'page' => 'content/edit',
                    'article_id' => $ep->getParam('article_id'),
                    'bloecks' => 'status',
                    'module_id' => $ep->getParam('module_id'),
                    'slice_id' => $ep->getParam('slice_id'),
                    'clang' => $ep->getParam('clang'),
                    'ctype' => $ep->getParam('ctype'),
                    'status' => $status ? '0' : '1',
                ]),
                'attributes' => [
                    'class' => ['btn-'.$mode],
                    'title' => static::package()->i18n('toggle_status_'.$mode),
                    'data-state' => $mode,
                ],
                'icon' => $mode,
            ]);
        }
    }

    /**
     * Processes the request: Looks if there is a variable named BLOECKS is set and if it matches
     * the plugins name. If so, try to change the status of the slice depending on the
     * submitted "status" parameter.
     *
     * @return string a message if the slice could be toggled
     */
    public static function process(rex_extension_point $ep)
    {
        $function = rex_request('bloecks', 'string', null);
        $slice_id = $ep->getParam('slice_id');
        $clang = $ep->getParam('clang');
        $module_id = $ep->getParam('module_id');
        $status = rex_request('status', 'bool', null);
        $revision = $ep->getParam('slice_revision');

        if ($function === static::plugin()->getName()) {
            if (!static::setSliceStatus($slice_id, $clang, $revision, $status)) {
                return rex_view::warning(static::package()->i18n('slice_not_updated', static::package()->i18n($status ? 'visible' : 'invisible')));
            }
        }
    }

    /**
     * Changes the status of a slice. Before the slice status is changed it calls a
     * SLICE_UPDATE_STATUS extension point. After successful changing of the status
     * it calls the SLICE_STATUS_UPDATED extension point.
     *
     * @param int $slice_id the id of the slice
     * @param int $clang    the id of the current language
     * @param int revision      the revision id
     * @param int $status the status (1 for online, 0 for offline)
     *
     * @return bool
     */
    public static function setSliceStatus($slice_id, $clang = null, $revision = null, $status = null)
    {
        $slice = rex_article_slice::getArticleSliceById($slice_id, $clang, $revision);
        if ($slice) {
            // the slice exists...
            //
            if (static::hasModulePerm(rex::getUser(), $slice->getModuleId()) && null !== $status) {
                // the user can edit the module AND has the rights to use this plugin

                // define the new status - make sure it is a 0 or a 1
                $new_status = max(0, min(1, (int) $status));

                // get the old status
                $old_status = (int) static::getValueOfSlice($slice->getId(), 'status', 1);

                if (null !== $old_status) {
                    // there is a former status set...

                    if ($old_status != $new_status) {
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
                        $sql->setWhere(['id' => $slice_id]);
                        $sql->setValue('status', $new_status);
                        if (!$sql->update()) {
                            // something went wrong
                            return false;
                        }

                        // all went well, we call our UPDATED extension point
                        rex_extension::registerPoint(new rex_extension_point('SLICE_STATUS_UPDATED', '', [
                            'article_id' => $slice->getArticleId(),
                            'clang' => $slice->getClang(),
                            'slice_id' => $slice->getId(),
                            'page' => rex_be_controller::getCurrentPage(),
                            'ctype' => $slice->getCtype(),
                            'module_id' => $slice->getModuleId(),
                            'status' => $new_status,
                            'old_status' => $old_status,
                        ]));

                        // recreate caches!
                        rex_article_cache::delete(static::getValueOfSlice($slice_id, 'article_id'), static::getValueOfSlice($slice_id, 'clang_id'));
                    }
                    return true;
                }
            }
        }

        return null;
    }

    /**
     * Adds a class .rex-slice-status-off to the slice so we can style the block via CSS.
     *
     * @param rex_extension_point $ep [description]
     *
     * @return string the slice content
     */
    public static function showSlice(rex_extension_point $ep)
    {
        $subject = $ep->getSubject();

        if ($ep->hasParam('sql')) {
            /** @var rex_sql $sql */
            $sql = $ep->getParam('sql');
            $status = (bool) $sql->getValue('status');
        } else {
            $status = (bool) static::getValueOfSlice($ep->getParam('slice_id'), 'status', 1);
        }
        if (false === $status) {
            // inject CSS status class
            $subject = str_replace('class="rex-slice rex-slice-output"', 'class="rex-slice rex-slice-output rex-slice-status-off"', $subject);

            // inject offline marker
            $marker = '<span class="bloecks-offline-marker">' . static::package()->i18n('invisible') . '</span>';
            $subject = preg_replace('/(<div class="panel-title">)(.*)(<\/div>)/imU', '${1}${2}'.$marker.'${3}', $subject);
        }
        return $subject;
    }

    /**
     * Updates an inserted slice status.
     *
     * @param rex_extension_point $ep [description]
     *
     * @return none
     */
    public static function updateSliceStatus(rex_extension_point $ep)
    {
        $slice_id = $ep->getParam('inserted_slice_id');
        $clang = $ep->getParam('clang_id');
        $revision = $ep->getParam('slice_revision');
        $status = $ep->getParam('status');
        $cutncopyAction = $ep->getParam('cutncopy-action');

        if ('copy' === $cutncopyAction || 'cut' === $cutncopyAction) {
            static::setSliceStatus($slice_id, $clang, $revision, $status);
        }
    }
}
