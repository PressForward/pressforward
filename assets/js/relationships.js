jQuery(document).ready(function() {
	jQuery('.star-item').click(function(evt){
		evt.preventDefault();
		var item 		= jQuery(this).closest('article');
		var id			= item.attr('pf-post-id');
		var parent		= jQuery(this).parent();
		var otherstar;
		if (parent.hasClass('modal-btns')){
			otherstar = item.find('header .star-item');
		} else {
			otherstar = item.find('.modal .star-item');
		}		

		dostarstuff(this, item, id, parent, otherstar);
		
	});
	
	function dostarstuff(obj, item, id, parent, otherstar){
		if (jQuery(obj).hasClass('btn-warning')){
		
			jQuery(obj).removeClass('btn-warning');
			jQuery(obj).html('<i class="icon-star"></i> Star');
			otherstar.removeClass('btn-warning');
			otherstar.html('<i class="icon-star"></i> Star');		
		} else {
			

			jQuery(obj).addClass('btn-warning');
			jQuery(obj).html('<i class="icon-star"></i> Starred');
			otherstar.addClass('btn-warning');
			otherstar.html('<i class="icon-star"></i> Starred');
		}
	}
});