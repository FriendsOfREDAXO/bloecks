var bloecks_code = {
    init: function() {
        console.log('code initialized');
        $(document).on('keydown.bloecks', 'textarea.bloecks--code', function(e) {
            bloecks_code.handleKeyDown(e, this);
        });
    },

    handleKeyDown: function(e, el) {
        console.log(`Key pressed ${e.keyCode} (and SHIFT is ${e.shiftKey ? '' : 'not '}pressed)`);
        switch(e.keyCode) {
            case 9: // Tab key
                e.preventDefault(); // Prevent the default tab behavior
                if(e.shiftKey) {
                    bloecks_code.jumpToPreviousTab(el);
                } else {
                    bloecks_code.insertTextAtCursor(el, "\t");
                }
                break;
            case 13: // Enter key
                e.preventDefault(); // Prevent the default enter key behavior
                bloecks_code.insertLinebreakAtCursor(el);
                break;
            default:
                // Handle other keys if needed
                break;
        }
    },

    insertLinebreakAtCursor: function(el) {
        var val = el.value;
        var before = val.slice(0, el.selectionStart);
        var matches = before.match(/(\n|^)(\t+|\s+)?[^\n]*$/);
        if(matches && matches[2] !== undefined) {
            this.insertTextAtCursor(el, "\n" + matches[2]);
        } else {
            this.insertTextAtCursor(el, "\n");
        }
    },

    jumpToPreviousTab: function(el) {
        var val = el.value;
        var before = val.substring(0, el.selectionStart);
        var matches = before.match(/(\t+|\s+)$/);
        if(matches && matches[0] !== undefined) {
            // Remove the last tab or spaces
            var newCaretPosition = el.selectionStart - matches[0].length;
            el.value = val.substring(0, newCaretPosition) + val.substring(el.selectionEnd);
            el.selectionStart = el.selectionEnd = newCaretPosition;
        }
    },

    insertTextAtCursor: function(el, text) {
        var val = el.value;
        var startIndex = el.selectionStart;
        var endIndex = el.selectionEnd;
        el.value = val.substring(0, startIndex) + text + val.substring(endIndex);
        var pos = startIndex + text.length;
        el.selectionStart = pos;
        el.selectionEnd = pos;
    }
}

$(document).on('ready.bloecks', function() {
    bloecks_code.init();
});
;
var bloecks_fragments = {
    init: function() {
        console.log('fragments');
        this.setupEventListeners();
    },

    setupEventListeners: function() {
        // Verwende .on() direkt für bessere Performance und Klarheit
        $(document).on('change.bloecks', '.bloecks--setting input[type="checkbox"][name*="[active]"]', function() {
            bloecks_fragments.toggle(this);
        });
    },

    toggle: function(el) {
        var $el = $(el), // Cache das jQuery Objekt für wiederholte Nutzung
            on = $el.is(':checked'),
            id = $el.attr('id'),
            $target = $('.' + id); // Vermeide wiederholte Selektion

        if (on) {
            $target.removeClass('is--hidden');
        } else {
            $target.addClass('is--hidden');
        }
    }
};

// Verwende die 'rex:ready' und 'ready.bloecks' Ereignisse für eine initiale Ausführung
$(document).on('rex:ready ready.bloecks', function() {
    bloecks_fragments.init();

    // Trigger manuell die 'change' Ereignisse, um den Anfangszustand korrekt zu setzen
    $('.bloecks--setting input[type="checkbox"][name*="[active]"]').change();
});
;
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

//# sourceMappingURL=be.js.map