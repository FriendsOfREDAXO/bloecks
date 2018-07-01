Changelog
=========

Version 1.3.15 - 01.07.2018
--------------------------

- REDAXO Mindestversion angehoben auf 5.5

Version 1.3.14 - 19.06.2018
--------------------------

- Traducción en castellano ([Issue #51](https://github.com/FriendsOfREDAXO/bloecks/pull/51) von [@nandes2062](https://github.com/@nandes2062))

Version 1.3.13 - 03.05.2018
--------------------------

- D'n'd: CSRF-Problematik gelöst ([Issue #50](https://github.com/FriendsOfREDAXO/bloecks/issues/50) von [gharlan](https://github.com/gharlan))
- Update it_it.lang ([Issue #40](https://github.com/FriendsOfREDAXO/bloecks/pull/40) von [Fanello](https://github.com/Fanello))

Version 1.3.12 - 17.10.2017
--------------------------
- Slice Status wird nun über neuen EP-param abgefragt ([Issue #36](https://github.com/FriendsOfREDAXO/bloecks/pull/36) von [gharlan](https://github.com/gharlan))
- sv_se.lang hinzugefügt ([Issue #38](https://github.com/FriendsOfREDAXO/bloecks/pull/38) von [ytraduko-bot](https://github.com/ytraduko-bot))


Version 1.3.11 - 13.08.2017
--------------------------

- package()-Aufruf in Fehlermeldung innerhalb rex_api_content_move_slice_to.php gefixt
- console.log()s in Produktivversion entfernt ([Issue #30](https://github.com/FriendsOfREDAXO/bloecks/issues/30))
- aktualisierte englische Sprachdatei [ynamite](https://github.com/ynamite)
- pt_br.lang hinzugefügt (Taina Soares)


Version 1.3.10 - 13.08.2017
--------------------------

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
--------------------------

Erste funktionierende Version des Cut&Copy-Plugins eingebunden. Außerdem beim Status-Plugin eine
update.php eingebaut.


Version 1.1.1 - 23.09.2016
--------------------------

Erste funktionierende Version des Drag&Drop-Plugins eingebunden - muss aber noch aufgeräumt werden.


Version 1.1 – 22.09.2016
--------------------------

Kompletter Neuaufbau der Codebasis, Dokumentation und Bereinigung.
