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
