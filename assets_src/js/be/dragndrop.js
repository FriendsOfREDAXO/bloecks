bloecks.addPlugin(
    'dragndrop',
    {
        init : function()
        {
            var _this = this;
            // for each rex-slices container
            $('.rex-slices:not(.is--undraggable)').each(function(i, slicewrapper)
            {
                // remove any sortable
                try {
                    $(slicewrapper).sortable('destroy');
                } catch(ev) { }

                if(!$(slicewrapper).find('.rex-slice.rex-slice-edit, .rex-slice.rex-slice-add').length)
                {
                    // only add sortables if the page is not in EDIT mode
                    _this.addSortables(slicewrapper);
                }
                else
                {
                    $(slicewrapper).addClass('is--editing');
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
                handle: '.rex-page-section>.panel>.panel-heading',
                placeholder: 'rex-slice rex-slice-placeholder',
                cancel: disabledClass,
                // containment: $(slicewrapper),
                helper: 'clone',
                items: '>.rex-slice.rex-slice-draggable',

                create: function (event, ui)
                {
                    // fix wrapper height to avoid page jumps
                    $(slicewrapper).css({
                        minHeight: $(slicewrapper).outerHeight()
                    });
                },

                start : function(event, ui)
                {
                    $(this).addClass('ui-state-sorting');

                    // refresh positions just to make sure everything is okay
                    $(this).sortable('refreshPositions');

                    // set placeholder height according to item (helper)
                    ui.placeholder.height(ui.helper.outerHeight());
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

                    // refresh items just to make sure everything is okay
                    $(this).sortable('refresh');

                    var direction = ui.position.top < ui.originalPosition.top ? 'up' : 'down';

                    var this_id = bloecks.getSliceId(ui.item),
                        prev_id = ui.item.prevAll('.rex-slice-draggable').length ? bloecks.getSliceId(ui.item.prevAll('.rex-slice-draggable').first()) : 0;

                    if(this_id !== null && prev_id !== null)
                    {
                        console.log('Move ' + this_id + ' ' + direction + ', after ' + prev_id);

                        var url = ui.item.find('[href*="direction=move' + direction + '"]').length ? ui.item.find('[href*="direction=move' + direction + '"]').first().attr('href') : null;
                        if(url !== null)
                        {
                            url = url.replace(/(&amp;|&)direction=move(up|down)/, "$1direction=move$2$1insertafter=" + prev_id);
                            url = url.replace(/content_move_slice/, "content_move_slice_to");
                            url = url.replace(/_csrf_token=[^&]+/, "_csrf_token=" + ui.item.data('csrf-token'));
                            url += '#slice' + this_id;
                        }
                        else
                        {
                            url = window.location.href;
                        }

                        console.log(url);

                        bloecks.executePjax(url);
                    }
                }
            });
        },
    }
);
