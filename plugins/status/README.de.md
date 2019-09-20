# Status

Fügt Inhaltsmodulen einen Status `online` und `offline` hinzu, mit dem du sie auf deiner Website anzeigen oder verstecken kannst.  
Offline-Blöcke sind ausgegraut und zeigen eine Status-Markierung an.

<img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_status_01.png" alt="Screenshot" style="width: 100%; max-width: 1000px; margin: 20px 0;">
<br>

## Benutzung

Klicke auf den Knopf mit dem __Auge-Symbol__ <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_status_eye_closed.png" alt="Auge geschlossen" style="width: 32px;">, um einen Block offline zu setzen. Er wird danach ausgegraut und zeigt eine Status-Markierung an. Ein erneuter Klick auf das Auge <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_status_eye_open.png" alt="Auge geöffnet" style="width: 32px;"> bringt den Block wieder online.

## Benutzerrechte

Benutzer müssen entweder Administratoren sein oder über das Recht `bloecks[status]` (»Blöcke an/abschalten«) verfügen, um den Status eines Blocks ändern zu können.

## Status innerhalb eines Moduls ändern

Beispiel-Code: 

```php
if (rex::isBackend()) {
    $slice_status = bloecks_status_backend::setSliceStatus("REX_SLICE_ID", 0); // status: true/false
}
```

## Extension Points

| EP                      | Beschreibung                     |
|-------------------------|----------------------------------|
| `SLICE_UPDATE_STATUS`   | Wird aufgerufen, bevor sich der Status eines Blocks ändert |
| `SLICE_STATUS_UPDATED ` | Wird aufgerufen, nachdem der Status eines Blocks erfolgreich geändert wurde |
