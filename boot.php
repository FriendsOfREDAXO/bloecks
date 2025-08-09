<?php

    use FriendsOfRedaxo\Bloecks\BlOecks;
    use FriendsOfRedaxo\Bloecks\BlOecksCutNCopy;  
    use FriendsOfRedaxo\Bloecks\BlOecksDragNDrop;

    // Initialize the main addon
    rex_extension::register('PACKAGES_INCLUDED', [BlOecks::class, 'init'], rex_extension::EARLY);

    // Initialize cut & copy functionality (previously cutncopy plugin)
    if (rex_addon::get('bloecks')->getConfig('cutncopy_active', true)) {
        rex_extension::register('PACKAGES_INCLUDED', [BlOecksCutNCopy::class, 'init']);
    }

    // Initialize drag & drop functionality (previously dragndrop plugin)
    if (rex_addon::get('bloecks')->getConfig('dragndrop_active', true)) {
        rex_extension::register('PACKAGES_INCLUDED', [BlOecksDragNDrop::class, 'init'], rex_extension::EARLY);
    }
