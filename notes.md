Overall Important Notes
========================

Stuff from FeedWordPress
------------------------

Ref to `feedwordpress.php` stuff
- 	`ln 163` and `164` have a discussion of the problem with WordPress applying filters to syndicated content.
	
	FWP uses `feedwordpress_preserve_syndicated_content` (`ln 478`) and `feedwordpress_restore_syndicated_content` (`ln 493`) to bypass this issue. It takes the content received, stores it and then, after all filters are applied, restores the original syndicated content. Something to keep in mind. We may need to do the same thing. 
	
-	**Good idea**: providing functions that can be used in the post template, make template aware of syndicated content vs non-syndicated? Get item source and include it, make format different depending on source. 

	Ex:
	*	FWP's template API starts at `ln 305`
	*	Good start with `is_syndicated` (`ln 317`)
	
-	Should we create a similar functionality to FWP's replacing native permalink with RSS content permalink? 
	
	*	A function that FWP uses to have the original syndicated post's link used as the post link is `syndication_permalink` on `ln 547`.
	
	*	Another: `munge_permalinks` at `ln 1405`.
	
-	Do we want to match categories? Match tags? **Yes!**

	*	Some of FWP's functions start at `ln 1422`.

-	Should we worry about pings? See function set starting with `ln 742`.

-	Interesting notes on Magpie and SimplePie use at `ln 1544`.

-	Do we need the `FeedFinder` class on `ln 13` of `feedfinder.class.php`? Doesn't SimplePie do that already? I'm pretty sure it does. Should we bother supplying support to pre-2.8 WP with Magpie? Prob. not. 

-	It looks like FWP started working on OPML parsing in `feedfinder.class.php` on `ln 238` with `_opml_rss_uris()`.


Stuff from MyCurator
--------------------

-	Perhaps worth considering the API key mechanism? 

-	Simple category creation? `MyCurator.php` at `ln 466`. Look into `wp_insert_term`.

-	Looks like it creates a separate database table to store Relevance rating info. The `Relevance` class is located in `MyCurator_local_classify.php` at `ln 97`.

-	It also seems to create a separate table for the list of sources, `ln 789` of `MyCurator.php`. 

-	Interesting use of Google Pie Chart API at `ln 1085` of `MyCurator.php`.


Looking at ThreeWP Broadcast
----------------------------




Use WP Cron for pulling posts?
------------------------------
Ref:
-	http://themocracy.com/2010/02/wp-cron-automating-scheduling/
-	http://wp.tutsplus.com/articles/insights-into-wp-cron-an-introduction-to-scheduling-tasks-in-wordpress/
-	http://wp.tutsplus.com/tutorials/theme-development/do-it-yourself-wordpress-scheduling-mastering-wp-cron/



Ideas for better visualization of syndicated content
----------------------------------------------------

-	We could use a function to include a preview image in both the feed and the nominations page. Perhaps even the post page? We can grab the opengraph image, if that isn't there grab the first image in the content. If that isn't there, a default image or no image?

	*	Possible ref? http://wpmu.org/daily-tip-how-to-automatically-set-the-featured-image-in-wordpress/
	
-	What (if anything) should go in a dashboard widget? 

	*	Looking at FWP, is it really pulling down the array of Dashboard widgets and adding to them (`ln 1186`)?! Isn't there a function for that? Like `wp_add_dashboard_widget`.
	
	
Possible Issues
---------------

-	Do we need to worry about updating posts when their origin post is updated? In other words, if the external post is updated should we update the internal post? If we do is there a time limit for this synchronization? 

	*	FWP deals with this in a number of places
	
		+ `feedwordpress_post_edit_controls` at `ln 805`
		
-	Do we want to assign authorship of a post from the author property of the RSS feed item? Perhaps do that and also pass and display properties for **orig author**, **orig nominated**, and **editor who published**?

	*	If so... how to handle inc. authors? A separate options field? 
	
-	If the feed parsing happens on our side, how much functionality needs to be built in for figuring out an RSS feed if it isn't found via SimplePie? Just how good at finding RSS feeds is SimplePie? 

-	If the feed parsing happens elsewhere, does the plugin need to have an API key? For each person, each site? 

-	A thought? If we want to maintain using the RSS feed to push down from the recommendation engine... why not use the `media:rating` node in RSS? SimplePie has a function to deal with it. I know it is not what it is made for, but this way we don't have to worry about build a whole bunch of extra stuff to parse the engine's output. 