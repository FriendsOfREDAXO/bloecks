var bloecks_code={init:function(){},insertLinebreakAtCursor:function(e){var t,n=e.value;if(void 0!==e.selectionStart&&void 0!==e.selectionEnd){if(before=n.slice(0,e.selectionStart),matches=before.match(/(\n|^)(\t+|\s+)?[^\n]+$/),matches&&void 0!==matches[2])return this.insertTextAtCursor(e,"\n"+matches[2]),!1}else void 0!==document.selection&&void 0!==document.selection.createRange&&(e.focus(),(t=document.selection.createRange()).collapse(!1),t.select());return!0},jumpToPreviousTab:function(e){var t,n=e.value;void 0!==e.selectionStart&&void 0!==e.selectionEnd?(before=n.slice(0,e.selectionStart),matches=before.match(/(\n|^)(.*)[^\n]+$/)):void 0!==document.selection&&void 0!==document.selection.createRange&&(e.focus(),(t=document.selection.createRange()).collapse(!1),t.select())},insertTextAtCursor:function(e,t){var n,i,o=e.value;void 0!==e.selectionStart&&void 0!==e.selectionEnd?(n=e.selectionEnd,e.value=o.slice(0,e.selectionStart)+t+o.slice(n),e.selectionStart=e.selectionEnd=n+t.length):void 0!==document.selection&&void 0!==document.selection.createRange&&(e.focus(),(i=document.selection.createRange()).collapse(!1),i.text=t,i.select())}};$(document).on("ready.bloecks",$.proxy(bloecks_code.init,bloecks_code));var bloecks_fragments={init:function(){this.addToggleButtons()},addToggleButtons:function(){$(document).on({"change.bloecks":function(){bloecks_fragments.toggle(this)}},'.bloecks--setting input[type="checkbox"][name*="[active]"]')},toggle:function(e){var t=$(e).is(":checked"),n=$(e).attr("id");t?$("."+n).removeClass("is--hidden"):$("."+n).addClass("is--hidden")}};$(document).on("ready.bloecks",$.proxy(bloecks_fragments.init,bloecks_fragments)),$(document).on("rex:ready",function(){$('.bloecks--setting input[type="checkbox"][name*="[active]"]').each(function(e,t){bloecks_fragments.toggle(t)})});var bloecks={plugins:[],init:function(){for(var e=this.getPlugins(!0),t=e.length,n=0;n<t;n++)this[e[n]].init()},getSliceId:function(e){var t=null;return $(e).is(".rex-slice-output")||(e=$(e).parents(".rex-slice-output").length?$(e).parents(".rex-slice-output").first():1==$(e).find(".rex-slice-output").length?$(e).find(".rex-slice-output").first():null),e&&($(e).find('[href*="slice_id="]').length?t=parseInt($(e).find('[href*="slice_id="]').first().attr("href").replace(/.*slice_id=(\d+).*/,"$1")):$(e).attr("id")&&(t=parseInt($(e).attr("id").replace(/[^0-9]/g,"")))),t},executePjax:function(e){var t=e.match(/(#[^\?\&]+)/);t&&(e=e.replace(/(#[^\?\&]+)/,"")+t[0]),$.pjax({url:e,container:"#rex-js-page-main-content",fragment:"#rex-js-page-main-content",push:!1})},getPlugins:function(t){return t=!0===t,this.plugins.filter(function(e){return"string"==typeof e&&void 0!==bloecks[e]&&(!t||"function"==typeof bloecks[e].init)})},addPlugin:function(e,t,n){this[e]=t,n=parseInt(n),(n=Math.max(isNaN(n)?0:n,this.plugins.length))>this.plugins.length&&(this.plugins=this.plugins.concat(Array.apply(null,Array(n-this.plugins.length)))),this.plugins.splice(n,0,e)}};$(document).on("rex:ready",$.proxy(bloecks.init,bloecks));
//# sourceMappingURL=be.js.map