<?php
namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Core\Admin\PFTemplater as PFTemplater;
use PressForward\Core\Utility\Forward_Tools as Forward_Tools;
use PressForward\Core\Schema\Nominations as Nominations;
use PressForward\Controllers\Metas;
use PressForward\Interfaces\SystemUsers;
use WP_Ajax_Response;
use WP_Query;

class Nominated implements HasActions {

	function __construct( $metas, PFTemplater $template_factory, Forward_Tools $forward_tools, Nominations $nominations, SystemUsers $user_interface ) {
		$this->metas            = $metas;
		$this->template_factory = $template_factory;
		$this->forward_tools    = $forward_tools;
		$this->nomination_slug  = $nominations->post_type;
		$this->user_interface   = $user_interface;
	}

	public function action_hooks() {
		return array(
			array(
				'hook'     => 'admin_menu',
				'method'   => 'add_plugin_admin_menu',
				'priority' => 12,
			),
			array(
				'hook'     => 'feeder_menu',
				'method'   => 'nominate_this_tile',
				'priority' => 11,
			),
			array(
				'hook'   => 'edit_post',
				'method' => 'send_nomination_for_publishing',
			),
			array(
				'hook'   => 'nominations_box',
				'method' => 'nominations_box_builder',
			),
			array(
				'hook'   => 'manage_nomination_posts_custom_column',
				'method' => 'nomination_custom_columns',
			),
		);
	}


	public function add_plugin_admin_menu() {

		add_submenu_page(
			PF_MENU_SLUG,
			__( 'Nominated', 'pf' ),
			__( 'Nominated', 'pf' ),
			get_option( 'pf_menu_under_review_access', $this->user_interface->pf_get_defining_capability_by_role( 'contributor' ) ),
			PF_SLUG . '-review',
			array( $this, 'display_review_builder' )
		);

	}

	public function display_review_builder() {
		// Code for Under Review menu page generation
		// Duping code from 1053 in main.
		// Mockup - https://gomockingbird.com/mockingbird/#mr28na1/I9lz7i
				// Calling the feedlist within the pf class.
		if ( isset( $_GET['pc'] ) ) {
			$page = $_GET['pc'];
			$page = $page - 1;
		} else {
			$page = 0;
		}
				$count       = $page * 20;
				$countQ      = 0;
				$extra_class = '';
		if ( isset( $_GET['reveal'] ) && ( 'no_hidden' == $_GET['reveal'] ) ) {
			$extra_class .= ' archived_visible';
		} else {
			$extra_class .= '';
		}
				?>
				<div class="pf-loader"></div>
				<div class="list pf_container pf-nominated full<?php echo $extra_class; ?>">
				<header id="app-banner">
					<div class="title-span title">
						<?php pressforward( 'controller.template_factory' )->the_page_headline( 'Nominated' ); ?>
						<button class="btn btn-small" id="fullscreenfeed"> <?php _e( 'Full Screen', 'pf' ); ?> </button>
					</div><!-- End title -->
						<?php pressforward( 'admin.templates' )->search_template(); ?>
				</header><!-- End Header -->

				<?php pressforward( 'admin.templates' )->nav_bar( 'pf-review' ); ?>


				<div role="main">
					<?php pressforward( 'admin.templates' )->the_side_menu(); ?>
					<?php pressforward( 'schema.folders' )->folderbox(); ?>
					<div id="entries">
						<?php echo '<img class="loading-top" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />'; ?>
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

				// Hidden here, user options, like 'show archived' etc...
						?>
						<div id="page_data" style="display:none">
							<?php
								$current_user                = wp_get_current_user();
								$metadata['current_user']    = $current_user->slug;
								$metadata['current_user_id'] = $current_user_id = $current_user->ID;
							?>
							<span id="current-user-id"><?php echo $current_user_id; ?></span>
															</div>
					<?php
					echo '<div class="row-fluid" class="nom-row">';
					// Bootstrap Accordion group
					echo '<div class="span12 nom-container" id="nom-accordion">';
					wp_nonce_field( 'drafter', 'pf_drafted_nonce', false );
					// Reset Post Data
					wp_reset_postdata();

					// This part here is for eventual use in pagination and then infinite scroll.
					$c = 0;
					$c = $c + $count;
					if ( $c < 20 ) {
						$offset = 0;
					} else {
						$offset = $c;
					}

					// Now we must loop.
					// Eventually we may want to provide options to change some of these, so we're going to provide the default values for now.
					$pageCheck = absint( $page );
					if ( ! $pageCheck ) {
						$pageCheck = 1; }

					$nom_args = array(

						'post_type'        => 'nomination',
						'orderby'          => 'date',
						'order'            => 'DESC',
						'posts_per_page'   => 20,
						'suppress_filters' => false,
						'offset'           => $offset, // The query function will turn page into a 1 if it is a 0.

					);
					if ( isset( $_GET['feed'] ) ) {
						$nom_args['post_parent'] = $_GET['feed'];
					} elseif ( isset( $_GET['folder'] ) ) {
						$parents_in_folder = new WP_Query(
							array(
								'post_type'              => pressforward( 'schema.feeds' )->post_type,
								// 'fields'=> 'ids',
								'update_post_term_cache' => false,
								'update_post_meta_cache' => false,
								'tax_query'              => array(
									array(
										'taxonomy' => pressforward( 'schema.feeds' )->tag_taxonomy,
										'field'    => 'term_id',
										'terms'    => $_GET['folder'],
									),
								),
							)
						);
						// var_dump('<pre>'); var_dump($parents_in_folder); die();
						$nom_args['post_parent__in'] = $parents_in_folder->posts;
					}

					add_filter( 'posts_request', 'prep_archives_query' );
					$nom_query = new WP_Query( $nom_args );
					remove_filter( 'posts_request', 'prep_archives_query' );
					// var_dump($nom_query);
					$count      = 0;
					$countQ     = $nom_query->post_count;
					$countQT    = $nom_query->found_posts;
					$maxNbPages = $nom_query->max_num_pages;
					// print_r($countQ);
					while ( $nom_query->have_posts() ) :
						$nom_query->the_post();

						// declare some variables for use, mostly in various meta roles.
						// 1773 in rssforward.php for various post meta.
						// Get the submitter's user slug
						$metadata['submitters'] = $submitter_slug = get_the_author_meta( 'nicename' );
						// Nomination (post) ID
						$metadata['nom_id'] = $nom_id = get_the_ID();
						// Get the WP database ID of the original item in the database.
						$metadata['pf_item_post_id'] = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'pf_item_post_id', true );
						// Number of Nominations recieved.
						$metadata['nom_count'] = $nom_count = pressforward( 'controller.metas' )->retrieve_meta( $nom_id, 'nomination_count' );
						// Permalink to orig content
						$metadata['permalink'] = $nom_permalink = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_link', true );
						$urlArray              = parse_url( $nom_permalink );
						// Source Site
						$metadata['source_link'] = isset( $urlArray['host'] ) ? $sourceLink = 'http://' . $urlArray['host'] : '';
						// Source site slug
						$metadata['source_slug'] = $sourceSlug = isset( $urlArray['host'] ) ? pf_slugger( $urlArray['host'], true, false, true ) : '';
						// RSS Author designation
						$metadata['item_author'] = $item_authorship = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_author', true );
						// Datetime item was nominated
						$metadata['date_nominated'] = $date_nomed = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'date_nominated', true );
						// Datetime item was posted to its home RSS
						$metadata['item_date'] = $date_posted = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_date', true );
						// Unique RSS item ID
						$metadata['item_id'] = $rss_item_id = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_id', true );
						// RSS-passed tags, comma seperated.
						$item_nom_tags = $nom_tags = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_tags', true );
						$wp_nom_tags   = '';
						$getTheTags    = array();// get_the_tags();
						if ( empty( $getTheTags ) ) {
							$getTheTags[]   = '';
							$wp_nom_tags    = '';
							$wp_nom_slugs[] = '';
						} else {
							foreach ( $getTheTags as $tag ) {
								$wp_nom_tags .= ', ';
								$wp_nom_tags .= $tag->name;
							}
							$wp_nom_slugs = array();
							foreach ( $getTheTags as $tag ) {
								$wp_nom_slugs[] = $tag->slug;
							}
						}
						$metadata['nom_tags'] = $nomed_tag_slugs = $wp_nom_slugs;
						$metadata['all_tags'] = $nom_tags .= $wp_nom_tags;
						$nomTagsArray         = explode( ',', $item_nom_tags );
						$nomTagClassesString  = '';
						foreach ( $nomTagsArray as $nomTag ) {
							$nomTagClassesString .= pf_slugger( $nomTag, true, false, true );
							$nomTagClassesString .= ' '; }
						// RSS-passed tags as slugs.
						$metadata['item_tags'] = $nom_tag_slugs = $nomTagClassesString;
						// All users who nominated.
						$metadata['nominators'] = $nominators = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'nominator_array', true );
						// Number of times repeated in source.
						$metadata['source_repeat'] = $source_repeat = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'source_repeat', true );
						// Post-object tags
						$metadata['item_title']   = $item_title = get_the_title();
						$metadata['item_content'] = get_the_content();
						// UNIX datetime last modified.
						$metadata['timestamp_nom_last_modified'] = $timestamp_nom_last_modified = get_the_modified_date( 'U' );
						// UNIX datetime added to nominations.
						$metadata['timestamp_unix_date_nomed'] = $timestamp_unix_date_nomed = strtotime( $date_nomed );
						// UNIX datetime item was posted to its home RSS.
						$metadata['timestamp_item_posted'] = $timestamp_item_posted = strtotime( $date_posted );
						$metadata['archived_status']       = $archived_status = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'archived_by_user_status' );
						$userObj                           = wp_get_current_user();
						$user_id                           = $userObj->ID;

						if ( ! empty( $metadata['archived_status'] ) ) {
							$archived_status_string     = '';
							$archived_user_string_match = 'archived_' . $current_user_id;
							foreach ( $archived_status as $user_archived_status ) {
								if ( $user_archived_status == $archived_user_string_match ) {
									$archived_status_string = 'archived';
									$dependent_style        = 'display:none;';
								}
							}
						} elseif ( 1 == pf_get_relationship_value( 'archive', $nom_id, $user_id ) ) {
							$archived_status_string = 'archived';
							$dependent_style        = 'display:none;';
						} else {
							$dependent_style        = '';
							$archived_status_string = '';
						}
						$item = pf_feed_object( get_the_title(), pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'source_title', true ), $date_posted, $item_authorship, get_the_content(), $nom_permalink, get_the_post_thumbnail( $nom_id /**, 'nom_thumb'*/ ), $rss_item_id, pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_wp_date', true ), $nom_tags, $date_nomed, $source_repeat, $nom_id, '1' );

						pressforward( 'admin.templates' )->form_of_an_item( $item, $c, 'nomination', $metadata );
						$count++;
						$c++;
						endwhile;

					// Reset Post Data
					wp_reset_postdata();
					?>
					<div class="clear"></div>
					<?php
					echo '</div><!-- End under review entries -->';

					echo '</div><!-- End main -->';
					if ( $countQT > $countQ ) {
						$page += 1;
						if ( $page == 0 ) {
							$page = 1; }
						$pagePrevNb = $page - 1;
						$pageNextNb = $page + 1;
						if ( ! empty( $_GET['by'] ) ) {
							$limit_q = '&by=' . $limit;
						} else {
							$limit_q = '';
						}
						$pagePrev = '?page=pf-review' . $limit_q . '&pc=' . $pagePrevNb;
						$pageNext = '?page=pf-review' . $limit_q . '&pc=' . $pageNextNb;
						if ( isset( $_GET['folder'] ) ) {
							$pageQ     = $_GET['folder'];
							$pageQed   = '&folder=' . $pageQ;
							$pageNext .= $pageQed;
							$pagePrev .= $pageQed;

						}
						if ( isset( $_GET['feed'] ) ) {
							$pageQ     = $_GET['feed'];
							$pageQed   = '&feed=' . $pageQ;
							$pageNext .= $pageQed;
							$pagePrev .= $pageQed;

						}
						if ( isset( $_GET['pf-see'] ) && $_GET['pf-see'] != '' ) {
							$pageQ     = $_GET['pf-see'];
							$pageQed   = '&pf-see=' . $pageQ;
							$pageNext .= $pageQed;
							$pagePrev .= $pageQed;
						}
						// Nasty hack because infinite scroll only works starting with page 2 for some reason.
						echo '<div class="pf-navigation">';
						if ( $pagePrevNb > 0 ) {
							echo '<span class="feedprev"><a class="prevnav" href="admin.php' . $pagePrev . '">Previous Page</a></span> | ';
						} else {
							echo '<span class="feedprev">Previous Page</span> | ';
						}
						if ( $pageNextNb > $maxNbPages ) {
							echo '<span class="feednext">Next Page</span>';
						} else {
							echo '<span class="feednext"><a class="nextnav" href="admin.php' . $pageNext . '">Next Page</a></span>';
						}
						?>
						<div class="clear"></div>
						<?php
						echo '</div>';
					}
			?>
			<div class="clear"></div>
			<?php
			echo '</div><!-- End container-fluid -->';

	}

	public function send_nomination_for_publishing() {
		global $post;

		ob_start();
		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		// if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		if ( isset( $_POST['post_status'] ) && isset( $_POST['post_type'] ) && ( ( $_POST['post_status'] == 'publish' ) || ( $_POST['post_status'] == 'draft' ) ) && ( $_POST['post_type'] == $this->nomination_slug ) && ! empty( $_POST['ID'] ) ) {
			// print_r($_POST); die();
			$item_id = $this->metas->get_post_pf_meta( $_POST['ID'], 'item_id', true );
			pf_log( 'Sending to last step ' . $item_id . ' from Nomination post ' . $_POST['ID'] );
			ob_end_clean();
			return $this->forward_tools->nomination_to_last_step( $item_id, $_POST['ID'] );
		} else {
			ob_end_clean();
		}

	}

	public function nominate_this_tile() {
		$this->template_factory->nominate_this( 'as_feed' );
	}

		// Via http://slides.helenhousandi.com/wcnyc2012.html#15 and http://svn.automattic.com/wordpress/tags/3.4/wp-admin/includes/class-wp-posts-list-table.php
	function nomination_custom_columns( $column ) {

		global $post;
		switch ( $column ) {
			case 'nomcount':
				echo $this->metas->get_post_pf_meta( $post->ID, 'nomination_count', true );
				break;
			case 'nominatedby':
				$nominatorID = $this->metas->get_post_pf_meta( $post->ID, 'submitted_by', true );
				$user        = get_user_by( 'id', $nominatorID );
				if ( is_a( $user, 'WP_User' ) ) {
					echo $user->display_name;
				}
				break;
			case 'original_author':
				$orig_auth = $this->metas->get_post_pf_meta( $post->ID, 'item_author', true );
				echo $orig_auth;
				break;
			case 'date_nominated':
				$dateNomed = $this->metas->get_post_pf_meta( $post->ID, 'date_nominated', true );
				echo $dateNomed;
				break;

		}
	}

	public function nominations_box_builder() {
		// wp_nonce_field( 'nominate_meta', 'nominate_meta_nonce' );
		$origin_item_ID   = $this->metas->get_post_pf_meta( $post->ID, 'item_id', true );
		$nomination_count = $this->metas->get_post_pf_meta( $post->ID, 'nomination_count', true );
		$submitted_by     = $this->metas->get_post_pf_meta( $post->ID, 'submitted_by', true );
		$source_title     = $this->metas->get_post_pf_meta( $post->ID, 'source_title', true );
		$posted_date      = $this->metas->get_post_pf_meta( $post->ID, 'item_date', true );
		$nom_authors      = $this->metas->get_post_pf_meta( $post->ID, 'item_author', true );
		$item_link        = $this->metas->get_post_pf_meta( $post->ID, 'item_link', true );
		$date_nominated   = $this->metas->get_post_pf_meta( $post->ID, 'date_nominated', true );
		// var_dump($date_nominated); die();
		$user          = get_user_by( 'id', $submitted_by );
		$item_tags     = $this->metas->get_post_pf_meta( $post->ID, 'item_tags', true );
		$source_repeat = $this->metas->get_post_pf_meta( $post->ID, 'source_repeat', true );
		if ( ! empty( $origin_item_ID ) ) {
			$this->meta_box_printer( __( 'Item ID', 'pf' ), $origin_item_ID );
		}
		if ( empty( $nomination_count ) ) {
			$nomination_count = 1;}
		$this->meta_box_printer( __( 'Nomination Count', 'pf' ), $nomination_count );
		if ( empty( $user ) ) {
			$user = wp_get_current_user(); }
		$this->meta_box_printer( __( 'Submitted By', 'pf' ), $user->display_name );
		if ( ! empty( $source_title ) ) {
			$this->meta_box_printer( __( 'Feed Title', 'pf' ), $source_title );
		}
		if ( empty( $posted_date ) ) {
			$this->meta_box_printer( __( 'Posted by source on', 'pf' ), $posted_date );
		} else {
			$this->meta_box_printer( __( 'Source Posted', 'pf' ), $posted_date );
		}
		$this->meta_box_printer( __( 'Source Authors', 'pf' ), $nom_authors );
		$this->meta_box_printer( __( 'Source Link', 'pf' ), $item_link, true, __( 'Original Post', 'pf' ) );
		$this->meta_box_printer( __( 'Item Tags', 'pf' ), $item_tags );
		if ( empty( $date_nominated ) ) {
			$date_nominated = current_time( 'mysql' ); }
		$this->meta_box_printer( __( 'Date Nominated', 'pf' ), $date_nominated );
		if ( ! empty( $source_repeat ) ) {
			$this->meta_box_printer( __( 'Repeated in Feed', 'pf' ), $source_repeat );
		}
	}

	public function get_the_source_statement( $nom_id ) {

		$title_of_item = get_the_title( $nom_id );
		$link_to_item  = $this->metas->get_post_pf_meta( $nom_id, 'item_link', true );
		$args          = array(
			'html_before'      => '<p>',
			'source_statement' => 'Source: ',
			'item_url'         => $link_to_item,
			'link_target'      => '_blank',
			'item_title'       => $title_of_item,
			'html_after'       => '</p>',
			'sourced'          => true,
		);
		$args          = apply_filters( 'pf_source_statement', $args );
		if ( true == $args['sourced'] ) {
			$statement = sprintf(
				'%1$s<a href="%2$s" target="%3$s" pf-nom-item-id="%4$s">%5$s</a>',
				esc_html( $args['source_statement'] ),
				esc_url( $args['item_url'] ),
				esc_attr( $args['link_target'] ),
				esc_attr( $nom_id ),
				esc_html( $args['item_title'] )
			);
			$statement = $args['html_before'] . $statement . $args['html_after'];
		} else {
			$statement = '';
		}
		return $statement;

	}

	public function get_first_nomination( $item_id, $post_type ) {
		$q = pf_get_posts_by_id_for_check( $post_type, $item_id, true );
		if ( 0 < $q->post_count ) {
			$nom = $q->posts;
			$r   = $nom[0];
			return $r;
		} else {
			return false;
		}
	}

	public function is_nominated( $item_id, $post_type = false, $update = false ) {
		if ( ! $post_type ) {
			$post_type = array( 'post', 'nomination' );
		}
		$attempt = $this->get_first_nomination( $item_id, $post_type );
		if ( ! empty( $attempt ) ) {
			$r = $attempt;
			pf_log( 'Existing post at ' . $r );
		} else {
			$r = false;
		}
		/* Restore original Post Data */
		wp_reset_postdata();
		return $r;
	}

	public function resolve_nomination_state( $item_id ) {
		$pt = array( 'nomination' );
		if ( $this->is_nominated( $item_id, $pt ) ) {
			$attempt = $this->get_first_nomination( $item_id, $pt );
			if ( ! empty( $attempt ) ) {
				$nomination_id = $attempt;
				$nominators    = $this->metas->retrieve_meta( $nomination_id, 'nominator_array' );
				if ( empty( $nominators ) ) {
					pf_log( 'There is no one left who nominated this item.' );
					pf_log( 'This nomination has been taken back. We will now remove the item.' );
					pf_delete_item_tree( $nomination_id );
				} else {
					pf_log( 'Though one user retracted their nomination, there are still others who have nominated this item.' );
				}
			} else {
				pf_log( 'We could not find the nomination to resolve the state of.' );
			}
		} else {
			pf_log( 'There is no nomination to resolve the state of.' );
		}
	}

	public function change_nomination_count( $id, $up = true ) {
		$nom_count = $this->metas->retrieve_meta( $id, 'nomination_count' );
		if ( $up ) {
			$nom_count++;
		} else {
			$nom_count--;
		}
		$check = $this->metas->update_pf_meta( $id, 'nomination_count', $nom_count );
		pf_log( 'Nomination now has a nomination count of ' . $nom_count . ' applied to post_meta with the result of ' . $check );
		return $check;
	}

	public function toggle_nominator_array( $id, $update = true ) {
		$nominators = $this->forward_tools->update_nomination_array( $id );
		$check      = $this->metas->update_pf_meta( $id, 'nominator_array', $nominators );
		return $check;
	}

	public function did_user_nominate( $id, $user_id = false ) {
		$nominators = $this->metas->retrieve_meta( $id, 'nominator_array' );
		if ( ! $user_id ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		}
		if ( ! empty( $nominators ) && in_array( $user_id, $nominators ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function handle_post_nomination_status( $item_id, $force = false ) {
		$nomination_state = $this->is_nominated( $item_id );
		$check            = false;
		if ( false != $nomination_state ) {
			if ( $this->did_user_nominate( $nomination_state ) ) {
				$this->change_nomination_count( $nomination_state, false );
				$this->toggle_nominator_array( $nomination_state, false );
				$check = false;
				pf_log( 'user_unnonminated' );
				$this->resolve_nomination_state( $item_id );
			} else {
				$this->change_nomination_count( $nomination_state );
				$this->toggle_nominator_array( $nomination_state );
				$check = false;
				pf_log( 'user_added_additional_nomination' );
			}
		} else {
			$check = true;
		}
		pf_log( $check );
		return $check;
	}

	public function remove_post_nomination( $date, $item_id, $post_type, $updateCount = true ) {
		$postsAfter = pf_get_posts_by_id_for_check( $post_type, $item_id );
		// Assume that it will not find anything.
		$check = false;
		if ( $postsAfter->have_posts() ) :
			while ( $postsAfter->have_posts() ) :
				$postsAfter->the_post();

					$id             = get_the_ID();
					$origin_item_id = $this->metas->retrieve_meta( $id, 'item_id' );
					$current_user   = wp_get_current_user();
				if ( $origin_item_id == $item_id ) {
					$check    = true;
					$nomCount = $this->metas->retrieve_meta( $id, 'nomination_count' );
					$nomCount--;
					$this->metas->update_pf_meta( $id, 'nomination_count', $nomCount );
					if ( 0 != $current_user->ID ) {
						$this->toggle_nominator_array( $id );
					}
				}
			endwhile;   else :
				pf_log( ' No nominations found for ' . $item_id );
			endif;
			wp_reset_postdata();
	}

	public function get_post_nomination_status( $date, $item_id, $post_type, $updateCount = true ) {
		global $post;
		// Get the query object, limiting by date, type and metavalue ID.
		pf_log( 'Get posts matching ' . $item_id );
		$postsAfter = pf_get_posts_by_id_for_check( $post_type, $item_id );
		// Assume that it will not find anything.
		$check = false;
		pf_log( 'Check for nominated posts.' );
		if ( $postsAfter->have_posts() ) :
			while ( $postsAfter->have_posts() ) :
				$postsAfter->the_post();

				$id = get_the_ID();
				pf_log( 'Deal with nominated post ' . $id );
				$origin_item_id = $this->metas->retrieve_meta( $id, 'item_id' );
				$current_user   = wp_get_current_user();
				if ( $origin_item_id == $item_id ) {
					$check = true;
					// Only update the nomination count on request.
					if ( $updateCount ) {
						if ( 0 == $current_user->ID ) {
							// Not logged in.
							// If we ever reveal this to non users and want to count nominations by all, here is where it will go.
							pf_log( 'Can not find user for updating nomionation count.' );
							$nomCount = $this->metas->retrieve_meta( $id, 'nomination_count' );
							$nomCount++;
							$this->metas->update_pf_meta( $id, 'nomination_count', $nomCount );
														$check = 'no_user';
						} else {
							$nominators_orig = $this->metas->retrieve_meta( $id, 'nominator_array' );
							if ( ! array_key_exists( $current_user->ID, $nominators_orig ) ) {
								$check = $this->toggle_nominator_array( $id, false );
							} else {
								$check = 'user_nominated_already';
							}
						}

						return $check;
						break;
					}
				} else {
					pf_log( 'No nominations found for ' . $item_id );
					$check = 'unmatched_post';
				}
			endwhile;   else :
				pf_log( ' No nominations found for ' . $item_id );
				$check = 'unmatched_post';
			endif;
			wp_reset_postdata();
			return $check;
	}

		/**
		 * Handles an archive action submitted via AJAX
		 *
		 * @since 1.7
		 */
	public static function archive_a_nom() {
		$pf_drafted_nonce = $_POST['pf_drafted_nonce'];
		if ( ! wp_verify_nonce( $pf_drafted_nonce, 'drafter' ) ) {
			die( $this->__( 'Nonce not recieved. Are you sure you should be archiving?', 'pf' ) );
		} else {
			$current_user    = wp_get_current_user();
			$current_user_id = $current_user->ID;
			pressforward( 'controller.metas' )->add_pf_meta( $_POST['nom_id'], 'archived_by_user_status', 'archived_' . $current_user_id );
			print_r( __( 'Archived.', 'pf' ) );
			// @TODO This should have a real AJAX response.
			die();
		}
	}


	public function meta_box_printer( $title, $variable, $link = false, $anchor_text = 'Link' ) {
		echo '<strong>' . $title . '</strong>: ';
		if ( empty( $variable ) ) {
			echo '<br /><input type="text" name="' . $title . '">';
		} else {
			if ( $link === true ) {
				if ( $anchor_text === 'Link' ) {
					$anchor_text = $this->__( 'Link', 'pf' );
				}
				echo '<a href=';
				echo $variable;
				echo '" target="_blank">';
				echo $anchor_text;
				echo '</a>';
			} else {
				echo $variable;
			}
		}

		echo '<br />';
	}

	public function build_nomination() {

		// Verify nonce
		if ( ! wp_verify_nonce( $_POST[ PF_SLUG . '_nomination_nonce' ], 'nomination' ) ) {
			die( __( "Nonce check failed. Please ensure you're supposed to be nominating stories.", 'pf' ) ); }

		if ( '' != ( get_option( 'timezone_string' ) ) ) {
			date_default_timezone_set( get_option( 'timezone_string' ) );
		}
		// ref http://wordpress.stackexchange.com/questions/8569/wp-insert-post-php-function-and-custom-fields, http://wpseek.com/wp_insert_post/
		$time = current_time( 'mysql', $gmt = 0 );
		// @todo Play with post_exists (wp-admin/includes/post.php ln 493) to make sure that submissions have not already been submitted in some other method.
			// Perhaps with some sort of "Are you sure you don't mean this... reddit style thing?
			// Should also figure out if I can create a version that triggers on nomination publishing to send to main posts.
		// There is some serious delay here while it goes through the database. We need some sort of loading bar.
		ob_start();
		$current_user = wp_get_current_user();
		$userID       = $current_user->ID;
		// set up nomination check
		$item_wp_date = $_POST['item_wp_date'];
		$item_id      = $_POST['item_id'];
		// die($item_wp_date);
		pf_log( 'We handle the item into a nomination?' );

		if ( ! empty( $_POST['pf_amplify'] ) && ( '1' == $_POST['pf_amplify'] ) ) {
			$amplify = true;
		} else {
			$amplify = false;
		}
			pf_log( 'Amplification?' );
			pf_log( $amplify );
			$nomination_id = $this->forward_tools->item_to_nomination( $item_id, $_POST['item_post_id'] );
			pf_log( 'ID received:' );
			pf_log( $nomination_id );
		if ( is_wp_error( $nomination_id ) || ! $nomination_id ) {
			pf_log( 'Nomination has gone wrong somehow.' );
			pf_log( $nomination_id );
			$response = array(
				'what'         => 'nomination',
				'action'       => 'build_nomination',
				'id'           => $_POST['item_post_id'],
				'data'         => 'Nomination failed',
				'supplemental' => array(
					'originID'  => $item_id,
					'nominater' => $userID,
					'buffered'  => ob_get_flush(),
				),
			);
		} else {
			// $this->metas->transition_post_meta( $_POST['item_post_id'], $newNomID, $amplify );
			$response = array(
				'what'         => 'nomination',
				'action'       => 'build_nomination',
				'id'           => $nomination_id,
				'data'         => $nomination_id . ' nominated.',
				'supplemental' => array(
					'originID'  => $item_id,
					'nominater' => $userID,
					'buffered'  => ob_get_flush(),
				),
			);

		}
				$xmlResponse = new WP_Ajax_Response( $response );
				$xmlResponse->send();
			ob_end_flush();
			die();
	}

	public function simple_nom_to_draft( $id = false ) {
		global $post;
		// ob_start();
		$pf_drafted_nonce = $_POST['pf_nomination_nonce'];
		if ( ! wp_verify_nonce( $pf_drafted_nonce, 'nomination' ) ) {
			die( __( 'Nonce not recieved. Are you sure you should be drafting?', 'pf' ) );
		} else {
			ob_start();
			if ( ! $id ) {
				if (array_key_exists('nom_id', $_POST) && !empty($_POST['nom_id'])){
					$id = $_POST['nom_id'];
				} else {
					$post_id = $_POST['post_id'];
					$item_id = $this->metas->retrieve_meta( $post_id, 'item_id' );
					$id = $this->forward_tools->is_a_pf_type($item_id, $this->nomination_slug);
				}
				// $nom = get_post($id);
				$item_id = $this->metas->retrieve_meta( $id, 'item_id' );
			}
			$item_id      = $this->metas->retrieve_meta( $id, 'item_id' );
			$last_step_id = $this->forward_tools->nomination_to_last_step( $item_id, $id );
			// Check
				add_post_meta( $id, 'nom_id', $id, true );
				// $this->metas->transition_post_meta($id, $new_post_id, true);
				$already_has_thumb = has_post_thumbnail( $id );
			if ( $already_has_thumb ) {
				$post_thumbnail_id = get_post_thumbnail_id( $id );
				set_post_thumbnail( $last_step_id, $post_thumbnail_id );
			}

				$response = array(
					'what'         => 'draft',
					'action'       => 'simple_nom_to_draft',
					'id'           => $last_step_id,
					'data'         => $last_step_id . ' drafted.',
					'supplemental' => array(
						'originID' => $id,
						'buffered' => ob_get_flush(),
					),
				);

				$xmlResponse = new WP_Ajax_Response( $response );
				$xmlResponse->send();
				ob_end_flush();
				die();
		}
	}

	function build_nom_draft() {
		global $post;
		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		// if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		$pf_drafted_nonce = $_POST['pf_drafted_nonce'];
		if ( ! wp_verify_nonce( $pf_drafted_nonce, 'drafter' ) ) {
			die( __( 'Nonce not recieved. Are you sure you should be drafting?', 'pf' ) );
		} else {
			// Check
			// print_r(__('Sending to Draft.', 'pf'));
			// Check
			// print_r($_POST);
			ob_start();

			$item_id       = $_POST['item_id'];
			$nomination_id = $this->forward_tools->nomination_to_last_step( $item_id, $_POST['nom_id'] );
			$response      = array(
				'what'         => 'draft',
				'action'       => 'build_nom_draft',
				'id'           => $nomination_id,
				'data'         => $nomination_id . ' drafted.',
				'supplemental' => array(
					'originID' => $item_id,
					'buffered' => ob_get_flush(),
				),
			);
			$xmlResponse   = new WP_Ajax_Response( $response );
			$xmlResponse->send();
			ob_end_flush();
			die();
		}
	}


}
