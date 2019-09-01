# Status

Implements `online` and `offline` status for slices so you can show or hide them on your website.  
Offline slices are painted greyish and show a status label.

<img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_status_01.png" alt="Screenshot" style="width: 100%; max-width: 1000px; margin: 20px 0;">
<br>

## User permissions

Users must either be administrators or have assigned the permission `bloecks[status]` (»Activate/Deactive blocks«) to change the status of a block.

## Change status within module

Example code: 

```php
if (rex::isBackend()) {
    $slice_status = bloecks_status_backend::setSliceStatus("REX_SLICE_ID", 0); // status: true/false
}
```

## Extension Points

| EP                      | Description                      |
|-------------------------|----------------------------------|
| `SLICE_UPDATE_STATUS`   | Is called before the status of a block changes |
| `SLICE_STATUS_UPDATED ` | Is called after the status of a block was successfully changed |
