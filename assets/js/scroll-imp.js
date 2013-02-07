jQuery(document).ready(function () {

 
    var container = jQuery('.feed-container');
    
    container.infinitescroll({
      navSelector  : '.pf-navigation',    // selector for the paged navigation 
      nextSelector : '.feednext a',  // selector for the NEXT link (to page 2)
      itemSelector : '.feed-item',     // selector for all items you'll retrieve
      loading: {
          finishedMsg: 'No more pages to load.',
          img: 'images/wpspin_light.gif'
        }
      }
    
	
	);
	


});