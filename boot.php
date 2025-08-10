<?php

    rex_extension::register('PACKAGES_INCLUDED', ['bloecks', 'init'], rex_extension::EARLY);
    
    // Initialize consolidated cutncopy functionality
    rex_extension::register('PACKAGES_INCLUDED', ['bloecks_cutncopy', 'init']);
    
    // Initialize consolidated dragndrop functionality  
    rex_extension::register('PACKAGES_INCLUDED', ['bloecks_dragndrop', 'init'], rex_extension::EARLY);
