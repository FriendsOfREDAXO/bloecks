<?php
    if (rex::isBackend() && rex::getUser() && rex::getUser()->hasPerm('bloecks[dragndrop]') && rex::getUser()->hasPerm('moveslice'))
    {
        if(strpos(rex_request('page'),'content/edit') !== false)
        {
            $include = true;
            if($art = rex_article::get(rex_request('article_id'), rex_request('clang')))
            {
                $include = BloecksBackend::hasTemplatePerm($art->getTemplateId(), rex_request('ctype', 'int', 1), 'dragndrop');
            }

            if($include)
            {
                rex_view::addCssFile($this->getAssetsUrl('css/be.css'));
                rex_view::addJsFile($this->getAssetsUrl('js/be.js'));
            }
        }
    }
?>
