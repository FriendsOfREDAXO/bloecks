<?php
/**
 * bloecks_cutncopy_backend class - basic backend functions for the plugin.
 */
class bloecks_cutncopy_backend extends bloecks_backend
{
    /**
     * The name of the plugin.
     *
     * @var string
     */
    protected static $plugin_name = 'cutncopy';

    /**
     * Will store the cookie once it is read.
     *
     * @var string
     */
    protected static $cookie;

    /**
     * Will store the copied/cut slice once it is inserted somewhere.
     *
     * @var string
     */
    protected static $clipboard_slice;

    /**
     * Initialize the plugin in the backend.
     */
    public static function init(rex_extension_point $ep)
    {
        if (rex::getUser()) {
            static::prepareClipboardSliceForAdding();

            // add buttons to slice menu
            rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', ['bloecks_cutncopy_backend', 'addButtons']);

            // process any cut or copy call
            rex_extension::register('STRUCTURE_CONTENT_BEFORE_SLICES', ['bloecks_cutncopy_backend', 'process']);

            // insert the "INSERT BLOCK" link into module dropdown
            rex_extension::register('STRUCTURE_CONTENT_MODULE_SELECT', ['bloecks_cutncopy_backend', 'addBlockToDropdown']);

            // post process the clipboard after a slice is inserted into another article
            rex_extension::register('SLICE_ADDED', ['bloecks_cutncopy_backend', 'postProcessClipboard']);
        } else {
            // remove cookie whenever the backend is accessed without a login
            // (so we make sure no slice is in clipboard when a user logs in)
            static::deleteCookie();
        }

        // call the addon init function - see blocks_backend:init() class
        parent::init($ep);
    }

    /**
     * Returns the name of the cookie for this plugin.
     *
     * @return [string] Name of the cookie variable
     */
    protected static function getCookieName()
    {
        return 'rex_' . static::$addon_name . '_' . static::$plugin_name;
    }

    /**
     * Removes a cookie.
     *
     * @return none
     */
    public static function deleteCookie()
    {
        setcookie(static::getCookieName(), '', time() - 3600);
    }

    /**
     * Sets a cookie variable.
     *
     * @param [mixed] $value Value (should be an array)
     */
    protected static function setCookie($value)
    {
        if (!is_array($value)) {
            $value = [
                'value' => $value,
            ];
        }

        setcookie(static::getCookieName(), json_encode($value), time() + 60 * 60 * 24 * 365);
        static::$cookie = $value;
    }

    /**
     * REtrieves a cookie variable.
     *
     * @param [string] $key     The name of the value in the cookie array
     * @param [string] $vartype The type the value should be casted against
     * @param [mixed]  $default The default value if $key does not exist
     *
     * @return [mixed] If $key is empty it returns the whole cookie array, otherwise the $key value in the array
     */
    protected static function getCookie($key = null, $vartype = null, $default = null)
    {
        if (!isset(static::$cookie)) {
            static::$cookie = @json_decode(rex_request::cookie(static::getCookieName(), 'string', ''), true);
            if (empty(static::$cookie)) {
                static::$cookie = [];
            }
        }

        if (!empty($key) && is_string($key)) {
            if (isset(static::$cookie[$key])) {
                if (!empty($vartype)) {
                    return rex_type::cast(static::$cookie[$key], $vartype);
                }

                return static::$cookie[$key];
            }
            return $default;
        }

        return static::$cookie;
    }

    /**
     * Adds cut and copy buttons to the slice actions panel.
     *
     * @param rex_extension_point $ep The extension point
     */
    public static function addButtons(rex_extension_point $ep)
    {
        if (rex::getUser()->hasPerm(static::getPermName()) && true === $ep->getParam('perm')) {
            $is_copied = (int) $ep->getParam('slice_id') === static::getCookie('slice_id', 'int', null);
            $action = static::getCookie('action', 'string', null);
            $revision = static::getValueOfSlice($ep->getParam('slice_id'), 'revision', 0);

            foreach (['copy', 'cut'] as $type) {
                static::addButton($ep, [
                    'hidden_label' => static::package()->i18n($type . '_slice'),
                    'url' => rex_url::backendController([
                        'page' => 'content/edit',
                        'article_id' => $ep->getParam('article_id'),
                        'bloecks' => 'cutncopy',
                        'module_id' => $ep->getParam('module_id'),
                        'slice_id' => $ep->getParam('slice_id'),
                        'clang' => $ep->getParam('clang'),
                        'ctype' => $ep->getParam('ctype'),
                        'revision' => $revision,
                        'cuc_action' => $type,
                    ]),
                    'attributes' => [
                        'class' => ['btn-' . $type],
                        'title' => static::package()->i18n($type . '_slice'),
                        'data-bloecks-cutncopy-iscopied' => $is_copied && ($action === $type) ? 'true' : 'false',
                        'data-pjax-no-history' => 'true',
                    ],
                    'icon' => $type,
                ]);
            }
        }
    }

    /**
     * Processes a URL and tries to call the concurrent action.
     *
     * @param rex_extension_point $ep EP
     *
     * @return none
     */
    public static function process(rex_extension_point $ep)
    {
        $function = rex_request('bloecks', 'string', null);
        $slice_id = rex_request('slice_id', 'int', 0);
        $revision = rex_request('revision', 'int', 0);
        $action = rex_request('cuc_action', 'string', null);

        if ($function === static::plugin()->getName()) {
            if (!empty($action)) {
                $action .= 'Slice';
            }

            if (method_exists('bloecks_cutncopy_backend', $action)) {
                $slice = rex_article_slice::getArticleSlicebyId($slice_id, false, $revision);
                if ($slice) {
                    $subject = $ep->getSubject();

                    $subject .= self::$action($slice);

                    $ep->setSubject($subject);
                }
            }
        }
    }

    /**
     * Copies a slice into the clipboard - if it is already copied the clipboard will be emptied.
     *
     * @param rex_article_slice $slice The slice to copy
     *
     * @return [string] A success / Warning message
     */
    public static function copySlice(rex_article_slice $slice)
    {
        if (rex::getUser()->hasPerm(static::getPermName()) && true === rex::getUser()->getComplexPerm('modules')->hasPerm($slice->getModuleId())) {
            if ((int) $slice->getId() === static::getCookie('slice_id', 'int', null) && 'copy' === static::getCookie('action', 'string', null)) {
                static::setCookie('');
                return '';
            }

            $status = static::getValueOfSlice($slice->getId(), 'status', 1);
            static::setCookie(['slice_id' => $slice->getId(), 'clang' => $slice->getClang(), 'revision' => $slice->getRevision(), 'status' => $status, 'action' => 'copy']);

            rex_extension::registerPoint(new rex_extension_point('SLICE_COPIED', '', [
                'slice_id' => $slice->getId(),
                'article_id' => $slice->getArticleId(),
                'clang_id' => $slice->getClang(),
                'slice_revision' => $slice->getRevision(),
                'status' => $status,
                'cutncopy-action' => 'copy',
            ]));

            return rex_view::success(static::package()->i18n('slice_copied', $slice->getId(), $slice->getArticle()->getName()));
        }

        return rex_view::warning(static::package()->i18n('slice_not_copied', $slice->getId(), $slice->getArticle()->getName()));
    }

    /**
     * Copies a slice into the clipboard - if it is already copied the clipboard will be emptied -
     * and marks the slice to be deleted after inserting womewhere else.
     *
     * @param rex_article_slice $slice The slice to copy
     *
     * @return [string] A success / Warning message
     */
    public static function cutSlice(rex_article_slice $slice)
    {
        if (rex::getUser()->hasPerm(static::getPermName()) && true === rex::getUser()->getComplexPerm('modules')->hasPerm($slice->getModuleId())) {
            if ((int) $slice->getId() === static::getCookie('slice_id', 'int', null) && 'cut' === static::getCookie('action', 'string', null)) {
                static::setCookie('');
                return null;
            }

            $status = static::getValueOfSlice($slice->getId(), 'status', 1);
            static::setCookie(['slice_id' => $slice->getId(), 'clang' => $slice->getClang(), 'revision' => $slice->getRevision(), 'status' => $status, 'action' => 'cut']);

            rex_extension::registerPoint(new rex_extension_point('SLICE_CUT', '', [
                'slice_id' => $slice->getId(),
                'article_id' => $slice->getArticleId(),
                'clang_id' => $slice->getClang(),
                'slice_revision' => $slice->getRevision(),
                'status' => $status,
                'cutncopy-action' => 'cut',
            ]));

            return rex_view::success(static::package()->i18n('slice_copied', $slice->getId(), $slice->getArticle()->getName()));
        }

        return rex_view::warning(static::package()->i18n('slice_not_copied', $slice->getId(), $slice->getArticle()->getName()));
    }

    /**
     * Pepares the $_REQUEST and the $_POST Arrays and fills it with the data
     * of the source / clipboard slice so it can be processed via
     * /redaxo/src/addons/structure/plugins/content/pages/content.php.
     *
     * @return none
     */
    public static function prepareClipboardSliceForAdding()
    {
        if (rex::getUser()->hasPerm(static::getPermName())) {
            $source_slice_id = rex_request('source_slice_id', 'int', null);
            if ($source_slice_id) {
                if ((int) $source_slice_id === static::getCookie('slice_id', 'int', null)) {
                    $action = static::getCookie('action', 'string', '');
                    $clang = static::getCookie('clang', 'int', null);
                    $revision = static::getCookie('revision', 'int', 0);

                    if ($action) {
                        $slice = rex_article_slice::getArticleSlicebyId((int) $source_slice_id, $clang, $revision);
                        if ($slice && true === rex::getUser()->getComplexPerm('modules')->hasPerm($slice->getModuleId())) {
                            static::$clipboard_slice = $slice;

                            // prepeare REQUEST Array
                            $_NEW_REQUEST = [
                                'save' => '1',
                            ];

                            $sql = rex_sql::factory();
                            $query = 'SELECT * FROM `' . rex::getTable('article_slice') . '` WHERE `id` = ' . $slice->getId();
                            $sql->setQuery($query);
                            if (count($article = $sql->getArray())) {
                                $article = $article[0];

                                $request = ['value' => 20, 'media' => 10, 'medialist' => 10, 'link' => 10, 'linklist' => 10];
                                foreach ($request as $key => $max) {
                                    $_NEW_REQUEST['REX_INPUT_' . strtoupper($key)] = [];

                                    for ($i = 1; $i <= $max; ++$i) {
                                        $_NEW_REQUEST['REX_INPUT_' . strtoupper($key)][$i] = $article[$key . $i];
                                    }
                                    unset($i);
                                }
                                unset($max, $key, $request);

                                // Prepare POST Array
                                $_POST = array_replace($_POST, [
                                    'module_id' => $article['module_id'],
                                    'bloecks_cutncopy_action' => $action,
                                ]);
                            }
                            unset($article, $query, $sql);

                            $_REQUEST = array_replace($_REQUEST, $_NEW_REQUEST);
                        }
                    }
                }
            }
        }
    }

    /**
     * When a clipboard slice has been inserted into another place it
     * has to be removed if it was cut instead of copied.
     *
     * @param rex_extension_point $ep EP
     *
     * @return [string] Return message
     */
    public static function postProcessClipboard(rex_extension_point $ep)
    {
        $info = '';

        if (static::$clipboard_slice instanceof rex_article_slice) {
            if ((int) static::$clipboard_slice->getId() === static::getCookie('slice_id', 'int', null)) {
                $action = static::getCookie('action', 'string', '');
                if ($action === rex_post('bloecks_cutncopy_action', 'string', null)) {
                    rex_extension::registerPoint(new rex_extension_point('SLICE_INSERTED', '', [
                        'source_slice_id' => static::$clipboard_slice,
                        'before_slice_id' => rex_request('slice_id', 'int', null),
                        'inserted_slice_id' => (int) $ep->getParam('slice_id'),
                        'clang_id' => static::$clipboard_slice->getClang(),
                        'slice_revision' => static::$clipboard_slice->getRevision(),
                        'status' => static::getCookie('status', 'int', 1),
                        'cutncopy-action' => $action,
                    ]));

                    if ('cut' === $action && static::hasModulePerm(rex::getUser(), static::$clipboard_slice->getModuleId())) {
                        // remove slice!
                        if (rex_content_service::deleteSlice(static::$clipboard_slice->getId())) {
                            $epParams = [
                                'article_id' => static::$clipboard_slice->getArticleId(),
                                'clang' => static::$clipboard_slice->getClang(),
                                'function' => 'delete',
                                'slice_id' => static::$clipboard_slice->getId(),
                                'page' => rex_be_controller::getCurrentPage(),
                                'ctype' => static::$clipboard_slice->getCtype(),
                                'category_id' => static::$clipboard_slice->getArticle()->getCategoryId(),
                                'module_id' => static::$clipboard_slice->getModuleId(),
                            ];

                            $info = static::package()->i18n('slice_removed_after_insert', static::getModuleName(static::$clipboard_slice->getModuleId()), static::$clipboard_slice->getId(), static::$clipboard_slice->getArticle()->getName());

                            rex_extension::registerPoint(new rex_extension_point('SLICE_DELETED', $info, $epParams));
                            /* deprecated */ rex_extension::registerPoint(new rex_extension_point('STRUCTURE_CONTENT_SLICE_DELETED', $info, $epParams));
                        } else {
                            $info = static::package()->i18n('slice_not_removed_after_insert', static::getModuleName(static::$clipboard_slice->getModuleId()), static::$clipboard_slice->getId(), static::$clipboard_slice->getArticle()->getName());
                        }

                        static::deleteCookie();
                    }

                    return $info;
                }
            }
        }
    }

    /**
     * Adds a new option to the module selector that will add a new slice with the content of
     * the clipboard slice.
     *
     * @param rex_extension_point $ep EP
     */
    public static function addBlockToDropdown(rex_extension_point $ep)
    {
        $slice_id = static::getCookie('slice_id', 'int', null);
        $clang = static::getCookie('clang', 'int', null);
        $revision = static::getCookie('revision', 'int', 0);
        $action = static::getCookie('action', 'string', null);

        if ($slice_id && $action) {
            $slice = rex_article_slice::getArticleSlicebyId($slice_id, $clang, $revision);

            if ($slice) {
                $subject = $ep->getSubject();

                if (preg_match('/href="([^"]+module_id=' . $slice->getModuleId() . '[^"]+)"/ismu', $subject, $matches)) {
                    $url = $matches[1];
                    $url = str_replace('slice_id=', 'source_slice_id=' . $slice->getId() . '&amp;slice_id=', $url);

                    $prefix = substr($subject, 0, strpos($subject, '<li>'));
                    $suffix = substr($subject, strpos($subject, '<li>'));

                    $subject = $prefix . '<li class="bloecks-cutncopy-clipboard-slice is--' . $action . '"><a href="' . $url . '" data-pjax="true" data-pjax-no-history="true">' . static::package()->i18n('insert_slice', static::getModuleName($slice->getModuleId()), $slice->getId(), $slice->getArticle()->getName()) . '</a></li>' . $suffix;

                    $ep->setSubject($subject);
                }
            }
        }
    }

    /**
     * Retrieves the module's name by its ID.
     *
     * @param [int] $module_id A module ID
     *
     * @return [string] The module's name (or the ID if no module was found for the given module ID)
     */
    protected static function getModuleName($module_id)
    {
        $module_id = (int) $module_id;
        if (!empty($module_id)) {
            $sql = rex_sql::factory();
            $qry = 'SELECT `name` FROM `' . rex::getTable('module') . '` WHERE `id` = ' . $module_id;
            $sql->setQuery($qry);
            if (count($row = $sql->getArray())) {
                return rex_i18n::translate($row[0]['name']);
            }
        }

        return $module_id;
    }
}
