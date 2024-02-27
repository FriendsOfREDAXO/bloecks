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
