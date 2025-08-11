# BLOECKS

Schlankes REDAXO AddOn für Copy / Cut / Paste und Drag & Drop Sortierung von Slices.

## Features
- Slice kopieren, ausschneiden (Cut) und an beliebiger Position einfügen
- Drag & Drop Neuordnung (SortableJS v1.15.6, nur im Backend)
- Granulare Rechte: `bloecks[]`, `bloecks[copy]`, `bloecks[order]`, `bloecks[settings]`
- CSRF-Schutz für alle API-Operationen
- Minimaler CSS-Footprint, orientiert sich an Core-Styles

## Installation
1. AddOn in `redaxo/src/addons` legen (oder über Installer bereitstellen)
2. Im Backend aktivieren
3. Alte AddOns `bloecks_legacy` und ggf. `slice_columns` deaktivieren (Konflikt wird verhindert)

## Konfiguration
Unter `AddOns -> BLOECKS -> Einstellungen`:
- Copy & Paste aktivieren/deaktivieren
- Drag & Drop aktivieren/deaktivieren
- Templates und Module per ID ausschließen (Pipe `|` getrennt)

## Nutzung
- Buttons erscheinen im Slice-Menü (Copy, Cut, Paste)
- Drag & Drop über die Panel-Heading Fläche
- Cut: Kopiert Slice und löscht Original beim Einfügen
- Paste vor Ziel: Einfügen vor dem Slice dessen Menü genutzt wurde

## Sicherheit
- CSRF Token Pflicht (`rex_csrf_token` Namespace `bloecks`)
- Rechteprüfung in jedem API-Endpunkt

## API Endpoints (intern via `rex-api-call=bloecks`)
| Funktion        | Parameter                                        | Beschreibung |
|-----------------|--------------------------------------------------|--------------|
| copy_slice      | slice_id                                         | Speichert Slice-Daten in Session |
| paste_slice     | article_id, clang, target_slice?                 | Fügt kopierten Slice ein (vor target_slice oder ans Ende) |
| update_order    | article, clang, order (JSON Array von Slice-IDs) | Aktualisiert Reihenfolge |
| delete_slice    | slice_id                                         | Löscht Slice (für Cut) |

## Rechte-Matrix
| Aktion              | Benötigtes Recht |
|---------------------|------------------|
| Copy/Cut/Paste       | bloecks[] oder bloecks[copy] |
| Drag & Drop Sort     | bloecks[] oder bloecks[order] |
| Einstellungen sehen  | bloecks[] oder bloecks[settings] |

## Roadmap / Ideen
- Optional: Mehrfach-Auswahl (Batch Copy)
- Optional: Export/Import zwischen Instanzen
- Optionale Tastaturkürzel (⌘C / ⌘V innerhalb Content-Kontext)
- Optional: JSON Response Standardisierung mit `rex_response::sendJson`

## Entwicklung

### Dependencies
- PHP >= 8.1
- Node.js >= 16 (für SortableJS Updates)
- SortableJS v1.15.6 (via npm)

### Development Setup
```bash
# Im AddOn-Verzeichnis
npm install
npm run copy-assets
```

### SortableJS aktualisieren
```bash
npm run update-sortable
```

Siehe [`DEVELOPMENT.md`](DEVELOPMENT.md) für detaillierte Anweisungen.

### Architektur
- Kein Frontend-Eingriff (nur Backend Bearbeitung)
- JS init über `rex:ready`
- Session-basierte Zwischenablage
- REDAXO Extension Points für nahtlose Integration

## Changelog
### 1.0.0
Initiale schlanke Version (Copy/Cut/Paste + Drag & Drop).

## Lizenz
MIT
