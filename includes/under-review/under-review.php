<?php
//Code for Under Review menu page generation

//Duping code from 1053 in main.
//Mockup - https://gomockingbird.com/mockingbird/#mr28na1/I9lz7i

	echo '<div class="container-fluid">';
		echo '<div class="row-fluid">';
			echo '<div class="span12 title-span">';
				echo '<h1>' . PF_TITLE . ': Under Review</h1>';
				echo '<img class="loading-top" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />';
				echo '<div id="errors"></div>';
			echo '</div><!-- End title 9 span -->';
		echo '</div><!-- End Row -->';
		echo '<div class="row-fluid">';

			echo 	'<div class="span6">
						<div class="btn-group">
							<button type="submit" class="showarchived btn btn-warning" id="showarchived" value="' . __('Show archived', 'pf') . '">' . __('Show archived', 'pf') . '.</button>
							<button type="submit" class="btn btn-info feedsort" id="sortbyitemdate" value="' . __('Sort by item date', 'pf') . '" >' . __('Sort by item date', 'pf') . '</button>
							<button type="submit" class="btn btn-info feedsort" id="sortbynomdate" value="' . __('Sort by date Nominated', 'pf') . '">' . __('Sort by date Nominated', 'pf') . '</button>
							<button class="btn btn-inverse" id="fullscreenfeed">Full Screen</button>
						</div><!-- End btn-group -->
					</div><!-- End span6 -->';
			echo 	'<div class="span3 offset3">
						<button type="submit" class="delete btn btn-danger pull-right" id="archivenoms" value="' . __('Archive all', 'pf') . '" >' . __('Archive all', 'pf') . '</button>
					</div><!-- End span3 -->';
//Hidden here, user options, like 'show archived' etc...
				?><div id="page_data" style="display:none">
					<?php
						$current_user = wp_get_current_user();
						$current_user_id = $current_user->ID;
					?>
					<span id="current-user-id"><?php echo $current_user_id; ?></span>
					<?php

					?>
				</div><?php
		echo '</div><!-- End Row -->';
		?>
		<div class="pressforward-alertbox" style="display:none;">
			<div class="row-fluid">
				<div class="span11 pf-alert">
				</div>
				<div class="span1 pf-dismiss">
				<i class="icon-remove-circle">Close</i>
				</div>
			</div>
		</div>
		<?php
		echo '<div class="row-fluid" class="nom-row">';
#Bootstrap Accordion group
		echo '<div class="span12 nom-container accordion" id="nom-accordion">';
		wp_nonce_field('drafter', 'pf_drafted_nonce', false);
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
			if ($c < 20) {
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
			$count = 0;
			while ( $nom_query->have_posts() ) : $nom_query->the_post();

				//declare some variables for use, mostly in various meta roles.
				//1773 in rssforward.php for various post meta.

				//Get the submitter's user slug
				$metadata['submitters'] = $submitter_slug = get_the_author_meta('user_nicename');
				// Nomination (post) ID
				$metadata['nom_id'] = $nom_id = get_the_ID();
				//Number of Nominations recieved.
				$metadata['nom_count'] = $nom_count = get_post_meta($nom_id, 'nomination_count', true);
				//Permalink to orig content
				$metadata['permalink'] = $nom_permalink = get_post_meta($nom_id, 'nomination_permalink', true);
				$urlArray = parse_url($nom_permalink);
				//Source Site
				$metadata['source_link'] = isset( $urlArray['host'] ) ? $sourceLink = 'http://' . $urlArray['host'] : '';
				//Source site slug
				$metadata['source_slug'] = $sourceSlug = isset( $urlArray['host'] ) ? pf_slugger($urlArray['host'], true, false, true) : '';
				//RSS Author designation
				$metadata['authors'] = $item_authorship = get_post_meta($nom_id, 'authors', true);
				//Datetime item was nominated
				$metadata['date_nominated'] = $date_nomed = get_post_meta($nom_id, 'date_nominated', true);
				//Datetime item was posted to its home RSS
				$metadata['posted_date'] = $date_posted = get_post_meta($nom_id, 'posted_date', true);
				//Unique RSS item ID
				$metadata['item_id'] = $rss_item_id = get_post_meta($nom_id, 'origin_item_ID', true);
				//RSS-passed tags, comma seperated.
				$nom_tags = get_post_meta($nom_id, 'item_tags', true);
				$nomTagsArray = explode(",", $nom_tags);
				$nomTagClassesString = '';
				foreach ($nomTagsArray as $nomTag) { $nomTagClassesString .= pf_slugger($nomTag, true, false, true); $nomTagClassesString .= ' '; }
				//RSS-passed tags as slugs.
				$metadata['nom_tags'] = $nom_tag_slugs = $nomTagClassesString;
				//All users who nominated.
				$metadata['nominators'] = $nominators = get_post_meta($nom_id, 'nominator_array', true);
				//Number of times repeated in source.
				$metadata['source_repeat'] = $source_repeat = get_post_meta($nom_id, 'source_repeat', true);
				//Post-object tags
				$metadata['nom_tags'] = $nomed_tag_slugs = get_the_tags();
				$metadata['item_title'] = $item_title = get_the_title();
				$metadata['item_content'] = get_the_content();
				//UNIX datetime last modified.
				$timestamp_nom_last_modified = get_the_modified_date( 'U' );
				//UNIX datetime added to nominations.
				$timestamp_unix_date_nomed = strtotime($date_nomed);
				//UNIX datetime item was posted to its home RSS.
				$timestamp_item_posted = strtotime($date_posted);
				$archived_status = get_post_meta($nom_id, 'archived_by_user_status');
				if (!empty($archived_status)){
					$archived_status_string = '';
					$archived_user_string_match = 'archived_' . $current_user_id;
					foreach ($archived_status as $user_archived_status){
						if ($user_archived_status == $archived_user_string_match){
						$archived_status_string = 'archived';
						$dependent_style = 'display:none;';
						}
					}
				} else {
					$dependent_style = '';
					$archived_status_string = '';
				}

			?>
			<div class="row-fluid nom-container <?php echo $archived_status_string; ?>" id="<?php the_ID(); ?>" style="<?php echo $dependent_style; ?>">
			<div class="span12" id="item-box-<?php echo $count; ?>">
				<div class="row-fluid well accordion-group nom-item<?php pf_nom_class_tagger(array($submitter_slug, $nom_id, $item_authorship, $nom_tag_slugs, $nominators, $nomed_tag_slugs, $rss_item_id )); ?>" id="<?php echo $count; ?>">
					<div class="span12">

						<div class="sortable-hidden-meta" style="display:none;">
							<?php
							_e('UNIX timestamp from source RSS', 'pf');
							echo ': <span class="sortable_source_timestamp">' . $timestamp_item_posted . '</span><br />';

							_e('UNIX timestamp last modified', 'pf');
							echo ': <span class="sortable_mod_timestamp">' . $timestamp_nom_last_modified . '</span><br />';

							_e('UNIX timestamp date nominated', 'pf');
							echo ': <span class="sortable_nom_timestamp">' . $timestamp_unix_date_nomed . '</span><br />';

							_e('Times repeated in source feeds', 'pf');
							echo ': <span class="sortable_sources_repeat">' . $source_repeat . '</span><br />';

							_e('Number of nominations received', 'pf');
							echo ': <span class="sortable_nom_count">' . $nom_count . '</span><br />';

							_e('Slug for origon site', 'pf');
							echo ': <span class="sortable_origin_link_slug">' . $sourceSlug . '</span><br />';

							//Add an action here for others to provide additional sortables.

						echo '</div>';
						echo '<div class="row-fluid nom-content-container accordion-heading">';
							echo '<div class="span12">';
								echo '<a class="accordion-toggle" data-toggle="collapse" data-parent="#nom-accordion" href="#collapse' . $count . '" count="' . $count . '" style="display:block;">';
								//Figure out feature image later. Put it here when you do.
								echo '<div class="row-fluid span12">';
								remove_filter('get_the_excerpt', 'wp_trim_excerpt');
								add_filter( 'get_the_excerpt', 'pf_noms_excerpt' );
									echo '<h6 class="nom-title">' . get_the_title() . '</h6>';
									?>
									<div class="excerpt-graf" id="excerpt-graf-<?php echo $count; ?>">
										<?php print_r( get_the_excerpt() ); ?>
									</div>
									<?php
								remove_filter( 'get_the_excerpt', 'pf_noms_excerpt' );
								add_filter('get_the_excerpt', 'wp_trim_excerpt');
								echo '</div>';

								echo '</a>';
							echo '</div>';
						echo '</div>';
						echo '<div class="accordion-body collapse" id="collapse' . $count . '">';
						echo '<div class="accordion-inner">';
								echo '<div class="row-fluid span12 authorship-info">';
									$author_string = sprintf(__('Authored by %1$d on %2$d', 'pf'), $item_authorship, $date_posted);
									echo '<h6>' . $author_string . '</h6>';
									if ($nom_count > 1){
										$nomersArray = explode(',',$nominators);
										$userString = "";
										foreach ($nomersArray as $nomer){
											$userObj = get_userdata($nomer);
											$userString .= $userObj->user_nicename;
										}
										$nominators = $userString;
									} else {
										$userObj = get_userdata($nominators);
										$nominators = $userObj->user_nicename;
									}
									$nom_by_string = sprintf(__('Nominated by %1$d on %2$d', 'pf'), $nominators, date('Y-m-d', strtotime($date_nomed)));;
									echo '<h6>' . $nom_by_string . '</h6>';
								echo '</div>
										<div class="nom-content-body row-fluid span12">';
											the_content();
									echo '</div>';
								echo '<div class="item_commenting">';
								comment_form();
								echo '</div>';

						echo '</div>';
						echo '</div>';
					echo '</div>';

				echo '</div>';
			echo '</div>';

			echo '<div class="post-control span3 well" id="action-box-' . $count . '" style="display:none;">';
											?>
									<div class="nom-master-buttons row-fluid">
										<div class="span12">
											<div class="result-status-<?php echo $rss_item_id; ?>">
												<?php echo '<img class="loading-' . $rss_item_id . '" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="' . __('Loading', 'pf') . '..." style="display: none" />'; ?>
												<div class="msg-box"></div>
											</div>
											<form name="form-<?php echo $rss_item_id; ?>" id="<?php echo $rss_item_id ?>"><p>
												<?php pf_prep_item_for_submit($metadata); ?>
												<button class="btn btn-inverse nom-to-draft" form="<?php echo $rss_item_id ?>"><?php _e('Send to Draft', 'pf'); ?></button>
												<button class="btn btn-inverse nom-to-archive" form="<?php echo $nom_id ?>"><?php _e('Archive', 'pf'); ?></button>
															<?php $tax = get_taxonomy( 'category' ); ?>

			<div id="tagsdiv-post_tag" class="postbox">
				<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle', 'pf' ); ?>"><br /></div>
				<h3><span><?php _e('Tags'); ?></span></h3>
				<div class="inside">
					<div class="tagsdiv" id="post_tag">
						<div class="jaxtag">
							<label class="screen-reader-text" for="newtag"><?php _e('Tags'); ?></label>
							<input type="hidden" name="tax_input[post_tag]" class="the-tags" id="tax-input[post_tag] tag_input_<?php echo $rss_item_id; ?>" value="" />
							<div class="ajaxtag">
								<input type="text" name="newtag[post_tag]" class="newtag form-input-tip" size="16" autocomplete="off" value="" />
								<input type="button" class="button tagadd" value="<?php esc_attr_e('Add', 'pf'); ?>" tabindex="3" />
							</div>
						</div>
						<div class="tagchecklist"></div>
					</div>
					<p class="tagcloud-link"><a href="#titlediv" class="tagcloud-link" id="link-post_tag"><?php _e('Choose from the most used tags', 'pf'); ?></a></p>
				</div>
			</div>

											</form>
										</div>
									</div>
									<?php
			echo '</div>';
					?>
			</div>
			<?php
			$count++;
			endwhile;

		// Reset Post Data
		wp_reset_postdata();

		echo '</div><!-- End the posts nom-accordion -->';
		echo '</div><!-- End nom-row -->';


	echo '</div><!-- End container -->';


?>
