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
