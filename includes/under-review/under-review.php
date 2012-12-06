<?php

//Code for Under Review menu page generation

//Duping code from 1053 in main. 
//Mockup - https://gomockingbird.com/mockingbird/#mr28na1/I9lz7i

	echo '<div class="container-fluid">';
		echo '<div class="row-fluid">';
			echo '<div class="span12 title-span">';
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
		
		echo '<div class="row-fluid" class="nom-row">';
		echo '<div class="span12 nom-container accordion" id="nom-accordion">';
		
		// Reset Post Data
		wp_reset_postdata();
		
			//This part here is for eventual use in pagination and then infinite scroll.
			$c = 0;
			if (isset($_GET["pc"])){
				$page = $_GET["pc"];
				$page = $page-1;
			} else {
				$page = 0;
			}
			$count = $page * 20;
			$c = $c+$count;
			if ($c <= 20) {
				$offset = 0;
			} else {
				$offset = $c;
			}
		
			//Now we must loop.
			//Eventually we may want to provide options to change some of these, so we're going to provide the default values for now.
			$nom_args = array(
							
							'post_type' => 'nomination',
							'orderby' => 'date',
							'order' => 'DESC'
							
							);
			$nom_query = new WP_Query( $nom_args );
			
			while ( $nom_query->have_posts() ) : $nom_query->the_post();
			
				//declare some variables for use
				//1773 in rssforward.php for various post meta.
				
				//Get the submitter's user slug
				$submitter_slug = get_the_author_meta('user_nicename');
				// Nomination (post) ID
				$nom_id = get_the_ID();				
				//Number of Nominations recieved. 
				$nom_count = get_post_meta($nom_id, 'nomination_count', true);
				//Permalink to orig content	
				$nom_permalink = get_post_meta($nom_id, 'nomination_permalink', true);
				//RSS Author designation
				$item_authorship = get_post_meta($nom_id, 'authors', true);
				//Datetime item was nominated
				$date_nomed = get_post_meta($nom_id, 'date_nominated', true);
				//Datetime item was posted to its home RSS
				$date_posted = get_post_meta($nom_id, 'posted_date', true);
				//RSS-passed tags, comma seperated.
				$nom_tags = get_post_meta($nom_id, 'item_tags', true);
				$nomTagsArray = explode(",", $nom_tags);
				$nomTagClassesString = '';
				foreach ($nomTagsArray as $nomTag) { $nomTagClassesString .= $this->slugger($nomTag, true, false, true); $nomTagClassesString .= ' '; }
				//RSS-passed tags as slugs.
				$nom_tag_slugs = $nomTagClassesString;
				//All users who nominated.
				$nominators = get_post_meta($nom_id, 'nominator_array', true);
				//Number of times repeated in source. 
				$source_repeat = get_post_meta($nom_id, 'source_repeat', true);
				//Post-object tags
				$nomed_tag_slugs = implode(" ", get_the_tags());
				//UNIX datetime last modified.
				$timestamp_nom_last_modified = get_the_modified_date( 'U' );
				//UNIX datetime added to nominations. 
				$timestamp_unix_date_nomed = strtotime($date_nomed);
				//UNIX datetime item was posted to its home RSS.
				$timestamp_item_posted = strtotime($date_posted);
			
			?>
				
				<div class="row well accordion-group nom-item <?php ?>">
					<div class="span12">
					</div>
				</div>
				
			<?php
			
			endwhile;
			
		// Reset Post Data
		wp_reset_postdata();	
		
		echo '</div><!-- End the posts nom-accordion -->';
		echo '</div><!-- End nom-row -->';
		
		<?php
		
		
	echo '</div><!-- End container -->';

?>