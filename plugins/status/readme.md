# blÖcks / Status

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/master/plugins/status/readme/screenshot.jpg)

Das AddOn ermöglicht es, einzelne Slices online und offline zu stellen. Dazu wird ein neuer Button
zwischen "Block löschen" und "Block verschieben" eingefügt, der den Status des Blocks  umstellt. Deaktivierte
Blöcke werden im Backend ausgegraut, im Frontend nicht angezeigt.

## Extension points

Bevor der Status des Blocks geändert wird, wird der ExtensionPoint ```SLICE_UPDATE_STATUS``` aufgerufen. Nach
erflogreichem Ändern des Status wird der ExtensionPoint ```SLICE_STATUS_UPDATED``` aufgerufen.

## Rechte
Um die Funktion zu nutzen muss der Nutzer entweder ein Administrator sein oder über das Recht ```bloecks[status]```
verfügen (im Backend als ```Blöcke an/abschalten``` beschrieben).

## blÖcks im Modul setzen

Beispiel-Code: 

```php
if (rex::isBackend()) {
    $slice_status = bloecks_status_backend::setSliceStatus("REX_SLICE_ID", 0); // status: true/false
}
```

