<?php
/**
 * Nominated functionality.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use PressForward\Core\Admin\PFTemplater;
use PressForward\Core\Utility\Forward_Tools;
use PressForward\Core\Schema\Nominations;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PFtoWPUsers;
use WP_Ajax_Response;
use WP_Query;

/**
 * Nominated functionality.
 */
class Nominated implements \Intraxia\Jaxion\Contract\Core\HasActions {
	/**
	 * Metas object.
	 *
	 * @access public
	 * @var object
	 */
	public $metas;

	/**
	 * PFTemplater object.
	 *
	 * @access public
	 * @var \PressForward\Core\Admin\PFTemplater
	 */
	public $template_factory;

	/**
	 * Forward_Tools interface.
	 *
	 * @access public
	 * @var \PressForward\Core\Utility\Forward_Tools
	 */
	public $forward_tools;

	/**
	 * Nomination slug.
	 *
	 * @access public
	 * @var string
	 */
	public $nomination_slug;

	/**
	 * PFtoWPUsers class.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PFtoWPUsers
	 */
	public $user_interface;

	/**
	 * Constructor.
	 *
	 * @param object                                   $metas            Metas object.
	 * @param \PressForward\Core\Admin\PFTemplater     $template_factory PFTemplater object.
	 * @param \PressForward\Core\Utility\Forward_Tools $forward_tools    Forward_Tools object.
	 * @param \PressForward\Core\Schema\Nominations    $nominations      Nominations object.
	 * @param \PressForward\Controllers\PFtoWPUsers    $user_interface   PFtoWPUsers object.
	 */
	public function __construct( $metas, PFTemplater $template_factory, Forward_Tools $forward_tools, Nominations $nominations, PFtoWPUsers $user_interface ) {
		$this->metas            = $metas;
		$this->template_factory = $template_factory;
		$this->forward_tools    = $forward_tools;
		$this->nomination_slug  = $nominations->post_type;
		$this->user_interface   = $user_interface;
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'     => 'admin_menu',
				'method'   => 'add_plugin_admin_menu',
				'priority' => 12,
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
			array(
				'hook'     => 'rest_pre_insert_nomination',
				'method'   => 'prevent_auto_draft_from_converting_to_draft',
				'priority' => 10,
				'args'     => 2,
			),
		);
	}

	/**
	 * Adds the 'Nominated' admin panel.
	 */
	public function add_plugin_admin_menu() {
		add_submenu_page(
			PF_MENU_SLUG,
			__( 'Nominated', 'pressforward' ),
			__( 'Nominated', 'pressforward' ),
			get_option( 'pf_menu_under_review_access', $this->user_interface->pf_get_defining_capability_by_role( 'contributor' ) ),
			PF_SLUG . '-review',
			array( $this, 'display_review_builder' )
		);
	}

	/**
	 * Builds the interface for the review tool.
	 */
	public function display_review_builder() {
		wp_enqueue_script( 'pf' );
		wp_enqueue_script( 'pf-views' );
		wp_enqueue_script( 'pf-send-to-draft-imp' );

		wp_enqueue_style( 'pf-style' );

		if ( 'false' !== get_user_option( 'pf_user_scroll_switch', pressforward( 'controller.template_factory' )->user_id() ) ) {
			wp_enqueue_script( 'pf-scroll' );
		}

		/*
		 * Code for Under Review menu page generation.
		 * Duping code from 1053 in main.
		 * Mockup - https://gomockingbird.com/mockingbird/#mr28na1/I9lz7i
		 * Calling the feedlist within the pf class.
		 */
		if ( isset( $_GET['pc'] ) ) {
			$page = intval( $_GET['pc'] );
			--$page;
		} else {
			$page = 0;
		}

		$count       = $page * 20;
		$count_q     = 0;
		$extra_class = '';

		if ( isset( $_GET['reveal'] ) && ( 'no_hidden' === sanitize_text_field( wp_unslash( $_GET['reveal'] ) ) ) ) {
			$extra_class .= ' archived_visible';
		} else {
			$extra_class .= '';
		}

		$pf_url = defined( 'PF_URL' ) ? PF_URL : '';

		?>
		<div class="pf-loader"></div>

		<div class="list pf_container pf-nominated full<?php echo esc_attr( $extra_class ); ?>">
			<header id="app-banner">
				<div class="title-span title">
					<?php pressforward( 'controller.template_factory' )->the_page_headline( 'Nominated' ); ?>
					<button class="btn btn-small" id="fullscreenfeed"> <?php esc_html_e( 'Full Screen', 'pressforward' ); ?> </button>
				</div><!-- End title -->

				<?php pressforward( 'admin.templates' )->search_template(); ?>
			</header><!-- End Header -->

			<?php pressforward( 'admin.templates' )->nav_bar( 'pf-review' ); ?>

			<div role="main">
				<?php pressforward( 'admin.templates' )->the_side_menu(); ?>
				<?php pressforward( 'schema.folders' )->folderbox(); ?>

				<div id="entries">
					<?php echo '<img class="loading-top" src="' . esc_attr( $pf_url ) . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />'; ?>
					<div id="errors">
						<div class="pressforward-alertbox" style="display:none;">
							<div class="row-fluid">
								<div class="span11 pf-alert">
								</div>

								<div class="span1 pf-dismiss">
									<i class="icon-remove-circle"><?php esc_html_e( 'Close', 'pressforward' ); ?></i>
								</div>
							</div>
						</div>
					</div>

					<?php
					// Hidden here, user options, like 'show archived' etc...
					?>

					<div id="page_data" style="display:none">
						<?php
						$current_user    = wp_get_current_user();
						$current_user_id = $current_user->ID;

						$metadata['current_user']    = $current_user->user_nicename;
						$metadata['current_user_id'] = $current_user_id;
						?>

						<span id="current-user-id"><?php echo esc_html( (string) $current_user_id ); ?></span>
					</div>

					<?php
					echo '<div class="row-fluid" class="nom-row">';
					// Bootstrap Accordion group.
					echo '<div class="span12 nom-container" id="nom-accordion">';
					wp_nonce_field( 'drafter', 'pf_drafted_nonce', false );
					// Reset Post Data.
					wp_reset_postdata();

					// This part here is for eventual use in pagination and then infinite scroll.

					$c = 0;
					$c = $c + $count;
					if ( isset( $_GET['pc'] ) ) {
						$offset = intval( $_GET['pc'] ) - 1;
						$offset = $offset * 20;
					} elseif ( $c < 20 ) {
						$offset = 0;
					} else {
						$offset = $c;
					}

					// Now we must loop.
					// Eventually we may want to provide options to change some of these, so we're going to provide the default values for now.
					$page_check = absint( $page );
					if ( ! $page_check ) {
						$page_check = 1;
					}

					$nom_args = array(
						'post_type'        => 'nomination',
						'post_status'      => [ 'publish', 'draft' ],
						'orderby'          => 'date',
						'order'            => 'DESC',
						'posts_per_page'   => 20,
						'suppress_filters' => false,
						'meta_query'       => [],
						'offset'           => $offset, // The query function will turn page into a 1 if it is a 0.
					);

					if ( isset( $_GET['pf-see'] ) ) {
						$pf_see = sanitize_text_field( wp_unslash( $_GET['pf-see'] ) );
						switch ( $pf_see ) {
							case 'archive-only':
								$nom_args['meta_query']['archive-only'] = [
									'key'   => 'pf_archive',
									'value' => '1',
								];

								$nom_args['post_status'] = 'removed_feed_item';
								break;

							case 'unread-only':
								$nom_args['meta_query']['unread-only'] = [
									'key'     => 'sortable_item_date',
									'value'   => 0,
									'compare' => '>',
								];

								$nom_args['post_status'] = 'draft';

								$nom_args['post__not_in'] = pf_get_read_items_for_user( get_current_user_id(), 'simple' );
								break;

							case 'starred-only':
								$nom_args['post_status'] = 'draft';
								$nom_args['post__in']    = pf_get_starred_items_for_user( get_current_user_id(), 'simple' );
								break;
						}
					}

					if ( isset( $_GET['action'] ) && isset( $_POST['search-terms'] ) ) {
						$nom_args['s'] = sanitize_text_field( wp_unslash( $_POST['search-terms'] ) );
					}

					if ( isset( $_GET['sort-by'] ) ) {
						$sort_by    = sanitize_text_field( wp_unslash( $_GET['sort-by'] ) );
						$sort_order = isset( $_GET['sort-order'] ) && 'asc' === strtolower( sanitize_text_field( wp_unslash( $_GET['sort-order'] ) ) ) ? 'ASC' : 'DESC';

						switch ( $sort_by ) {
							case 'item-date':
							default:
								$nom_args['orderby'] = [
									'meta_value' => $sort_order,
								];
								break;

							case 'feed-in-date':
								$nom_args['orderby'] = [
									'date' => $sort_order,
								];
								break;

							case 'nom-date':
								$nom_args['meta_key'] = 'sortable_nom_date';
								$nom_args['orderby']  = [
									'meta_value' => $sort_order,
								];
								break;

							case 'nom-count':
								$nom_args['meta_key']   = '';
								$nom_args['meta_value'] = '';

								$nom_args['meta_query'] = [
									'nomination_count' => [
										'key'     => 'nomination_count',
										'compare' => 'EXISTS',
										'type'    => 'SIGNED',
									],
								];

								$nom_args['orderby'] = [
									'nomination_count' => $sort_order,
									'date'             => 'DESC',
								];
								break;
						}
					}

					if ( isset( $_GET['feed'] ) ) {
						$nom_args['post_parent'] = intval( $_GET['feed'] );
					} elseif ( isset( $_GET['folder'] ) ) {
						$parents_in_folder           = new WP_Query(
							array(
								'post_type'              => pressforward( 'schema.feeds' )->post_type,
								'fields'                 => 'ids',
								'update_post_term_cache' => false,
								'update_post_meta_cache' => false,
								'tax_query'              => array(
									array(
										'taxonomy' => pressforward( 'schema.feeds' )->tag_taxonomy,
										'field'    => 'term_id',
										'terms'    => sanitize_text_field( wp_unslash( $_GET['folder'] ) ),
									),
								),
							)
						);
						$nom_args['post_parent__in'] = $parents_in_folder->posts;
					}

					$nom_query = new WP_Query( $nom_args );

					$count        = 0;
					$count_q      = $nom_query->post_count;
					$count_qt     = $nom_query->found_posts;
					$max_nb_pages = $nom_query->max_num_pages;

					while ( $nom_query->have_posts() ) :
						$nom_query->the_post();

						// declare some variables for use, mostly in various meta roles.
						// 1773 in rssforward.php for various post meta.
						// Get the submitter's user slug.
						$submitter_slug         = get_the_author_meta( 'nicename' );
						$metadata['submitters'] = $submitter_slug;

						// Nomination (post) ID.
						$nom_id             = get_the_ID();
						$metadata['nom_id'] = $nom_id;

						// Get the WP database ID of the original item in the database.
						$metadata['pf_item_post_id'] = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'pf_item_post_id', true );

						// Number of Nominations recieved.
						$nom_count             = pressforward( 'controller.metas' )->retrieve_meta( $nom_id, 'nomination_count' );
						$metadata['nom_count'] = $nom_count;

						// Permalink to orig content.
						$nom_permalink         = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_link', true );
						$metadata['permalink'] = $nom_permalink;

						$url_array = wp_parse_url( $nom_permalink );

						// Source Site.
						$source_link             = isset( $url_array['host'] ) ? 'http://' . $url_array['host'] : '';
						$metadata['source_link'] = isset( $url_array['host'] ) ? $source_link = 'http://' . $url_array['host'] : '';

						// Source site slug.
						$source_slug             = isset( $url_array['host'] ) ? pf_slugger( $url_array['host'], true, false, true ) : '';
						$metadata['source_slug'] = $source_slug;

						// RSS Author designation.
						$item_authorship         = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_author', true );
						$metadata['item_author'] = $item_authorship;

						// Datetime item was nominated.
						$date_nomed                 = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'date_nominated', true );
						$metadata['date_nominated'] = $date_nomed;

						// Datetime item was posted to its home RSS.
						$date_posted           = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_date', true );
						$metadata['item_date'] = $date_posted;

						// Unique RSS item ID.
						$rss_item_id         = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_id', true );
						$metadata['item_id'] = $rss_item_id;

						// RSS-passed tags, comma seperated.
						$nom_tags      = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_tags', true );
						$item_nom_tags = $nom_tags;

						// @todo This makes no sense and is a bug.
						$wp_nom_tags  = '';
						$get_the_tags = array();
						if ( empty( $get_the_tags ) ) { // @phpstan-ignore-line
							$get_the_tags[] = '';
							$wp_nom_tags    = '';
							$wp_nom_slugs[] = '';
						} else {
							foreach ( $get_the_tags as $tag ) {
								$wp_nom_tags .= ', ';
								$wp_nom_tags .= $tag->name;
							}
							$wp_nom_slugs = array();
							foreach ( $get_the_tags as $tag ) {
								$wp_nom_slugs[] = $tag->slug;
							}
						}

						$metadata['nom_tags'] = $wp_nom_slugs;

						$nom_tags_string    = is_array( $nom_tags ) ? implode( ',', $nom_tags ) : $nom_tags;
						$wp_nom_tags_string = $wp_nom_tags;

						$metadata['all_tags'] = $nom_tags_string . ',' . $nom_tags_string;

						if ( is_array( $item_nom_tags ) ) {
							$nom_tags_array = $item_nom_tags;
						} else {
							$nom_tags_array = explode( ',', $item_nom_tags );
						}

						$nom_tag_classes_string = '';
						foreach ( $nom_tags_array as $nom_tag ) {
							$nom_tag_classes_string .= pf_slugger( $nom_tag, true, false, true );
							$nom_tag_classes_string .= ' ';
						}

						// RSS-passed tags as slugs.
						$metadata['item_tags'] = $nom_tag_classes_string;
						// All users who nominated.
						$metadata['nominators'] = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'nominator_array', true );

						// Number of times repeated in source.
						$source_repeat             = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'source_repeat', true );
						$metadata['source_repeat'] = $source_repeat;

						// Post-object tags.
						$metadata['item_title']   = get_the_title();
						$metadata['item_content'] = get_the_content();
						// UNIX datetime last modified.
						$metadata['timestamp_nom_last_modified'] = get_the_modified_date( 'U' );
						// UNIX datetime added to nominations.
						$metadata['timestamp_unix_date_nomed'] = strtotime( $date_nomed );
						// UNIX datetime item was posted to its home RSS.
						$metadata['timestamp_item_posted'] = strtotime( $date_posted );

						$archived_status             = pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'archived_by_user_status' );
						$metadata['archived_status'] = $archived_status;

						$user_obj = wp_get_current_user();
						$user_id  = $user_obj->ID;

						if ( ! empty( $metadata['archived_status'] ) ) {
							$archived_status_string     = '';
							$archived_user_string_match = 'archived_' . $current_user_id;
							foreach ( $archived_status as $user_archived_status ) {
								if ( $user_archived_status === $archived_user_string_match ) {
									$archived_status_string = 'archived';
									$dependent_style        = 'display:none;';
								}
							}
						} elseif ( 1 === (int) pf_get_relationship_value( 'archive', $nom_id, $user_id ) ) {
							$archived_status_string = 'archived';
							$dependent_style        = 'display:none;';
						} else {
							$dependent_style        = '';
							$archived_status_string = '';
						}

						$item = pf_feed_object(
							get_the_title(),
							pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'source_title', true ),
							$date_posted,
							$item_authorship,
							get_the_content(),
							$nom_permalink,
							get_the_post_thumbnail( $nom_id /**, 'nom_thumb'*/ ),
							$rss_item_id,
							pressforward( 'controller.metas' )->get_post_pf_meta( $nom_id, 'item_wp_date', true ),
							$nom_tags,
							$date_nomed,
							$source_repeat,
							(string) $nom_id,
							'1'
						);

						pressforward( 'admin.templates' )->form_of_an_item( $item, $c, 'nomination', $metadata );
						++$count;
						++$c;
						endwhile;

					// Reset Post Data.
					wp_reset_postdata();
					?>
					<div class="clear"></div>
					<?php
					echo '</div><!-- End under review entries -->';

					echo '</div><!-- End main -->';
					if ( $count_qt > $count_q ) {
						++$page;
						if ( 0 === $page ) {
							$page = 1;
						}

						$page_prev_nb = $page - 1;
						$page_next_nb = $page + 1;

						if ( ! empty( $_GET['by'] ) ) {
							$limit_q = '&by=' . sanitize_text_field( wp_unslash( $_GET['by'] ) );
						} else {
							$limit_q = '';
						}

						$page_prev = '?page=pf-review' . $limit_q . '&pc=' . $page_prev_nb;
						$page_next = '?page=pf-review' . $limit_q . '&pc=' . $page_next_nb;
						if ( isset( $_GET['folder'] ) ) {
							$page_q     = sanitize_text_field( wp_unslash( $_GET['folder'] ) );
							$page_qed   = '&folder=' . $page_q;
							$page_next .= $page_qed;
							$page_prev .= $page_qed;

						}

						if ( isset( $_GET['feed'] ) ) {
							$page_q     = sanitize_text_field( wp_unslash( $_GET['feed'] ) );
							$page_qed   = '&feed=' . $page_q;
							$page_next .= $page_qed;
							$page_prev .= $page_qed;
						}

						if ( isset( $_GET['pf-see'] ) && '' !== $_GET['pf-see'] ) {
							$page_q     = sanitize_text_field( wp_unslash( $_GET['pf-see'] ) );
							$page_qed   = '&pf-see=' . $page_q;
							$page_next .= $page_qed;
							$page_prev .= $page_qed;
						}
						// Nasty hack because infinite scroll only works starting with page 2 for some reason.
						echo '<div class="pf-navigation">';
						if ( $page_prev_nb > 0 ) {
							echo '<span class="feedprev"><a class="prevnav" href="admin.php' . esc_attr( $page_prev ) . '">' . esc_html__( 'Previous Page', 'pressforward' ) . '</a></span> | ';
						} else {
							echo '<span class="feedprev">' . esc_html__( 'Previous Page', 'pressforward' ) . '</span> | ';
						}
						if ( $page_next_nb > $max_nb_pages ) {
							echo '<span class="feednext">' . esc_html__( 'Next Page', 'pressforward' ) . '</span>';
						} else {
							echo '<span class="feednext"><a class="nextnav" href="admin.php' . esc_attr( $page_next ) . '">' . esc_html__( 'Next Page', 'pressforward' ) . '</a></span>';
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

	/**
	 * Sends a nomination for publishing.
	 */
	public function send_nomination_for_publishing() {
		if ( ! isset( $_POST['post_status'] ) || ! isset( $_POST['post_type'] ) || empty( $_POST['ID'] ) ) {
			return;
		}

		$post_type   = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );
		$post_status = sanitize_text_field( wp_unslash( $_POST['post_status'] ) );
		$post_id     = intval( $_POST['ID'] );

		if ( ( 'publish' === $post_status || 'draft' === $post_status ) && ( $this->nomination_slug === $post_type ) ) {
			ob_start();

			$item_id = $this->metas->get_post_pf_meta( $post_id, 'item_id', true );
			pf_log( 'Sending to last step ' . $item_id . ' from Nomination post ' . $post_id );
			ob_end_clean();
			return $this->forward_tools->nomination_to_last_step( $item_id, $post_id );
		}
	}

	/**
	 * Adds columns to nomination page.
	 *
	 * Via http://slides.helenhousandi.com/wcnyc2012.html#15 and http://svn.automattic.com/wordpress/tags/3.4/wp-admin/includes/class-wp-posts-list-table.php.
	 *
	 * @param string $column Column key.
	 */
	public function nomination_custom_columns( $column ) {
		global $post;

		switch ( $column ) {
			case 'nomcount':
				echo esc_html( $this->metas->get_post_pf_meta( $post->ID, 'nomination_count', true ) );
				break;
			case 'nominatedby':
				$nominator_id = $this->metas->get_post_pf_meta( $post->ID, 'submitted_by', true );
				$user         = get_user_by( 'id', $nominator_id );
				if ( is_a( $user, '\WP_User' ) ) {
					echo esc_html( $user->display_name );
				}
				break;
			case 'original_author':
				$orig_auth = $this->metas->get_post_pf_meta( $post->ID, 'item_author', true );
				echo esc_html( $orig_auth );
				break;
			case 'date_nominated':
				$date_nomed = $this->metas->get_post_pf_meta( $post->ID, 'date_nominated', true );
				echo esc_html( $date_nomed );
				break;

		}
	}

	/**
	 * Builds the nomination box.
	 */
	public function nominations_box_builder() {
		global $post;

		$origin_item_id   = $this->metas->get_post_pf_meta( $post->ID, 'item_id', true );
		$nomination_count = $this->metas->get_post_pf_meta( $post->ID, 'nomination_count', true );
		$submitted_by     = $this->metas->get_post_pf_meta( $post->ID, 'submitted_by', true );
		$source_title     = $this->metas->get_post_pf_meta( $post->ID, 'source_title', true );
		$posted_date      = $this->metas->get_post_pf_meta( $post->ID, 'item_date', true );
		$nom_authors      = $this->metas->get_post_pf_meta( $post->ID, 'item_author', true );
		$item_link        = $this->metas->get_post_pf_meta( $post->ID, 'item_link', true );
		$date_nominated   = $this->metas->get_post_pf_meta( $post->ID, 'date_nominated', true );
		$user             = get_user_by( 'id', $submitted_by );
		$item_tags        = $this->metas->get_post_pf_meta( $post->ID, 'item_tags', true );
		$source_repeat    = $this->metas->get_post_pf_meta( $post->ID, 'source_repeat', true );
		if ( ! empty( $origin_item_id ) ) {
			$this->meta_box_printer( __( 'Item ID', 'pressforward' ), $origin_item_id );
		}
		if ( empty( $nomination_count ) ) {
			$nomination_count = 1;}
		$this->meta_box_printer( __( 'Nomination Count', 'pressforward' ), $nomination_count );
		if ( empty( $user ) ) {
			$user = wp_get_current_user(); }
		$this->meta_box_printer( __( 'Submitted By', 'pressforward' ), $user->display_name );
		if ( ! empty( $source_title ) ) {
			$this->meta_box_printer( __( 'Feed Title', 'pressforward' ), $source_title );
		}
		if ( empty( $posted_date ) ) {
			$this->meta_box_printer( __( 'Posted by source on', 'pressforward' ), $posted_date );
		} else {
			$this->meta_box_printer( __( 'Source Posted', 'pressforward' ), $posted_date );
		}
		$this->meta_box_printer( __( 'Source Authors', 'pressforward' ), $nom_authors );
		$this->meta_box_printer( __( 'Source Link', 'pressforward' ), $item_link, true, __( 'Original Post', 'pressforward' ) );
		$this->meta_box_printer( __( 'Item Tags', 'pressforward' ), $item_tags );
		if ( empty( $date_nominated ) ) {
			$date_nominated = current_time( 'mysql' ); }
		$this->meta_box_printer( __( 'Date Nominated', 'pressforward' ), $date_nominated );
		if ( ! empty( $source_repeat ) ) {
			$this->meta_box_printer( __( 'Repeated in Feed', 'pressforward' ), $source_repeat );
		}
	}

	/**
	 * Builds the source statement for an item.
	 *
	 * @since 5.4.0 Introduced the $args parameter.
	 * @since 5.7.0 Introduced the pressforward_source_statement_formats() method and
	 *              associated Settings UI. For backward compatibility, we use the legacy
	 *              method of building the markup if the 'pf_source_statement' filter
	 *              is in use.
	 *
	 * @param int   $nom_id ID of the item.
	 * @param array $args   Deprecated.
	 * @return string
	 */
	public function get_the_source_statement( $nom_id, $args = array() ) {
		if ( ! empty( $args ) ) {
			_deprecated_argument( __METHOD__, '5.7.0', 'The $args parameter is deprecated. Use the pressforward_source_statement_formats() method and associated Settings UI instead.' );
		}

		if ( has_filter( 'pf_source_statement' ) ) {
			return $this->get_the_source_statement_legacy( $nom_id, $args );
		}

		$formats = pressforward_source_statement_formats();

		$item_title = get_the_title( $nom_id );
		$item_url   = $this->metas->get_post_pf_meta( $nom_id, 'item_link', true );

		$publication_name = $this->metas->get_post_pf_meta( $nom_id, 'source_publication_name', true );
		$publication_url  = $this->metas->get_post_pf_meta( $nom_id, 'source_publication_url', true );

		$item_link = sprintf(
			'<a pf-nom-item-id="%s" href="%s" target="_blank">%s</a>',
			esc_attr( (string) $nom_id ),
			esc_url( $item_url ),
			esc_html( $item_title )
		);

		if ( $publication_name ) {
			// Empty format means no source statement.
			if ( empty( $formats['with_publication'] ) ) {
				return '';
			}

			if ( $publication_url ) {
				$publication_link = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $publication_url ), esc_html( $publication_name ) );
			} else {
				$publication_link = esc_html( $publication_name );
			}

			$statement = str_replace(
				[
					'{{item}}',
					'{{publication}}',
				],
				[
					$item_link,
					$publication_link,
				],
				$formats['with_publication']
			);
		} else {
			// Empty format means no source statement.
			if ( empty( $formats['without_publication'] ) ) {
				return '';
			}

			$statement = str_replace(
				'{{item}}',
				$item_link,
				$formats['without_publication']
			);
		}

		return sprintf(
			'<!-- wp:paragraph --><p class="pf-source-statement">%s</p><!-- /wp:paragraph -->',
			$statement
		);
	}

	/**
	 * Builds the source statement for an item using the legacy method.
	 *
	 * @since 5.7.0
	 *
	 * @param int   $nom_id ID of the item.
	 * @param array $args   An array of arguments used to build the markup.
	 * @return string
	 */
	protected function get_the_source_statement_legacy( $nom_id, $args ) {
		$title_of_item = get_the_title( $nom_id );
		$link_to_item  = $this->metas->get_post_pf_meta( $nom_id, 'item_link', true );
		$default_args  = array(
			'html_before' => '<p class="pf-source-statement">',
			// translators: Link to item source URL.
			'format'      => __( 'Source: %s', 'pressforward' ),
			'item_url'    => $link_to_item,
			'link_target' => '_blank',
			'item_title'  => $title_of_item,
			'html_after'  => '</p>',
			'sourced'     => true,
		);

		$_args = array_merge( $default_args, $args );
		$args  = apply_filters( 'pf_source_statement', $_args );
		if ( true === $args['sourced'] ) {
			if ( isset( $args['source_statement'] ) ) {
				$statement = sprintf(
					'%1$s<a href="%2$s" target="%3$s" pf-nom-item-id="%4$s">%5$s</a>',
					esc_html( __( 'Source: ', 'pressforward' ) ),
					esc_url( $args['item_url'] ),
					esc_attr( $args['link_target'] ),
					esc_attr( (string) $nom_id ),
					esc_html( $args['item_title'] )
				);
			} else {
				$statement = sprintf(
					esc_html( $args['format'] ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_attr( $args['item_url'] ),
						esc_html( $args['item_title'] )
					)
				);
			}

			// Ensure 'target' and 'pf-nom-item-id' attributes on output.
			$target_attr = sprintf( 'target="%s"', esc_attr( $args['link_target'] ) );
			if ( false === strpos( $statement, $target_attr ) ) {
				$statement = preg_replace( '|<a (href="[^"]+")|', '<a \1 ' . $target_attr, $statement );
			}

			$nom_id_attr = sprintf( 'pf-nom-item-id="%s"', esc_attr( (string) $nom_id ) );
			if ( false === strpos( $statement, $nom_id_attr ) ) {
				$statement = preg_replace( '|<a (href="[^"]+")|', '<a \1 ' . $nom_id_attr, $statement );
			}

			$statement = $args['html_before'] . $statement . $args['html_after'];
		} else {
			$statement = '';
		}
		return $statement;
	}

	/**
	 * Increments nomination count for an item.
	 *
	 * @param int  $id ID of the item.
	 * @param bool $up True for increment, false for decrement.
	 * @return bool
	 */
	public function change_nomination_count( $id, $up = true ) {
		$nom_count = $this->metas->retrieve_meta( $id, 'nomination_count' );
		if ( $up ) {
			++$nom_count;
		} else {
			--$nom_count;
		}
		$check = $this->metas->update_pf_meta( $id, 'nomination_count', $nom_count );
		pf_log( 'Nomination now has a nomination count of ' . $nom_count . ' applied to post_meta with the result of ' . $check );
		return $check;
	}

	/**
	 * Checks whether a user nominated an item.
	 *
	 * @param int $id      ID of the item.
	 * @param int $user_id ID of the user. Defaults to current user.
	 * @return bool
	 */
	public function did_user_nominate( $id, $user_id = 0 ) {
		$nominators = $this->metas->retrieve_meta( $id, 'nominator_array' );
		if ( ! $user_id ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		}

		// @todo Check that nominator array is cast to int.
		// phpcs:ignore
		if ( ! empty( $nominators ) && in_array( $user_id, $nominators ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Handles an archive action submitted via AJAX
	 *
	 * @since 1.7
	 */
	public static function archive_a_nom() {
		$pf_drafted_nonce = isset( $_POST['pf_drafted_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['pf_drafted_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $pf_drafted_nonce, 'drafter' ) ) {
			die( esc_html__( 'Nonce not recieved. Are you sure you should be archiving?', 'pressforward' ) );
		} else {
			$current_user    = wp_get_current_user();
			$current_user_id = $current_user->ID;
			$nom_id          = isset( $_POST['nom_id'] ) ? intval( $_POST['nom_id'] ) : 0;
			pressforward( 'controller.metas' )->add_pf_meta( $nom_id, 'archived_by_user_status', 'archived_' . $current_user_id );
			esc_html_e( 'Archived.', 'pressforward' );
			// @TODO This should have a real AJAX response.
			die();
		}
	}

	/**
	 * Prints the content of a metabox.
	 *
	 * @param string $title       Title.
	 * @param string $variable    Variable.
	 * @param bool   $link        Whether to link.
	 * @param string $anchor_text Text for link.
	 */
	public function meta_box_printer( $title, $variable, $link = false, $anchor_text = 'Link' ) {
		echo '<strong>' . esc_html( $title ) . '</strong>: ';
		if ( empty( $variable ) ) {
			echo '<br /><input type="text" name="' . esc_attr( $title ) . '">';
		} elseif ( true === $link ) {
			if ( 'Link' === $anchor_text ) {
				$anchor_text = __( 'Link', 'pressforward' );
			}

			echo '<a href=';
			echo esc_attr( $variable );
			echo '" target="_blank">';
			echo esc_html( $anchor_text );
			echo '</a>';
		} else {
			echo esc_html( $variable );
		}

		echo '<br />';
	}

	/**
	 * AJAX callback for building a nomination.
	 */
	public function build_nomination() {
		// Verify nonce.
		if ( empty( $_POST[ PF_SLUG . '_nomination_nonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ PF_SLUG . '_nomination_nonce' ] ) ), 'nomination' ) ) {
			die( esc_html__( "Nonce check failed. Please ensure you're supposed to be nominating stories.", 'pressforward' ) ); }

		if ( '' !== ( get_option( 'timezone_string' ) ) ) {
			// @todo Investigate.
			// phpcs:ignore WordPress.DateTime
			date_default_timezone_set( get_option( 'timezone_string' ) );
		}

		// ref http://wordpress.stackexchange.com/questions/8569/wp-insert-post-php-function-and-custom-fields, http://wpseek.com/wp_insert_post/.
		$time = current_time( 'mysql', $gmt = 0 );

		/*
		 * @todo Play with post_exists (wp-admin/includes/post.php ln 493) to make sure that submissions have not already been submitted in some other method.
		 * Perhaps with some sort of "Are you sure you don't mean this... reddit style thing?
		 * Should also figure out if I can create a version that triggers on nomination publishing to send to main posts.
		 * There is some serious delay here while it goes through the database. We need some sort of loading bar.
		 */
		ob_start();
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;

		// Set up nomination check.
		$item_wp_date = isset( $_POST['item_wp_date'] ) ? sanitize_text_field( wp_unslash( $_POST['item_wp_date'] ) ) : '';
		$item_id      = isset( $_POST['item_id'] ) ? sanitize_text_field( wp_unslash( $_POST['item_id'] ) ) : '';
		$item_post_id = isset( $_POST['item_post_id'] ) ? intval( $_POST['item_post_id'] ) : 0;

		pf_log( 'We handle the item into a nomination?' );

		if ( ! empty( $_POST['pf_amplify'] ) && ( '1' === sanitize_text_field( wp_unslash( $_POST['pf_amplify'] ) ) ) ) {
			$amplify = true;
		} else {
			$amplify = false;
		}

		pf_log( 'Amplification?' );
		pf_log( $amplify );
		$nomination_id = $this->forward_tools->item_to_nomination( $item_id, $item_post_id );
		pf_log( 'ID received:' );
		pf_log( $nomination_id );

		if ( is_wp_error( $nomination_id ) || ! $nomination_id ) {
			pf_log( 'Nomination has gone wrong somehow.' );
			pf_log( $nomination_id );
			$response = array(
				'what'         => 'nomination',
				'action'       => 'build_nomination',
				'id'           => $item_post_id,
				'data'         => 'Nomination failed',
				'supplemental' => array(
					'originID'  => $item_id,
					'nominater' => $user_id,
					'buffered'  => ob_get_flush(),
				),
			);
		} else {
			$response = array(
				'what'         => 'nomination',
				'action'       => 'build_nomination',
				'id'           => $nomination_id,
				'data'         => $nomination_id . ' nominated.',
				'supplemental' => array(
					'originID'  => $item_id,
					'nominater' => $user_id,
					'buffered'  => ob_get_flush(),
				),
			);

		}

		$xml_response = new WP_Ajax_Response( $response );
		$xml_response->send();
	}

	/**
	 * AJAX callback for 'simple_nom_to_draft' action.
	 *
	 * @param int $id Item ID.
	 */
	public function simple_nom_to_draft( $id = 0 ) {
		$pf_drafted_nonce = isset( $_POST['pf_nomination_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['pf_nomination_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $pf_drafted_nonce, 'nomination' ) ) {
			die( esc_html__( 'Nonce not recieved. Are you sure you should be drafting?', 'pressforward' ) );
		} else {
			ob_start();
			if ( ! $id ) {
				if ( array_key_exists( 'nom_id', $_POST ) && ! empty( $_POST['nom_id'] ) ) {
					$id = intval( $_POST['nom_id'] );
				} else {
					$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
					$item_id = $this->metas->retrieve_meta( $post_id, 'item_id' );
					$id      = $this->forward_tools->is_a_pf_type( $item_id, $this->nomination_slug );
				}

				$item_id = $this->metas->retrieve_meta( $id, 'item_id' );
			}

			$item_id      = $this->metas->retrieve_meta( $id, 'item_id' );
			$last_step_id = $this->forward_tools->nomination_to_last_step( $item_id, $id );

			add_post_meta( $id, 'nom_id', $id, true );

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

			$xml_response = new WP_Ajax_Response( $response );
			$xml_response->send();
		}
	}

	/**
	 * AJAX callback for building a nomination draft.
	 */
	public function build_nom_draft() {
		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything.
		$pf_drafted_nonce = isset( $_POST['pf_drafted_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['pf_drafted_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $pf_drafted_nonce, 'drafter' ) ) {
			die( esc_html__( 'Nonce not recieved. Are you sure you should be drafting?', 'pressforward' ) );
		} else {
			ob_start();

			$item_id = isset( $_POST['item_id'] ) ? sanitize_text_field( wp_unslash( $_POST['item_id'] ) ) : 0;
			$nom_id  = isset( $_POST['nom_id'] ) ? intval( $_POST['nom_id'] ) : 0;

			$nomination_id = $this->forward_tools->nomination_to_last_step( $item_id, $nom_id, false );
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
			$xml_response  = new WP_Ajax_Response( $response );
			$xml_response->send();
		}
	}

	/**
	 * Ensures that nomination auto-drafts are not converted to drafts during autosave.
	 *
	 * See https://github.com/WordPress/gutenberg/issues/56881 for background.
	 *
	 * @param \stdClass        $prepared_post Post object.
	 * @param \WP_REST_Request $request       Request object.
	 * @return \stdClass
	 */
	public function prevent_auto_draft_from_converting_to_draft( $prepared_post, $request ) {
		if ( ! isset( $prepared_post->ID ) || ! isset( $prepared_post->post_status ) || 'draft' !== $prepared_post->post_status ) {
			return $prepared_post;
		}

		if ( ! defined( 'DOING_AUTOSAVE' ) || ! DOING_AUTOSAVE ) {
			return $prepared_post;
		}

		// Check the status of the post in the database.
		$post = get_post( $prepared_post->ID );
		if ( 'auto-draft' === $post->post_status ) {
			$prepared_post->post_status = 'auto-draft';
		}

		return $prepared_post;
	}
}
