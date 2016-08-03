<?php

abstract class Bloecks
{
    protected static function getSlice($slice_id)
    {
        if (rex::isBackend())
        {
            $slice = BloecksBackend::getSlice($slice_id, static::getPluginName());
        }
        else
        {
            $slice = rex_article_slice::getArticleSliceById($slice_id);
        }

        return $slice;
    }

    protected static function getPluginName()
    {
        return preg_replace('/^bloecks/', '', strtolower(get_called_class()));
    }

    protected static function getConfig($what = null)
    {
        if(class_exists('BloecksBackend'))
        {
            $plugin = BloecksBackend::getPlugin(static::getPluginName());
            return $plugin->getConfig($what);
        }
        return null;
    }

    protected static function getProperty($what = null)
    {
        if(class_exists('BloecksBackend'))
        {
            $plugin = BloecksBackend::getPlugin(static::getPluginName());
            return $plugin->getProperty($what);
        }
        return null;
    }

    public static function processRequests()
    {
        $page = rex_request('page');
        preg_match('/^bloecks\/([a-z]+)\/([a-z]+)$/i', $page, $match);
        if(!empty($match[1]))
        {
            if(class_exists($class = 'Bloecks' . ucfirst($match[1])))
            {
                if(is_callable(array($class, $method = $match[2] . 'Action')))
                {
                    $class::$method();
                }
            }

            $url = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php?page=content/edit&article_id=' . rex_request('article_id') . '&mode=edit&module_id=' . rex_request('module_id') . '&slice_id=' . rex_request('slice_id') . '&clang=' . rex_request('clang') . '&ctype=' . rex_request('ctype') . '&mode=edit';

            // Alle OBs schließen
            while (@ob_end_clean());
            header('Location: ' . $url);
            exit();
        }
    }
}

?>
