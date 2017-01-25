blÖcks / Status 1.3.4-alpha
=======================

Das AddOn ermöglicht es, einzelne Slices online und offline zu stellen. Dazu wird ein neuer Button
zwischen "Block löschen" und "Block verschieben" eingefügt, der den Status des Blocks  umstellt. Deaktivierte
Blöcke werden im Backend ausgegraut, im Frontend nicht angezeigt.

### Extension points

Bevor der Status des Blocks geändert wird, wird der ExtensionPoint ```SLICE_UPDATE_STATUS``` aufgerufen. Nach
erflogreichem Ändern des Status wird der ExtensionPoint ```SLICE_STATUS_UPDATED``` aufgerufen.

### Rechte
Um die Funktion zu nutzen muss der Nutzer entweder ein Administrator sein oder über das Recht ```bloecks[status]```
verfügen (im Backend als ```Blöcke an/abschalten``` beschrieben).
