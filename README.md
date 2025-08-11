# BLOECKS

Schlankes REDAXO AddOn für Copy/Cut/Paste und Drag & Drop Sortierung von Slices.

## Features
- Slice kopieren, ausschneiden und einfügen
- Drag & Drop Neuordnung von Slices
- Granulare Rechte: `bloecks[]`, `bloecks[copy]`, `bloecks[order]`, `bloecks[settings]`
- CSRF-Schutz für alle Operationen

## Installation
1. AddOn aktivieren
2. Features unter `AddOns → BLOECKS → Einstellungen` konfigurieren

## Konfiguration
- Copy & Paste aktivieren/deaktivieren
- Drag & Drop aktivieren/deaktivieren
- Templates und Module per ID ausschließen (kommagetrennt)

## Rechte
| Funktion | Recht |
|----------|-------|
| Copy/Cut/Paste | bloecks[] oder bloecks[copy] |
| Drag & Drop | bloecks[] oder bloecks[order] |
| Einstellungen | bloecks[] oder bloecks[settings] |

## Entwicklung
```bash
npm install
npm run copy-assets
```

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

### Architektur
- Kein Frontend-Eingriff (nur Backend Bearbeitung)
- JS init über `rex:ready`
- Session-basierte Zwischenablage
- REDAXO Extension Points für nahtlose Integration

## Lizenz
MIT

#### DankeschÖn

BlÖcks ist ursprünglich von [Thomas Göllner](https://github.com/tgoellner) entwickelt worden. Deshalb die Sache mit dem Ö.
Es wird gepflegt und weiterentwickelt von den [Friends Of REDAXO](https://github.com/FriendsOfREDAXO/bloecks). Deshalb die Sache mit FOR.
