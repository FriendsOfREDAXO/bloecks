bloecks.addPlugin('dragndrop', {
    init: function() {
        // Verwende const für unveränderliche Variablen
        const _this = this;
        
        // Durchlaufe alle rex-slices Container, die nicht unverschiebbar sind
        $('.rex-slices:not(.is--undraggable)').each(function(i, slicewrapper) {
            // Entferne jegliche Sortierbarkeit
            try {
                $(slicewrapper).sortable('destroy');
            } catch (ev) { /* Ignoriere Fehler */ }

            if (!$(slicewrapper).find('.rex-slice.rex-slice-edit, .rex-slice.rex-slice-add').length) {
                // Füge Sortierbarkeit hinzu, wenn die Seite nicht im Bearbeitungsmodus ist
                _this.addSortables(slicewrapper);
            } else {
                $(slicewrapper).addClass('is--editing');
            }
        });
    },

    markDisabledItems: function(slicewrapper, disabledClass = 'ui-state-disabled') {
        // Markiere Elemente, die nicht verschoben werden können
        $(slicewrapper).find('.rex-slice-output:not(.' + disabledClass + ')').each(function(j, slice) {
            if (!$(slice).find('[href*="direction=move"]').length) {
                $(slice).addClass(disabledClass);
            }
        });
    },

    addSortables: function(slicewrapper) {
        const disabledClass = 'ui-state-disabled';

        this.markDisabledItems(slicewrapper, disabledClass);

        $(slicewrapper).sortable({
            appendTo: document.body,
            handle: '.rex-page-section>.panel>.panel-heading',
            placeholder: 'rex-slice rex-slice-placeholder',
            cancel: '.' + disabledClass,
            helper: 'clone',
            items: '>.rex-slice.rex-slice-draggable',

            create: function(event, ui) {
                // Fixiere Wrapper-Höhe, um Seitensprünge zu vermeiden
                $(slicewrapper).css({
                    minHeight: $(slicewrapper).outerHeight()
                });
            },

            start: function(event, ui) {
                $(this).addClass('ui-state-sorting');
                $(this).sortable('refreshPositions');
                ui.placeholder.height(ui.helper.outerHeight());
            },

            stop: function(event, ui) {
                if (!$(this).hasClass('ui-state-updated')) {
                    $(this).removeClass('ui-state-sorting');
                }
            },

            update: function(event, ui) {
                $(this).addClass('ui-state-updated');
                $(this).sortable('refresh');

                const direction = ui.position.top < ui.originalPosition.top ? 'up' : 'down';
                const this_id = bloecks.getSliceId(ui.item);
                const prev_id = ui.item.prevAll('.rex-slice-draggable').length ? bloecks.getSliceId(ui.item.prevAll('.rex-slice-draggable').first()) : 0;

                if (this_id !== null && prev_id !== null) {
                    let url = ui.item.find(`[href*="direction=move${direction}"]`).length ? ui.item.find(`[href*="direction=move${direction}"]`).first().attr('href') : null;
                    if (url !== null) {
                        url = url.replace(/(&amp;|&)direction=move(up|down)/, `$1direction=move$2$1insertafter=${prev_id}`);
                        url = url.replace(/content_move_slice/, "content_move_slice_to");
                        url = url.replace(/_csrf_token=[^&]+/, `_csrf_token=${ui.item.data('csrf-token')}`);
                        url += `#slice${this_id}`;
                    } else {
                        url = window.location.href;
                    }

                    bloecks.executePjax(url);
                }
            }
        });
    },
});

//# sourceMappingURL=be.js.map