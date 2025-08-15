# blÖcks

Schlankes REDAXO AddOn für Copy/Cut/Paste und Drag & Drop Sortierung von Slices mit Toast-Benachrichtigungen und intelligenter Scroll-Funktionalität.

## Features
- **Copy/Cut/Paste**: Slices kopieren, ausschneiden und einfügen mit visueller Rückmeldung
- **Multi-Clipboard**: Mehrere Slices gleichzeitig kopieren und selektiv einfügen
- **Smart Positioning**: Wahlweise oberhalb oder unterhalb des aktuellen Slices einfügen
- **Drag & Drop**: Intuitive Neuordnung von Slices per Drag & Drop
- **Toast Notifications**: Elegante Erfolgsmeldungen und Fehlermeldungen
- **Auto-Scroll**: Automatisches Scrollen zum eingefügten Slice nach dem Einfügen
- **Version Plugin Support**: Vollständige Kompatibilität mit dem Version Plugin
- **Granulare Rechte**: `bloecks[]`, `bloecks[copy]`, `bloecks[order]`, `bloecks[multi]`, `bloecks[settings]`
- **CSRF-Schutz**: Sichere Operationen durch eingebauten CSRF-Schutz
- **Multi-Language**: Deutsche und englische Übersetzungen

## Installation
1. AddOn aktivieren
2. Features unter `AddOns → BLOECKS → Einstellungen` konfigurieren

## Konfiguration

### Grundeinstellungen
- **Copy & Paste aktivieren/deaktivieren**: Schaltet die Kopieren/Einfügen-Funktionalität ein oder aus
- **Multi-Clipboard aktivieren/deaktivieren**: Ermöglicht das Kopieren mehrerer Slices und selektives Einfügen
- **Drag & Drop aktivieren/deaktivieren**: Schaltet die Drag & Drop-Sortierung ein oder aus
- **Paste Position**: Bestimmt, wo neue Slices eingefügt werden
  - `after` (Standard): Slices werden **unterhalb** des aktuellen Slices eingefügt
  - `before`: Slices werden **oberhalb** des aktuellen Slices eingefügt

### Ausschlüsse
- **Templates ausschließen**: Template-IDs (kommagetrennt), in denen BlÖcks deaktiviert sein soll
- **Module ausschließen**: Modul-IDs (kommagetrennt), für die BlÖcks deaktiviert sein soll

### Benutzerfreundlichkeit
- **Toast-Benachrichtigungen**: Informative Erfolgsmeldungen mit 5-6 Sekunden Anzeigedauer
- **Automatisches Scrollen**: Nach dem Einfügen wird automatisch zum neuen Slice gescrollt
- **PJAX-Optimierung**: Nahtlose Integration in das REDAXO Backend ohne Seitenneuladen

## Rechte
| Funktion | Recht | Beschreibung |
|----------|-------|--------------|
| Copy/Cut/Paste | `bloecks[]` oder `bloecks[copy]` | Erlaubt das Kopieren, Ausschneiden und Einfügen von Slices |
| Multi-Clipboard | `bloecks[multi]` | Ermöglicht Zugriff auf Multi-Clipboard (Admin hat immer Zugriff) |
| Drag & Drop | `bloecks[]` oder `bloecks[order]` | Erlaubt die Neuordnung von Slices per Drag & Drop |
| Einstellungen | nur Admin | Zugriff auf die AddOn-Konfiguration |

## Bedienung

### Copy/Cut/Paste
1. **Kopieren/Ausschneiden**: Klick auf das entsprechende Icon im Slice-Menü
2. **Einfügen**: Paste-Button erscheint in verfügbaren Positionen
3. **Multi-Clipboard**: Bei mehreren kopierten Slices erscheint Dropdown mit Auswahl
4. **Position**: Je nach Einstellung wird oberhalb oder unterhalb eingefügt
5. **Rückmeldung**: Toast-Benachrichtigung bestätigt die Aktion
6. **Navigation**: Automatisches Scrollen zum eingefügten Slice

### Multi-Clipboard
1. **Mehrere Slices kopieren**: Beliebig viele Slices nacheinander kopieren/ausschneiden
2. **Dropdown-Auswahl**: Paste-Button zeigt Anzahl und öffnet Auswahl bei Klick
3. **Selektives Einfügen**: Einzelne oder mehrere Slices aus Liste auswählen
4. **Alle einfügen**: Option zum Einfügen aller Clipboard-Elemente
5. **Verwaltung**: Einzelne Elemente aus Clipboard löschen

### Drag & Drop
1. **Verschieben**: Slice am Drag-Handle greifen und ziehen
2. **Positionierung**: Drop-Zone zeigt gültige Positionen an
3. **Bestätigung**: Toast-Benachrichtigung bei erfolgreicher Sortierung

### Einfügeposition konfigurieren
Die **Paste Position** bestimmt das Verhalten beim Einfügen:
- **Nach unten** (`after`): Neue Slices werden unter dem aktuellen Slice eingefügt
- **Nach oben** (`before`): Neue Slices werden über dem aktuellen Slice eingefügt

Diese Einstellung kann in `AddOns → BLOECKS → Einstellungen` geändert werden und gilt global für alle Benutzer.

## Entwicklung
```bash
npm install
npm run copy-assets
```

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

**Leads**
- [Thomas Skerbis](https://github.com/skerbis) 


#### DankeschÖn

BlÖcks ist ursprünglich von [Thomas Göllner](https://github.com/tgoellner) entwickelt worden. Deshalb die Sache mit dem Ö.
Es wird gepflegt und weiterentwickelt von den [Friends Of REDAXO](https://github.com/FriendsOfREDAXO/bloecks). Deshalb die Sache mit FOR.
