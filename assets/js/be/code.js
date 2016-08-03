var bloecks_code = {
    init : function()
    {
        return; 
        console.log('code');
        $(document).on({
            'keydown.bloecks' : function(e)
            {
                console.log(e);
                console.log('Key pressed ' + e.keyCode + ' (and SHIFT is ' + (e.shiftKey === true ? '' : 'not ') + 'pressed');
                switch(e.keyCode)
                {
                    case 9 :
                        if(e.shiftKey)
                        {
                            bloecks_code.jumpToPreviousTab(this);
                        }
                        else
                        {
                            bloecks_code.insertTextAtCursor(this, "\t");
                        }
                        return false;
                        break;
                    case 13 :
                        return bloecks_code.insertLinebreakAtCursor(this);
                        break;
                }
            }
        }, 'textarea.bloecks--code');
    },

    insertLinebreakAtCursor : function (el) {
        var val = el.value, endIndex, range;
        if (typeof el.selectionStart != "undefined" && typeof el.selectionEnd != "undefined")
        {
            before = val.slice(0, el.selectionStart);
            matches = before.match(/(\n|^)(\t+|\s+)?[^\n]+$/);
            console.log(matches);
            if(matches && typeof(matches[2]) != 'undefined')
            {
                this.insertTextAtCursor(el, "\n" + matches[2]);
                return false;
            }
        }
        else if (typeof document.selection != "undefined" && typeof document.selection.createRange != "undefined")
        {
            el.focus();
            range = document.selection.createRange();
            range.collapse(false);
            console.log("RANGE");
            console.log(range);
            range.select();
        }

        return true;
    },

    jumpToPreviousTab : function(el)
    {
        var val = el.value, endIndex, range;
        if (typeof el.selectionStart != "undefined" && typeof el.selectionEnd != "undefined")
        {
            before = val.slice(0, el.selectionStart);

            matches = before.match(/(\n|^)(.*)[^\n]+$/);
            console.log(matches);
        }
        else if (typeof document.selection != "undefined" && typeof document.selection.createRange != "undefined")
        {
            el.focus();
            range = document.selection.createRange();
            range.collapse(false);
            console.log("RANGE");
            console.log(range);
            range.select();
        }
    },

    insertTextAtCursor : function (el, text) {
        var val = el.value, endIndex, range;
        if (typeof el.selectionStart != "undefined" && typeof el.selectionEnd != "undefined")
        {
            endIndex = el.selectionEnd;
            el.value = val.slice(0, el.selectionStart) + text + val.slice(endIndex);
            el.selectionStart = el.selectionEnd = endIndex + text.length;
        }
        else if (typeof document.selection != "undefined" && typeof document.selection.createRange != "undefined")
        {
            el.focus();
            range = document.selection.createRange();
            range.collapse(false);
            range.text = text;
            range.select();
        }
    }
}

$(document).on('ready.bloecks', $.proxy(bloecks_code.init, bloecks_code));
