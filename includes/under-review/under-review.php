<?php

//Code for Under Review menu page generation

	echo '<div class="container-fluid">';
		echo '<div class="row-fluid">';
			echo '<div class="span9 title-span">';
				echo '<h1>' . RSSPF_TITLE . ': Under Review</h1>';
				echo '<img class="loading-top" src="' . RSSPF_URL . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />';
				echo '<div id="errors"></div>';
			echo '</div><!-- End title 9 span -->';
		echo '</div><!-- End Row -->';
		echo '<div class="row-fluid">';

			echo 	'<div class="span6">
						<div class="btn-group">
							<button type="submit" class="refreshfeed btn btn-warning" id="refreshfeed" value="Refresh">Refresh</button>
							<button type="submit" class="btn btn-info feedsort" id="sortbyitemdate" value="Sort by item date" >Sort by item date</button>
							<button type="submit" class="btn btn-info feedsort" id="sortbyfeedindate" value="Sort by date entered RSS">Sort by date entered RSS</button>
							<button class="btn btn-inverse" id="fullscreenfeed">Full Screen</button>
						</div><!-- End btn-group -->
					</div><!-- End span6 -->';
			echo 	'<div class="span3 offset3">
						<button type="submit" class="delete btn btn-danger pull-right" id="deletefeedarchive" value="Delete entire feed archive" >Delete entire feed archive</button>
					</div><!-- End span3 -->';

		echo '</div><!-- End Row -->';
		
		
	echo '</div><!-- End container -->';

?>