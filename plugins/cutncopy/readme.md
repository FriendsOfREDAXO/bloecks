blÖcks / Cut&Copy
=======================

Das AddOn ermöglicht es, einzelne Slices zu kopieren, bzw. auszuschneiden und an anderer
Stelle einzufügen.

### Bedienung

Nachdem das Plugin installiert und aktiviert wurde, erscheinen bei jedem Modul oben rechts bei den Editierfunktionen
zwei neue Symbole:
![Kopier-/Ausschneide-Icons](https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/master/plugins/cutncopy/readme/icons.png)

Um einen Block zu kopieren oder auszuschneiden muss der entsprechende Button geklickt werden. Ausgeschnittene oder kopierte Blöcke zeigen den entsprechenden Button dann hervorgehoben. Wurde der Block kopiert so bleibt er solange im »Zwischenspeicher«, bis der Button beim Quell-Block nochmals gedrückt wird.

Um einen kopierten oder ausgeschnittenen Block wieder einzufügen wird das »Block hinzufügen«-Dropdown verwendet - am Ende
der Modulliste erscheint dann ein entsprechender Hinweis, dass das ```Modul »X« mit der ID #Y aus Artikel »Z« an dieser Stelle```
eingefügt werden kann:

![Dropdown mit Einfüge-Option](https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/master/plugins/cutncopy/readme/dropdown.png)

Kopierte Module können nur auf solchen Seiten eingefügt werden, auf denen der Nutzer entsprechende Modulrechte hat und auf denen das Modul (via Templateeinstellungen) auch erlaubt ist.

### Extension points

- Nachdem ein Block in den Zwischenspeicher kopiert wurde, wird der ExtensionPoint ```SLICE_COPIED``` aufgerufen.
- Nachdem ein Block zum Ausschneiden in den Zwischenspeicher kopiert wurde, wird der ExtensionPoint ```SLICE_CUT``` aufgerufen. 
- Nach Einfügen des Blocks an anderer Stelle wird der ExtensionPoint ```SLICE_INSERTED``` aufgerufen.

### Rechte
Um die Funktion zu nutzen muss der Nutzer entweder ein Administrator sein oder über das Recht ```bloecks[cutncopy]```
verfügen (im Backend als ```Blöcke kopieren``` beschrieben).
