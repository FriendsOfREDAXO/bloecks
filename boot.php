<?php
    if (rex::isBackend() && rex::getUser())
    {
        rex_extension::register('PACKAGES_INCLUDED', array('Bloecks', 'processRequests'));

        rex_view::addCssFile($this->getAssetsUrl('css/be.css'));
        rex_view::addJsFile($this->getAssetsUrl('js/be.js'));
/*

        rex_extension::register('PACKAGES_INCLUDED', array('SliceGrid', 'processRequests'));
        rex_extension::register('STRUCTURE_CONTENT_SLICE_MENU', array('SliceGrid', 'addSliceFormats'));

        rex_view::addCssFile($this->getAssetsUrl('css/be.css'));

        if (rex::getUser()->hasPerm('moveSlice[]')) {
            rex_view::addJsFile($this->getAssetsUrl('js/be.js'));

            // rex_view::addJsFile($this->getAssetsUrl('js/jquery.gridster.min.js'));
            // rex_view::addCssFile($this->getAssetsUrl('js/jquery.gridster.min.css'));

            rex_view::addJsFile($this->getAssetsUrl('js/gridstack/lodash.min.js'));
            rex_view::addJsFile($this->getAssetsUrl('js/gridstack/gridstack.min.js'));
            rex_view::addCssFile($this->getAssetsUrl('js/gridstack/gridstack.min.css'));
            rex_view::addCssFile($this->getAssetsUrl('js/gridstack/gridstack-extra.min.css'));
        }
        */
    }
?>
