Overall Important Notes
========================

Stuff from FeedWordPress
------------------------

Ref to `feedwordpress.php` stuff
- 	`ln 163` and `164` have a dissucssion of the problem with WordPress applying filters to syndicated content.
	
	FWP uses `feedwordpress_preserve_syndicated_content` (`ln 478`) and `feedwordpress_restore_syndicated_content` (`ln 493`) to bypass this issue. It takes the content received, stores it and then, after all filters are applied, restores the origonal syndicated content. Something to keep in mind. We may need to do the same thing. 
	
-	**Good idea**: providing functions that can be used in the post template, make template aware of syndicated content vs non-syndicated? Get item source and include it, make format different depending on source. 

	Ex:
	*	FWP's template API starts at `ln 305`
	*	Good start with `is_syndicated` (`ln 317`)
	
-	The function that FWP uses to have the origonal syndicated post's link used as the post link is `syndication_permalink` on `ln 547`.

-	Should we worry about pings? See function set starting with `ln 742`.



Ideas for better visualization of syndicated content
----------------------------------------------------

-	We could use a function to include a preview image in both the feed and the nominations page. Perhaps even the post page? We can grab the opengraph image, if that isn't there grab the first image in the content. If that isn't there, a default image or no image?

	*	Possible ref? http://wpmu.org/daily-tip-how-to-automatically-set-the-featured-image-in-wordpress/
	
	
Possible Issues
---------------

-	Do we need to worry about updating posts when their origon post is updated? In other words, if the external post is updated should we update the internal post? If we do is there a time limit for this syncronization? 

	*	FWP deals with this in a number of places
	
		+ `feedwordpress_post_edit_controls` at `ln 805`