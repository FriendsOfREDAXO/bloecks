var bloecks = {
    plugins: [],

    init: function() {
        // Iterate through plugins using a more efficient loop
        this.getPlugins(true).forEach(function(plugin) {
            if (bloecks[plugin] && typeof bloecks[plugin].init === 'function') {
                bloecks[plugin].init();
            }
        });
    },

    getSliceId: function(slice) {
        let id = null;
        let $slice = $(slice);

        // Optimize DOM traversal and condition checks
        if (!$slice.is('.rex-slice-output')) {
            $slice = $slice.closest('.rex-slice-output');
        }

        if ($slice.length) {
            let href = $slice.find('[href*="slice_id="]').first().attr('href');
            if (href) {
                id = parseInt(href.match(/slice_id=(\d+)/)[1], 10);
            } else if ($slice.attr('id')) {
                id = parseInt($slice.attr('id').replace(/\D/g, ''), 10);
            }
        }

        return id;
    },

    executePjax: function(url) {
        // Simplify URL manipulation
        let cleanUrl = url.replace(/(#[^\?\&]+)/, '');
        let hash = url.match(/(#[^\?\&]+)/) ? url.match(/(#[^\?\&]+)/)[0] : '';
        let finalUrl = cleanUrl + hash;

        console.log('PJAXing ' + finalUrl);

        $.pjax({
            url: finalUrl,
            container: '#rex-js-page-main-content',
            fragment: '#rex-js-page-main-content',
            push: false
        });
    },

    getPlugins: function(initializable) {
        return this.plugins.filter(function(plugin) {
            return typeof plugin === 'string' &&
                   typeof bloecks[plugin] !== 'undefined' &&
                   (!initializable || typeof bloecks[plugin].init === 'function');
        });
    },

    addPlugin: function(name, object, priority) {
        this[name] = object;

        priority = isNaN(parseInt(priority)) ? this.plugins.length : Math.max(parseInt(priority), 0);

        // Ensure the plugins array is large enough
        while (this.plugins.length < priority) {
            this.plugins.push(undefined);
        }

        this.plugins.splice(priority, 0, name);
    }
}

$(document).on('rex:ready', function() { bloecks.init(); });
