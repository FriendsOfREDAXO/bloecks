var bloecks_code = {
    init : function()
    {
        return; 
        console.log('code');
        $(document).on({
            'keydown.bloecks' : function(e)
            {
                console.log(e);
                console.log('Key pressed ' + e.keyCode + ' (and SHIFT is ' + (e.shiftKey === true ? '' : 'not ') + 'pressed');
                switch(e.keyCode)
                {
                    case 9 :
                        if(e.shiftKey)
                        {
                            bloecks_code.jumpToPreviousTab(this);
                        }
                        else
                        {
                            bloecks_code.insertTextAtCursor(this, "\t");
                        }
                        return false;
                        break;
                    case 13 :
                        return bloecks_code.insertLinebreakAtCursor(this);
                        break;
                }
            }
        }, 'textarea.bloecks--code');
    },

    insertLinebreakAtCursor : function (el) {
        var val = el.value, endIndex, range;
        if (typeof el.selectionStart != "undefined" && typeof el.selectionEnd != "undefined")
        {
            before = val.slice(0, el.selectionStart);
            matches = before.match(/(\n|^)(\t+|\s+)?[^\n]+$/);
            console.log(matches);
            if(matches && typeof(matches[2]) != 'undefined')
            {
                this.insertTextAtCursor(el, "\n" + matches[2]);
                return false;
            }
        }
        else if (typeof document.selection != "undefined" && typeof document.selection.createRange != "undefined")
        {
            el.focus();
            range = document.selection.createRange();
            range.collapse(false);
            console.log("RANGE");
            console.log(range);
            range.select();
        }

        return true;
    },

    jumpToPreviousTab : function(el)
    {
        var val = el.value, endIndex, range;
        if (typeof el.selectionStart != "undefined" && typeof el.selectionEnd != "undefined")
        {
            before = val.slice(0, el.selectionStart);

            matches = before.match(/(\n|^)(.*)[^\n]+$/);
            console.log(matches);
        }
        else if (typeof document.selection != "undefined" && typeof document.selection.createRange != "undefined")
        {
            el.focus();
            range = document.selection.createRange();
            range.collapse(false);
            console.log("RANGE");
            console.log(range);
            range.select();
        }
    },

    insertTextAtCursor : function (el, text) {
        var val = el.value, endIndex, range;
        if (typeof el.selectionStart != "undefined" && typeof el.selectionEnd != "undefined")
        {
            endIndex = el.selectionEnd;
            el.value = val.slice(0, el.selectionStart) + text + val.slice(endIndex);
            el.selectionStart = el.selectionEnd = endIndex + text.length;
        }
        else if (typeof document.selection != "undefined" && typeof document.selection.createRange != "undefined")
        {
            el.focus();
            range = document.selection.createRange();
            range.collapse(false);
            range.text = text;
            range.select();
        }
    }
}

$(document).on('ready.bloecks', $.proxy(bloecks_code.init, bloecks_code));
;
var bloecks_fragments = {
    init : function()
    {
        console.log('fragments');
        this.addToggleButtons();
    },

    addToggleButtons : function()
    {
        $(document).on({
            'change.bloecks' : function(e)
            {
                bloecks_fragments.toggle(this);
            }
        }, '.bloecks--setting input[type="checkbox"][name*="[active]"]');
    },

    toggle : function(el)
    {
        var on = $(el).is(':checked'),
            id = $(el).attr('id');

        if(on)
        {
            $('.' + id).removeClass('is--hidden');
        }
        else
        {
            $('.' + id).addClass('is--hidden');
        }
    }
}

$(document).on('ready.bloecks', $.proxy(bloecks_fragments.init, bloecks_fragments));
$(document).on('rex:ready', function(e){
    $('.bloecks--setting input[type="checkbox"][name*="[active]"]').each(function(i, el){
        bloecks_fragments.toggle(el);
    });
});
;
var bloecks = {

    plugins : [],

    init : function()
    {
        // get clean plugins list
        var plugins = this.getPlugins(true),
            plugins_length = plugins.length;

        for(var i = 0; i < plugins_length; i++)
        {
            // for each plugin - execute init routine
            this[plugins[i]].init();
        }
    },

    getSliceId : function(slice)
    {
        var id = null;

        if(!$(slice).is('.rex-slice-output'))
        {
            if($(slice).parents('.rex-slice-output').length)
            {
                slice = $(slice).parents('.rex-slice-output').first();
            }
            else if($(slice).find('.rex-slice-output').length == 1)
            {
                slice = $(slice).find('.rex-slice-output').first();
            }
            else
            {
                slice = null;
            }
        }

        if(slice)
        {
            if($(slice).find('[href*="slice_id="]').length)
            {
                id = parseInt($(slice).find('[href*="slice_id="]').first().attr('href').replace(/.*slice_id=(\d+).*/,'$1'));
            }
            else if($(slice).attr('id'))
            {
                id = parseInt($(slice).attr('id').replace(/[^0-9]/g, ''))
            }
        }

        return id;
    },

    executePjax : function(url)
    {
        var matches = url.match(/(#[^\?\&]+)/);
        if(matches)
        {
            url = url.replace(/(#[^\?\&]+)/, '') + matches[0];
        }
        console.log('PJAXing ' + url);

        $.pjax(
        {
            url: url,
            container: '#rex-js-page-main-content',
            fragment : '#rex-js-page-main-content',
            push : false
        });
    },

    getPlugins : function(initializable)
    {
        initializable = initializable === true;
        return this.plugins.filter(function(value){
            // a plugin name is only valid if it's (a) a string, (b) a method exists in main js object and (c) if it contains an init() routine
            return typeof(value) == 'string' && typeof(bloecks[value]) != 'undefined' && (!initializable || typeof(bloecks[value].init) == 'function');
        });

    },

    addPlugin : function(name, object, priority)
    {
        this[name] = object; // add plugin object to main object

        // get valid priority
        priority = parseInt(priority);
        priority = Math.max(isNaN(priority) ? 0 : priority, this.plugins.length);

        // fill up plugins array if priority is greater than plugins array length
        if(priority > this.plugins.length)
        {
            this.plugins = this.plugins.concat(Array.apply(null, Array(priority - this.plugins.length)));
        }

        // add plugin name to plugins array
        this.plugins.splice(priority, 0, name);
    }
}

$(document).on('rex:ready', $.proxy(bloecks.init, bloecks));
