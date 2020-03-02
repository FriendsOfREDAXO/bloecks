<?php

/** @var rex_addon $this */

$package = rex_package::get($this->getProperty('package'));


/* deprecation info for REDAXO >=5.10 */

if (rex_string::versionCompare(rex::getVersion(), '5.10.0-dev', '>=')) {
    $content = '';

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'info', false);
    $fragment->setVar('title', rex_i18n::msg('bloecks_status_deprecated_title'), false);
    $fragment->setVar('body', '<p>'.rex_i18n::msg('bloecks_status_deprecated_info').'</p>', false);
    echo $fragment->parse('core/page/section.php');
}


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
