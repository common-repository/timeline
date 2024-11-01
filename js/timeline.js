jQuery(document).ready(function($) { 
	$(document).on('click', '.add_feature_image', function() {	
		var send_attachment_bkp = wp.media.editor.send.attachment;
		wp.media.editor.send.attachment = function(props, attachment) {
			$("#feature_image_holder").html('<a href="'+attachment.url+'" target="_blank"><img width="280" height="155" src="'+attachment.url+'" /></a>');
			$("input#attachment_id").val(attachment.id);
			wp.media.editor.send.attachment = send_attachment_bkp;
		}
		wp.media.editor.open();
		return false;    
	});
});
jQuery(function($){
	$('.tdih-scroller').each(function(){
				tdih_speed =400;
				curidx = 0;
				tdih_scroller_wrap = $('.tdih_list_wrap');
				tdih_scroller_content = $('.tdih-scroller-content');
				tdih_container = $('.tdih_list_content',tdih_scroller_content);
				tdih_item_li = $("li",$(this));
				tdih_item_li_size = $("li",$(this)).size()-1;
				tdih_item_prev = $(".tdih-prev",tdih_scroller_wrap);
				tdih_item_next = $(".tdih-next",tdih_scroller_wrap);
				tdih_item = $('.year',tdih_scroller_content);	
				tdih_item_top = 0;			
				tdih_item_left = 0;		
				tdih_item.each(function(){
					$this = $(this);
					$this.attr({
						'data-height':$this.outerHeight(true),					
						'data-width':$this.outerWidth(),			
						'data-top':$this.outerHeight() + tdih_item_top,	
						'data-left':$this.outerWidth() + tdih_item_left,
					});
					$this.data({
						height:$this.height(),					
						width:$this.width(),			
						top:$this.height() + tdih_item_top,	
						left:$this.width() + tdih_item_left,
					});
					tdih_item_top += $this.height(); 
					tdih_item_left += $this.width();  
				});
				function timeline_init(curidx){					
					console.log(curidx);
					tdih_scroller_content.css({
						height:tdih_item.eq(curidx).data('height')
					});
					tdih_container
						.animate(
							{
								marginTop:-tdih_item.eq(curidx).data('top')+tdih_item.eq(curidx).data('height')
							},
							{
								duration:tdih_speed
							});
					tdih_item_li
						.eq(curidx)
						.addClass("current")
						.siblings()
						.removeClass("current");					
					tdih_item_prev.data({
						index:curidx<0?tdih_item_li_size:curidx-1
					});				
					tdih_item_next.data({
						index:curidx>tdih_item_li_size-1?0:curidx+1
					});
					if(curidx==0){
						tdih_item_prev.hide();
					}else{
						tdih_item_prev.show();
					}
					if(curidx==tdih_item_li_size){
						tdih_item_next.hide();
					}else{
						tdih_item_next.show();
					}
				}
				timeline_init(0);
				$(this).find("a").click(function(){
					curidx = $(this).parent("li").index();
					timeline_init(curidx);
				});
				tdih_item_prev.click(function(){
					curidx = $(this).data("index");
					timeline_init(curidx);
				});
				tdih_item_next.click(function(){
					curidx = $(this).data("index");
					timeline_init(curidx);
				});
			});
}) 