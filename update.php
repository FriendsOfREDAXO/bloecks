<?php


rex_dir::delete(rex_path::plugin('bloecks', 'status'));
rex_dir::delete(rex_path::plugin('bloecks', 'cutncopy'));
rex_dir::delete(rex_path::plugin('bloecks', 'dragndrop'));

rex_dir::delete(rex_path::addonAssets('bloecks'));

if (version_compare(rex_addon::get('bloecks')->getVersion(), '5.0.0', '<')) {
  rex_config::removeNamespace("bloecks");
}
