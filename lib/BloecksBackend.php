<?php

abstract class BloecksBackend
{
    public static function getPlugIn($key)
    {
        if(class_exists('Bloecks' . ucfirst($key)))
        {
            return rex_addon::get('bloecks')->getPlugIn($key);
        }

        return null;
    }

    public static function saveSettings($key)
    {
        // user is admin - allowed to save settings...
        if(rex_post('btn_save', 'string') != '')
        {
            // btn_save is submitted, let's process the request...
            $request = rex_post('bloecks', [
                [$key, 'array']
            ]);

            if(is_array($request[$key]))
            {
                if($plugin = self::getPlugIn($key))
                {
                    // class exists - is user allowed to access this
                    if(is_object($user = rex::getUser()))
                    {
                        if($user->hasPerm('admin'))
                        {
                            $save_settings = [
                            ];

                            // user is admin - allowed to save settings...
                            foreach($request[$key] as $name => $settings)
                            {
                                if(in_array($name, ['modules','templates']))
                                {
                                    // there is a modules array
                                    if(in_array('all', $settings))
                                    {
                                        $settings = ['all'];
                                    }
                                }
                                $save_settings[$name] = $settings;
                            }
                            unset($request, $name, $settings);

                            if(!empty($save_settings))
                            {
                                $plugin->setConfig($save_settings);

                                return true;
                            }

                            unset($save_settings);
                        }
                    }
                }

                return false;
            }
        }
        return null;
    }

    public static function getSliceByRequest()
    {
        if(rex_request('slice_id', 'int') !== false)
        {
            return static::getSlice(rex_request('slice_id', 'int'));
        }

        return null;
    }

    public static function getSlice($slice_id, $plugin_name = '')
    {
        $slice = rex_article_slice::getArticleSliceById($slice_id);
        if(is_object($slice))
        {
            if(static::hasPerm($slice, $plugin_name))
            {
                return $slice;
            }
        }
        return null;
    }

    public static function hasModulePerm($module_id, $plugin_name)
    {
        // user has permission to use the addon
        if($config = BloecksBackend::getPlugin($plugin_name))
        {
            $modules = $config->getConfig('modules');
            if(!is_array($modules) || in_array('all', $modules) || in_array($module_id, $modules))
            {
                return true;
            }
        }

        return false;
    }

    public static function hasTemplatePerm($template_id, $ctype, $plugin_name)
    {
        // user has permission to use the addon
        if($config = BloecksBackend::getPlugin($plugin_name))
        {
            $templates = $config->getConfig('templates');
            if(!is_array($templates))
            {
                // no ctype settings set - available for all ctypes and templates
                return true;
            }
            else
            {
                if(!isset($templates[$template_id]))
                {
                    // no ctype settings set for this template - available for all ctypes
                    return true;
                }
                else
                {
                    if(in_array('all', $templates[$template_id]) || in_array($ctype, $templates[$template_id]))
                    {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public static function hasDefaultPerm(rex_article_slice $slice)
    {
        $article_id = $slice->getArticleId();
        $clang = $slice->getClang();
        $slice_id = $slice->getId();
        $user = rex::getUser();
        $ooArt = rex_article::get($slice->getArticleId(), $slice->getClang());

        if (!$ooArt instanceof rex_article)
        {
            return false;
        }

        $category_id = $ooArt->getCategoryId();
        if (!$user->getComplexPerm('structure')->hasCategoryPerm($category_id))
        {
            return false;
        }

        $CM = rex_sql::factory();
        $CM->setQuery('select * from ' . rex::getTablePrefix() . 'article_slice left join ' . rex::getTablePrefix() . 'module on ' . rex::getTablePrefix() . 'article_slice.module_id=' . rex::getTablePrefix() . 'module.id where ' . rex::getTablePrefix() . "article_slice.id='$slice_id' and clang_id=$clang");
        if ($CM->getRows() != 1)
        {
            return false;
        }
        else
        {
            $module_id = (int) $CM->getValue(rex::getTablePrefix() . 'article_slice.module_id');

            if ($user->getComplexPerm('modules')->hasPerm($module_id))
            {
                return true;
            }
        }

        return false;
    }

    public static function hasPerm(rex_article_slice $slice, $plugin_name = '')
    {
        if(static::hasDefaultPerm($slice))
        {
            if(!empty($plugin_name))
            {
                // check for permissions set by plugin
                if(is_object($user = rex::getUser()))
                {
                    if($user->hasPerm('bloecks[' . $plugin_name . ']'))
                    {
                        if(static::hasModulePerm($slice->getModuleId(), $plugin_name))
                        {
                            return static::hasTemplatePerm($slice->getArticle()->getTemplateId(), $slice->getCtype(), $plugin_name);
                        }
                    }
                }
                return false;
            }

            // no plugin set return true
            return true;
        }

        return false;
    }

    protected static function getPluginName()
    {
        return preg_replace('/^bloecks/', '', strtolower(get_called_class()));
    }

    public static function regenerateArticleOfSlice(rex_article_slice $slice) {
        $slice_id = $slice->getId();
        $article_id =  $slice->getArticleId();
        $clang = $slice->getClang();
        $module_id = $slice->getModuleId();
        $ctype = $slice->getCtype();

        // ----- artikel neu generieren
        $EA = rex_sql::factory();
        $EA->setTable(rex::getTablePrefix() . 'article');
        $EA->setWhere(['id' => $article_id, 'clang_id' => $clang]);
        $EA->addGlobalUpdateFields();
        $EA->update();
        rex_article_cache::delete($article_id, $clang);
    }
}

?>
