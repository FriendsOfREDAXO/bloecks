# Ausschneiden & Kopieren

Inhaltsblöcke eines Artikels können ausgeschnitten oder kopiert werden, um sie in anderen Artikeln einzufügen.

<img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_cutncopy_01.png" alt="Screenshot" style="width: 100%; max-width: 1000px; margin: 20px 0;">
<br>

## Benutzung

Klicke als erstes auf den Knopf mit dem __Dokumente-Icon__ <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_cutncopy_copy.png" alt="Dokumente" style="width: 32px;"> (Kopieren) oder auf den Knopf mit dem __Schere-Icon__ <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_cutncopy_cut.png" alt="Schere" style="width: 32px;"> (Ausschneiden), um einen Block in der Zwischenablage zu speichern. Beim Ausschneiden wird der Block später an dieser Stelle entfernt, sobald er an anderer Stelle eingefügt wird.

Blöcke, die sich in der Zwischenablage befinden, wechseln die Farbe ihrer Knöpfe zu Blau, um anzuzeigen, ob sie mittels __Kopieren__ <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_cutncopy_copy_active.png" alt="Dokumente" style="width: 32px;"> oder mittels __Ausschneiden__ <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_cutncopy_cut_active.png" alt="Schere" style="width: 32px;"> gespeichert worden sind. Ein Block bleibt so lange in der Zwischenablage erhalten, bis er in einen Artikel eingefügt oder ein anderer Block in der Zwischenablage gespeichert wird.

Um einen gespeicherten Block in einen Artikel einzufügen, benutze REDAXOs »__Block hinzufügen__«-Menü und wähle den gespeicherten Block vom Anfang der Liste aus.

## Benutzerrechte

Benutzer müssen entweder Administratoren sein oder über das Recht `bloecks[cutncopy]` (»Blöcke kopieren«) verfügen, um den Status eines Blocks ändern zu können.

## Extension Points

| EP                      | Beschreibung                     |
|-------------------------|----------------------------------|
| `SLICE_COPIED`          | Wird aufgerufen, nachdem ein Block in die Zwischenablage kopiert worden ist. |
| `SLICE_CUT`             | Wird aufgerufen, nachdem ein Block durch Ausschneiden in die Zwischenablage kopiert worden ist. |
| `SLICE_INSERTED`        | Wird aufgerufen, nachdem ein Block in einen Artikel eingefügt worden ist. |
