Overall Important Notes
========================

Stuff from FeedWordPress
------------------------

`feedwordpress.php` stuff
- 	ln 163 and 164 have a dissucssion of the problem with WordPress applying filters to syndicated content.
	
	FWP uses `feedwordpress_preserve_syndicated_content` and `feedwordpress_restore_syndicated_content` to bypass this issue. It takes the content received, stores it and then, after all filters are applied, restores the origonal syndicated content. Something to keep in mind. We may need to do the same thing. 