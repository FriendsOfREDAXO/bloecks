blÖcks / Cut&Copy 1.3-alpha
=======================

Das AddOn ermöglicht es, einzelne Slices zu kopieren, bzw. auszuschneiden und an anderer
Stelle einzufügen.

### Extension points

Nachdem ein Block in den Zwischenspeicher kopiert wurde, wird der ExtensionPoint ```SLICE_COPIED``` aufgerufen. Nach
Einfügen des Blocks an anderer Stelle wird der ExtensionPoint ```SLICE_INSERTED``` aufgerufen.

### Rechte
Um die Funktion zu nutzen muss der Nutzer entweder ein Administrator sein oder über das Recht ```bloecks[cutncopy]```
verfügen (im Backend als ```Blöcke kopieren``` beschrieben).
