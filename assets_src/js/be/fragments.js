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
