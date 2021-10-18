Changelog
=========

## Version 3.1.1 - 18.10.2021

### Bugfixes

- Provides backward compatibility for Dark Mode in REDAXO 5.12 and earlier


## Version 3.1.0 - 02.10.2021

### Features

- Dressed up for new dark mode (REDAXO 5.13) 🦇


Version 3.0.2 - 11.02.2021
--------------------------

- Corrected build of assets


Version 3.0.1 - 11.02.2021
--------------------------

- Now installable under PHP >7 


Version 3.0.0 - 02.03.2020
--------------------------

This releases provides REDAXO 5.10 compatibility. Since online/offline status of slices is now implemented into the core system, blÖcks doesn’t need to provide this feature any more.

**Breaking changes:**

Status plugIn has been disabled for REDAXO >=5.10. In case you’ve built custom functions on top of this plugIn, they will most likely break.


Version 2.1.2 - 21.02.2020
--------------------------

**Bugfixes:**

- Svensk översättning ([#88](https://github.com/FriendsOfREDAXO/bloecks/pull/88), [@interweave-media](https://github.com/interweave-media))
- Traducción en castellano ([#89](https://github.com/FriendsOfREDAXO/bloecks/pull/89), [@nandes2062](https://github.com/nandes2062))
- Show buttons only if users got permissions ([#93](https://github.com/FriendsOfREDAXO/bloecks/pull/93), [@tbaddade](https://github.com/tbaddade))
- Fix offline marker styles ([#83](https://github.com/FriendsOfREDAXO/bloecks/issues/83))
- add PHP min version ([#91](https://github.com/FriendsOfREDAXO/bloecks/issues/91))
- Update developer docs


Version 2.1.1 - 31.10.2019
--------------------------

**Bugfixes:**

- Fix sourcemaps flow: do NOT generate for production (messed this up before, sry)
- Fix release flow


Version 2.1.0 - 31.10.2019
--------------------------

**Features:**

- Grunt: implement production workflow and use it for releases

**Bugfixes:**

- Fix missing sourcemaps ([#79](https://github.com/FriendsOfREDAXO/bloecks/issues/79))


Version 2.0.1 - 27.09.2019
--------------------------

**Bugfixes:**

- Fix dropdown layer issues on hover ([#80](https://github.com/FriendsOfREDAXO/bloecks/issues/80))


Version 2.0.0 - 20.09.2019
--------------------------

**Features:**

* Offline blocks are now painted greyish and show a marker which makes them easier to distinguish from online blocks. ([#73](https://github.com/FriendsOfREDAXO/bloecks/pull/73))
* No system message is displayed when the status of blocks has changed, since the visual feedback is now sufficient. ([#73](https://github.com/FriendsOfREDAXO/bloecks/pull/73))
* Status icons no longer show the current status, but the target status: click on the crossed-out eye to set a block offline, and on the seeing eye to set it online. ([#73](https://github.com/FriendsOfREDAXO/bloecks/pull/73))
* Scroll page to current block after drag and drop
* Improved drag and drop styles
* Revised the documentation
* Updated AddOn and PlugIn pages to show the documentation ([#35](https://github.com/FriendsOfREDAXO/bloecks/pull/35))
* Used English as primary language

**Breaking changes:**

CSS styles have been changed and may not work as expected, if you are using a __custom theme for your REDAXO backend__! Without custom theme, there shouldn’t be any breaking changes.


Version 1.4.2 - 15.07.2019
--------------------------

**Bugfixes:**

- Anfasser-Markierung (Handle) für Drag & Drop korrigiert ([#71](https://github.com/FriendsOfREDAXO/bloecks/issues/71))


Version 1.4.1 - 14.07.2019
--------------------------

**Bugfixes:**

- Positionierung der Blöcke bei Drag & Drop korrigiert ([#68](https://github.com/FriendsOfREDAXO/bloecks/issues/68))
- Fehler beim Cut & Copy bei aktiviertem `structure/version`-Plugin korrigiert ([#69](https://github.com/FriendsOfREDAXO/bloecks/issues/69))
- Fehler beim Drag & Drop bei aktiviertem `structure/version`-Plugin korrigiert ([#70](https://github.com/FriendsOfREDAXO/bloecks/issues/70))


Version 1.4.0 - 19.05.2019
--------------------------

**Features:**

- Artikel aus der Zwischenablage werden innerhalb der Auswahl »Block hinzufügen« nun oben statt unten angezeigt ([#56](https://github.com/FriendsOfREDAXO/bloecks/issues/56)).
- Der Status eines Blocks wird beim Einfügen übernommen ([#45](https://github.com/FriendsOfREDAXO/bloecks/issues/45)).
- UI für Drag & Drop angepasst: Anfasser und Schatten ([#34](https://github.com/FriendsOfREDAXO/bloecks/issues/34)).

**Bugfixes:**

- Fehlendes visuelles Feedback bei ausgeblendeten Blöcken ([#42](https://github.com/FriendsOfREDAXO/bloecks/issues/42))
- Drag & Drop plugin zeigt Move-Cursor auch dann, wenn Nutzerrechte fehlen ([#60](https://github.com/FriendsOfREDAXO/bloecks/issues/60))
- Verschiedene kleine Bugfixes


Version 1.3.15 - 01.07.2018
---------------------------

- REDAXO Mindestversion angehoben auf 5.5


Version 1.3.14 - 19.06.2018
---------------------------

- Traducción en castellano ([Issue #51](https://github.com/FriendsOfREDAXO/bloecks/pull/51) von [@nandes2062](https://github.com/@nandes2062))


Version 1.3.13 - 03.05.2018
---------------------------

- D'n'd: CSRF-Problematik gelöst ([Issue #50](https://github.com/FriendsOfREDAXO/bloecks/issues/50) von [gharlan](https://github.com/gharlan))
- Update it_it.lang ([Issue #40](https://github.com/FriendsOfREDAXO/bloecks/pull/40) von [Fanello](https://github.com/Fanello))


Version 1.3.12 - 17.10.2017
---------------------------

- Slice Status wird nun über neuen EP-param abgefragt ([Issue #36](https://github.com/FriendsOfREDAXO/bloecks/pull/36) von [gharlan](https://github.com/gharlan))
- sv_se.lang hinzugefügt ([Issue #38](https://github.com/FriendsOfREDAXO/bloecks/pull/38) von [ytraduko-bot](https://github.com/ytraduko-bot))


Version 1.3.11 - 13.08.2017
---------------------------

- package()-Aufruf in Fehlermeldung innerhalb rex_api_content_move_slice_to.php gefixt
- console.log()s in Produktivversion entfernt ([Issue #30](https://github.com/FriendsOfREDAXO/bloecks/issues/30))
- aktualisierte englische Sprachdatei [ynamite](https://github.com/ynamite)
- pt_br.lang hinzugefügt (Taina Soares)


Version 1.3.10 - 13.08.2017
---------------------------

- EP in cut&copy plugin aktualisiert ([Issue #19](https://github.com/FriendsOfREDAXO/bloecks/issues/19)
- online/offline-Ansicht von Blöcken aktualisiert
- Status-Plugin-Installation gefixt ([Issue #27](https://github.com/FriendsOfREDAXO/bloecks/issues/27) von [tbaddade](https://github.com/tbaddade)


Version 1.3.9 - 03.02.2017
--------------------------

Probleme bei der Cache-Erstellung in verbindung mit dem search_it Addon behoben.


Version 1.3.8 - 31.01.2017
--------------------------

Blöcke können nun auch zwischen verschiedenen Sprachen kopiert und eingefügt werden.


Version 1.3.7 - 26.01.2017
--------------------------

Erste finale Release-Version.


Version 1.3.6 - 26.01.2017
--------------------------

Das AddOn läuft nun auch in Kombination mit dem CacheWarmup-Plugin.


Version 1.3.5 - 25.01.2017
--------------------------

Beim Drag&Drop Plugin kann nun bei Bedarf die Anzeige der Sortierungsbuttons per Einstellung eingeschaltet werden
([Issue #2](https://github.com/FriendsOfREDAXO/bloecks/issues/2)).


Version 1.3.4 - 25.01.2017
--------------------------

Beim EP ```SLICE_DELETED``` und ```STRUCTURE_CONTENT_SLICE_DELETED``` wird nun auch bei ```article_id```
die Artikel-ID und nicht der Artikel übergeben ([Issue #12](https://github.com/FriendsOfREDAXO/bloecks/issues/12)).


Version 1.3.3 - 19.01.2017
--------------------------

Auch der Status eines Revision-Slices kann nun geändert werden (Fix durch [@omphteliba](https://github.com/omphteliba).


Version 1.3.2 - 18.01.2017
--------------------------

Wird ein kopierter Artikel über das BLOCK HINZUFÜGEN DropDown eingefügt, geschieht das nun via PJAX,
sodass sich die URL des Browsers nicht ändert. Sonst fügt man versehentlich weitere Blöcke beim
Reload der Seite ein oder kann den Block am Ende nur einmal einfügen.

Außerdem wird der kopierte Block beim Einfügen weiterhin im Clipboard behalten (wurde vorher entfernt).


Version 1.3.1 - 11.01.2017
--------------------------

Readme für das Cut&Copy-Plugin aktualisiert und PJAX/URL-Push für die Icons angepasst.


Version 1.3 - 15.12.2016
------------------------

Erste funktionierende Version des Cut&Copy-Plugins eingebunden. Außerdem beim Status-Plugin eine
update.php eingebaut.


Version 1.1.1 - 23.09.2016
--------------------------

Erste funktionierende Version des Drag&Drop-Plugins eingebunden - muss aber noch aufgeräumt werden.


Version 1.1 – 22.09.2016
------------------------

Kompletter Neuaufbau der Codebasis, Dokumentation und Bereinigung.
