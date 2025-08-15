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
        duration = duration || 4000;
        
        var toastId = 'bloecks-toast-' + (++toastCounter);
        return showToastWithId(message, type, duration, toastId);
    }
    
    function showToastWithId(message, type, duration, toastId) {
        type = type || 'success';
        duration = duration || 4000;
        
        var container = createToastContainer();
        var toast = document.createElement('div');
        
        toast.id = toastId;
        toast.className = 'bloecks-toast bloecks-toast-' + type;
        toast.style.cssText = `
            background: ${type === 'success' ? '#28a745' : type === 'warning' ? '#ffc107' : type === 'info' ? '#17a2b8' : '#dc3545'};
            color: white;
            padding: 20px 26px;
            margin-bottom: 12px;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.25);
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: auto;
            cursor: pointer;
            max-width: 500px;
            min-width: 350px;
            word-wrap: break-word;
            font-size: 16px;
            line-height: 1.5;
            position: relative;
            text-align: center;
            font-weight: 500;
        `;
        
        toast.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 8px;">
                <div style="flex: 1;">${message}</div>
                <button onclick="BLOECKS.closeToast('${toastId}')" 
                        style="background: none; border: none; color: white; font-size: 18px; 
                               line-height: 1; cursor: pointer; padding: 0; margin: -2px 0 0 0;">×</button>
            </div>
        `;
        
        container.appendChild(toast);
        
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
                handle: '.bloecks-drag-handle',
                animation: 150,
                ghostClass: 'bloecks-sortable-ghost',
                chosenClass: 'bloecks-sortable-chosen',
                dragClass: 'bloecks-dragging',
                
                onStart: function(evt) {
                    evt.item.classList.add('bloecks-dragging');
                },
                
                onEnd: function(evt) {
                    evt.item.classList.remove('bloecks-dragging');
                    
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
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        initDragDrop();
        checkForMessages();
        initCopyPasteHandlers();
    });
    
    // Check for BLOECKS messages and show as toasts
    function checkForMessages() {
        // Look for BLOECKS success/warning/error messages in rex-page-main-content
        var mainContent = document.getElementById('rex-page-main-content');
        if (!mainContent) {
            mainContent = document.body;
        }
        
        // Check for success messages
        var successMessages = mainContent.querySelectorAll('.alert-success');
        successMessages.forEach(function(alert) {
            var text = alert.textContent.trim();
            if (text.includes('kopiert') || text.includes('eingefügt') || text.includes('ausgeschnitten')) {
                showToast(text, 'success');
                // Hide the original alert after showing toast
                alert.style.display = 'none';
            }
        });
        
        // Check for warning messages
        var warningMessages = mainContent.querySelectorAll('.alert-warning');
        warningMessages.forEach(function(alert) {
            var text = alert.textContent.trim();
            if (text.includes('bloecks') || text.includes('Berechtigung') || text.includes('Clipboard')) {
                showToast(text, 'warning', 6000);
                alert.style.display = 'none';
            }
        });
        
        // Check for error messages
        var errorMessages = mainContent.querySelectorAll('.alert-danger, .alert-error');
        errorMessages.forEach(function(alert) {
            var text = alert.textContent.trim();
            if (text.includes('bloecks') || text.includes('Fehler')) {
                showToast(text, 'error', 8000);
                alert.style.display = 'none';
            }
        });
        
        // Test Toast - show once on page load (remove this later)
        // if (!window.bloecksToastTested) {
        //     window.bloecksToastTested = true;
        //     setTimeout(function() {
        //         showToast('BLOECKS Toast-System geladen!', 'success', 3000);
        //     }, 1000);
        // }
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
    }    function findSliceById(sliceId) {
        
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
    
    // Reinitialize after PJAX requests
    $(document).on('rex:ready', function() {
        // Always reinitialize on content pages
        if (window.location.href.includes('page=content')) {
            destroy();
            setTimeout(function() {
                initDragDrop();
                checkForMessages();
                initCopyPasteHandlers();
                checkForScrollTarget(); // Add this call!
            }, 100);
        }
    });
    
    // Also listen for pjax:end as backup
    $(document).on('pjax:end', function() {
        if (window.location.href.includes('page=content')) {
            destroy();
            setTimeout(function() {
                initDragDrop();
                checkForMessages();
                initCopyPasteHandlers();
                checkForScrollTarget(); // Add this call!
            }, 150);
        }
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
        if (window.location.href.includes('page=content')) {
            destroy();
            setTimeout(function() {
                initDragDrop();
                checkForMessages();
                initCopyPasteHandlers();
                checkForScrollTarget();
            }, 100);
        }
    });
    
    // Additional event listeners for real page loads (not just PJAX)
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
            
            var $this = $(this);
            var targetSlice = $this.data('target-slice') || null;
            var articleId = $this.data('article-id');
            var clangId = $this.data('clang-id');
            var ctypeId = $this.data('ctype-id') || 1;
            
            if (!articleId || !clangId) {
                showToast('Fehler: Artikel-Parameter fehlen', 'error');
                return;
            }
            
            performCopyPasteAction('paste', {
                bloecks_target: targetSlice,
                article_id: articleId,
                clang: clangId,
                ctype: ctypeId
            });
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
                    showToast(response.message, 'success', 6000); // Längere Anzeigedauer für Paste-Erfolg
                    
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
                    
                    // PJAX reload für alle Aktionen (copy/cut/paste) für Button-State Updates
                    setTimeout(function() {
                        // Use the correct PJAX method like REDAXO core does
                        $.pjax({
                            url: window.location.href,
                            container: '#rex-js-page-main-content',
                            fragment: '#rex-js-page-main-content',
                            push: false // Important: don't push to history
                        });
                    }, 800); // Shorter wait time
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
    
    // Public API
    return {
        init: initDragDrop,
        destroy: destroy,
        showToast: showToast,
        closeToast: closeToast,
        checkForMessages: checkForMessages,
        checkForScrollTarget: checkForScrollTarget,
        initCopyPasteHandlers: initCopyPasteHandlers,
        version: '2.4.0'
    };
    
})(jQuery);
