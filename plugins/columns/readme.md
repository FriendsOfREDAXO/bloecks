blÖcks/columns PlugIn
============================

This addon allows you to add column classes to your slices. When a page is edited, the slices will appear in a minified
version and enhanced by a resize bar (depending on configuration settings). By resizing the slice the column/grid
definition will be saved to the database.

When the slice is displayed a DIV with the CSS class of the format is wrapped around the slice.

In the configuration settings you can define

    grid :        the number of columns (default is 4)
    default :     the default size of a slice (default is 4 -> 100% width)
    min :         the minimum size of a slice (default is 1 -> 25% width)
    max :         the maximum size of a slice (default is 4 -> 100% width)

You can define exceptions from the default settings for each module depending on template, language, ctype, module or article.
Each exception is defined as a new line in the Advanced Settings box. To define a rule you can use

    clang :       followed by a clang id
    template :    followed by a template id
    ctype :       followed by a ctype id
    module :      followed by a module id
    article :     followed by a article id

To define a new setting you have to insert the value for the columns

    column_grid :     overwrites the default grid size
    default_grid :    overwrites the default default size
    column_min :      overwrites the default minimum size size
    column_max :      overwrites the default maximum size

**Examples**

    // set new grid size of 12 for all modules on pages with template id 2
    template:2,column_grid:12

    // set new grid size of 12 for all modules within ctype 3 on pages with template id 2 and
    template:2,ctype:3,column_grid:12

    // set new grid size of 12 for all modules with id of 3
    module:3,column_grid:12

    // set new grid size of 12 and minimum size of 10 for all modules with id of 3 within ctype 3
    // on pages with template id 2 and clang 1
    template:2,ctype:3,clang:1,module:3,column_grid:12,column_min:10

Rules with most matches overwrite those with fewer matches:

    // we have a slice with module id of 3 within ctype 3 on template 2

    template:2,column_grid:12                   // now grid size is 12 - 1 match
    template:2,ctype:3,column_grid:10           // now grid size is 10 - 2 matches
    template:2,ctype:3,module:3,column_grid:8   // now grid size is 8 - 3 matches
    module:3,column_grid:6                      // grid size is still 8 - only 1 match

**Output**

When the slice is displayed it automatically is wrapped by a DIV with the grid rule:

    <div class="bc--columns-[SIZE]-[GRID]"> ... </div>

That means: A slice that is 4 columns wide within a grid of 12 would be displayed as

    <div class="bc--columns-4-12"> ... </div>

If you provide a special placeholder within your module, the wrapper DIV will not be added to the
slice, instead the placeholder is replaced by the class:

    <section class="my-module {{bloecks_columns_css}}">
        <h1>My Content</h1>
    </section>

becomes

    <section class="my-module bc--columns-4-12">
        <h1>My Content</h1>
    </section>

You can define the class that is inserted yourself on the settings page - use placeholders within the definition
that will be replaced by size, grid, minimum and maximum size. If you use uppercase placeholders a word
is inserted instead of a number: [columns_size] becomes "4", [COLUMNS_SIZE] becomes "four"." Define multiple
separated by comma (,). If a class definition contains an unreplaced placeholder it is left out.

    col-md-[columns]                  // will be output as "col-md-4" (Bootstrap style)
    grid_[columns]                    // will be output as "grid_4" (960.js style)
    [COLUMNS] wide column             // will be output as "four wide column" (SemanticUi style)
    col-[columns]-of-[columns_grid]   // will be output as "col-4-of-12"
    col-[columns], some-[rows]        // will be output as "col-4 some-99"
    col-[columns], some-[bogus]       // will be output as "col-4"

**The styling of these elements is up to you!**

---
Credits
-------
* [blÖcks redaxo addon](https://github.com/FriendsOfREDAXO/bloecks)
