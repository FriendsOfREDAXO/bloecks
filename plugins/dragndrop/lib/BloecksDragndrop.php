<?php

abstract class BloecksDragndrop extends Bloecks
{
    protected static $column_name = null;

    public static function moveAction()
    {
        $success = false;

        $items = rex_request('move', 'array', []);

        foreach($items as $slice_id => $item)
        {
            $slice = static::getSlice($slice_id);
            if($slice instanceof rex_article_slice)
            {
                $new_priority = null;

                $prev_slice_id = Max((int) $item['prev'], 0);
                if($prev_slice_id)
                {
                    $prev_slice = static::getSlice(Max((int) $item['prev'], 0));
                    if($prev_slice instanceof rex_article_slice)
                    {
                        $new_priority = $prev_slice->getValue('priority');
                    }
                    unset($prev_slice);
                }
                else
                {
                    $new_priority = 0;
                }

                if($new_priority !== null)
                {
                    $direction = $slice->getValue('priority') > $new_priority ? 'moveup' : 'movedown';
                    if($direction == 'moveup')
                    {
                        $new_priority+=1;
                    }

                    if($slice->getValue('priority') != $new_priority)
                    {
                        // store origin value for later success-check
                        $old_priority = $slice->getValue('priority');

                        // prepare sql for later saving
                        $upd = rex_sql::factory();
                        $upd->setTable(rex::getTablePrefix() . 'article_slice');
                        $upd->setWhere([
                            'id' => $slice_id,
                        ]);
                        $upd->setValue('priority', $new_priority);

                        // some vars for later use
                        $article_id = $slice->getArticleId();
                        $ctype = $slice->getCType();
                        $clang = $slice->getClang();
                        $slice_revision = $slice->getRevision();

                        rex_extension::registerPoint(new rex_extension_point('SLICE_MOVE', '', [
                            'direction' => $direction,
                            'slice_id' => $slice_id,
                            'article_id' => $article_id,
                            'clang_id' => $clang,
                            'slice_revision' => $slice_revision,
                        ]));

                        $upd->addGlobalUpdateFields(rex::isBackend() ? null : 'frontend');

                        $sql = rex_sql::factory();
                        $qry = "SELECT `id` FROM `" . rex::getTablePrefix() . "article_slice` WHERE `article_id` = $article_id AND `ctype_id` = $ctype AND `clang_id` = $clang AND ";
                        if($direction == 'movedown')
                        {
                            $qry.= "`priority` > $old_priority AND `priority` <= $new_priority ORDER BY `priority`";
                        }
                        else
                        {
                            $qry.= "`priority` < $old_priority AND `priority` >= $new_priority ORDER BY `priority` DESC";
                        }
                        $sql->setQuery($qry);

                        if(count($slices = $sql->getArray()))
                        {
                            $p = $old_priority;
                            foreach($slices as $sid)
                            {
                                $sid = $sid['id'];

                                $s = rex_sql::factory();
                                $s->setTable(rex::getTablePrefix() . 'article_slice');
                                $s->setWhere([
                                    'id' => $sid,
                                ]);
                                $s->setValue('priority', $p);
                                $s->addGlobalUpdateFields(rex::isBackend() ? null : 'frontend');
                                $s->update();

                                $p+= $direction == 'movedown' ? 1 : -1;

                                unset($s);
                            }
                            unset($p, $sid);
                        }
                        unset($slices, $qry, $sql);

                        $upd->update();
                        unset($upd);

                        // check if the slice moved at all (first cannot be moved up, last not down)
                        $sql = rex_sql::factory();
                        $sql->setQuery("SELECT `priority` FROM `" . rex::getTablePrefix() . "article_slice` WHERE `id` = $slice_id AND `clang_id` = $clang");
                        if ($old_priority == $sql->getValue('priority')) {
                            throw new rex_api_exception(rex_i18n::msg('slice_moved_error'));
                        }
                        unset($sql);

                        $success = true;

                        rex_article_cache::deleteContent($article_id, $clang);

                        rex_extension::registerPoint(new rex_extension_point('BLOECKS_SLICE_PRIORITY_UPDATED', '', [
                          'slice' => $slice,
                          'old_priority' => $old_priority,
                          'new_priority' => $new_priority
                        ]));

                        unset($old_priority);
                    }
                    unset($direction);
                }
                unset($new_priority);
            }
            unset($slice);
        }
        unset($items, $slice_id, $item);
        
        return $success;
    }
}

?>
