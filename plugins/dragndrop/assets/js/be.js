bloecks.addPlugin("dragndrop",{init:function(){var i=this;$(".rex-slices:not(.is--undraggable)").each(function(e,t){try{$(t).sortable("destroy")}catch(e){}$(t).find(".rex-slice.rex-slice-edit, .rex-slice.rex-slice-add").length?$(t).addClass("is--editing"):i.addSortables(t)})},markDisabledItems:function(e,i){i="string"!=typeof i?"ui-state-disabled":i,$(e).find(".rex-slice-output:not(."+i+")").each(function(e,t){$(t).find('[href*="direction=move"]').length||$(t).addClass(i)})},addSortables:function(e){var t="ui-state-disabled";this.markDisabledItems(e,t),$(e).sortable({appendTo:document.body,handle:".rex-page-section>.panel>.panel-heading",placeholder:"rex-slice rex-slice-placeholder",cancel:t,helper:"clone",items:">.rex-slice.rex-slice-draggable",create:function(){$(e).css({minHeight:$(e).outerHeight()})},start:function(e,t){$(this).addClass("ui-state-sorting"),$(this).sortable("refreshPositions"),t.placeholder.height(t.helper.outerHeight())},stop:function(){$(this).hasClass("ui-state-updated")||$(this).removeClass("ui-state-sorting")},update:function(e,t){$(this).addClass("ui-state-updated"),$(this).sortable("refresh");var i=t.position.top<t.originalPosition.top?"up":"down",s=bloecks.getSliceId(t.item),a=t.item.prevAll(".rex-slice-draggable").length?bloecks.getSliceId(t.item.prevAll(".rex-slice-draggable").first()):0;if(null!==s&&null!==a){var l=t.item.find('[href*="direction=move'+i+'"]').length?t.item.find('[href*="direction=move'+i+'"]').first().attr("href"):null;l=null!==l?(l=(l=l.replace(/(&amp;|&)direction=move(up|down)/,"$1direction=move$2$1insertafter="+a)).replace(/content_move_slice/,"content_move_slice_to")).replace(/_csrf_token=[^&]+/,"_csrf_token="+t.item.data("csrf-token")):window.location.href,bloecks.executePjax(l)}}})}});
//# sourceMappingURL=be.js.map