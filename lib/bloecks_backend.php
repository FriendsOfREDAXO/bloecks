<?php
/**
 * bloecks_backend class - basic backend functions for the addon and its plugins.
 */
class bloecks_backend extends bloecks_abstract
{
    /**
     * Initializes the addon in the backend.
     */
    public static function init(rex_extension_point $ep)
    {
        // only aexecute this function within the backend and when a user is logged in
        if (rex::isBackend() && rex::getUser()) {
            // let's register the permission for this addon / plugin
            static::addPerm();

            if (false !== strpos(rex_request('page'), 'content/edit')) {
                if (!static::plugin()) {
                    // hook into SLICE_SHOW extension point so we can change the display of the slice a bit
                    rex_extension::register('SLICE_SHOW', ['bloecks_backend', 'showSlice'], rex_extension::EARLY);
                }

                // and only on content/edit pages we load the css and js files
                $package = static::package();

                // and load assets
                rex_view::addCssFile($package->getAssetsUrl('css/be.css'));
                rex_view::addJsFile($package->getAssetsUrl('js/be.js'));
            }
        }
    }

    /**
     * Retrieves the permission name by getting (a) the addon name and (b) the plugin name (if
     * this class is extending a plugin_backend class).
     *
     * @return (string) e.g. "bloecks[status]" or "bloecks[]"
     */
    public static function getPermName()
    {
        $perm = '';
        if ($addon = static::addon()) {
            $perm = $addon->getName();

            $suffix = '';

            if ($plugin = static::plugin()) {
                $suffix = $plugin->getName() . (!empty($suffix) ? '_' : '') . $suffix;
            }

            $perm .= '[' . $suffix . ']';
        }

        unset($addon, $plugin, $suffix);
        return $perm;
    }

    /**
     * Registers a permisson in Redaxo.
     */
    public static function addPerm()
    {
        if ($perm = static::getPermName()) {
            if (!rex_perm::has($perm)) {
                $group = preg_match('/\[\]$/', $perm) ? rex_perm::GENERAL : rex_perm::OPTIONS;

                $name = 'perm_description';
                if ($plugin = static::plugin()) {
                    $name = $plugin->getName() . '_' . $name;
                }

                rex_perm::register($perm, static::package()->i18n($name), $group);
            }
            return $perm;
        }

        return false;
    }

    /**
     * Checks if a user has the permission to edit a module AND if the user has the
     * permission to use this addon / plugin.
     *
     * @param rex_user $user The user to check
     * @param  (number)            the id of the module
     *
     * @return bool TRUE if the user has all neccessary rights
     */
    public static function hasModulePerm(rex_user $user, $module_id)
    {
        if (!$user->hasPerm('admin[]')) {
            if (static::getPermName()) {
                if (!$user->hasPerm(static::getPermName())) {
                    return false;
                }
            }
        }

        return $user->getComplexPerm('modules')->hasPerm($module_id);
    }

    /**
     * Wraps a LI around the slice within the backend and call
     * a custom extension point SLICE_SHOW_BLOECKS_BE we can use to hook
     * in with our plugins.
     *
     * @return string the slice content
     */
    public static function showSlice(rex_extension_point $ep)
    {
        $slice_content = $ep->getSubject();

        $slice_content = rex_extension::registerPoint(new rex_extension_point(
            'SLICE_SHOW_BLOECKS_BE',
            $slice_content,
            $ep->getParams()
        ));

        return $slice_content;
    }

    public static function addButton(rex_extension_point $ep, array $btn)
    {
        $items = (array) $ep->getSubject();
        $items[] = $btn;
        $ep->setSubject($items);
    }
}
