<?php

/**
 * @deprecated Use FriendsOfRedaxo\Bloecks\Backend instead
 */
abstract class bloecks_abstract
{
    /**
     * @deprecated This functionality is no longer needed
     */
    protected static $addon_name = 'bloecks';

    /**
     * @deprecated This functionality is no longer needed
     */
    protected static $plugin_name;

    /**
     * @deprecated Use rex_addon::get('bloecks') instead
     */
    public static function package()
    {
        return rex_addon::get('bloecks');
    }

    /**
     * @deprecated Use rex_addon::get('bloecks') instead
     */
    protected static function addon()
    {
        return rex_addon::get('bloecks');
    }

    /**
     * @deprecated Plugins are no longer used in bloecks 5.x
     */
    protected static function plugin()
    {
        return null;
    }

    /**
     * @deprecated Use rex_addon::get('bloecks')->getConfig($key, $default) instead
     */
    public static function settings($key = null, $default = null)
    {
        return rex_addon::get('bloecks')->getConfig($key, $default);
    }

    /**
     * @deprecated Use rex_article_slice::getArticleSliceById($slice_id) instead
     */
    public static function getValueOfSlice($slice_id, $key, $default = null)
    {
        $slice_id = (int) $slice_id;
        $value = $default;

        if (!is_nan($slice_id) && $slice_id > 0) {
            $sql = rex_sql::factory();
            $sql->setTable(rex::getTablePrefix() . 'article_slice');
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
