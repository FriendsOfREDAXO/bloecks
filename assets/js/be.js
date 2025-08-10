/**
 * blÖcks - REDAXO Addon for slice management
 * Vanilla JavaScript version - no jQuery dependencies
 */

// Main bloecks object
var bloecks = {
    plugins: [],

    init: function() {
        // Get clean plugins list
        var plugins = this.getPlugins(true);
        var plugins_length = plugins.length;

        for (var i = 0; i < plugins_length; i++) {
            // For each plugin - execute init routine
            this[plugins[i]].init();
        }
    },

    getSliceId: function(slice) {
        var id = null;

        if (!slice.classList.contains('rex-slice-output')) {
            var parent = slice.closest('.rex-slice-output');
            if (parent) {
                slice = parent;
            } else {
                var child = slice.querySelector('.rex-slice-output');
                if (child) {
                    slice = child;
                } else {
                    slice = null;
                }
            }
        }

        if (slice) {
            var linkWithSliceId = slice.querySelector('[href*="slice_id="]');
            if (linkWithSliceId) {
                id = parseInt(linkWithSliceId.getAttribute('href').replace(/.*slice_id=(\d+).*/, '$1'));
            } else if (slice.getAttribute('id')) {
                id = parseInt(slice.getAttribute('id').replace(/[^0-9]/g, ''));
            }
        }

        return id;
    },

    executePjax: function(url) {
        var matches = url.match(/(#[^\?\&]+)/);
        if (matches) {
            url = url.replace(/(#[^\?\&]+)/, '') + matches[0];
        }
        console.log('PJAXing ' + url);

        // Use fetch API for PJAX requests - simpler than full PJAX implementation
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-PJAX': 'true',
                'X-PJAX-Container': '#rex-js-page-main-content'
            }
        })
        .then(function(response) {
            return response.text();
        })
        .then(function(html) {
            var container = document.getElementById('rex-js-page-main-content');
            if (container) {
                container.innerHTML = html;
                // Reinitialize after content update
                bloecks.init();
            }
        })
        .catch(function(error) {
            console.error('PJAX request failed:', error);
            // Fallback to regular page reload
            window.location.href = url;
        });
    },

    getPlugins: function(initializable) {
        initializable = initializable === true;
        return this.plugins.filter(function(value) {
            // A plugin name is only valid if it's (a) a string, (b) a method exists in main js object and (c) if it contains an init() routine
            return typeof(value) === 'string' && typeof(bloecks[value]) !== 'undefined' && (!initializable || typeof(bloecks[value].init) === 'function');
        });
    },

    addPlugin: function(name, object, priority) {
        this[name] = object; // Add plugin object to main object

        // Get valid priority
        priority = parseInt(priority);
        priority = Math.max(isNaN(priority) ? 0 : priority, this.plugins.length);

        // Fill up plugins array if priority is greater than plugins array length
        if (priority > this.plugins.length) {
            this.plugins = this.plugins.concat(Array.apply(null, Array(priority - this.plugins.length)));
        }

        // Add plugin name to plugins array
        this.plugins.splice(priority, 0, name);
    }
};

// Code functionality (was mostly disabled anyway)
var bloecks_code = {
    init: function() {
        // This was disabled in the original jQuery version - keeping it disabled
        return;
    },

    insertLinebreakAtCursor: function(el) {
        var val = el.value;
        if (typeof el.selectionStart !== "undefined" && typeof el.selectionEnd !== "undefined") {
            var before = val.slice(0, el.selectionStart);
            var matches = before.match(/(\n|^)(\t+|\s+)?[^\n]+$/);
            if (matches && typeof(matches[2]) !== 'undefined') {
                this.insertTextAtCursor(el, "\n" + matches[2]);
                return false;
            }
        } else if (typeof document.selection !== "undefined" && typeof document.selection.createRange !== "undefined") {
            el.focus();
            var range = document.selection.createRange();
            range.collapse(false);
            range.select();
        }
        return true;
    },

    insertTextAtCursor: function(el, text) {
        var val = el.value;
        if (typeof el.selectionStart !== "undefined" && typeof el.selectionEnd !== "undefined") {
            var endIndex = el.selectionEnd;
            el.value = val.slice(0, el.selectionStart) + text + val.slice(endIndex);
            el.selectionStart = el.selectionEnd = endIndex + text.length;
        } else if (typeof document.selection !== "undefined" && typeof document.selection.createRange !== "undefined") {
            el.focus();
            var range = document.selection.createRange();
            range.collapse(false);
            range.text = text;
            range.select();
        }
    }
};

// Drag and drop functionality
bloecks.addPlugin('dragndrop', {
    init: function() {
        var _this = this;
        // For each rex-slices container
        var sliceContainers = document.querySelectorAll('.rex-slices:not(.is--undraggable)');
        
        sliceContainers.forEach(function(slicewrapper) {
            // Remove any existing sortable (if using a library)
            try {
                if (slicewrapper._sortable) {
                    slicewrapper._sortable.destroy();
                }
            } catch(ev) {}

            var hasEditSlices = slicewrapper.querySelector('.rex-slice.rex-slice-edit, .rex-slice.rex-slice-add');
            if (!hasEditSlices) {
                // Only add sortables if the page is not in EDIT mode
                _this.addSortables(slicewrapper);
            } else {
                slicewrapper.classList.add('is--editing');
            }
        });
    },

    markDisabledItems: function(slicewrapper, disabledClass) {
        disabledClass = typeof(disabledClass) !== 'string' ? 'ui-state-disabled' : disabledClass;

        var sliceOutputs = slicewrapper.querySelectorAll('.rex-slice-output:not(.' + disabledClass + ')');
        sliceOutputs.forEach(function(slice) {
            var moveLink = slice.querySelector('[href*="direction=move"]');
            if (!moveLink) {
                // We won't let the user move items that cannot be moved by perms etc.
                slice.classList.add(disabledClass);
            }
        });
    },

    addSortables: function(slicewrapper) {
        var disabledClass = 'ui-state-disabled';
        var _this = this;

        this.markDisabledItems(slicewrapper, disabledClass);

        // Set minimum height to avoid page jumps
        slicewrapper.style.minHeight = slicewrapper.offsetHeight + 'px';

        // Simple drag and drop implementation without external libraries
        var draggableSlices = slicewrapper.querySelectorAll('.rex-slice.rex-slice-draggable');
        
        draggableSlices.forEach(function(slice) {
            var handle = slice.querySelector('.rex-page-section > .panel > .panel-heading');
            if (handle && !slice.classList.contains(disabledClass)) {
                handle.style.cursor = 'grab';
                handle.setAttribute('draggable', 'true');
                
                handle.addEventListener('dragstart', function(e) {
                    slice.classList.add('ui-sortable-helper');
                    slicewrapper.classList.add('ui-state-sorting');
                    e.dataTransfer.setData('text/plain', '');
                    e.dataTransfer.effectAllowed = 'move';
                    
                    // Store reference to dragged element
                    slicewrapper._draggedElement = slice;
                });

                handle.addEventListener('dragend', function(e) {
                    slice.classList.remove('ui-sortable-helper');
                    slicewrapper.classList.remove('ui-state-sorting', 'ui-state-updated');
                    handle.style.cursor = 'grab';
                    
                    // Clean up
                    var placeholders = slicewrapper.querySelectorAll('.rex-slice-placeholder');
                    placeholders.forEach(function(placeholder) {
                        placeholder.remove();
                    });
                    
                    slicewrapper._draggedElement = null;
                });
            }
        });

        // Add drop zones
        draggableSlices.forEach(function(slice) {
            if (!slice.classList.contains(disabledClass)) {
                slice.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    
                    var draggedElement = slicewrapper._draggedElement;
                    if (draggedElement && draggedElement !== slice) {
                        // Create placeholder if it doesn't exist
                        var placeholder = slicewrapper.querySelector('.rex-slice-placeholder');
                        if (!placeholder) {
                            placeholder = document.createElement('div');
                            placeholder.className = 'rex-slice rex-slice-placeholder';
                            placeholder.style.height = draggedElement.offsetHeight + 'px';
                        }
                        
                        // Determine insert position
                        var rect = slice.getBoundingClientRect();
                        var midpoint = rect.top + rect.height / 2;
                        
                        if (e.clientY < midpoint) {
                            slice.parentNode.insertBefore(placeholder, slice);
                        } else {
                            slice.parentNode.insertBefore(placeholder, slice.nextSibling);
                        }
                    }
                });

                slice.addEventListener('drop', function(e) {
                    e.preventDefault();
                    var draggedElement = slicewrapper._draggedElement;
                    
                    if (draggedElement && draggedElement !== slice) {
                        slicewrapper.classList.add('ui-state-updated');
                        
                        var placeholder = slicewrapper.querySelector('.rex-slice-placeholder');
                        if (placeholder) {
                            placeholder.parentNode.insertBefore(draggedElement, placeholder);
                            placeholder.remove();
                        }
                        
                        // Calculate move direction and previous slice
                        var allSlices = Array.from(slicewrapper.querySelectorAll('.rex-slice-draggable'));
                        var draggedIndex = allSlices.indexOf(draggedElement);
                        var prevSlice = draggedIndex > 0 ? allSlices[draggedIndex - 1] : null;
                        
                        var this_id = bloecks.getSliceId(draggedElement);
                        var prev_id = prevSlice ? bloecks.getSliceId(prevSlice) : 0;
                        
                        if (this_id !== null && prev_id !== null) {
                            console.log('Move ' + this_id + ' after ' + prev_id);
                            
                            // Build URL for moving slice
                            var moveLink = draggedElement.querySelector('[href*="direction=move"]');
                            var url = null;
                            
                            if (moveLink) {
                                url = moveLink.getAttribute('href');
                                url = url.replace(/(&amp;|&)direction=move(up|down)/, "$1direction=move$2$1insertafter=" + prev_id);
                                url = url.replace(/content_move_slice/, "content_move_slice_to");
                                
                                var csrfToken = draggedElement.dataset.csrfToken;
                                if (csrfToken) {
                                    url = url.replace(/_csrf_token=[^&]+/, "_csrf_token=" + csrfToken);
                                }
                                url += '#slice' + this_id;
                            } else {
                                url = window.location.href;
                            }
                            
                            console.log(url);
                            bloecks.executePjax(url);
                        }
                    }
                });
            }
        });
    }
});

// Fragments functionality  
var bloecks_fragments = {
    init: function() {
        console.log('fragments');
        this.addToggleButtons();
    },

    addToggleButtons: function() {
        // Add event listeners to checkbox inputs
        var checkboxes = document.querySelectorAll('.bloecks--setting input[type="checkbox"][name*="[active]"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                bloecks_fragments.toggle(this);
            });
        });
    },

    toggle: function(el) {
        var on = el.checked;
        var id = el.getAttribute('id');

        var elements = document.querySelectorAll('.' + id);
        elements.forEach(function(element) {
            if (on) {
                element.classList.remove('is--hidden');
            } else {
                element.classList.add('is--hidden');
            }
        });
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        bloecks_code.init();
        bloecks_fragments.init();
    });
} else {
    bloecks_code.init();
    bloecks_fragments.init();
}

// Initialize when REDAXO is ready (rex:ready event)
document.addEventListener('rex:ready', function() {
    bloecks.init();
    
    // Initialize fragment toggles
    var checkboxes = document.querySelectorAll('.bloecks--setting input[type="checkbox"][name*="[active]"]');
    checkboxes.forEach(function(checkbox) {
        bloecks_fragments.toggle(checkbox);
    });
});