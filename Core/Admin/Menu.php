<?php
namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;
use PressForward\Interfaces\SystemUsers;

class Menu implements HasActions, HasFilters {

	protected $basename;

	function __construct( $basename, SystemUsers $user_interface ) {
		$this->basename       = $basename;
		$this->user_interface = $user_interface;
	}

	public function action_hooks() {
		return array(
			array(
				'hook'     => 'admin_menu',
				'method'   => 'add_plugin_admin_menu',
				'priority' => 10,
			),
			array(
				'hook'   => 'admin_notices',
				'method' => 'admin_notices_action',
			),
			array(
				'hook'   => 'admin_enqueue_scripts',
				'method' => 'hook_default_scripts',
			),
			// Catch form submits
			array(
				'hook'   => 'admin_init',
				'method' => 'pf_options_admin_page_save',
			),
			// Launch a batch delete process, if necessary.
			array(
				'hook'   => 'admin_init',
				'method' => 'launch_batch_delete',
			),

		);
	}

	public function filter_hooks() {
		return array(
			array(
				'hook'   => 'admin_body_class',
				'method' => 'add_pf_body_class',
			),
			array(
				'hook'     => 'pf_admin_pages',
				'method'   => 'state_pf_admin_pages',
				'priority' => 10,
				'args'     => 3,
			),
			array(
				'hook'   => 'ab_bug_status_args',
				'method' => 'pf_ab_bug_status_args',
			),
		);
	}

	function hook_default_scripts() {
		if ( false != pressforward( 'controller.template_factory' )->is_a_pf_page() ) {
			// var_dump('heartbeat'); die();
			wp_enqueue_script( 'heartbeat' );
			wp_enqueue_script( 'jquery-ui-progressbar' );
			// wp_enqueue_script( PF_SLUG . '-heartbeat', PF_URL . 'assets/js/pf-heartbeat.js', array( 'heartbeat', 'jquery-ui-progressbar', 'jquery' ) );
		}
	}

	function admin_notices_action() {
		settings_errors( 'add_pf_feeds' );
	}

	public function add_plugin_admin_menu() {
		remove_menu_page( 'edit.php?post_type=pf_feed_item' );

		// Top-level menu page
		add_menu_page(
			PF_TITLE, // <title>
			PF_TITLE, // menu title
			get_option( 'pf_menu_group_access', $this->user_interface->pf_get_defining_capability_by_role( 'contributor' ) ), // cap required
			PF_MENU_SLUG, // slug
			array( $this, 'display_reader_builder' ), // callback
			PF_URL . 'pressforward-16.png', // icon URL
			24 // Position (just above comments - 25)
		);

		remove_submenu_page( PF_MENU_SLUG, 'edit.php?post_type=pf_feed' );
	}


	function add_pf_body_class( $classes ) {

		if ( pressforward( 'controller.template_factory' )->is_a_pf_page() ) {
			$classes .= ' ' . strtolower( PF_TITLE );
		}
		return $classes;
	}


	function state_pf_admin_pages( $thepages ) {

		$basePages = array( PF_SLUG . '-feeder', PF_SLUG . '-options', PF_SLUG . '-review', PF_MENU_SLUG );
		$thepages  = array_merge( $basePages, (array) $thepages );
		return $thepages;

	}

	/**
	 * Launch a batch delete, if one is queued.
	 *
	 * @since 3.6
	 */
	public function launch_batch_delete() {
		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		pf_launch_batch_delete();
	}


	/**
	 * Display function for the main All Content panel
	 */

	public function display_reader_builder() {
		$userObj = wp_get_current_user();
		$user_id = $userObj->ID;
		// Calling the feedlist within the pf class.
		if ( isset( $_GET['pc'] ) ) {
			$page   = $_GET['pc'];
			$page_c = $page - 1;
		} else {
			$page   = 0;
			$page_c = 0;
		}
		$count       = $page_c * 20;
		$extra_class = '';
		if ( isset( $_GET['reveal'] ) && ( 'no_hidden' == $_GET['reveal'] ) ) {
			$extra_class .= ' archived_visible';
		}
		$view_state = ' grid';
		$view_check = get_user_meta( $user_id, 'pf_user_read_state', true );
		if ( 'golist' == $view_check ) {
			$view_state = ' list';
		}
		$extra_class = $extra_class . $view_state;

		?>
		<div class="pf-loader"></div>
		<div class="pf_container pf-all-content full<?php echo $extra_class; ?>">
			<header id="app-banner">
				<div class="title-span title">
					<?php

						pressforward( 'controller.template_factory' )->the_page_headline();

					?>
					<button class="btn btn-small" id="fullscreenfeed"> <?php _e( 'Full Screen', 'pf' ); ?> </button>
				</div><!-- End title -->
				<?php pressforward( 'admin.templates' )->search_template(); ?>

			</header><!-- End Header -->
			<?php
				pressforward( 'admin.templates' )->nav_bar();
			?>
			<div role="main">
				<?php pressforward( 'admin.templates' )->the_side_menu(); ?>
				<?php pressforward( 'schema.folders' )->folderbox(); ?>
				<div id="entries">
					<?php echo '<img class="loading-top" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />'; ?>
					<div id="errors">
					<?php
					if ( 0 >= self::count_the_posts( 'pf_feed' ) ) {
						echo '<p>You need to add feeds, there are none in the system.</p>';
					}
					?>
					</div>


				<?php

					pressforward( 'admin.templates' )->nominate_this( 'as_feed_item' );

					// Use this foreach loop to go through the overall feedlist, select each individual feed item (post) and do stuff with it.
					// Based off SimplePie's tutorial at http://simplepie.org/wiki/tutorial/how_to_display_previous_feed_items_like_google_reader.
					$c  = 1;
					$ic = 0;
					$c  = $c + $count;
						// print_r($count);
				if ( isset( $_GET['by'] ) ) {
					$limit = $_GET['by'];
				} else {
					$limit = false;
				}
				// var_dump($limit);
				$archive_feed_args = array(
					'start'          => $count + 1,
					'posts_per_page' => false,
					'relationship'   => $limit,
				);

				if ( isset( $_POST['search-terms'] ) ) {
					$archive_feed_args['search_terms']     = stripslashes( $_POST['search-terms'] );
					$archive_feed_args['exclude_archived'] = true;
				}

				if ( ! isset( $_GET['reveal'] ) ) {
					$archive_feed_args['exclude_archived'] = true;
				}

				if ( isset( $_GET['reveal'] ) ) {
					$archive_feed_args['reveal'] = stripslashes( $_GET['reveal'] );
				}

				foreach ( pressforward( 'controller.loops' )->archive_feed_to_display( $archive_feed_args ) as $item ) {

					pressforward( 'admin.templates' )->form_of_an_item( $item, $c );

					$c++;

					// check out the built comment form from EditFlow at https://github.com/danielbachhuber/Edit-Flow/blob/master/modules/editorial-comments/editorial-comments.php
					// So, we're going to need some AJAXery method of sending RSS data to a nominations post.
					// Best example I can think of? The editorial comments from EditFlow, see edit-flow/modules/editorial-comments/editorial-comments.php, esp ln 284
					// But lets start simple and get the hang of AJAX in WP first. http://wp.tutsplus.com/articles/getting-started-with-ajax-wordpress-pagination/
					// Eventually should use http://wpseek.com/wp_insert_post/ I think....
					// So what to submit? I could store all the post data in hidden fields and submit it within seperate form docs, but that's a lot of data.
					// Perhaps just an md5 hash of the ID of the post? Then use the retrieval function to find the matching post and submit it properly?
					// Something to experement with...
				} // End foreach

			?>
			<div class="clear"></div>
			<?php
			echo '</div><!-- End entries -->';
			?>
			<div class="clear"></div>
			<?php
			echo '</div><!-- End main -->';

			// Nasty hack because infinite scroll only works starting with page 2 for some reason.
			if ( $page == 0 ) {
				$page = 1; }
			$pagePrev = $page - 1;
			$pageNext = $page + 1;
			if ( ! empty( $_GET['by'] ) ) {
				$limit_q = '&by=' . $limit;
			} else {
				$limit_q = '';
			}
			$pagePrev = '?page=pf-menu' . $limit_q . '&pc=' . $pagePrev;
			$pageNext = '?page=pf-menu' . $limit_q . '&pc=' . $pageNext;
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
			if ( $c > 19 ) {

				echo '<div class="pf-navigation">';
				if ( -1 > $pagePrev ) {
					echo '<!-- something has gone wrong -->';
				} elseif ( 1 > $pagePrev ) {
					echo '<span class="feedprev"><a class="prevnav" href="admin.php?page=pf-menu">Previous Page</a></span> | ';
				} elseif ( $pagePrev > -1 ) {
					echo '<span class="feedprev"><a class="prevnav" href="admin.php' . $pagePrev . '">Previous Page</a></span> | ';
				}
				echo '<span class="feednext"><a class="nextnav" href="admin.php' . $pageNext . '">Next Page</a></span>';
				echo '</div>';
			}
		?>
		<div class="clear"></div>
		<?php
		echo '</div><!-- End container-fluid -->';
	}
	public function count_the_posts( $post_type, $date_less = false ) {

		if ( ! $date_less ) {
			$query_arg = array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
			);
		} else {
			if ( ! empty( $date_less ) && $date_less < 12 ) {
				$y = date( 'Y' );
				$m = date( 'm' );
				$m = $m + $date_less;
			} elseif ( ! empty( $date_less ) && $date_less >= 12 ) {
				$y = date( 'Y' );
				$y = $y - floor( $date_less / 12 );
				$m = date( 'm' );
				$m = $m - ( abs( $date_less ) - ( 12 * floor( $date_less / 12 ) ) );
			}
			$query_arg = array(
				'post_type'      => $post_type,
				'year'           => $y,
				'monthnum'       => $m,
				'posts_per_page' => -1,
			);
		}

				$query      = new \WP_Query( $query_arg );
				$post_count = $query->post_count;
				wp_reset_postdata();

		return $post_count;
	}

	function pf_options_admin_page_save() {
		global $pagenow;

		if ( 'admin.php' != $pagenow ) {
			return;
		}

		if ( empty( $_POST['submit'] ) ) {
			return;
		}

		if ( empty( $_POST['_wp_http_referer'] ) || false === strpos( $_POST['_wp_http_referer'], 'page=pf-options' ) ) {
			return;
		}

		check_admin_referer( 'pf_settings' );

		if ( current_user_can( get_option( 'pf_menu_all_content_access', pf_get_defining_capability_by_role( 'contributor' ) ) ) ) {
			$user_ID = get_current_user_id();
			if ( isset( $_POST['pf_user_scroll_switch'] ) ) {
				$pf_user_scroll_switch = $_POST['pf_user_scroll_switch'];
				// var_dump($pf_user_scroll_switch); die();
				update_user_option( $user_ID, 'pf_user_scroll_switch', $pf_user_scroll_switch );
			} else {
				update_user_option( $user_ID, 'pf_user_scroll_switch', 'false' );
			}

			if ( isset( $_POST['pf_user_menu_set'] ) ) {
				$pf_user_menu_set = $_POST['pf_user_menu_set'];
				// var_dump($pf_user_scroll_switch); die();
				update_user_option( $user_ID, 'pf_user_menu_set', $pf_user_menu_set );
			} else {
				update_user_option( $user_ID, 'pf_user_menu_set', 'false' );
			}

			if ( isset( $_POST['pf_pagefull'] ) ) {
				$pf_pagefull = $_POST['pf_pagefull'];
				// var_dump($pf_user_scroll_switch); die();
				update_user_option( $user_ID, 'pf_pagefull', $pf_pagefull );
			} else {
				update_user_option( $user_ID, 'pf_pagefull', 'false' );
			}
		}

		$verifyPages = array();

		$pf_admin_pages = apply_filters( 'pf_admin_pages', $verifyPages );

		if ( ! in_array( $_GET['page'], $pf_admin_pages ) ) {
			return;
		}

		if ( current_user_can( get_option( 'pf_menu_preferences_access', pf_get_defining_capability_by_role( 'administrator' ) ) ) ) {

			$arrayedAdminRights = array(
				'pf_menu_group_access'          => array(
					'default' => 'contributor',
					'title'   => __( 'PressForward Menu Group', 'pf' ),
				),
				'pf_menu_all_content_access'    => array(
					'default' => 'contributor',
					'title'   => __( 'All Content Menu', 'pf' ),
				),
				'pf_menu_under_review_access'   => array(
					'default' => 'contributor',
					'title'   => __( 'Nominated Menu', 'pf' ),
				),
				'pf_menu_nominate_this_access'  => array(
					'default' => pf_get_role_by_capability( get_option( 'pf_menu_all_content_access', 'edit_posts' ) ),
					'title'   => __( 'Nominate This', 'pf' ),
				),
				'pf_menu_preferences_access'    => array(
					'default' => 'administrator',
					'title'   => __( 'Preferences Menu', 'pf' ),
				),
				'pf_menu_feeder_access'         => array(
					'default' => 'editor',
					'title'   => __( 'Add Feeds', 'pf' ),
				),
				'pf_menu_add_nomination_access' => array(
					'default' => 'contributor',
					'title'   => __( 'Add Nomination Menu', 'pf' ),
				),
			);

			$arrayedAdminRights = apply_filters( 'pf_setup_admin_rights', $arrayedAdminRights );

			foreach ( $arrayedAdminRights as $right => $parts ) {
				if ( isset( $_POST[ $right ] ) ) {
					$enabled = $_POST[ $right ];
					update_option( $right, $enabled );
					$feed_caps      = pressforward( 'schema.feeds' )->map_feed_caps();
					$feed_item_caps = pressforward( 'schema.feed_item' )->map_feed_item_caps();
					if ( 'pf_menu_feeder_access' == $right ) {
						$all_roles = get_editable_roles();
						foreach ( $all_roles as $a_role => $permissions ) {
							$a_role = get_role( $a_role );
							foreach ( $feed_caps as $feed_cap ) {
								$a_role->remove_cap( $feed_cap );
							}
							foreach ( $feed_item_caps as $feed_item_cap ) {
								$a_role->remove_cap( $feed_item_cap );
							}
						}
						// assign_pf_to_standard_roles();
						$role = get_role( pf_get_role_by_capability( $enabled ) );
						// var_dump($role); die();
						$role->add_cap( $feed_caps['edit_posts'] );
						$role->add_cap( $feed_item_caps['edit_posts'] );
						// var_dump($role); die();
					}
				}
			}

			if ( isset( $_POST['pf_link_to_source'] ) ) {
				$pf_links_opt_check = $_POST['pf_link_to_source'];
				// print_r($pf_links_opt_check); die();
				update_option( 'pf_link_to_source', $pf_links_opt_check );
			} else {
				update_option( 'pf_link_to_source', 0 );
			}

			if ( isset( $_POST['pf_retain_time'] ) ) {
				$pf_links_opt_check = $_POST['pf_retain_time'];
				// print_r($pf_links_opt_check); die();
				update_option( 'pf_retain_time', $pf_links_opt_check );
			} else {
				update_option( 'pf_retain_time', 2 );
			}

			if ( isset( $_POST['pf_errors_until_alert'] ) ) {
				$pf_errors_until_alert = $_POST['pf_errors_until_alert'];
				// print_r($pf_links_opt_check); die();
				update_option( 'pf_errors_until_alert', $pf_errors_until_alert );
			} else {
				update_option( 'pf_errors_until_alert', 3 );
			}
			if ( isset( $_POST[ PF_SLUG . '_retrieval_frequency' ] ) ) {
				$pf_retrieval_frequency = $_POST[ PF_SLUG . '_retrieval_frequency' ];
				// print_r($pf_links_opt_check); die();
				update_option( PF_SLUG . '_retrieval_frequency', $pf_retrieval_frequency );
			} else {
				update_option( PF_SLUG . '_retrieval_frequency', 30 );
			}
			if ( isset( $_POST['pf_nomination_send_email'] ) ) {
				$pf_nomination_send_email_opt_check = $_POST['pf_nomination_send_email'];
				// print_r($pf_nomination_send_email_opt_check); die();
				update_option( 'pf_nomination_send_email', $pf_nomination_send_email_opt_check );
			} else {
				update_option( 'pf_nomination_send_email', '' );
			}
			if ( isset( $_POST['pf_present_author_as_primary'] ) ) {
				$pf_author_opt_check = $_POST['pf_present_author_as_primary'];
				// print_r($pf_links_opt_check); die();
				update_option( 'pf_present_author_as_primary', $pf_author_opt_check );
			} else {
				update_option( 'pf_present_author_as_primary', 'no' );
			}

			if ( isset( $_POST['pf_source_statement_position'] ) ) {
				$pf_author_opt_check = $_POST['pf_source_statement_position'];
				// print_r($pf_links_opt_check); die();
				update_option( 'pf_source_statement_position', $pf_author_opt_check );
			} else {
				update_option( 'pf_source_statement_position', 'no' );
			}

			$pf_draft_post_type = ( ! empty( $_POST[ PF_SLUG . '_draft_post_type' ] ) )
				? $_POST[ PF_SLUG . '_draft_post_type' ]
				: 'post';
			update_option( PF_SLUG . '_draft_post_type', $pf_draft_post_type );

			$pf_draft_post_status = ( ! empty( $_POST[ PF_SLUG . '_draft_post_status' ] ) )
				? $_POST[ PF_SLUG . '_draft_post_status' ]
				: 'draft';
			update_option( PF_SLUG . '_draft_post_status', $pf_draft_post_status );

			if ( isset( $_POST[PF_SLUG.'_advanced_meta_handling'] ) ) {
				$meta_handling_check = $_POST[PF_SLUG.'_advanced_meta_handling'];
				// print_r($pf_links_opt_check); die();
				update_option( PF_SLUG.'_advanced_meta_handling', $meta_handling_check );
			} else {
				update_option( PF_SLUG.'_advanced_meta_handling', 'no' );
			}

			if ( class_exists( 'The_Alert_Box' ) ) {
				// var_dump($_POST);
				if ( empty( $_POST[ pressforward( 'library.alertbox' )->option_name() ] ) ) {
					// var_dump('<pre>'); var_dump($_POST); var_dump('</pre>');
					update_option( pressforward( 'library.alertbox' )->option_name(), 'false' );
				} else {
					update_option( pressforward( 'library.alertbox' )->option_name(), $_POST[ pressforward( 'library.alertbox' )->option_name() ] );
				}
			}

			if ( isset( $_POST['pf_use_advanced_user_roles'] ) ) {
				$pf_author_opt_check = $_POST['pf_use_advanced_user_roles'];
				// print_r($pf_links_opt_check); die();
				update_option( 'pf_use_advanced_user_roles', $pf_author_opt_check );
			} else {
				update_option( 'pf_use_advanced_user_roles', 'no' );
			}

			if ( isset( $_POST['pf_metrics_config'] ) ) {
				$pf_metrics_opt_check             = $_POST['pf_metrics_config'];
				$pf_metrics_opt_check['detailed'] = 'no';
				$pf_metrics_opt_check['checked']  = 'yes';
				update_option( 'pf_metrics_config', $pf_metrics_opt_check );
			} else {
				update_option( 'pf_metrics_config', array() );
			}
		}

		do_action( 'pf_admin_op_page_save' );
	}

	public function pf_alert_displayer() {
		add_filter( 'ab_alert_specimens_post_types', array( $this, 'alert_filterer' ) );
		add_filter( 'ab_alert_safe', array( $this, 'alert_safe_filterer' ) );
		add_filter( 'ab_alert_specimens_check_message', array( $this, 'alert_check_message' ) );
		add_filter( 'ab_alert_specimens_delete_all_text', array( $this, 'alert_delete_all_message' ) );
		pressforward( 'library.alertbox' )->alert_box_outsides();
		remove_filter( 'ab_alert_specimens_delete_all_text', array( $this, 'alert_delete_all_message' ) );
		remove_filter( 'ab_alert_specimens_check_message', array( $this, 'alert_check_message' ) );
		remove_filter( 'ab_alert_safe', array( $this, 'alert_safe_filterer' ) );
		remove_filter( 'ab_alert_specimens_post_types', array( $this, 'alert_filterer' ) );
	}

	public function alert_filterer( $post_types ) {
		return array( pressforward( 'schema.feeds' )->post_type );
	}

	public function alert_check_message( $msg ) {
		return __( 'Are you sure you want to delete all feeds with alerts?', 'pf' );
	}

	public function alert_delete_all_message( $msg ) {
		return __( 'Delete all feeds with alerts', 'pf' );
	}

	public function alert_safe_filterer( $safe_msg ) {
		return __( 'All feeds are ok!', 'pf' );
	}

	function include_alerts_in_edit_feeds( $query ) {
		global $pagenow;
		if ( is_admin() && 'edit.php' === $pagenow && 'pf_feed' === $_GET['post_type'] ) {
			// $statuses = $query->query['post_status'];
			// var_dump('<pre>'); var_dump( $query ); die();
			// $query->query['post_status'] = '';
			// $query->query_vars['post_status'] = '';
		}
		// return $query;
	}

	function pf_ab_bug_status_args( $args ) {
		$args['public'] = true;

		return $args;
	}


}
