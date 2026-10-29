/**
 * BLOECKS - REDAXO Backend Drag & Drop System with Wrapper Support
 * Based on SortableJS for modern drag & drop functionality
 * Now supports slice_columns-style wrappers and toast notifications
 */

var BLOECKS = (function($) {
    'use strict';
    
    var sortableInstances = [];
    
    // Toast notification system
    var toastContainer = null;
    var toastCounter = 0;
    var processedMessages = new Set(); // Track processed messages to prevent duplicates
    
    function createToastContainer() {
        if (toastContainer) {
            return toastContainer;
        }
        
        toastContainer = document.createElement('div');
        toastContainer.className = 'bloecks-toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            pointer-events: none;
        `;
        document.body.appendChild(toastContainer);
        return toastContainer;
    }
    
    function showToast(message, type, duration) {
        type = type || 'success';
        // Shorter default durations, longer for warnings/errors
        if (!duration) {
            duration = (type === 'warning' || type === 'error') ? 4000 : 2500;
        }
        
        var toastId = 'bloecks-toast-' + (++toastCounter);
        return showToastWithId(message, type, duration, toastId);
    }
    
    function showToastWithId(message, type, duration, toastId) {
        type = type || 'success';
        duration = duration || 4000;
        
        var container = createToastContainer();
        var toast = document.createElement('div');
        
        toast.id = toastId;
        // Use Bootstrap alert classes for styling
        var alertClass = type === 'error' ? 'alert-danger' : 'alert-' + type;
        toast.className = 'bloecks-toast ' + alertClass;
        toast.style.cssText = `
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: auto;
            cursor: pointer;
            max-width: 500px;
            min-width: 350px;
            word-wrap: break-word;
            position: relative;
            margin-bottom: 12px;
        `;
        
        toast.innerHTML = `
            <button type="button" class="close" onclick="BLOECKS.closeToast('${toastId}')" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            ${message}
        `;
        
        // Insert at the beginning instead of appending to prevent jumping
        if (container.firstChild) {
            container.insertBefore(toast, container.firstChild);
        } else {
            container.appendChild(toast);
        }
        
        // Trigger animation
        setTimeout(function() {
            toast.style.transform = 'scale(1)';
            toast.style.opacity = '1';
        }, 10);
        
        // Auto remove
        setTimeout(function() {
            removeToast(toastId);
        }, duration);
        
        // Click to close
        toast.addEventListener('click', function() {
            removeToast(toastId);
        });
        
        // Store type in dataset for scroll listener
        toast.setAttribute('data-toast-type', type);
        
        return toastId; // Return the ID so it can be removed later
    }
    
    function removeToast(toastId) {
        var toast = document.getElementById(toastId);
        if (toast) {
            toast.style.transform = 'scale(0.8)';
            toast.style.opacity = '0';
            setTimeout(function() {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }
    }
    
    function closeToast(toastId) {
        removeToast(toastId);
    }
    
    // Remove success/info toasts on scroll (keep warnings and errors)
    var lastScrollTime = 0;
    function attachScrollListener() {
        var scrollTimeout;
        
        window.addEventListener('scroll', function() {
            // Debounce scroll event
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                var now = Date.now();
                // Only process every 200ms
                if (now - lastScrollTime < 200) {
                    return;
                }
                lastScrollTime = now;
                
                // Remove all success/info toasts, keep warnings and errors
                if (toastContainer) {
                    var toasts = toastContainer.querySelectorAll('.bloecks-toast');
                    toasts.forEach(function(toast) {
                        var toastType = toast.getAttribute('data-toast-type');
                        // Only remove success and info toasts on scroll
                        if (toastType === 'success' || toastType === 'info') {
                            var toastId = toast.id;
                            if (toastId) {
                                removeToast(toastId);
                            }
                        }
                    });
                }
            }, 100);
        }, { passive: true });
    }
    
    function initDragDrop() {
        // Check if drag & drop is enabled in PHP config
        var bloecksConfig = rex.bloecks || {};
        if (!bloecksConfig.enable_drag_drop) {
            return;
        }
        
        // Find the parent container that holds all wrapper elements
        var mainContainer = document.body;
        
        // Check if we have drag elements
        var dragElements = mainContainer.querySelectorAll('.bloecks-dragdrop');
        
        if (dragElements.length === 0) {
            return;
        }
        
        // Find the common parent of all drag elements
        var commonParent = dragElements[0].parentNode;
        while (commonParent && commonParent !== document.body) {
            var allInParent = true;
            dragElements.forEach(function(el) {
                if (!commonParent.contains(el)) {
                    allInParent = false;
                }
            });
            if (allInParent) break;
            commonParent = commonParent.parentNode;
        }
        
        try {
            var sortable = Sortable.create(commonParent, {
                draggable: '.bloecks-dragdrop',
                // Nur der eigene Kopf des Slices -- nicht Panels im Modul-Inhalt (MForm, MBlock, Gridblock ...)
                handle: '.rex-slice-output > .rex-page-section > .panel > .panel-heading',
                filter: 'a, button, input, select, textarea, .btn, .dropdown-toggle, [data-toggle], [data-bs-toggle]',
                preventOnFilter: false,
                animation: 150,
                ghostClass: 'bloecks-sortable-ghost',
                chosenClass: 'bloecks-sortable-chosen',
                dragClass: 'bloecks-dragging',
                
                onStart: function(evt) {
                    evt.item.classList.add('bloecks-dragging');
                    document.body.classList.add('bloecks-drag-active');
                    
                    // Hide all open dropdowns immediately
                    var openDropdowns = document.querySelectorAll('.dropdown.open, .dropdown.show');
                    openDropdowns.forEach(function(dropdown) {
                        dropdown.classList.remove('open', 'show');
                        dropdown.style.display = 'none';
                    });
                },
                
                onEnd: function(evt) {
                    evt.item.classList.remove('bloecks-dragging');
                    document.body.classList.remove('bloecks-drag-active');
                    
                    // Flash effect on drop
                    if (evt.oldIndex !== evt.newIndex) {
                        evt.item.classList.add('bloecks-dropped-flash');
                        setTimeout(function() {
                            evt.item.classList.remove('bloecks-dropped-flash');
                        }, 600);
                    }
                    
                    // Re-enable dropdowns after a short delay
                    setTimeout(function() {
                        var hiddenDropdowns = document.querySelectorAll('.rex-slice-select > .dropdown[style*="display: none"]');
                        hiddenDropdowns.forEach(function(dropdown) {
                            dropdown.style.display = '';
                        });
                    }, 100);
                    
                    if (evt.oldIndex !== evt.newIndex) {
                        updateSliceOrder(evt);
                    }
                }
            });
            
            sortableInstances.push(sortable);
            
        } catch (error) {
            console.error('BLOECKS: Error creating sortable:', error);
        }
    }
    
    function updateSliceOrder(evt) {
        var draggedElement = evt.item;
        var sliceId = draggedElement.getAttribute('data-slice-id');
        var articleId = draggedElement.getAttribute('data-article-id');
        var clangId = draggedElement.getAttribute('data-clang-id');
        
        if (!sliceId || !articleId || !clangId) {
            console.error('BLOECKS: Missing data attributes for slice reorder');
            return;
        }
        
        // Get all slice IDs in new order from wrapper elements
        var sliceOrder = [];
        var containers = document.querySelectorAll('.bloecks-dragdrop');
        containers.forEach(function(container) {
            var id = container.getAttribute('data-slice-id');
            if (id) {
                sliceOrder.push(id);
            }
        });
        
        // AJAX request to update order using rex_api_bloecks
        $.ajax({
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            data: {
                'rex-api-call': 'bloecks',
                'function': 'update_order',
                'article': articleId,
                'clang': clangId,
                'order': JSON.stringify(sliceOrder)
            },
            success: function(response) {
                // Show success toast instead of relying on page reload
                BLOECKS.showToast('Slice-Reihenfolge aktualisiert', 'success', 3000);
                
                // Just reinitialize without page reload for better UX
                setTimeout(function() {
                    destroy();
                    initDragDrop();
                }, 100);
            },
            error: function(xhr, status, error) {
                console.error('BLOECKS: Failed to update order', error);
                BLOECKS.showToast('Fehler beim Aktualisieren der Reihenfolge', 'error', 5000);
                // Revert visual changes on error
                evt.to.insertBefore(evt.item, evt.to.children[evt.oldIndex]);
            }
        });
    }
    
    function destroy() {
        sortableInstances.forEach(function(sortable) {
            if (sortable && typeof sortable.destroy === 'function') {
                sortable.destroy();
            }
        });
        sortableInstances = [];
        
        // Remove initialization markers
        document.querySelectorAll('.bloecks-initialized').forEach(function(el) {
            el.classList.remove('bloecks-initialized');
        });
        
        // Don't clear processed messages - keep them to prevent duplicates across page reloads
        // processedMessages.clear();
    }
    
    // Check for BLOECKS messages and show as toasts - now with duplicate prevention
    function checkForMessages() {
        // Look for BLOECKS success/warning/error messages in rex-page-main-content
        var mainContent = document.getElementById('rex-page-main-content');
        if (!mainContent) {
            mainContent = document.body;
        }
        
        // Function to process only BLOECKS-owned alerts with duplicate prevention
        function processAlerts(selector, type, duration) {
            duration = duration || 4000;
            // Only select alerts that were explicitly marked as BLOECKS-owned by the PHP backend.
            // This prevents accidentally converting alerts from other AddOns into toasts.
            // Each base selector must be qualified individually: concatenating a
            // comma-containing selector (e.g. '.alert-danger, .alert-error') would leave
            // the first group unqualified and match every foreign alert on the page.
            var baseSelectors = (typeof selector === 'string') ? selector.split(',') : selector;
            var bloecksSelector = baseSelectors.map(function(sel) {
                sel = sel.trim();
                return sel + '.bloecks-message, ' + sel + '[data-bloecks-message="1"]';
            }).join(', ');
            var alerts = mainContent.querySelectorAll(bloecksSelector);
            
            alerts.forEach(function(alert) {
                // Skip if already processed (marked)
                if (alert.hasAttribute('data-bloecks-processed')) {
                    return;
                }
                
                var text = alert.textContent.trim();
                
                // Create unique identifier for this message based on content and timestamp window
                var timestamp = Math.floor(Date.now() / 1000); // 1 second window
                var messageId = type + ':' + text + ':' + timestamp;
                
                // Skip if we've already processed this exact message in the last second
                if (processedMessages.has(messageId)) {
                    alert.setAttribute('data-bloecks-processed', 'true');
                    alert.style.display = 'none';
                    return;
                }
                
                // Mark as processed
                processedMessages.add(messageId);
                alert.setAttribute('data-bloecks-processed', 'true');

                // Show toast
                showToast(text, type, duration);

                // Hide the original alert
                alert.style.display = 'none';

                // Clean up old messages from Set after 2 seconds to prevent memory leak
                setTimeout(function() {
                    processedMessages.delete(messageId);
                }, 2000);
            });
        }
        
        // Process different types of alerts with shorter durations
        processAlerts('.alert-success', 'success', 2500);
        processAlerts('.alert-warning', 'warning', 4000);
        processAlerts('.alert-danger, .alert-error', 'error', 5000);
    }
    
    // Check for scroll target after page reload
    function checkForScrollTarget() {
        var scrollTarget = sessionStorage.getItem('bloecks_scroll_target');
        var scrollPosition = sessionStorage.getItem('bloecks_scroll_position');
        
        if (scrollTarget) {
            // Clear the target immediately to prevent repeated scrolling
            sessionStorage.removeItem('bloecks_scroll_target');
            
            // Use shorter delays for PJAX-optimized system
            var delay = 200; // Fixed short delay for PJAX
            
            setTimeout(function() {
                var targetSlice = findSliceById(scrollTarget);
                
                if (targetSlice) {
                    scrollToSlice(targetSlice);
                } else {
                    
                    var allSlices = document.querySelectorAll('.rex-slice, .bloecks-dragdrop, .panel, form');
                    
                    for (var i = 0; i < allSlices.length; i++) {
                        var slice = allSlices[i];
                        var sliceIdInput = slice.querySelector('input[name="slice_id"]');
                        var sliceId = sliceIdInput ? sliceIdInput.value : 'no-id';
                        // Debug info for slice
                    }
                }
            }, delay);
        } else if (scrollPosition) {
            // Restore scroll position für copy/cut operations
            sessionStorage.removeItem('bloecks_scroll_position');
            
            setTimeout(function() {
                window.scrollTo(0, parseInt(scrollPosition, 10));
            }, 100); // Shorter delay for position restore
        }
    }
    
    function findSliceById(sliceId) {
        
        // First, try to find slice_id input fields (most reliable in REDAXO)
        var sliceInputs = document.querySelectorAll('input[name="slice_id"]');
        
        for (var i = 0; i < sliceInputs.length; i++) {
            var input = sliceInputs[i];
            if (input.value == sliceId) {
                // Find the parent slice container
                var sliceContainer = input.closest('.rex-slice, .panel, .bloecks-dragdrop, form');
                if (sliceContainer) {
                    return sliceContainer;
                }
                return input.parentNode;
            }
        }
        
        // Try other selectors as fallback
        var selectors = [
            '#slice' + sliceId,
            '[data-slice-id="' + sliceId + '"]',
            '.rex-slice[data-slice-id="' + sliceId + '"]',
            '.bloecks-dragdrop[data-slice-id="' + sliceId + '"]'
        ];
        
        for (var j = 0; j < selectors.length; j++) {
            var element = document.querySelector(selectors[j]);
            if (element) {
                return element;
            }
        }
        
        return null;
    }
    
    function scrollToSlice(sliceElement) {
        if (!sliceElement) {
            return;
        }
        
        // Make sure the element is visible and rendered
        if (sliceElement.offsetParent === null) {
            return;
        }
        
        // Scroll to the slice element with some offset
        setTimeout(function() {
            try {
                var rect = sliceElement.getBoundingClientRect();
                var targetPosition = rect.top + window.pageYOffset - 100; // 100px offset from top
                
                
                // Make sure we have a valid position
                if (targetPosition < 0) targetPosition = 0;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                
                // Find the .rex-slice element inside the wrapper for animation
                var rexSliceElement = sliceElement.querySelector('.rex-slice');
                if (!rexSliceElement) {
                    // If no .rex-slice found, check if the element itself is .rex-slice
                    if (sliceElement.classList.contains('rex-slice')) {
                        rexSliceElement = sliceElement;
                    }
                }
                
                if (rexSliceElement) {
                    
                    // Enhanced highlight effect with zoom and glow on .rex-slice
                    rexSliceElement.style.transition = 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                    rexSliceElement.style.transform = 'scale(1.02)';
                    rexSliceElement.style.boxShadow = '0 8px 32px rgba(40, 167, 69, 0.4), 0 0 0 3px rgba(40, 167, 69, 0.3)';
                    rexSliceElement.style.backgroundColor = 'rgba(40, 167, 69, 0.08)';
                    rexSliceElement.style.borderRadius = '8px';
                    rexSliceElement.style.position = 'relative';
                    rexSliceElement.style.zIndex = '10';
                    
                    // First phase: zoom in and glow (400ms)
                    setTimeout(function() {
                        rexSliceElement.style.transform = 'scale(1.01)';
                        rexSliceElement.style.boxShadow = '0 6px 24px rgba(40, 167, 69, 0.3), 0 0 0 2px rgba(40, 167, 69, 0.2)';
                    }, 200);
                    
                    // Second phase: start fading (after 800ms)
                    setTimeout(function() {
                        rexSliceElement.style.transition = 'all 0.8s ease-out';
                        rexSliceElement.style.transform = 'scale(1)';
                        rexSliceElement.style.boxShadow = '0 2px 8px rgba(40, 167, 69, 0.1)';
                        rexSliceElement.style.backgroundColor = 'rgba(40, 167, 69, 0.02)';
                    }, 800);
                    
                    // Final phase: complete fade out (after 1.6s)
                    setTimeout(function() {
                        rexSliceElement.style.transform = '';
                        rexSliceElement.style.boxShadow = '';
                        rexSliceElement.style.backgroundColor = '';
                        rexSliceElement.style.borderRadius = '';
                        rexSliceElement.style.position = '';
                        rexSliceElement.style.zIndex = '';
                        // Reset transition after animation
                        setTimeout(function() {
                            rexSliceElement.style.transition = '';
                        }, 800);
                    }, 1600);
                } else {
                }
                
            } catch (e) {
                console.error('Error during scrolling:', e);
            }
        }, 50);
    }
    
    // Central initialization function to prevent multiple event handler registration
    var isInitialized = false;
    var lastMessageCheck = 0;
    var scrollListenerAttached = false;
    
    function initializeOnce() {
        if (isInitialized) {
            return;
        }
        
        isInitialized = true;
        
        // Initialize drag & drop only once
        initDragDrop();
        
        // Check for messages only if not checked recently (debounce)
        var now = Date.now();
        if (now - lastMessageCheck > 500) {
            lastMessageCheck = now;
            checkForMessages();
        }
        
        // Initialize copy/paste handlers
        initCopyPasteHandlers();
        
        // Attach scroll listener once
        if (!scrollListenerAttached) {
            attachScrollListener();
            scrollListenerAttached = true;
        }
    }
    
    // Reinitialize after PJAX - but control the frequency
    function reinitializeAfterPjax() {
        if (window.location.href.includes('page=content')) {
            // Reset initialization flag to allow re-init
            isInitialized = false;
            
            destroy();
            setTimeout(function() {
                initializeOnce();
                checkForScrollTarget();
                // Button states will be updated via loadMultiClipboardFromServer()
                
                // Check for messages again after PJAX with debouncing
                var now = Date.now();
                if (now - lastMessageCheck > 500) {
                    lastMessageCheck = now;
                    setTimeout(checkForMessages, 200);
                }
            }, 100);
        }
    }
    
    // Reinitialize after PJAX requests
    $(document).on('rex:ready', function() {
        reinitializeAfterPjax();
    });
    
    // Also listen for pjax:end as backup
    $(document).on('pjax:end', function() {
        reinitializeAfterPjax();
    });
    
    // Listen specifically for PJAX events on the main content container
    $(document).on('pjax:success', '#rex-js-page-main-content', function(event) {
        if (window.location.href.includes('page=content')) {
            setTimeout(function() {
                checkForScrollTarget();
            }, 250);
        }
    });
    
    // Listen for PJAX complete event
    $(document).on('pjax:complete', '#rex-js-page-main-content', function(event) {
        reinitializeAfterPjax();
    });
    
    // Initial page load events - only run once
    $(document).ready(function() {
        if (window.location.href.includes('page=content')) {
            setTimeout(function() {
                checkForScrollTarget();
            }, 100);
        }
    });
    
    // Also listen for window load event (after all resources loaded)
    $(window).on('load', function() {
        if (window.location.href.includes('page=content')) {
            setTimeout(function() {
                checkForScrollTarget();
            }, 200);
        }
    });
    
    // Native DOM events for better compatibility
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.href.includes('page=content')) {
                setTimeout(function() {
                    checkForScrollTarget();
                }, 100);
            }
        });
    } else {
        // DOM already loaded
        if (window.location.href.includes('page=content')) {
            setTimeout(function() {
                checkForScrollTarget();
            }, 50);
        }
    }
    
    // Initialize copy/paste handlers for AJAX operations
    function initCopyPasteHandlers() {
        // Handle copy/cut buttons
        $(document).off('click', '.bloecks-copy, .bloecks-cut').on('click', '.bloecks-copy, .bloecks-cut', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var action = $this.hasClass('bloecks-copy') ? 'copy' : 'cut';
            var sliceId = $this.data('slice-id');
            
            if (!sliceId) {
                showToast('Fehler: Slice-ID nicht gefunden', 'error');
                return;
            }
            
            performCopyPasteAction(action, { slice_id: sliceId });
        });
        
        // Handle paste buttons
        $(document).off('click', '.bloecks-paste').on('click', '.bloecks-paste', function(e) {
            e.preventDefault();

            var $btn = $(this);
            if (multiClipboard.length === 0 && favorites.length === 0) {
                // Status kommt gesammelt und kurz verzoegert -- bei einem schnellen Klick jetzt holen
                clearTimeout(clipboardStatusTimer);
                fetchClipboardStatus(function() {
                    showClipboardDropdown($btn);
                });
                return;
            }

            showClipboardDropdown($btn);
        });

        // Slice als Favorit speichern
        $(document).off('click', '.bloecks-favorite-save').on('click', '.bloecks-favorite-save', function(e) {
            e.preventDefault();
            showFavoriteSaveDialog($(this));
        });
    }

    function performCopyPasteAction(action, params) {
        // Show loading toast with unique ID
        var loadingToastId = 'bloecks-loading-' + Date.now();
        var loadingToast = showToastWithId('Wird verarbeitet...', 'info', 30000, loadingToastId);
        
        var data = {
            'function': action,  // Use 'function' parameter like the existing API
            'rex-api-call': 'bloecks'  // Use the existing API
        };
        
        // Merge parameters
        for (var key in params) {
            data[key] = params[key];
        }
        
        $.ajax({
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(response) {
                // Remove loading toast first
                removeToast(loadingToastId);
                
                
                if (response.success) {
                    showToast(response.message, 'success', 2500);
                    
                    // Always add to multi-clipboard on successful copy/cut
                    if ((action === 'copy' || action === 'cut') && response.clipboard_item) {
                        addToMultiClipboard(response.clipboard_item);
                    }
                    
                    // Scroll-Position für copy/cut speichern, für paste den Ziel-Slice
                    if (action === 'paste' && response.reload_needed) {
                        // Store scroll target in sessionStorage for after reload
                        if (response.scroll_to_slice && response.new_slice_id) {
                            sessionStorage.setItem('bloecks_scroll_target', response.new_slice_id);
                        }
                    } else if (action === 'copy' || action === 'cut') {
                        // Für copy/cut: aktuelle Scroll-Position speichern
                        sessionStorage.setItem('bloecks_scroll_position', window.pageYOffset || document.documentElement.scrollTop);
                    }
                    
                    // PJAX reload für alle Aktionen (copy/cut/paste) für Button-State Updates.
                    // Sofort -- der Toast liegt ausserhalb des neu geladenen Bereichs und bleibt stehen.
                    $.pjax({
                        url: getCleanUrlForReload(),
                        container: '#rex-js-page-main-content',
                        fragment: '#rex-js-page-main-content',
                        push: false // Important: don't push to history
                    }).done(function() {
                        // After PJAX reload, restore button states based on clipboard
                        setTimeout(function() {
                            updateAllButtonStates();
                        }, 100);
                    });
                } else {
                    showToast(response.message || 'Unbekannter Fehler', 'error');
                }
            },
            error: function(xhr, status, error) {
                // Remove loading toast first
                removeToast(loadingToastId);
                showToast('Netzwerk-Fehler bei der Verarbeitung', 'error');
                console.error('BLOECKS AJAX Error:', error);
            }
        });
    }
    
    // Helper function to get clean URL for PJAX reloads (removes edit-specific parameters)
    function getCleanUrlForReload() {
        var currentUrl = new URL(window.location.href);
        var params = currentUrl.searchParams;
        
        // Remove problematic parameters that should not persist after paste operations
        params.delete('function');
        params.delete('slice_id');
        params.delete('save');
        params.delete('update');
        
        return currentUrl.toString();
    }
    
    // Public API
    // Multi-Clipboard functionality
    var multiClipboard = [];
    var isMultiClipboardEnabled = false;
    var activeDropdown = null;
    var currentPastePosition = (window.rex && rex.bloecks && rex.bloecks.paste_position) || ((typeof BLOECKS_PASTE_POSITION !== 'undefined') ? BLOECKS_PASTE_POSITION : 'after');

    function setMultiClipboardEnabled(enabled) {
        isMultiClipboardEnabled = enabled;
    }

    function addToMultiClipboard(item) {
        // Check if item already exists (by slice_id)
        var existingIndex = multiClipboard.findIndex(function(clipItem) {
            return clipItem.source_slice_id === item.source_slice_id;
        });
        
        if (existingIndex !== -1) {
            // Update existing item
            multiClipboard[existingIndex] = item;
        } else {
            // Add new item
            multiClipboard.push(item);
        }
        
        updatePasteButtons();
        // Update button states after adding
        updateAllButtonStates();
    }

    function removeFromMultiClipboard(sliceId) {
        multiClipboard = multiClipboard.filter(function(item) {
            return item.source_slice_id !== sliceId;
        });
        updatePasteButtons();
        // Update button states after removal
        updateAllButtonStates();
    }
    
    // Update all copy/cut button states based on current clipboard
    function updateAllButtonStates() {
        // Reset all buttons first
        $('.bloecks-copy, .bloecks-cut').each(function() {
            var $btn = $(this);
            $btn.removeClass('btn-success')
                .removeClass('active')
                .removeAttr('data-bloecks-cutncopy-iscopied');
        });
        
        // Mark buttons for items in clipboard
        multiClipboard.forEach(function(item) {
            var sliceId = item.source_slice_id;
            var action = item.action;
            var selector = '.bloecks-' + action + '[data-slice-id="' + sliceId + '"]';
            
            $(selector).each(function() {
                var $btn = $(this);
                $btn.addClass('btn-success')
                    .addClass('active')
                    .attr('data-bloecks-cutncopy-iscopied', 'true');
            });
        });
    }

    function clearMultiClipboard() {
        // Clear local clipboard
        multiClipboard = [];
        updatePasteButtons();
        hideClipboardDropdown();
        
        // Clear server-side clipboard
        var data = {
            'function': 'clear_clipboard',
            'rex-api-call': 'bloecks'
        };
        
        $.ajax({
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    
                    // Force PJAX reload to update button states
                    $.pjax({
                        url: getCleanUrlForReload(),
                        container: '#rex-js-page-main-content',
                        fragment: '#rex-js-page-main-content',
                        push: false // Important: don't push to history
                    });
                } else {
                    showToast('Fehler beim Leeren der Zwischenablage', 'error');
                }
            },
            error: function() {
                showToast('Fehler beim Leeren der Zwischenablage', 'error');
            }
        });
    }

    function updatePasteButtons() {
        $('.bloecks-paste').each(function() {
            var $btn = $(this);
            
            if (multiClipboard.length > 1) {
                // Multiple items - show dropdown indicator
                $btn.addClass('has-multiple');
                var title = $btn.attr('title');
                if (title && !title.includes('(')) {
                    $btn.attr('title', title + ' (' + multiClipboard.length + ' Elemente)');
                }
            } else if (multiClipboard.length === 1) {
                // Single item - show detailed info
                $btn.removeClass('has-multiple');
                var item = multiClipboard[0];
                if (item && item.source_info) {
                    var actionText = item.action === 'cut' ? 'ausgeschnittenes' : 'kopiertes';
                    var newTitle = actionText + ': "' + item.source_info.module_name + '" aus "' + item.source_info.article_name + '" (ID: ' + item.source_info.article_id + ')';
                    $btn.attr('title', newTitle);
                }
            } else {
                // No items - disable button visually
                $btn.removeClass('has-multiple');
                $btn.attr('title', 'Zwischenablage ist leer');
            }
        });
    }

    var favorites = [];
    var canManageFavorites = false;
    var activeTab = 'clipboard';

    function label(key, fallback) {
        var labels = (window.rex && rex.bloecks && rex.bloecks.i18n) || {};
        return labels[key] || fallback;
    }

    function escapeHtml(value) {
        return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function csrfToken() {
        return (window.rex && rex.bloecks && rex.bloecks.token) || '';
    }

    function showClipboardDropdown($button) {
        hideClipboardDropdown();

        if (multiClipboard.length === 0 && favorites.length === 0) {
            showToast(label('error_clipboard_empty', 'Zwischenablage ist leer'), 'warning');
            return;
        }

        // Ohne Zwischenablage gleich die Favoriten zeigen
        if (activeTab === 'favorites' && favorites.length === 0) activeTab = 'clipboard';
        if (activeTab === 'clipboard' && multiClipboard.length === 0) activeTab = 'favorites';

        var $dropdown = $('<div class="bloecks-clipboard-dropdown" role="dialog"></div>');
        var showFavorites = !!(window.rex && rex.bloecks && rex.bloecks.favorites);

        // Kopf: Reiter (Zwischenablage / Favoriten) oder nur Titel
        var clipboardTitle = label('tab_clipboard', 'Zwischenablage') + ' (' + multiClipboard.length + ')';
        if (showFavorites) {
            var $tabs = $('<div class="bloecks-dropdown-tabs" role="tablist"></div>');
            $tabs.append('<button type="button" role="tab" class="bloecks-dropdown-tab' + (activeTab === 'clipboard' ? ' is-active' : '') + '" data-tab="clipboard"' + (multiClipboard.length === 0 ? ' disabled' : '') + '>' + escapeHtml(clipboardTitle) + '</button>');
            $tabs.append('<button type="button" role="tab" class="bloecks-dropdown-tab' + (activeTab === 'favorites' ? ' is-active' : '') + '" data-tab="favorites"' + (favorites.length === 0 ? ' disabled' : '') + '><i class="rex-icon rex-icon-favorite"></i> ' + escapeHtml(label('tab_favorites', 'Favoriten') + ' (' + favorites.length + ')') + '</button>');
            $dropdown.append($tabs);
        } else {
            $dropdown.append('<div class="dropdown-header">' + escapeHtml(clipboardTitle) + '</div>');
        }

        var $list = $('<div class="bloecks-clipboard-list"></div>');
        var selectable;
        if (activeTab === 'favorites') {
            selectable = true;
            favorites.forEach(function(fav) {
                var $item = $('<label class="bloecks-clipboard-item"></label>');
                $item.append('<input type="checkbox" data-favorite-id="' + fav.id + '">');
                $item.append('<span class="bloecks-clipboard-item-info"><span class="bloecks-clipboard-item-title"><i class="rex-icon rex-icon-favorite"></i>' + escapeHtml(fav.name) + '</span><span class="bloecks-clipboard-item-meta">' + escapeHtml(fav.module_name) + '</span></span>');
                if (canManageFavorites) {
                    $item.append('<button type="button" class="btn btn-xs btn-link bloecks-favorite-delete" data-favorite-id="' + fav.id + '" data-favorite-name="' + escapeHtml(fav.name) + '" title="' + escapeHtml(label('favorite_delete', 'Favorit löschen')) + '"><i class="rex-icon rex-icon-delete"></i></button>');
                }
                $list.append($item);
            });
        } else {
            // Mehrere Eintraege oder Multi-Clipboard: Auswahl per Checkbox, alle vorausgewaehlt
            selectable = multiClipboard.length > 1 || isMultiClipboardEnabled;
            multiClipboard.map(function(item, index) {
                return { item: item, index: index };
            }).sort(function(a, b) {
                return (a.item.timestamp || 0) - (b.item.timestamp || 0);
            }).forEach(function(entry) {
                var item = entry.item;
                var $item = $('<label class="bloecks-clipboard-item"></label>');
                if (selectable) {
                    $item.append('<input type="checkbox" checked data-index="' + entry.index + '">');
                }
                var moduleName = (item.source_info && item.source_info.module_name) ? item.source_info.module_name : label('unknown_module', 'Unbekanntes Modul');
                var icon = item.action === 'cut' ? 'rex-icon-cut bloecks-icon-cut' : 'rex-icon-copy';
                var meta = '';
                if (item.source_info) {
                    meta = (item.action === 'cut' ? label('action_cut_short', 'ausgeschnitten') : label('action_copied_short', 'kopiert'))
                        + ' ' + label('from_article', 'aus') + ': ' + item.source_info.article_name + ' (ID: ' + item.source_info.article_id + ')';
                }
                $item.append('<span class="bloecks-clipboard-item-info"><span class="bloecks-clipboard-item-title"><i class="rex-icon ' + icon + '"></i>' + escapeHtml(moduleName) + '</span><span class="bloecks-clipboard-item-meta">' + escapeHtml(meta) + '</span></span>');
                $list.append($item);
            });
        }
        $dropdown.append($list);

        // Fuss: Alle/Keine, Einfuegen darueber/darunter, Leeren
        var $footer = $('<div class="bloecks-dropdown-footer"></div>');
        if (selectable && $list.children().length > 1) {
            $footer.append('<span class="bloecks-dropdown-select"><button type="button" class="btn btn-xs btn-link" data-action="select-all">' + escapeHtml(label('select_all_short', 'Alle')) + '</button><button type="button" class="btn btn-xs btn-link" data-action="select-none">' + escapeHtml(label('select_none_short', 'Keine')) + '</button></span>');
        }
        var $paste = $('<span class="bloecks-dropdown-paste"></span>');
        var hasTarget = !!$button.data('target-slice');
        if (hasTarget) {
            // Die eingestellte Standardposition ist hervorgehoben
            var defaultPos = currentPastePosition === 'before' ? 'before' : 'after';
            $paste.append('<button type="button" class="btn btn-sm ' + (defaultPos === 'before' ? 'btn-success' : 'btn-default') + '" data-action="paste" data-position="before"><i class="rex-icon fa-arrow-up"></i> ' + escapeHtml(label('paste_above', 'Darüber einfügen')) + '</button>');
            $paste.append('<button type="button" class="btn btn-sm ' + (defaultPos === 'after' ? 'btn-success' : 'btn-default') + '" data-action="paste" data-position="after"><i class="rex-icon fa-arrow-down"></i> ' + escapeHtml(label('paste_below', 'Darunter einfügen')) + '</button>');
        } else {
            $paste.append('<button type="button" class="btn btn-sm btn-success" data-action="paste" data-position="after">' + escapeHtml(label('paste_here', 'Einfügen')) + '</button>');
        }
        $footer.append($paste);
        if (activeTab === 'clipboard') {
            $footer.append('<button type="button" class="btn btn-sm btn-link bloecks-dropdown-clear" data-action="clear">' + escapeHtml(label('clear', 'Leeren')) + '</button>');
        }
        $dropdown.append($footer);

        $('body').append($dropdown);
        positionDropdown($dropdown, $button);
        activeDropdown = $dropdown;
        setupDropdownEventHandlers($dropdown, $button);
    }

    function positionDropdown($dropdown, $button) {
        var offset = $button.offset();
        var width = $dropdown.outerWidth();
        var left = offset.left - (width / 2) + ($button.outerWidth() / 2);
        left = Math.min(left, $(window).width() - width - 10);
        $dropdown.css({
            top: offset.top + $button.outerHeight() + 5,
            left: Math.max(10, left)
        }).show();
    }

    function setupDropdownEventHandlers($dropdown, $button) {
        $dropdown.on('click', '[data-action="select-all"]', function() {
            $dropdown.find('input[type="checkbox"]').prop('checked', true);
        });

        $dropdown.on('click', '[data-action="select-none"]', function() {
            $dropdown.find('input[type="checkbox"]').prop('checked', false);
        });

        $dropdown.on('click', '[data-action="clear"]', function() {
            clearMultiClipboard();
        });

        $dropdown.on('click', '[data-tab]', function() {
            activeTab = $(this).data('tab');
            showClipboardDropdown($button);
        });

        $dropdown.on('click', '.bloecks-favorite-delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var question = label('favorite_delete_confirm', 'Favorit „{0}“ wirklich löschen?').replace('{0}', $btn.data('favorite-name'));
            if (window.confirm(question)) {
                deleteFavorite($btn.data('favorite-id'), $button);
            }
        });

        $dropdown.on('click', '[data-action="paste"]', function() {
            var position = $(this).data('position');
            if (activeTab === 'favorites') {
                var ids = [];
                $dropdown.find('input[data-favorite-id]:checked').each(function() {
                    ids.push(parseInt($(this).data('favorite-id'), 10));
                });
                if (ids.length === 0) {
                    showToast(label('nothing_selected', 'Nichts ausgewählt'), 'warning');
                    return;
                }
                pasteFavorites($button, ids, position);
            } else {
                var $boxes = $dropdown.find('input[data-index]');
                var indexes = [];
                if ($boxes.length) {
                    $boxes.filter(':checked').each(function() {
                        indexes.push(parseInt($(this).data('index'), 10));
                    });
                    if (indexes.length === 0) {
                        showToast(label('nothing_selected', 'Nichts ausgewählt'), 'warning');
                        return;
                    }
                    indexes.sort(function(a, b) {
                        return (multiClipboard[a].timestamp || 0) - (multiClipboard[b].timestamp || 0);
                    });
                } else {
                    for (var i = 0; i < multiClipboard.length; i++) indexes.push(i);
                }
                pasteMultipleItems($button, indexes, position);
            }
            hideClipboardDropdown();
        });

        $dropdown.on('keydown', function(e) {
            if (e.key === 'Escape') {
                hideClipboardDropdown();
                $button.trigger('focus');
            }
        });
    }

    function hideClipboardDropdown() {
        if (activeDropdown) {
            activeDropdown.remove();
            activeDropdown = null;
        }
    }

    function pasteMultipleItems($button, indexes, position) {
        var targetSlice = $button.data('target-slice') || null;
        var articleId = $button.data('article-id');
        var clangId = $button.data('clang-id');
        var ctypeId = $button.data('ctype-id') || 1;
        position = position || currentPastePosition;

        if (!articleId || !clangId) {
            showToast('Fehler: Artikel-Parameter fehlen', 'error');
            return;
        }

        // If multi-clipboard is NOT enabled AND we have only one item, use simple paste
        if (!isMultiClipboardEnabled && indexes.length === 1) {
            performCopyPasteAction('paste', {
                'bloecks_target': targetSlice,
                'article_id': articleId,
                'clang': clangId,
                'ctype': ctypeId,
                'paste_position': position
            });
            return;
        }

        var loadingToastId = 'bloecks-multi-paste-' + Date.now();
        showToastWithId('Füge ' + indexes.length + ' Elemente ein...', 'info', 30000, loadingToastId);

        $.ajax({
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            data: {
                'function': 'multi_paste',
                'rex-api-call': 'bloecks',
                'selected_items': JSON.stringify(indexes),
                'bloecks_target': targetSlice,
                'article_id': articleId,
                'clang': clangId,
                'ctype': ctypeId,
                'paste_position': position
            },
            success: function(response) {
                removeToast(loadingToastId);
                if (response.success) {
                    showToast(response.message, 'success');
                    // Ausgeschnittene Eintraege sind nach dem Einfuegen weg
                    indexes.slice().sort(function(a, b) { return b - a; }).forEach(function(index) {
                        if (multiClipboard[index] && multiClipboard[index].action === 'cut') {
                            multiClipboard.splice(index, 1);
                        }
                    });
                    updatePasteButtons();
                    reloadAfterPaste(response.new_slice_ids);
                } else {
                    showToast(response.message || 'Fehler beim Einfügen der Elemente', 'error');
                }
            },
            error: function() {
                removeToast(loadingToastId);
                showToast('Fehler beim Einfügen der Elemente', 'error');
            }
        });
    }

    function pasteFavorites($button, ids, position) {
        var loadingToastId = 'bloecks-fav-paste-' + Date.now();
        showToastWithId('Wird verarbeitet...', 'info', 30000, loadingToastId);

        $.ajax({
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            data: {
                'function': 'favorite_paste',
                'rex-api-call': 'bloecks',
                '_csrf_token': csrfToken(),
                'favorite_ids': JSON.stringify(ids),
                'bloecks_target': $button.data('target-slice') || 0,
                'article_id': $button.data('article-id'),
                'clang': $button.data('clang-id'),
                'ctype': $button.data('ctype-id') || 1,
                'paste_position': position
            },
            success: function(response) {
                removeToast(loadingToastId);
                if (response.success) {
                    showToast(response.message, 'success');
                    reloadAfterPaste(response.new_slice_ids);
                } else {
                    showToast(response.message || 'Fehler beim Einfügen', 'error');
                }
            },
            error: function() {
                removeToast(loadingToastId);
                showToast('Fehler beim Einfügen', 'error');
            }
        });
    }

    function reloadAfterPaste(newSliceIds) {
        if (newSliceIds && newSliceIds.length > 0) {
            sessionStorage.setItem('bloecks_scroll_target', newSliceIds[0]);
        }
        $.pjax({
            url: getCleanUrlForReload(),
            container: '#rex-js-page-main-content',
            fragment: '#rex-js-page-main-content',
            push: false
        });
    }

    function showFavoriteSaveDialog($button) {
        hideClipboardDropdown();
        var sliceId = $button.data('slice-id');
        var $title = $button.closest('.panel-heading').find('.panel-title').first();
        var suggestion = $.trim($title.text());

        var $dialog = $('<div class="bloecks-clipboard-dropdown bloecks-favorite-dialog" role="dialog"></div>');
        var $form = $('<form class="bloecks-favorite-form"></form>');
        $form.append('<label class="bloecks-favorite-label" for="bloecks-favorite-name">' + escapeHtml(label('favorite_name', 'Name des Favoriten')) + '</label>');
        $form.append('<input type="text" class="form-control" id="bloecks-favorite-name" maxlength="191" value="' + escapeHtml(suggestion) + '">');
        $form.append('<div class="bloecks-dropdown-footer"><span class="bloecks-dropdown-paste"><button type="submit" class="btn btn-sm btn-success"><i class="rex-icon rex-icon-favorite"></i> ' + escapeHtml(label('save', 'Speichern')) + '</button></span><button type="button" class="btn btn-sm btn-link" data-action="cancel">' + escapeHtml(label('cancel', 'Abbrechen')) + '</button></div>');
        $dialog.append($form);

        $('body').append($dialog);
        positionDropdown($dialog, $button);
        activeDropdown = $dialog;
        $dialog.find('input').trigger('focus').trigger('select');

        $dialog.on('click', '[data-action="cancel"]', hideClipboardDropdown);
        $dialog.on('keydown', function(e) {
            if (e.key === 'Escape') hideClipboardDropdown();
        });
        $form.on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'index.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    'function': 'favorite_add',
                    'rex-api-call': 'bloecks',
                    '_csrf_token': csrfToken(),
                    'slice_id': sliceId,
                    'name': $dialog.find('input').val()
                },
                success: function(response) {
                    hideClipboardDropdown();
                    if (response.success) {
                        favorites = response.favorites || favorites;
                        showToast(response.message, 'success');
                        // Einfuege-Knoepfe erscheinen erst, wenn es etwas einzufuegen gibt
                        if ($('.bloecks-paste').length === 0) {
                            $.pjax({ url: getCleanUrlForReload(), container: '#rex-js-page-main-content', fragment: '#rex-js-page-main-content', push: false });
                        }
                    } else {
                        showToast(response.message || 'Fehler', 'error');
                    }
                },
                error: function() {
                    hideClipboardDropdown();
                    showToast('Netzwerk-Fehler bei der Verarbeitung', 'error');
                }
            });
        });
    }

    function deleteFavorite(id, $button) {
        $.ajax({
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            data: {
                'function': 'favorite_delete',
                'rex-api-call': 'bloecks',
                '_csrf_token': csrfToken(),
                'favorite_id': id
            },
            success: function(response) {
                if (response.success) {
                    favorites = response.favorites || [];
                    showToast(response.message, 'success');
                    if (favorites.length || multiClipboard.length) {
                        showClipboardDropdown($button);
                    } else {
                        hideClipboardDropdown();
                    }
                } else {
                    showToast(response.message || 'Fehler', 'error');
                }
            },
            error: function() {
                showToast('Netzwerk-Fehler bei der Verarbeitung', 'error');
            }
        });
    }

    // Re-initialize handlers after PJAX navigation
    $(document).on('pjax:complete pjax:end', function() {
        // Re-initialize copy/paste handlers after PJAX navigation
        setTimeout(function() {
            initCopyPasteHandlers();
            
            // Re-check multi-clipboard config after navigation
            if (typeof BLOECKS_MULTI_CLIPBOARD !== 'undefined' && BLOECKS_MULTI_CLIPBOARD) {
                setMultiClipboardEnabled(true);
                // Update button states after PJAX reload
                updateAllButtonStates();
                loadMultiClipboardFromServer();
            }
        }, 100);
    });
    
    // ready, rex:ready und pjax:* fordern den Status pro Seitenaufbau mehrfach an. Jeder Request
    // haelt die PHP-Session-Sperre, ein Klick auf "Einfuegen" muesste dahinter warten -- deshalb
    // kurz sammeln und nur einmal fragen.
    var clipboardStatusTimer = null;
    function loadMultiClipboardFromServer() {
        clearTimeout(clipboardStatusTimer);
        clipboardStatusTimer = setTimeout(fetchClipboardStatus, 200);
    }

    function fetchClipboardStatus(done) {
        // Load current clipboard status from server
        var data = {
            'function': 'get_clipboard_status',
            'rex-api-call': 'bloecks'
        };
        
        $.ajax({
            url: 'index.php',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Always sync multi-clipboard, regardless of setting
                    if (response.multi_clipboard_items && response.multi_clipboard_items.length > 0) {
                        multiClipboard = response.multi_clipboard_items;
                    } else {
                        multiClipboard = [];
                    }
                    
                    favorites = response.favorites || [];
                    canManageFavorites = !!response.favorites_manage;

                    // Set multi-clipboard mode based on server setting
                    if (response.multi_clipboard_enabled) {
                        setMultiClipboardEnabled(true);
                    }
                    
                    updatePasteButtons();
                    // Update button states after loading from server
                    updateAllButtonStates();
                }
                if (typeof done === 'function') {
                    done();
                }
            },
            error: function() {
                // Silently fail - not critical
                console.log('Could not load clipboard status');
            }
        });
    }

    // Hide dropdown when clicking outside
    $(document).on('click', function(e) {
        if (activeDropdown && !$(e.target).closest('.bloecks-clipboard-dropdown, .bloecks-paste, .bloecks-favorite-save').length) {
            hideClipboardDropdown();
        }
    });

    return {
        init: initDragDrop,
        destroy: destroy,
        showToast: showToast,
        closeToast: closeToast,
        checkForMessages: checkForMessages,
        checkForScrollTarget: checkForScrollTarget,
        initCopyPasteHandlers: initCopyPasteHandlers,
        setMultiClipboardEnabled: setMultiClipboardEnabled,
        addToMultiClipboard: addToMultiClipboard,
        removeFromMultiClipboard: removeFromMultiClipboard,
        clearMultiClipboard: clearMultiClipboard,
        loadMultiClipboardFromServer: loadMultiClipboardFromServer,
        updateAllButtonStates: updateAllButtonStates,
        version: '2.6.0'
    };
    
})(jQuery);

// Global initialization - use centralized initialization
$(document).ready(function() {
    // Use centralized initialization to prevent duplicates
    BLOECKS.initializeOnce = BLOECKS.initializeOnce || function() {
        if (BLOECKS._isInitialized) {
            return;
        }
        BLOECKS._isInitialized = true;
        
        // Always initialize copy/paste handlers
        BLOECKS.initCopyPasteHandlers();
        
        // Initialize multi-clipboard if enabled
        if (typeof BLOECKS_MULTI_CLIPBOARD !== 'undefined' && BLOECKS_MULTI_CLIPBOARD) {
            BLOECKS.setMultiClipboardEnabled(true);
        }
        
        // Load clipboard status from server (which will call updateAllButtonStates)
        BLOECKS.loadMultiClipboardFromServer();
    };
    
    BLOECKS.initializeOnce();
});

// Re-initialize after PJAX navigation - but prevent multiple initializations
$(document).on('pjax:complete pjax:end rex:ready', function() {
    setTimeout(function() {
        // Reset initialization flag to allow re-init after PJAX
        BLOECKS._isInitialized = false;
        
        // Always reinitialize handlers
        BLOECKS.initCopyPasteHandlers();
        
        // Always load clipboard status from server after navigation
        if (typeof BLOECKS_MULTI_CLIPBOARD !== 'undefined' && BLOECKS_MULTI_CLIPBOARD) {
            BLOECKS.setMultiClipboardEnabled(true);
        }
        
        // Load current clipboard status to sync frontend with backend
        // This will also call updateAllButtonStates() in its success callback
        BLOECKS.loadMultiClipboardFromServer();
        
        // Check for scroll target after PJAX navigation (important for paste operations)
        BLOECKS.checkForScrollTarget();
    }, 100);
});

// Fallback: Watch for new bloecks buttons being added to DOM - but prevent duplicate handlers
if (typeof MutationObserver !== 'undefined') {
    var bloecksObserver = new MutationObserver(function(mutations) {
        var shouldReinit = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                $(mutation.addedNodes).each(function() {
                    if ($(this).find('.bloecks-copy, .bloecks-cut, .bloecks-paste').length > 0) {
                        shouldReinit = true;
                        return false;
                    }
                });
            }
        });
        
        if (shouldReinit) {
            setTimeout(function() {
                BLOECKS.initCopyPasteHandlers();
                // Update button states when new buttons are added
                if (typeof BLOECKS.updateAllButtonStates === 'function') {
                    BLOECKS.updateAllButtonStates();
                }
            }, 50);
        }
    });
    
    // Start observing only when document.body is available
    function startObserving() {
        if (document.body && !BLOECKS._observerStarted) {
            BLOECKS._observerStarted = true;
            bloecksObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        } else if (!document.body) {
            // Wait for body to be available
            setTimeout(startObserving, 50);
        }
    }
    
    // Initialize observer
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startObserving);
    } else {
        startObserving();
    }
}
