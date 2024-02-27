<?php
/**
 * Backend-Klasse für Grundfunktionen des AddOns und seiner Plugins.
 */
class bloecks_backend extends bloecks_abstract
{
    /**
     * Initialisiert das AddOn im Backend.
     */
    public static function init(rex_extension_point $ep)
    {
        if (!rex::isBackend() || !rex::getUser()) {
            return;
        }

        // Berechtigung für dieses AddOn / Plugin registrieren
        static::addPerm();

        // Überprüfung, ob die aktuelle Seite content/edit ist, mit rex_context::fromGet()
        $context = rex_context::fromGet();
        $page  = $context->getParam('page', 'content/edit');

        if ($page === 'content/edit') {
            if (!static::plugin()) {
                rex_extension::register('SLICE_SHOW', ['bloecks_backend', 'showSlice'], rex_extension::EARLY);
            }

            $package = static::package();
            rex_view::addCssFile($package->getAssetsUrl('css/be.css'));
            rex_view::addJsFile($package->getAssetsUrl('js/be.js'));
        }
    }

    /**
     * Ruft den Namen der Berechtigung ab.
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

        return $perm;
    }

    /**
     * Registriert eine Berechtigung in Redaxo.
     */
    public static function addPerm()
    {
        $perm = static::getPermName();
        if ($perm && !rex_perm::has($perm)) {
            $group = preg_match('/\[\]$/', $perm) ? rex_perm::GENERAL : rex_perm::OPTIONS;
            $name = static::plugin() ? static::plugin()->getName() . '_perm_description' : 'perm_description';
            rex_perm::register($perm, static::package()->i18n($name), $group);
        }

        return $perm ?: false;
    }

    /**
     * Überprüft die Berechtigung eines Benutzers.
     */
    public static function hasModulePerm(rex_user $user, $module_id)
    {
        if ($user->hasPerm('admin[]') || $user->hasPerm(static::getPermName())) {
            return $user->getComplexPerm('modules')->hasPerm($module_id);
        }

        return false;
    }

    /**
     * Verpackt einen Slice im Backend.
     */
    public static function showSlice(rex_extension_point $ep)
    {
        return rex_extension::registerPoint(new rex_extension_point(
            'SLICE_SHOW_BLOECKS_BE',
            $ep->getSubject(),
            $ep->getParams()
        ));
    }

    /**
     * Fügt einen Button hinzu.
     */
    public static function addButton(rex_extension_point $ep, array $btn)
    {
        $items = (array) $ep->getSubject();
        $items[] = $btn;
        $ep->setSubject($items);
    }
}
