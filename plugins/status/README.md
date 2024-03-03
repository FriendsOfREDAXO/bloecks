# Status

Implements `online` and `offline` status for blocks so you can show or hide them on your website.  
Offline blocks are painted greyish and show a status label.

<img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_status_01.png" alt="Screenshot" style="width: 100%; max-width: 1000px; margin: 20px 0;">
<br>

## Usage

Click on the button with the __eye icon__ <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_status_eye_closed.png" alt="Eye closed" style="width: 32px;"> to set a block offline. It will be painted greyish and show a status label. Another click on the eye <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_status_eye_open.png" alt="Eye open" style="width: 32px;"> brings the block back online.

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
