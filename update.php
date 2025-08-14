<?php

if (version_compare(rex_addon::get('bloecks')->getVersion(), '5.0.0-beta1', '<')) {
    // Update von Version 4.x auf 5.x
    $addon = rex_addon::get('bloecks');
    
    // Prüfen welche Plugins in der alten Version aktiv waren BEVOR wir sie löschen
    $cutncopyWasActive = rex_addon::exists('bloecks') && rex_plugin::exists('bloecks', 'cutncopy') && rex_plugin::get('bloecks', 'cutncopy')->isAvailable();
    $dragndropWasActive = rex_addon::exists('bloecks') && rex_plugin::exists('bloecks', 'dragndrop') && rex_plugin::get('bloecks', 'dragndrop')->isAvailable();
    
    // Features basierend auf den aktiven Plugins aktivieren
    $config = [
        'enable_copy_paste' => $cutncopyWasActive,
        'enable_drag_drop' => $dragndropWasActive,
        'templates_exclude' => '',
        'modules_exclude' => ''
    ];
    
    // Neue Konfiguration setzen
    foreach ($config as $key => $value) {
        $addon->setConfig($key, $value);
    }
    
    // Log für Debug-Zwecke
    rex_logger::factory()->info('bloecks update: Updated from v4.x to v5.x. Copy/Paste: ' . ($cutncopyWasActive ? 'enabled' : 'disabled') . ', Drag/Drop: ' . ($dragndropWasActive ? 'enabled' : 'disabled'));
    
    // Alte Plugins entfernen    
    // JETZT erst die alten Plugin-Verzeichnisse löschen
    rex_dir::delete(rex_path::plugin('bloecks', 'status'));
    rex_dir::delete(rex_path::plugin('bloecks', 'cutncopy'));
    rex_dir::delete(rex_path::plugin('bloecks', 'dragndrop'));
    
    // Cleanup alte Assets
    rex_dir::delete(rex_path::addonAssets('bloecks'));
} 
