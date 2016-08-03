<?php
    if (rex::isBackend() && rex::getUser() && rex::getUser()->hasPerm('bloecks[status]'))
    {
        // register button to slice menu
        rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', array('BloecksStatus', 'addButtons'));

        if(strpos(rex_request('page'),'content/edit') !== false)
        {
            rex_view::addCssFile($this->getAssetsUrl('css/be.css'));
            rex_view::addJsFile($this->getAssetsUrl('js/be.js'));
        }
    }
    else if(!rex::isBackend())
    {
        rex_extension::register('SLICE_SHOW', array('BloecksStatus', 'show'));
    }
?>
