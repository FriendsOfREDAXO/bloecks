<?php
/**
 * Bloecks-Klasse - Grundfunktionen für das AddOn und seine Plugins.
 */
class bloecks extends bloecks_abstract
{
    /**
     * Initialisiert das AddOn.
     */
    public static function init(rex_extension_point $ep)
    {
        // Bei Zugriff im Backend und wenn ein Benutzer angemeldet ist, Backend-Funktionen initialisieren
        if (rex::isBackend() && rex::getUser()) {
            bloecks_backend::init($ep);
            return;
        }

        // Im Frontend, eigenen Extension-Point registrieren
        if (!rex::isBackend()) {
            rex_extension::register('SLICE_SHOW', ['bloecks', 'showSlice'], rex_extension::EARLY);
        }
    }

    /**
     * Erstellt einen eigenen Extension Point, der in allen unseren Plugins verwendet wird.
     *
     * @return string Inhalte des Slices
     */
    public static function showSlice(rex_extension_point $ep)
    {
        // Subject erhalten und unseren eigenen Extension Point hinzufügen
        $slice_content = rex_extension::registerPoint(new rex_extension_point(
            'SLICE_SHOW_BLOECKS_FE',
            $ep->getSubject(),
            $ep->getParams()
        ));

        return $slice_content;
    }
}
