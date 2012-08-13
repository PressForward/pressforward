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