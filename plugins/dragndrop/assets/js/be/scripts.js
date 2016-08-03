bloecks.addPlugin(
    'dragndrop',
    {
        init : function()
        {
            var _this = this;
            // for each rex-slices container
            $('.rex-slices').each(function(i, slicewrapper)
            {
                // remove any sortable
                try {
                    $(slicewrapper).sortable('destroy');
                } catch(ev) { }

                if(!$(slicewrapper).find('.rex-slice.rex-slice-edit').length)
                {
                    // only add sortables if the page is not in EDIT mode
                    _this.addSortables(slicewrapper);
                }
            });
        },

        markDisabledItems : function(slicewrapper, disabledClass)
        {
            disabledClass = typeof(disabledClass) != 'string' ? 'ui-state-disabled' : disabledClass;

            $(slicewrapper).find('.rex-slice-output:not(.' + disabledClass + ')').each(function(j, slice)
            {
                if(!$(slice).find('[href*="direction=move"]').length)
                {
                    // we won't let the user move items that cannot be moved by perms etc.
                    $(slice).addClass(disabledClass);
                }
            });
        },

        addSortables : function(slicewrapper)
        {
            var disabledClass = 'ui-state-disabled';

            this.markDisabledItems(slicewrapper, disabledClass);

            $(slicewrapper).sortable({
                appendTo: document.body,
                handle: '.panel-heading',
                placeholder: 'rex-slice rex-slice-placeholder',
                cancel: disabledClass,
                containment: $(slicewrapper),
                items: '>.rex-slice.rex-slice-output',

                start : function(event, ui)
                {
                    $(this).addClass('ui-state-sorting');

                    ui.placeholder.css({
                        height : ui.item.outerHeight(),
                        width : ui.item.outerWidth() - 1
                    });
                },

                stop : function(event, ui)
                {
                    if(!$(this).hasClass('ui-state-updated'))
                    {
                        $(this).removeClass('ui-state-sorting');
                    }
                },

                update : function(event, ui)
                {
                    $(this).addClass('ui-state-updated');

                    var this_id = bloecks.getSliceId(ui.item),
                        prev_id = ui.item.prevAll('.rex-slice.rex-slice-output').length ? bloecks.getSliceId(ui.item.prevAll('.rex-slice.rex-slice-output').first()) : 0;

                    if(this_id !== null && prev_id !== null)
                    {
                        console.log('Update prio of ' + this_id + ' and move it after ' + prev_id);
                        var url = window.location.href.replace(/page=([^&]+)/,'page=bloecks/dragndrop/move').replace(/(&|\?)move[(\d+)][prev]=(\d+)/g,'');
                        url+= (url.indexOf('?') > -1 ? '&' : '?') + 'move[' + this_id + '][prev]=' + prev_id;

                        bloecks.executePjax(url);
                    }
                }
            });
        },
    }
);
