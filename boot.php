<?php

    // Initialize the main addon
    rex_extension::register('PACKAGES_INCLUDED', ['bloecks', 'init'], rex_extension::EARLY);

    // Initialize cut & copy functionality (previously cutncopy plugin)  
    rex_extension::register('PACKAGES_INCLUDED', ['bloecks_cutncopy', 'init']);

    // Initialize drag & drop functionality (previously dragndrop plugin)
    rex_extension::register('PACKAGES_INCLUDED', ['bloecks_dragndrop', 'init'], rex_extension::EARLY);
