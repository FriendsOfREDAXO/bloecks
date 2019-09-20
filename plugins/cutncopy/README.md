# Cut & Copy

Content blocks of an article can be cut or copied to paste into other articles.

<img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_cutncopy_01.png" alt="Screenshot" style="width: 100%; max-width: 1000px; margin: 20px 0;">
<br>

## Usage

First click on the button with the __documents icon__ <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_cutncopy_copy.png" alt="Documents" style="width: 32px;"> (Copy) or on the button with the __scissors icon__ <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_cutncopy_cut.png" alt="Scissors" style="width: 32px;"> (Cut) to save a block to the clipboard. If it has been cut, the block will be deleted here later when it is inserted elsewhere.

Blocks in the clipboard change the color of their buttons to blue to indicate whether they have been saved by __copying__ <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_cutncopy_copy_active.png" alt="Documents" style="width: 32px;"> or __cutting__ <img src="https://raw.githubusercontent.com/FriendsOfREDAXO/bloecks/assets/bloecks_cutncopy_cut_active.png" alt="Scissors" style="width: 32px;">. A block remains in the clipboard until it is either pasted into an article or another block is saved to the clipboard.

To insert a saved block into an article, use REDAXO’s »__Add slice__« menu and select the saved block at the top of the list.

## User permissions

Users must either be administrators or have assigned the permission `bloecks[cutncopy]` (»Copy blocks«) to change the status of a block.

## Extension Points

| EP                      | Description                      |
|-------------------------|----------------------------------|
| `SLICE_COPIED`          | Is called after a block has been copied to the clipboard |
| `SLICE_CUT`             | Is called after a block has been copied to the clipboard to be cut from the current article |
| `SLICE_INSERTED`        | Is called after a block has been pasted into the current article |
