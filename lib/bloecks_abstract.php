<?php
/**
 * Abstrakte Basisklasse für das Addon und seine Plugins.
 */
abstract class bloecks_abstract
{
    /**
     * Enthält den Namen des Addons.
     *
     * @var string
     */
    protected static $addon_name = 'bloecks';

    /**
     * Kann einen Plugin-Namen enthalten, wenn diese Klasse eine Plugin-Klasse erweitert.
     *
     * @var string|null
     */
    protected static $plugin_name;

    /**
     * Gibt das aktuelle Paket zurück (entweder das Addon oder das Plugin, falls diese Klasse ein Plugin erweitert).
     *
     * @return rex_addon|rex_plugin|null
     */
    public static function package()
    {
        return static::plugin() ?: static::addon();
    }

    /**
     * Gibt die Addon-Klasse zurück.
     *
     * @return rex_addon|null
     */
    protected static function addon()
    {
        return !empty(static::$addon_name) && rex_addon::exists(static::$addon_name) ? rex_addon::get(static::$addon_name) : null;
    }

    /**
     * Gibt die Plugin-Klasse zurück, falls diese Klasse ein Plugin erweitert.
     *
     * @return rex_plugin|null
     */
    protected static function plugin()
    {
        $addon = static::addon();
        return $addon && !empty(static::$plugin_name) && $addon->pluginExists(static::$plugin_name) ? $addon->getPlugin(static::$plugin_name) : null;
    }

    /**
     * Gibt Einstellungen des Pakets zurück.
     *
     * @param string|null $key     Schlüssel der Einstellung
     * @param mixed       $default Standardwert, falls der Schlüssel nicht existiert
     *
     * @return mixed
     */
    public static function settings($key = null, $default = null)
    {
        return static::package()->getConfig($key, $default);
    }

    /**
     * Selektiert einen Wert eines Slices aus der Datenbank.
     *
     * @param int    $slice_id ID des Slices
     * @param string $key      Name des Werts
     * @param mixed  $default  Standardwert, falls der Wert nicht vorhanden oder NULL ist
     *
     * @return mixed Der Wert des Slices
     */
    public static function getValueOfSlice($slice_id, $key, $default = null)
    {
        $slice_id = (int) $slice_id;
        if ($slice_id <= 0) {
            return $default;
        }

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTablePrefix().'article_slice');
        $sql->setWhere(['id' => $slice_id]);
        $sql->select();

        return $sql->hasValue($key) ? $sql->getValue($key) : $default;
    }
}
