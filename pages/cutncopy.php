<?php

/** @var rex_addon $this */

$package = rex_package::get($this->getProperty('package'));


/* package info from README.md */

$content = '';
if (is_readable($package->getPath('README.'. rex_i18n::getLanguage() .'.md'))) {
    $fragment = new rex_fragment();
    $fragment->setVar('content', rex_markdown::factory()->parse(rex_file::get($package->getPath('README.'. rex_i18n::getLanguage() .'.md'))), false);
    $content .= $fragment->parse('core/page/docs.php');
} elseif (is_readable($package->getPath('README.md'))) {
    $fragment = new rex_fragment();
    $fragment->setVar('content', rex_markdown::factory()->parse(rex_file::get($package->getPath('README.md'))), false);
    $content .= $fragment->parse('core/page/docs.php');
}

if (!empty($content)) {
    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('package_help') . ' ' . $package->getPackageId(), false);
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
}
