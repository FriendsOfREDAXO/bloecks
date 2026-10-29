# blÖcks

Schlankes REDAXO AddOn für Copy/Cut/Paste und Drag & Drop Sortierung von Slices mit Toast-Benachrichtigungen und intelligenter Scroll-Funktionalität.

## Changelog

### 5.4.0
- **Einfüge-Overlay schlanker** ([#185](https://github.com/FriendsOfREDAXO/bloecks/issues/185)): statt Auswahlliste für die Position zwei Knöpfe **„Darüber einfügen“** und **„Darunter einfügen“**, „Leeren“ in der Fußzeile. Bei mehreren Einträgen bleiben die Häkchen – die Knöpfe fügen alle markierten Einträge in ihrer Reihenfolge ein. Die eingestellte Standardposition ist grün hervorgehoben.
- **Favoriten** ([#178](https://github.com/FriendsOfREDAXO/bloecks/issues/178), optional, standardmäßig aus): Slices als gemeinsame Vorlagen speichern (Stern im Slice-Menü) und im Einfüge-Overlay unter „Favoriten“ in jeden Artikel einfügen. Neues Recht `bloecks[favorites]` zum Anlegen und Löschen.
- **Drag & Drop** ([#183](https://github.com/FriendsOfREDAXO/bloecks/issues/183)): Beim Ziehen springt nichts mehr – Rahmen per `outline` statt Padding/Margin/Skalierung. Gegriffen wird am ganzen Kopf des Slices; Panels im Modul-Inhalt (MForm, MBlock, Gridblock …) starten kein Ziehen mehr und bekommen keinen Griff-Look.
- **Kompaktmodus** ([#184](https://github.com/FriendsOfREDAXO/bloecks/issues/184)): „Block hinzufügen“ liegt nicht mehr halb verdeckt über dem Slice-Kopf, sondern erscheint beim Überfahren (oder per Tastatur-Fokus) als schmale Leiste in der Lücke zwischen den Slices. Funktioniert jetzt auch ohne Copy/Paste und Drag & Drop und zeigt ohne Drag & Drop keinen Griff-Look mehr.
- Dark-Mode-Farben des Overlays greifen nur noch im automatischen Theme, nicht mehr bei manuell gewähltem hellen Theme.
- Assets werden über ihre Änderungszeit statt bei jedem Aufruf neu geladen.

### 5.3.2
- Scope-Fix für Toast-Erkennung: Konvertierung von Backend-Alerts zu BLOECKS-Toasts erfolgt nur noch für BLOECKS-eigene, markierte Meldungen.
- Verhindert Fehltrigger mit Alerts aus anderen AddOns.

## Features
- **Copy/Cut/Paste**: Slices kopieren, ausschneiden und einfügen mit visueller Rückmeldung
- **Multi-Clipboard**: Mehrere Slices gleichzeitig kopieren und selektiv einfügen
- **Favoriten**: Slices als gemeinsame Vorlagen speichern und in jeden Artikel einfügen (optional)
- **Kompaktmodus**: „Block hinzufügen“ zwischen den Slices erscheint erst beim Überfahren (optional)
- **Smart Positioning**: Wahlweise oberhalb oder unterhalb des aktuellen Slices einfügen
- **Drag & Drop**: Intuitive Neuordnung von Slices per Drag & Drop
- **Toast Notifications**: Elegante Erfolgsmeldungen und Fehlermeldungen
- **Auto-Scroll**: Automatisches Scrollen zum eingefügten Slice nach dem Einfügen
- **Version Plugin Support**: Vollständige Kompatibilität mit dem Version Plugin
- **Granulare Rechte**: `bloecks[]`, `bloecks[copy]`, `bloecks[order]`, `bloecks[multi]`, `bloecks[favorites]`
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
- **Standard-Einfügeposition**: Welcher der beiden Einfüge-Knöpfe hervorgehoben ist
  - `after` (Standard): „Darunter einfügen“
  - `before`: „Darüber einfügen“
- **Favoriten aktivieren/deaktivieren** (Standard: aus): Slices als gemeinsame Vorlagen, benötigt Copy & Paste
- **Kompakt-Modus aktivieren/deaktivieren** (Standard: aus): „Block hinzufügen“ zwischen den Slices nur beim Überfahren

Alle Funktionen sind einzeln schaltbar und nach der Installation deaktiviert.

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
| Favoriten einfügen | `bloecks[]` oder `bloecks[copy]` | Wer kopieren darf, darf auch Favoriten einfügen (nur Module, für die er Rechte hat) |
| Favoriten anlegen/löschen | `bloecks[favorites]` | Slices als Favorit speichern und Favoriten löschen (Admin hat immer Zugriff) |
| Drag & Drop | `bloecks[]` oder `bloecks[order]` | Erlaubt die Neuordnung von Slices per Drag & Drop |
| Einstellungen | nur Admin | Zugriff auf die AddOn-Konfiguration |

## Bedienung

### Copy/Cut/Paste
1. **Kopieren/Ausschneiden**: Klick auf das entsprechende Icon im Slice-Menü
2. **Einfügen**: Das Einfüge-Icon erscheint im Slice-Menü, sobald etwas in der Zwischenablage liegt. In einem leeren Artikel erscheint stattdessen ein grüner Knopf über „Block hinzufügen“.
3. **Einfüge-Overlay**: Ein Klick auf das Einfüge-Icon zeigt die Zwischenablage mit den Knöpfen **„Darüber einfügen“** und **„Darunter einfügen“** sowie **„Leeren“**. Die eingestellte Standardposition ist grün.
4. **Rückmeldung**: Toast-Benachrichtigung bestätigt die Aktion, danach wird zum eingefügten Slice gescrollt.

### Multi-Clipboard
1. **Mehrere Slices kopieren**: Beliebig viele Slices nacheinander kopieren/ausschneiden
2. **Auswahl**: Im Einfüge-Overlay sind alle Einträge vorausgewählt; mit den Häkchen bzw. „Alle“ / „Keine“ lässt sich die Auswahl ändern
3. **Einfügen**: „Darüber“ oder „Darunter“ fügt alle markierten Einträge in der Reihenfolge ein, in der sie kopiert wurden
4. **Ausgeschnittene Einträge** verschwinden nach dem Einfügen aus der Zwischenablage, kopierte bleiben

### Favoriten
Wiederkehrende Slices (z. B. Kontaktblock, Hinweisbox, vorbereitetes Gridblock-Raster) müssen nicht immer erst in anderen Artikeln gesucht werden:

1. **Speichern**: Stern im Slice-Menü → Name vergeben → „Speichern“. Gespeichert wird eine 1:1-Kopie aller Slice-Werte.
2. **Einfügen**: Einfüge-Icon → Reiter **„Favoriten“** → gewünschte Favoriten anhaken → „Darüber“ oder „Darunter einfügen“. Das Einfüge-Icon erscheint auch, wenn nur Favoriten vorhanden sind.
3. **Löschen**: Das ✕ hinter einem Favoriten (nur mit Recht `bloecks[favorites]` bzw. als Admin).

Favoriten gelten für alle Benutzer (gemeinsame Vorlagen) und sind nicht begrenzt. Angezeigt werden nur Favoriten, deren Modul der Benutzer verwenden darf. Wurde das Modul eines Favoriten gelöscht, wird er beim Einfügen mit einem Hinweis übersprungen. Die Favoriten liegen in der Tabelle `rex_bloecks_favorite`.

### Drag & Drop
1. **Verschieben**: Slice am Kopf (grauer Balken mit ⋮⋮) greifen und ziehen – Knöpfe im Kopf bleiben normal klickbar
2. **Positionierung**: Der gezogene Slice wird umrandet, die Liste behält ihr Layout
3. **Bestätigung**: Toast-Benachrichtigung bei erfolgreicher Sortierung

### Kompaktmodus
„Block hinzufügen“ zwischen den Slices nimmt keinen Platz ein. Beim Überfahren eines Slices erscheint darüber eine schmale Leiste in der Lücke zum vorherigen Slice; per Tab-Taste ist sie ebenfalls erreichbar. Der Kompaktmodus funktioniert mit und ohne Drag & Drop. Der letzte „Block hinzufügen“-Knopf am Ende des Artikels bleibt immer sichtbar.

## Kompatibilität mit MForm, MBlock und Gridblock
- Kopieren, Ausschneiden und Favoriten übernehmen alle Slice-Werte 1:1 (`value1`–`value20`, `media1`–`5`, `medialist1`–`5`, `link1`–`5`, `linklist1`–`5`, Status). JSON-Werte von MBlock und Gridblock bleiben unverändert.
- Drag & Drop greift nur am eigenen Kopf des Slices. Panels und Köpfe im Modul-Inhalt (z. B. Gridblock-Spalten, MBlock-Einträge) lösen kein Ziehen aus.
- BlÖcks greift nicht in Modul-Eingaben oder die Slice-Bearbeitung ein, sondern nutzt nur die Extension Points `SLICE_SHOW`, `STRUCTURE_CONTENT_SLICE_MENU` und `STRUCTURE_CONTENT_MODULE_SELECT`.
- Ersetzt ein anderes AddOn die Modulauswahl (z. B. Module Organizer), funktioniert der Kompaktmodus weiter, solange die Auswahl im üblichen `.rex-slice-select > .dropdown` liegt.

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
