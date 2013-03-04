/**
 * Display transform for pf
**/
jQuery(document).ready(function() {

	function modalNavigator(tabindex){
		var currentObj = jQuery('input[tabindex='+tabindex+']');
		var prevTab = tabindex-1;
		var nextTab = tabindex+1;
		var prevObj = jQuery('input[tabindex='+prevTab+']');
		var nextObj = jQuery('input[tabindex='+nextTab+']');
		
		//First lets assemble variables for the previous group.
		if (jQuery(prevObj).is('*')){
			var prevItemId = jQuery(prevObj).attr('id');
			var prevTitle = jQuery(prevObj).children('h1.item_title').text();
			var prevSource = jQuery(prevObj).children('p.source_title').text();
			var prevAuthor = jQuery(prevObj).children('span.item-authorship').text();
			var prevExcerpt = jQuery(prevObj).children('div.item_excerpt').html();
			var prevDate = jQuery(prevObj).children('p.pubdate').text();
		}
		//Next lets assemble variables for the next group.
		if (jQuery(nextObj).is('*')){
			var nextItemId = jQuery(nextObj).attr('id');
			var nextTitle = jQuery(nextObj).children('h1.item_title').text();
			var nextSource = jQuery(nextObj).children('p.source_title').text();
			var nextAuthor = jQuery(nextObj).children('span.item-authorship').text();
			var nextExcerpt = jQuery(nextObj).children('div.item_excerpt').html();
			var nextDate = jQuery(nextObj).children('p.pubdate').text();
		}
		
		
	}
	
	jQuery('#gogrid').click(function (evt){ 
			evt.preventDefault();
			jQuery("div.pf_container").removeClass('list').addClass('grid');
		});

	jQuery('#golist').click(function (evt){ 
			evt.preventDefault();
			jQuery("div.pf_container").removeClass('grid').addClass('list');
			jQuery('.feed-item').each(function (index){
				var element		= jQuery(this);
				var itemID		= element.attr('id');
				jQuery('#'+itemID+' header .actions').appendTo('#'+itemID+' footer');
			});
		}
	);
	
	jQuery('#gomenu').toggle(function (evt){ 
			evt.preventDefault();
			var toolswin = jQuery('#tools');
			jQuery("div.pf_container").removeClass('full');
			jQuery(toolswin).show('slide',{direction:'right', easing:'linear'},150);
		}, function() {
			var toolswin = jQuery('#tools');
			jQuery(toolswin).hide('slide',{direction:'right', easing:'linear'},150);
			jQuery("div.pf_container").addClass('full');
		});	
		
	//Need to fix this to only trigger on the specific model, but not sure how yet. 
	jQuery(".pressforward_page_pf-review .modal").on('show', function(evt){
		//alert('Modal Triggered.');
		jQuery('#wpadminbar').hide();
		document.body.style.overflow = 'hidden';
		var element = jQuery(this);		
		var modalID = element.attr('id');
		var bigModal = { 
			'display' : 'block',
			'position': 'fixed',
			'top': '0',
			'right': '0',
			'bottom': '100%',
			'left': '0',
			'margin': '0',
			'width': '100%',
			'height': '100%',
			'overflow' : 'hidden'
		};
		jQuery('#'+modalID).css(bigModal);
		//jQuery('#'+modalID+ ' .modal-header').css('max-height', '10%');
		jQuery('#'+modalID).css({'background-color' : '#f5f5f5', 'max-height' : '100%'});
		jQuery('#'+modalID+ ' .modal-header').css({'background-color' : 'white', 'max-height' : '10%'});
		jQuery('#'+modalID+ ' .modal-body').css({'max-height' : '80%', 'background-color' : 'white'});
		jQuery('#'+modalID+ ' .modal-footer').css({'max-height' : '10%', 'min-height' : '48px'});
		//alert(modalID);
		//showDiv(jQuery('#entries'), jQuery('#'+modalID));		
		var itemID = element.attr('pf-item-id');
		var postID = element.attr('pf-post-id');		
	});
	
	jQuery(".pressforward_page_pf-review .modal").on('hide', function(evt){
		jQuery('#wpadminbar').show();
		document.body.style.overflow = 'visible';
	});	
	
});