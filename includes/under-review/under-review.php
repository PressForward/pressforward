<?php
//Code for Under Review menu page generation

//Duping code from 1053 in main.
//Mockup - https://gomockingbird.com/mockingbird/#mr28na1/I9lz7i

		//Calling the feedlist within the pf class.
		if (isset($_GET["pc"])){
			$page = $_GET["pc"];
			$page = $page-1;
		} else {
			$page = 0;
		}
		$count = $page * 20;
		$countQ = 0;
		$extra_class = '';
		if(isset($_GET['reveal']) && ('no_hidden' == $_GET['reveal'])){
			$extra_class .= ' archived_visible';
		} else {
			$extra_class .= '';
		}
	?>
	<div class="pf-loader"></div>
	<div class="list pf_container pf-nominated full<?php echo $extra_class; ?>">
		<header id="app-banner">
			<div class="title-span title">
				<?php pressforward()->form_of->the_page_headline('Nominated'); ?>
				<button class="btn btn-small" id="fullscreenfeed"> <?php  _e('Full Screen', 'pf');  ?> </button>
			</div><!-- End title -->
				<?php pressforward()->admin->pf_search_template(); ?>
		</header><!-- End Header -->

		<?php pressforward()->admin->nav_bar('pf-review'); ?>


		<div role="main">
			<?php $this->toolbox();	?>
			<?php $this->folderbox(); ?>
			<div id="entries">
				<?php echo '<img class="loading-top" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />';  ?>
				<div id="errors">
					<div class="pressforward-alertbox" style="display:none;">
						<div class="row-fluid">
							<div class="span11 pf-alert">
							</div>
							<div class="span1 pf-dismiss">
							<i class="icon-remove-circle">Close</i>
							</div>
						</div>
					</div>
				</div>


		<?php

//Hidden here, user options, like 'show archived' etc...
				?><div id="page_data" style="display:none">
					<?php
						$current_user = wp_get_current_user();
						$metadata['current_user'] = $current_user->slug;
						$metadata['current_user_id'] = $current_user_id = $current_user->ID;
					?>
					<span id="current-user-id"><?php echo $current_user_id; ?></span>
					<?php

					?>
				</div>
		<?php
		echo '<div class="row-fluid" class="nom-row">';
#Bootstrap Accordion group
		echo '<div class="span12 nom-container" id="nom-accordion">';
		wp_nonce_field('drafter', 'pf_drafted_nonce', false);
		// Reset Post Data
		wp_reset_postdata();

			//This part here is for eventual use in pagination and then infinite scroll.
			$c = 0;
			$c = $c+$count;
			if ($c < 20) {
				$offset = 0;
			} else {
				$offset = $c;
			}

			//Now we must loop.
			//Eventually we may want to provide options to change some of these, so we're going to provide the default values for now.
			$pageCheck = absint($page);
			if (!$pageCheck){ $pageCheck = 1; }

			$nom_args = array(

							'post_type' => 'nomination',
							'orderby' => 'date',
							'order' => 'DESC',
							'posts_per_page' => 20,
							'suppress_filters' => FALSE,
							'offset' => $offset  #The query function will turn page into a 1 if it is a 0.

							);
			if (isset($_GET['feed'])) {
				$nom_args['post_parent'] = $_GET['feed'];
			} elseif (isset($_GET['folder'])){
				$parents_in_folder = new WP_Query( array(
					'post_type' => pressforward()->pf_feeds->post_type,
					'fields'=> 'ids',
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'tax_query' => array(
						array(
							'taxonomy' => pressforward()->pf_feeds->tag_taxonomy,
							'field'	=> 'term_id',
							'terms'	=> $_GET['folder']
						),
					),
				) );
				#var_dump('<pre>'); var_dump($parents_in_folder); die();
				$nom_args['post_parent__in'] = $parents_in_folder->posts;
			}

			add_filter( 'posts_request', 'prep_archives_query');
			$nom_query = new WP_Query( $nom_args );
			remove_filter( 'posts_request', 'prep_archives_query' );
			#var_dump($nom_query);
			$count = 0;
			$countQ = $nom_query->post_count;
			$countQT = $nom_query->found_posts;
			//print_r($countQ);
			while ( $nom_query->have_posts() ) : $nom_query->the_post();

				//declare some variables for use, mostly in various meta roles.
				//1773 in rssforward.php for various post meta.

				//Get the submitter's user slug
				$metadata['submitters'] = $submitter_slug = get_the_author_meta('user_nicename');
				// Nomination (post) ID
				$metadata['nom_id'] = $nom_id = get_the_ID();
				//Get the WP database ID of the original item in the database.
				$metadata['item_feed_post_id'] = pf_get_post_meta($nom_id, 'item_feed_post_id', true);
				//Number of Nominations recieved.
				$metadata['nom_count'] = $nom_count = pf_retrieve_meta($nom_id, 'nomination_count');
				//Permalink to orig content
				$metadata['permalink'] = $nom_permalink = pf_get_post_meta($nom_id, 'item_link', true);
				$urlArray = parse_url($nom_permalink);
				//Source Site
				$metadata['source_link'] = isset( $urlArray['host'] ) ? $sourceLink = 'http://' . $urlArray['host'] : '';
				//Source site slug
				$metadata['source_slug'] = $sourceSlug = isset( $urlArray['host'] ) ? pf_slugger($urlArray['host'], true, false, true) : '';
				//RSS Author designation
				$metadata['authors'] = $item_authorship = pf_get_post_meta($nom_id, 'item_author', true);
				//Datetime item was nominated
				$metadata['date_nominated'] = $date_nomed = pf_get_post_meta($nom_id, 'date_nominated', true);
				//Datetime item was posted to its home RSS
				$metadata['posted_date'] = $date_posted = pf_get_post_meta($nom_id, 'posted_date', true);
				//Unique RSS item ID
				$metadata['item_id'] = $rss_item_id = pf_get_post_meta($nom_id, 'origin_item_ID', true);
				//RSS-passed tags, comma seperated.
				$item_nom_tags = $nom_tags = pf_get_post_meta($nom_id, 'item_tags', true);
				$wp_nom_tags = '';
				$getTheTags = get_the_tags();
				if (empty($getTheTags)){
					$getTheTags[] = '';
					$wp_nom_tags = '';
					$wp_nom_slugs[] = '';
				} else {
					foreach ($getTheTags as $tag){
						$wp_nom_tags .= ', ';
						$wp_nom_tags .= $tag->name;
					}
					$wp_nom_slugs = array();
					foreach ($getTheTags as $tag){
						$wp_nom_slugs[] = $tag->slug;
					}

				}
				$metadata['nom_tags'] = $nomed_tag_slugs = $wp_nom_slugs;
				$metadata['all_tags'] = $nom_tags .= $wp_nom_tags;
				$nomTagsArray = explode(",", $item_nom_tags);
				$nomTagClassesString = '';
				foreach ($nomTagsArray as $nomTag) { $nomTagClassesString .= pf_slugger($nomTag, true, false, true); $nomTagClassesString .= ' '; }
				//RSS-passed tags as slugs.
				$metadata['item_tags'] = $nom_tag_slugs = $nomTagClassesString;
				//All users who nominated.
				$metadata['nominators'] = $nominators = pf_get_post_meta($nom_id, 'nominator_array', true);
				//Number of times repeated in source.
				$metadata['source_repeat'] = $source_repeat = pf_get_post_meta($nom_id, 'source_repeat', true);
				//Post-object tags
				$metadata['item_title'] = $item_title = get_the_title();
				$metadata['item_content'] = get_the_content();
				//UNIX datetime last modified.
				$metadata['timestamp_nom_last_modified'] = $timestamp_nom_last_modified = get_the_modified_date( 'U' );
				//UNIX datetime added to nominations.
				$metadata['timestamp_unix_date_nomed'] = $timestamp_unix_date_nomed = strtotime($date_nomed);
				//UNIX datetime item was posted to its home RSS.
				$metadata['timestamp_item_posted'] = $timestamp_item_posted = strtotime($date_posted);
				$metadata['archived_status'] = $archived_status = pf_get_post_meta($nom_id, 'archived_by_user_status');
				$userObj = wp_get_current_user();
				$user_id = $userObj->ID;


				if (!empty($metadata['archived_status'])){
					$archived_status_string = '';
					$archived_user_string_match = 'archived_' . $current_user_id;
					foreach ($archived_status as $user_archived_status){
						if ($user_archived_status == $archived_user_string_match){
							$archived_status_string = 'archived';
							$dependent_style = 'display:none;';
						}
					}
				} elseif ( 1 == pf_get_relationship_value( 'archive', $nom_id, $user_id)) {
					$archived_status_string = 'archived';
					$dependent_style = 'display:none;';
				} else {
					$dependent_style = '';
					$archived_status_string = '';
				}
			$item = pf_feed_object(get_the_title(), pf_get_post_meta($nom_id, 'source_title', true), $date_posted, $item_authorship, get_the_content(), $nom_permalink, get_the_post_thumbnail($nom_id /**, 'nom_thumb'**/), $rss_item_id, pf_get_post_meta($nom_id, 'item_wp_date', true), $nom_tags, $date_nomed, $source_repeat, $nom_id, '1');

			$this->form_of_an_item($item, $c, 'nomination', $metadata);
			$count++;
			$c++;
			endwhile;

		// Reset Post Data
		wp_reset_postdata();
		?><div class="clear"></div><?php
		echo '</div><!-- End under review entries -->';

	echo '</div><!-- End main -->';
	if ($countQT > $countQ){
				if ($page == 0){ $page = 1; }
		$pagePrev = $page-1;
		$pageNext = $page+1;
		if (!empty($_GET['by'])){
			$limit_q = '&by=' . $limit;
		} else {
			$limit_q = '';
		}
		$pagePrev = '?page=pf-review'.$limit_q.'&pc=' . $pagePrev;
		$pageNext = '?page=pf-review'.$limit_q.'&pc=' . $pageNext;
		if (isset($_GET['folder'])){
			$pageQ = $_GET['folder'];
			$pageQed = '&folder=' . $pageQ;
			$pageNext .= $pageQed;
			$pagePrev .= $pageQed;

		}
		if (isset($_GET['feed'])){
			$pageQ = $_GET['feed'];
			$pageQed = '&feed=' . $pageQ;
			$pageNext .= $pageQed;
			$pagePrev .= $pageQed;

		}
		//Nasty hack because infinite scroll only works starting with page 2 for some reason.
		echo '<div class="pf-navigation">';
		if ($pagePrev > -1){
			echo '<span class="feedprev"><a class="prevnav" href="admin.php?page=pf-review&pc=' . $pagePrev . '">Previous Page</a></span> | ';
		}
		echo '<span class="feednext"><a class="nextnav" href="admin.php?page=pf-review&pc=' . $pageNext . '">Next Page</a></span>';
		?><div class="clear"></div><?php
		echo '</div>';
	}
?><div class="clear"></div><?php
echo '</div><!-- End container-fluid -->';

?>
