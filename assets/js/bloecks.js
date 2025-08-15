/**
 * BLOECKS - REDAXO Backend Drag & Drop System with Wrapper Support
 * Based on SortableJS for modern drag & drop functionality
 * Now supports slice_columns-style wrappers
 */

var BLOECKS = (function($) {
    'use strict';
    
    var sortableInstances = [];
    
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
                // Refresh content with PJAX if available
                if (typeof rex !== 'undefined' && rex.backend && rex.backend.pjax) {
                    var currentScrollTop = $(window).scrollTop();
                    
                    rex.backend.pjax.request({
                        url: window.location.href,
                        container: '#rex-js-page-main-content',
                        success: function() {
                            // Restore scroll position after reload and reinitialize
                            setTimeout(function() {
                                $(window).scrollTop(currentScrollTop);
                                // Destroy old instances and reinitialize
                                destroy();
                                initDragDrop();
                            }, 100);
                        }
                    });
                } else {
                    // If no PJAX, just reinitialize drag drop to maintain functionality
                    setTimeout(function() {
                        destroy();
                        initDragDrop();
                    }, 100);
                }
            },
            error: function(xhr, status, error) {
                console.error('BLOECKS: Failed to update order', error);
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
    });
    
    // Reinitialize after PJAX requests
    $(document).on('rex:ready', function() {
        // Always reinitialize on content pages
        if (window.location.href.includes('page=content')) {
            destroy();
            setTimeout(function() {
                initDragDrop();
            }, 200);
        }
    });
    
    // Also listen for pjax:end as backup
    $(document).on('pjax:end', function() {
        if (window.location.href.includes('page=content')) {
            destroy();
            setTimeout(function() {
                initDragDrop();
            }, 300);
        }
    });
    
    // Public API
    return {
        init: initDragDrop,
        destroy: destroy,
        version: '2.3.0'
    };
    
})(jQuery);
