(function($){
"use strict";
var form=document.getElementById("eventForm");if(!form)return;var initialType=new URLSearchParams(location.search).get("type");form.setAttribute("data-event-type",initialType||"");
var key="booktkit:event-draft:"+location.pathname+":"+new URLSearchParams(location.search).get("type"),thumbKey=key+":thumb";
function save(){var o={};$(form).find(":input[name]").each(function(){var n=this.name;if(!n||n==="_token"||this.type==="file")return;if((this.type==="checkbox"||this.type==="radio")&&!this.checked)return;o[n]=this.value});try{localStorage.setItem(key,JSON.stringify(o))}catch(e){}}
function restore(){try{var o=JSON.parse(localStorage.getItem(key)||"{}");Object.keys(o).forEach(function(n){var $e=$(form).find('[name="'+CSS.escape(n)+'"]');if(!$e.length)return;if($e.first().is(":radio,:checkbox"))$e.filter('[value="'+CSS.escape(String(o[n]))+'"]').prop("checked",true).trigger("change");else $e.val(o[n]).trigger("change")})}catch(e){}}
var timer;$(form).on("input change","input,select,textarea",function(){clearTimeout(timer);timer=setTimeout(save,250)});
var eventType=new URLSearchParams(location.search).get("type"),isOnline=eventType==="online";
var $gallery=$("#my-dropzone").closest(".col-lg-12"),children=$(form).children();children.attr("data-btk-step","4");
$("#accordion").attr("data-btk-step","1");
$("#single_dates,.countDownStatus").attr("data-btk-step","2");$("#multiple_dates").closest(".row").attr("data-btk-step","2");$(".eventDateType").closest(".row").attr("data-btk-step","2");
$gallery.attr("data-btk-step","3");var $thumb=$(form).find('input[name="thumbnail"]').closest(".form-group");$thumb.attr("data-btk-step","3");
if(isOnline){
  var onlineNames=["ticket_available_type","ticket_available","max_ticket_buy_type","max_buy_ticket","price","pricing_type","meeting_url","early_bird_discount_type","discount_type","early_bird_discount_amount","early_bird_discount_date","early_bird_discount_time"];
  onlineNames.forEach(function(name){$(form).find('[name="'+name+'"]').each(function(){var $row=$(this).closest(".row");if($row.length&&$row.parent().is(form))$row.attr("data-btk-step","4");else $(this).closest(".col-lg-6,.col-lg-12").attr("data-btk-online-field","1")})});
}
var nav=$('<div class="btk-wizard-nav"><button type="button" data-step="1">1 <span>Event Details</span></button><button type="button" data-step="2">2 <span>Schedule</span></button><button type="button" data-step="3">3 <span>Media</span></button><button type="button" data-step="4">4 <span>'+(isOnline?'Tickets & Online':'Publishing')+'</span></button></div>');
$(".card-body .col-lg-8.mx-auto").first().prepend(nav);
if(isOnline){
 var $meeting=$(form).find('[name="meeting_url"]').closest(".col-lg-6");if($meeting.length)$meeting.prepend('<div class="btk-online-section-title"><i class="fas fa-video"></i> Online access</div>');
 var $ticket=$(form).find('[name="ticket_available_type"]').closest(".row");if($ticket.length)$ticket.addClass("btk-online-section").prepend('<div class="col-12 btk-online-section-title"><i class="fas fa-ticket-alt"></i> Tickets & access</div>');
 var $early=$("#early_bird_discount_free");if($early.length)$early.addClass("btk-online-section").prepend('<div class="col-12 btk-online-section-title"><i class="fas fa-tags"></i> Early bird offer</div>');
}
var actions=$('<div class="btk-wizard-actions"><button type="button" class="btn btn-light btk-prev">Back</button><span class="btk-autosave">Draft autosaves on this device</span><button type="button" class="btn btn-primary btk-next">Continue</button></div>');var $footerRow=$(".card-footer .row").first();if($footerRow.length)$footerRow.before(actions);else $(form).append(actions);var step=1;
function show(n){step=Math.max(1,Math.min(4,n));$("[data-btk-step]").hide();$('[data-btk-step="'+step+'"]').show();nav.find("button").removeClass("active done").each(function(){var s=+this.dataset.step;$(this).toggleClass("active",s===step).toggleClass("done",s<step)});$(".btk-prev").toggle(step>1);$(".btk-next").toggle(step<4);$("#EventSubmit").toggle(step===4);window.scrollTo({top:0,behavior:"smooth"})}
nav.on("click","button",function(){show(+this.dataset.step)});$(".btk-prev").on("click",function(){show(step-1)});$(".btk-next").on("click",function(){show(step+1)});
function selectText(sel,text){var el=document.querySelector(sel);if(!el)return false;var opt=[].slice.call(el.options).find(function(o){return o.text.trim().toLowerCase()===text.toLowerCase()});if(opt){el.value=opt.value;$(el).trigger("change");return true}return false}
function syncEventDateType(){var multiple=$('input[name="date_type"]:checked').val()==="multiple",$multiRow=$("#multiple_dates").closest(".row");$("#single_dates").toggleClass("d-none",multiple).toggle(!multiple);$multiRow.toggleClass("d-none",!multiple).toggle(multiple);$("#multiple_dates").toggleClass("d-none",!multiple).toggle(multiple);$(".countDownStatus").toggleClass("d-none",multiple).toggle(!multiple);$("#single_dates :input").prop("disabled",multiple);$("#multiple_dates :input").prop("disabled",!multiple)}
$(document).on("change",".eventDateType",syncEventDateType);
function defaults(){if(new URLSearchParams(location.search).get("type")!=="venue")return;var tries=0,iv=setInterval(function(){tries++;document.querySelectorAll(".countryDropdown").forEach(function(c){if(!c.value)selectText('[name="'+c.name+'"]',"India")});document.querySelectorAll(".stateDropdown").forEach(function(s){if(!s.value)selectText('[name="'+s.name+'"]',"Maharashtra")});document.querySelectorAll(".cityDropdown").forEach(function(c){if(!c.value)selectText('[name="'+c.name+'"]',"Jalgaon")});if(tries>25)clearInterval(iv)},300)}
restore();defaults();syncEventDateType();show(1);
// Keep venue defaults stable even when country/state/city options load asynchronously.
(function venueDefaults(){
 if(new URLSearchParams(location.search).get("type")!=="venue")return;
 var attempts=0,t=setInterval(function(){
   attempts++;
   document.querySelectorAll(".countryDropdown").forEach(function(el){var o=[].slice.call(el.options).find(function(x){return x.text.trim().toLowerCase()==="india"});if(o&&el.value!==o.value){el.value=o.value;$(el).trigger("change")}});
   document.querySelectorAll(".stateDropdown").forEach(function(el){var o=[].slice.call(el.options).find(function(x){return x.text.trim().toLowerCase()==="maharashtra"});if(o&&el.value!==o.value){el.value=o.value;$(el).trigger("change")}});
   document.querySelectorAll(".cityDropdown").forEach(function(el){var o=[].slice.call(el.options).find(function(x){return x.text.trim().toLowerCase()==="jalgaon"});if(o&&el.value!==o.value){el.value=o.value;$(el).trigger("change")}});
   if(attempts>=40)clearInterval(t);
 },250);
})();

function process(file,w,h,done){
if(!file||!file.type.match(/^image\//))return;
var url=URL.createObjectURL(file),img=new Image();
img.onload=function(){
var scale=Math.max(w/img.width,h/img.height),minScale=scale,maxScale=scale*3,tx=0,ty=0,drag=false,lastX=0,lastY=0;
var html='<div class="modal fade btk-position-modal" id="btkCropModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title">Position your image</h5><small class="text-muted">Drag the image to choose exactly what appears in the final '+w+'×'+h+' frame.</small></div><button type="button" class="close" data-dismiss="modal">×</button></div><div class="modal-body"><div class="btk-cover-stage" style="aspect-ratio:'+w+'/'+h+'"><img draggable="false" src="'+url+'"><div class="btk-cover-grid"></div></div><div class="btk-cover-tools"><button type="button" class="btn btn-light btn-sm btk-fit"><i class="fas fa-expand"></i> Reset</button><div class="btk-zoom-wrap"><i class="fas fa-search-minus"></i><input class="custom-range btk-z" type="range" min="100" max="300" value="100"><i class="fas fa-search-plus"></i></div><span class="btk-output-size">'+w+' × '+h+' px</span></div><p class="btk-editor-help mb-0"><i class="fas fa-arrows-alt"></i> Drag to reposition · use zoom for a tighter crop. Saved image is automatically optimized below 1 MB.</p></div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-primary btk-apply" type="button"><i class="fas fa-check"></i> Use this position</button></div></div></div></div>';
$("body").append(html);var m=$("#btkCropModal"),stage=m.find(".btk-cover-stage"),p=m.find("img");
function limits(){var sw=img.width*scale,sh=img.height*scale;return{x:Math.max(0,(sw-w)/2),y:Math.max(0,(sh-h)/2)}}
function clamp(){var l=limits();tx=Math.max(-l.x,Math.min(l.x,tx));ty=Math.max(-l.y,Math.min(l.y,ty))}
function render(){clamp();p.css({width:(img.width*scale)+"px",height:(img.height*scale)+"px",transform:"translate(calc(-50% + "+tx+"px),calc(-50% + "+ty+"px))"})}
function point(e){var o=e.originalEvent,t=o.touches&&o.touches[0]?o.touches[0]:o.changedTouches&&o.changedTouches[0]?o.changedTouches[0]:o;return{x:t.clientX,y:t.clientY}}
stage.on("mousedown touchstart",function(e){drag=true;var q=point(e);lastX=q.x;lastY=q.y;stage.addClass("is-dragging");e.preventDefault()});
$(document).on("mousemove.btkCrop touchmove.btkCrop",function(e){if(!drag)return;var q=point(e);tx+=q.x-lastX;ty+=q.y-lastY;lastX=q.x;lastY=q.y;render();e.preventDefault()}).on("mouseup.btkCrop touchend.btkCrop",function(){drag=false;stage.removeClass("is-dragging")});
m.find(".btk-z").on("input",function(){var old=scale;scale=minScale*(+this.value/100);var ratio=scale/old;tx*=ratio;ty*=ratio;render()});
m.find(".btk-fit").on("click",function(){scale=minScale;tx=0;ty=0;m.find(".btk-z").val(100);render()});
m.find(".btk-apply").on("click",function(){var c=document.createElement("canvas");c.width=w;c.height=h;var ctx=c.getContext("2d"),dw=img.width*scale,dh=img.height*scale,dx=(w-dw)/2+tx,dy=(h-dh)/2+ty;ctx.drawImage(img,dx,dy,dw,dh);function enc(q){c.toBlob(function(b){if(b.size>1024*1024&&q>.35)return enc(q-.07);if(b.size>1024*1024){alert("This image cannot be compressed below 1 MB. Please choose another image.");return}var out=new File([b],(file.name.replace(/\.[^.]+$/,"")||"event")+".jpg",{type:"image/jpeg"});m.modal("hide");setTimeout(function(){m.remove();URL.revokeObjectURL(url)},250);done(out,c.toDataURL("image/jpeg",q))},"image/jpeg",q)}enc(.9)});
m.on("hidden.bs.modal",function(){$(document).off(".btkCrop");if(document.body.contains(this)){m.remove();URL.revokeObjectURL(url)}});render();m.modal({backdrop:"static",keyboard:false})
};img.src=url
}
window.booktkitProcessEventImage=process;
var ti=form.querySelector('input[name="thumbnail"]');if(ti){ti.dataset.btkEditorSkip="1";ti.dataset.imageWidth="320";ti.dataset.imageHeight="230";ti.dataset.imageMaxKb="1024";var $wrap=$(ti).closest(".form-group"),$remove=$('<button type="button" class="btn btn-danger btn-sm ml-2 btk-remove-thumb"><i class="fas fa-trash"></i> Remove image</button>');$(ti).closest(".mt-3").append($remove);$remove.attr("hidden",true).addClass("btk-hidden").hide();$wrap.on("booktkit:thumbnail-ready",function(){$remove.removeAttr("hidden").removeClass("btk-hidden").show()});
ti.addEventListener("change",function(e){var f=e.target.files&&e.target.files[0];if(!f||f.__btk)return;e.stopImmediatePropagation();process(f,320,230,function(out,data){Object.defineProperty(out,"__btk",{value:true});var dt=new DataTransfer();dt.items.add(out);ti.files=dt.files;$wrap.find(".uploaded-img").attr("src",data);$remove.show();try{localStorage.setItem(thumbKey,data)}catch(e){}save()})},true);
$remove.on("click",function(){ti.value="";$wrap.find(".uploaded-img").attr("src",baseUrl+"/assets/admin/img/noimage.jpg");$remove.attr("hidden",true).addClass("btk-hidden").hide();localStorage.removeItem(thumbKey)});
try{var td=localStorage.getItem(thumbKey);if(td){fetch(td).then(function(r){return r.blob()}).then(function(b){var f=new File([b],"draft-thumbnail.jpg",{type:"image/jpeg"}),dt=new DataTransfer();Object.defineProperty(f,"__btk",{value:true});dt.items.add(f);ti.files=dt.files;$wrap.find(".uploaded-img").attr("src",td);$remove.show()})}}catch(e){}}

// Always-visible gallery manager: independent of Dropzone's internal preview controls.
var $dz=$("#my-dropzone");
if($dz.length){
  var $manager=$('<div class="btk-gallery-manager"><div class="btk-gallery-manager-title">Uploaded gallery images <small>Use Remove to delete an image before saving.</small></div><div class="btk-gallery-items"></div></div>');
  $dz.after($manager);
  window.addEventListener("booktkit:gallery-uploaded",function(e){
    var d=e.detail||{}; if(!d.id)return;
    var src=d.preview_url||"";
    var $card=$('<div class="btk-gallery-card" data-gallery-id="'+d.id+'"><img src="'+src+'" alt="Gallery image"><button type="button" class="btn btn-danger btn-sm btk-gallery-remove"><i class="fas fa-trash"></i> Remove</button></div>');
    $manager.find(".btk-gallery-items").append($card);
  });
  $manager.on("click",".btk-gallery-remove",function(){
    var $card=$(this).closest(".btk-gallery-card"),id=$card.data("gallery-id"),btn=$(this);
    btn.prop("disabled",true).text("Removing...");
    if(typeof window.booktkitRemoveGalleryImage==="function"){
      window.booktkitRemoveGalleryImage(id);
      $("#slider"+id).remove();
      $card.fadeOut(150,function(){$card.remove()});
      var dzEl=document.getElementById("my-dropzone");
      if(dzEl&&dzEl.dropzone){dzEl.dropzone.files.slice().forEach(function(file){if(String(file.serverFileId)===String(id))dzEl.dropzone.removeFile(file)})}
    }
  });
}

form.addEventListener("submit",function(){setTimeout(function(){if(!$("#eventErrors").is(":visible")){localStorage.removeItem(key);localStorage.removeItem(thumbKey)}},1500)});
})(jQuery);