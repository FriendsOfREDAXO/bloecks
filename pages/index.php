<?php

/**
 * Bloecks Overview Page.
 */

// Standard-Index: Titel + Subpage laden
echo rex_view::title(rex_i18n::msg('bloecks_navigation'));
// System-/API-Meldungen
echo rex_api_function::getMessage();
// Aktuelle Subpage einbinden
rex_be_controller::includeCurrentPageSubPath();
