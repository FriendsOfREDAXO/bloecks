bloecks.addPlugin(
    'columns',
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

                if(!$(slicewrapper).find('.rex-slice form').length)
                {
                    // only add sortables if the page is not in EDIT mode
                    _this.addResizables(slicewrapper);
                }
                else
                {
                    $(slicewrapper).addClass('is--editing');
                }
            });
        },

        getGrid : function(slicewrapper)
        {
            return [Math.ceil($(slicewrapper).outerWidth() / 4), Math.ceil($(slicewrapper).find('.rex-slice-select').last().outerHeight())];
        },

        addResizables : function(slicewrapper)
        {
            var _this = this;

            $(slicewrapper).find('>.rex-slice.rex-slice-output').each(function(i, el){
                var handles = [];

                if(_this.getItemData(el, 'columns')['min'] != _this.getItemData(el, 'columns')['max'])
                {
                    handles.push('e');
                }
                if(_this.getItemData(el, 'rows')['min'] != _this.getItemData(el, 'rows')['max'])
                {
                    handles.push('s');
                }
                handles = handles.join(', ');

                if(handles)
                {
                    $(el).resizable({
                        containment: document.body,
                        handles : handles,
                        stop: function(event, ui)
                        {
                            var grid = $(this).resizable('option', 'grid'),
                                x = Math.round(ui.size.width / grid[0]),
                                y = Math.round(ui.size.height / grid[1]),
                                columns = _this.getItemData(this, 'columns')
                                this_id = bloecks.getSliceId(this);

                            $(this).css({
                                 width: (Math.round( (x / columns.grid) * 10000) / 100) + '%'
                            });


                            if(this_id !== null)
                            {
                                console.log('Update format of ' + this_id);
                                var url = window.location.href.replace(/page=([^&]+)/,'page=bloecks/columns/resize').replace(/(&|\?)resize[(\d+)][x]=(\d+)/g,'').replace(/(&|\?)resize[(\d+)][y]=(\d+)/g,'');
                                url+= (url.indexOf('?') > -1 ? '&' : '?') + 'resize[' + this_id + '][x]=' + x + '&resize[' + this_id + '][y]=' + y;

                                bloecks.executePjax(url);
                            }
                        },

                        resize : function( event, ui ) {
                            var grid = $(this).resizable('option', 'grid');
                            ui.size.width = Math.floor( ui.size.width / grid[0] ) * grid[0];
                            ui.size.height = Math.floor( ui.size.height / grid[1] ) * grid[1];
                        }
                    });
                }
            });

            this.updateOnResize(slicewrapper);
        },

        getItemData : function(item, type)
        {
            var ret = {},
                attr = ($(item).attr('data-bloecks-' + type) || '').split(',');

            ret.grid = typeof(attr[1]) != 'undefined' ? parseInt(attr[1]) : 1;
            ret.grid = Math.max(1, isNaN(ret.grid) ? 1 : ret.grid);

            ret.min = typeof(attr[2]) != 'undefined' ? parseInt(attr[2]) : 1;
            ret.min = Math.max(1, isNaN(ret.min) ? 1 : ret.min);
            ret.min = Math.min(ret.grid, ret.min);

            ret.max = typeof(attr[3]) != 'undefined' ? parseInt(attr[3]) : 1;
            ret.max = Math.max(ret.min, isNaN(ret.max) ? ret.min : ret.max);
            ret.max = Math.min(ret.grid, ret.max);

            ret.size = typeof(attr[0]) != 'undefined' ? parseInt(attr[0]) : ret.min;
            ret.size = Math.max(1, isNaN(ret.size) ? ret.min : ret.size);
            ret.size = Math.min(ret.size, ret.max);

            return ret;
        },

        updateOnResize : function(slicewrapper)
        {
            var _this = this,
                sizes = {
                    rows : $(slicewrapper).find('.rex-slice-select').last().outerHeight(),
                    columns : $(slicewrapper).outerWidth()
                };

            $(slicewrapper).find('>.rex-slice.rex-slice-output.ui-resizable').each(function(i, el) {
                var types = {'rows' : {}, 'columns' : {} }, options = {};

                for(var type in types)
                {
                    var attr = ($(el).attr('data-bloecks-' + type) || '').split(',');

                    types[type] = _this.getItemData(el, type);

                    types[type].grid = sizes[type] / types[type].grid;
                    types[type].min = Math.ceil(types[type].grid * types[type].min);
                    types[type].max = Math.ceil(types[type].grid * types[type].max);
                    types[type].grid = Math.floor(types[type].grid);

                    options['min' + (type == 'rows' ? 'Height' : 'Width')] = types[type].min;
                    options['max' + (type == 'rows' ? 'Height' : 'Width')] = types[type].max;
                }

                options.grid = [types.columns.grid, types.rows.grid];

                $(el).resizable('option', options);
            });
        }
    }
);
