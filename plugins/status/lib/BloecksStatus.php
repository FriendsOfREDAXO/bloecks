<?php

abstract class BloecksStatus extends Bloecks
{
    protected static $column_name = 'bloecks_status';

    protected static function getSliceStatus($slice, $clang = null)
    {
        if($slice instanceof rex_article_slice)
        {
            $slice = $slice->getId();
        }
        else
        {
            $slice = (int) $slice;
        }

        if($clang === null || !in_array($clang, rex_clang::getAllIds()))
        {
            $clang = rex_clang::getCurrentId();
        }

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTablePrefix().'article_slice');
        $sql->setWhere(array('id' => $slice, 'clang_id' => $clang));
        $sql->select();

        $status = (int) $sql->getValue(self::$column_name) !== 0;

        unset($sql, $slice, $clang);

        return $status;
    }

    public static function addButtons($ep)
    {
        $items = [];

        $slice = BloecksBackend::getSlice($ep->getParam('slice_id'));
        if(is_object($slice))
        {
            $sql = rex_sql::factory();
            $sql->setTable(rex::getTablePrefix().'article_slice');
            $sql->setWhere(array('id' => $slice->getId()));
            $sql->select();

            $mode = static::getSliceStatus($slice) ? 'visible' : 'invisible';

            $btn = [
                'hidden_label' => rex_i18n::msg('bloeck_toggle_status_'.$mode),
                'url' => 'index.php?page=bloecks/status/status&article_id=' . $ep->getParam('article_id') . '&mode=edit&module_id=' . $ep->getParam('module_id') . '&slice_id=' . $ep->getParam('slice_id') . '&clang=' . $ep->getParam('clang') . '&ctype=' . $ep->getParam('ctype') . '&status=' . ((int) $sql->getValue(self::$column_name) === 0 ? '1' : '0'),
                'attributes' => [
                    'class' => array('btn-'.$mode),
                    'title' => rex_i18n::msg('bloeck_toggle_status_'.$mode),
                    'data-state' => $mode,
                ],
                'icon' => $mode,
            ];

            if(!BloecksBackend::hasPerm($slice, static::getPluginName()))
            {
                $btn['attributes']['disabled'] = true;
                $btn['attributes']['class'][] = 'disabled';
            }

            $items[] = $btn;
        }

        return $items;
    }

    public static function statusAction()
    {
        $slice = static::getSlice(rex_request('slice_id', 'int'));
        if($slice instanceof rex_article_slice)
        {
            $status = null;

            if(rex_request('toggleStatus', 'bool', null))
            {
                $sql = rex_sql::factory();
                $sql->setTable(rex::getTablePrefix().'article_slice');
                $sql->setWhere(array('id' => $slice->getId()));
                $sql->select();

                $status = (int) $sql->getValue(self::$column_name) !== 0;
                unset($sql);
            }
            elseif(rex_request('status', 'int', null) !== null)
            {
                $status = rex_request('status', 'int') !== 0;
            }

            if($status !== null)
            {
                $sql = rex_sql::factory();
                $sql->setDebug();
                if($sql->setQuery("UPDATE `" . rex::getTablePrefix() . "article_slice` SET `" . self::$column_name . "` = ? WHERE id = ?", array($status, $slice->getId())))
                {
                    BloecksBackend::regenerateArticleOfSlice($slice);

                    rex_extension::registerPoint(new rex_extension_point('BLOECKS_SLICE_STATUS_UPDATED', '', [
                      'slice' => $slice,
                      'status' => $status
                    ]));

                    return true;
                }
            }
        }

        return false;
    }

    public static function show($ep)
    {
        if(!static::getSliceStatus($ep->getParam('slice_id')))
        {
            // slice is not active - don't show anything!
            return '';
        }
    }
}

?>
