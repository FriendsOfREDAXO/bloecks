<?php
/**
 * bloecks abstract class - basic functions for the addon and its plugins.
 */
abstract class bloecks_abstract
{
    /**
     * contains the name of the plugin.
     *
     * @var string
     */
    protected static $addon_name = 'bloecks';

    /**
     * may contain a plugin name when this class is extending any plugin class.
     *
     * @var [type]
     */
    protected static $plugin_name;

    /**
     * Returns the current package (either the addon or the plugin if this is extending a plugin class).
     *
     * @return rex_addon / rex_plugin
     */
    public static function package()
    {
        if ($plugin = static::plugin()) {
            return $plugin;
        }
        if ($addon = static::addon()) {
            return $addon;
        }

        return null;
    }

    /**
     * Returns the addon class.
     *
     * @return rex_addon
     */
    protected static function addon()
    {
        if (!empty(static::$addon_name) && rex_addon::exists(static::$addon_name)) {
            $addon = rex_addon::get(static::$addon_name);
            if ($addon->isAvailable()) {
                return $addon;
            }
        }
        return null;
    }

    /**
     * Returns the plugin class if this is extending a plugin.
     *
     * @return [type] [description]
     */
    protected static function plugin()
    {
        $addon = static::addon();

        if ($addon && !empty(static::$plugin_name) && $addon->pluginExists(static::$plugin_name)) {
            $plugin = $addon->getPlugin(static::$plugin_name);
            if ($plugin->isAvailable()) {
                return $plugin;
            }
        }

        return null;
    }

    public static function settings($key = null, $default = null)
    {
        return static::package()->getConfig($key, $default);
    }

    /**
     * Selects a value of a slice from the database.
     *
     * @param (int)    $slice_id ID of the slice
     * @param (string) $key      name of the value
     * @param (mixed)  $default  if the value is not contained in the database or set to NULL return this value (default is NULL)
     *
     * @return (mixed) The slice's value
     */
    public static function getValueOfSlice($slice_id, $key, $default = null)
    {
        $slice_id = (int) $slice_id;
        $value = $default;

        if (!is_nan($slice_id) && $slice_id > 0) {
            $sql = rex_sql::factory();
            $sql->setTable(rex::getTablePrefix().'article_slice');
            $sql->setWhere(['id' => $slice_id]);
            $sql->select();

            if ($sql->hasValue($key)) {
                $value = $sql->getValue($key);
            }

            unset($sql);
        }

        return $value;
    }
}
