bloecks.addPlugin(
    'status',
    {
        init : function()
        {
            $('[href*="bloecks/status/status"][href*="status="]').each(function(i, btn){
                var setStatus = $(btn).attr('href').match(/status=(\d)/),
                    sliceContainer = $(btn).parents('.rex-slice-output');

                if(sliceContainer.length && setStatus)
                {
                    setStatus = parseInt(setStatus[1]) === 0;
                    if(setStatus)
                    {
                        sliceContainer.first().removeClass('bloecks--status--inactive');
                    }
                    else
                    {
                        sliceContainer.first().addClass('bloecks--status--inactive');
                    }
                }

                $(btn).on('click.bloecks', function(e){

                    $.pjax(
                    {
                        url: $(this).attr('href'),
                        container: '#rex-js-page-main-content',
                        fragment : '#rex-js-page-main-content',
                        push : false
                    });

                    return false;
                })
            });
        }
    }
);
