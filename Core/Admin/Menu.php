<?php
/**
 * Main admin menu setup utilities.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;
use PressForward\Controllers\PFtoWPUsers;

/**
 * Main admin menu setup utilities.
 */
class Menu implements HasActions, HasFilters {
	/**
	 * Basename.
	 *
	 * @access protected
	 * @var string
	 */
	protected $basename;

	/**
	 * PFtoWPUsers object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PFtoWPUsers
	 */
	public $user_interface;

	/**
	 * Constructor.
	 *
	 * @param string                                $basename       Basename.
	 * @param \PressForward\Controllers\PFtoWPUsers $user_interface PFtoWPUsers object.
	 */
	public function __construct( $basename, PFtoWPUsers $user_interface ) {
		$this->basename       = $basename;
		$this->user_interface = $user_interface;
	}

	/**
	 * {@inheritdoc}
	 */
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
			// Catch form submits.
			array(
				'hook'   => 'admin_init',
				'method' => 'pf_options_admin_page_save',
			),
			[
				'hook'   => 'admin_init',
				'method' => 'register_welcome_meta_boxes',
			],
		);
	}

	/**
	 * {@inheritdoc}
	 */
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

	/**
	 * Adds settings error callback.
	 */
	public function admin_notices_action() {
		settings_errors( 'add_pf_feeds' );
	}

	/**
	 * Adds top-level admin page.
	 */
	public function add_plugin_admin_menu() {
		remove_menu_page( 'edit.php?post_type=pf_feed_item' );

		// Top-level menu page.
		add_menu_page(
			PF_TITLE, // <title>.
			PF_TITLE, // menu title.
			get_option( 'pf_menu_group_access', $this->user_interface->pf_get_defining_capability_by_role( 'contributor' ) ), // cap required.
			PF_MENU_SLUG, // slug.
			[ $this, 'display_welcome_page' ], // callback.
			PF_URL . 'pressforward-16.png', // icon URL.
			24 // Position (just above comments - 25).
		);

		// Welcome page is the first item in the menu.
		add_submenu_page(
			PF_MENU_SLUG,
			__( 'Welcome', 'pressforward' ),
			__( 'Welcome', 'pressforward' ),
			get_option( 'pf_menu_group_access', $this->user_interface->pf_get_defining_capability_by_role( 'contributor' ) ),
			PF_MENU_SLUG,
			[ $this, 'display_welcome_page' ]
		);

		remove_submenu_page( PF_MENU_SLUG, 'edit.php?post_type=pf_feed' );
	}


	/**
	 * Adds 'pressforward' body class on PF pages.
	 *
	 * @param string $classes Body classes.
	 * @return string
	 */
	public function add_pf_body_class( $classes ) {
		if ( pressforward( 'controller.template_factory' )->is_a_pf_page() ) {
			$classes .= ' ' . strtolower( PF_TITLE );
		}
		return $classes;
	}

	/**
	 * Adds base pages to PF admin page array.
	 *
	 * @param array $thepages PF admin pages.
	 * @return array
	 */
	public function state_pf_admin_pages( $thepages ) {
		$base_pages = array( PF_SLUG . '-feeder', PF_SLUG . '-options', PF_SLUG . '-review', PF_MENU_SLUG );
		$thepages   = array_merge( $base_pages, (array) $thepages );
		return $thepages;
	}

	/**
	 * Display function for the Welcome panel.
	 *
	 * @return void
	 */
	public function display_welcome_page() {
		pressforward( 'admin.templates' )->the_view_for( 'welcome' );

		add_action(
			'shutdown',
			function () {
				$this->update_welcome_panel_last_visit_time();
			}
		);
	}

	/**
	 * Registers meta boxes for the welcome page.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function register_welcome_meta_boxes() {
		add_meta_box(
			'pf-welcome-getting-started',
			__( 'Getting Started', 'pressforward' ),
			[ $this, 'display_getting_started_meta_box' ],
			'toplevel_page_pf-menu',
			'normal',
			'high'
		);

		add_meta_box(
			'pf-welcome-nominated',
			__( 'Nominations', 'pressforward' ),
			[ $this, 'display_nominations_meta_box' ],
			'toplevel_page_pf-menu',
			'normal',
			'high'
		);

		add_meta_box(
			'pf-welcome-feeds',
			__( 'Feeds', 'pressforward' ),
			[ $this, 'display_feeds_meta_box' ],
			'toplevel_page_pf-menu',
			'normal',
			'high'
		);
	}

	/**
	 * Display function for the Getting Started meta box.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function display_getting_started_meta_box() {
		wp_enqueue_style( 'pf-welcome' );
		require_once PF_ROOT . '/parts/welcome/getting-started.php';
	}

	/**
	 * Display function for the Nominations meta box.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function display_nominations_meta_box() {
		wp_enqueue_style( 'pf-welcome' );
		require_once PF_ROOT . '/parts/welcome/nominations.php';
	}

	/**
	 * Display function for the Feeds meta box.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function display_feeds_meta_box() {
		wp_enqueue_style( 'pf-welcome' );
		require_once PF_ROOT . '/parts/welcome/feeds.php';
	}

	/**
	 * Save routine for the PF options page.
	 */
	public function pf_options_admin_page_save() {
		global $pagenow;

		if ( 'admin.php' !== $pagenow ) {
			return;
		}

		if ( empty( $_POST['submit'] ) ) {
			return;
		}

		if ( empty( $_POST['_wp_http_referer'] ) || false === strpos( sanitize_text_field( wp_unslash( $_POST['_wp_http_referer'] ) ), 'page=pf-options' ) ) {
			return;
		}

		check_admin_referer( 'pf_settings' );

		if ( current_user_can( get_option( 'pf_menu_all_content_access', pf_get_defining_capability_by_role( 'contributor' ) ) ) ) {
			$user_ID = get_current_user_id();
			if ( isset( $_POST['pf_user_scroll_switch'] ) ) {
				$pf_user_scroll_switch = sanitize_text_field( wp_unslash( $_POST['pf_user_scroll_switch'] ) );
				update_user_option( $user_ID, 'pf_user_scroll_switch', $pf_user_scroll_switch );
			} else {
				update_user_option( $user_ID, 'pf_user_scroll_switch', 'false' );
			}

			if ( isset( $_POST['pf_user_menu_set'] ) ) {
				$pf_user_menu_set = sanitize_text_field( wp_unslash( $_POST['pf_user_menu_set'] ) );
				update_user_option( $user_ID, 'pf_user_menu_set', $pf_user_menu_set );
			} else {
				update_user_option( $user_ID, 'pf_user_menu_set', 'false' );
			}

			if ( isset( $_POST['pf_pagefull'] ) ) {
				$pf_pagefull = sanitize_text_field( wp_unslash( $_POST['pf_pagefull'] ) );
				update_user_option( $user_ID, 'pf_pagefull', $pf_pagefull );
			} else {
				update_user_option( $user_ID, 'pf_pagefull', 'false' );
			}

			if ( ! empty( $_POST['pf-user-nomination-success-email-toggle'] ) ) {
				update_user_option( $user_ID, 'pf_nomination_success_email_toggle', 'on' );
			} else {
				update_user_option( $user_ID, 'pf_nomination_success_email_toggle', 'off' );
			}

			if ( ! empty( $_POST['pf-user-nomination-promoted-email-toggle'] ) ) {
				update_user_option( $user_ID, 'pf_nomination_promoted_email_toggle', 'on' );
			} else {
				update_user_option( $user_ID, 'pf_nomination_promoted_email_toggle', 'off' );
			}

			if ( ! empty( $_POST['pf-user-item-comment-email-toggle'] ) ) {
				update_user_option( $user_ID, 'pf_item_comment_email_toggle', 'on' );
			} else {
				update_user_option( $user_ID, 'pf_item_comment_email_toggle', 'off' );
			}

			if ( ! empty( $_POST['pf-user-nomination-duplicate-email-toggle'] ) ) {
				update_user_option( $user_ID, 'pf_nomination_duplicate_email_toggle', 'on' );
			} else {
				update_user_option( $user_ID, 'pf_nomination_duplicate_email_toggle', 'off' );
			}
		}

		$verify_pages = array();

		$pf_admin_pages = apply_filters( 'pf_admin_pages', $verify_pages );

		if ( ! isset( $_GET['page'] ) || ! in_array( $_GET['page'], $pf_admin_pages, true ) ) {
			return;
		}

		if ( current_user_can( get_option( 'pf_menu_preferences_access', pf_get_defining_capability_by_role( 'administrator' ) ) ) ) {

			$arrayed_admin_rights = array(
				'pf_menu_group_access'          => array(
					'default' => 'contributor',
					'title'   => __( 'PressForward Menu Group', 'pressforward' ),
				),
				'pf_menu_all_content_access'    => array(
					'default' => 'contributor',
					'title'   => __( 'All Content Menu', 'pressforward' ),
				),
				'pf_menu_under_review_access'   => array(
					'default' => 'contributor',
					'title'   => __( 'Nominated Menu', 'pressforward' ),
				),
				'pf_menu_nominate_this_access'  => array(
					'default' => pf_get_role_by_capability( get_option( 'pf_menu_all_content_access', 'edit_posts' ) ),
					'title'   => __( 'Nominate This', 'pressforward' ),
				),
				'pf_menu_preferences_access'    => array(
					'default' => 'administrator',
					'title'   => __( 'Preferences Menu', 'pressforward' ),
				),
				'pf_menu_feeder_access'         => array(
					'default' => 'editor',
					'title'   => __( 'Add Feeds', 'pressforward' ),
				),
				'pf_menu_stats_access'          => array(
					'default' => 'editor',
					'title'   => __( 'Access Stats', 'pressforward' ),
				),
				'pf_menu_add_nomination_access' => array(
					'default' => 'contributor',
					'title'   => __( 'Add Nomination Menu', 'pressforward' ),
				),
			);

			$arrayed_admin_rights = apply_filters( 'pf_setup_admin_rights', $arrayed_admin_rights );

			foreach ( $arrayed_admin_rights as $right => $parts ) {
				if ( isset( $_POST[ $right ] ) ) {
					$enabled = sanitize_text_field( wp_unslash( $_POST[ $right ] ) );
					update_option( $right, $enabled );
					$feed_caps      = pressforward( 'schema.feeds' )->map_feed_caps();
					$feed_item_caps = pressforward( 'schema.feed_item' )->map_feed_item_caps();
					if ( 'pf_menu_feeder_access' === $right ) {
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

						$role = get_role( pf_get_role_by_capability( $enabled ) );
						$role->add_cap( $feed_caps['edit_posts'] );
						$role->add_cap( $feed_item_caps['edit_posts'] );
					}
				}
			}

			if ( isset( $_POST['pf_link_to_source'] ) ) {
				$pf_links_opt_check = sanitize_text_field( wp_unslash( $_POST['pf_link_to_source'] ) );
				update_option( 'pf_link_to_source', $pf_links_opt_check );
			} else {
				update_option( 'pf_link_to_source', 0 );
			}

			if ( isset( $_POST['pf_retain_time'] ) ) {
				$pf_links_opt_check = sanitize_text_field( wp_unslash( $_POST['pf_retain_time'] ) );
				update_option( 'pf_retain_time', $pf_links_opt_check );
			} else {
				update_option( 'pf_retain_time', 2 );
			}

			if ( isset( $_POST['pf_errors_until_alert'] ) ) {
				$pf_errors_until_alert = sanitize_text_field( wp_unslash( $_POST['pf_errors_until_alert'] ) );
				update_option( 'pf_errors_until_alert', $pf_errors_until_alert );
			} else {
				update_option( 'pf_errors_until_alert', 3 );
			}
			if ( isset( $_POST[ PF_SLUG . '_retrieval_frequency' ] ) ) {
				$pf_retrieval_frequency = sanitize_text_field( wp_unslash( $_POST[ PF_SLUG . '_retrieval_frequency' ] ) );
				update_option( PF_SLUG . '_retrieval_frequency', $pf_retrieval_frequency );
			} else {
				update_option( PF_SLUG . '_retrieval_frequency', 30 );
			}
			if ( isset( $_POST['pf_nomination_send_email'] ) ) {
				$pf_nomination_send_email_opt_check = sanitize_text_field( wp_unslash( $_POST['pf_nomination_send_email'] ) );
				update_option( 'pf_nomination_send_email', $pf_nomination_send_email_opt_check );
			} else {
				update_option( 'pf_nomination_send_email', '' );
			}
			if ( isset( $_POST['pf_present_author_as_primary'] ) ) {
				$pf_author_opt_check = sanitize_text_field( wp_unslash( $_POST['pf_present_author_as_primary'] ) );
				update_option( 'pf_present_author_as_primary', $pf_author_opt_check );
			} else {
				update_option( 'pf_present_author_as_primary', 'no' );
			}

			if ( isset( $_POST['pf_source_statement_position'] ) ) {
				$pf_author_opt_check = sanitize_text_field( wp_unslash( $_POST['pf_source_statement_position'] ) );
				update_option( 'pf_source_statement_position', $pf_author_opt_check );
			} else {
				update_option( 'pf_source_statement_position', 'no' );
			}

			foreach ( [ 'pf_source_format_with_publication', 'pf_source_format_without_publication' ] as $format_var ) {
				if ( isset( $_POST[ $format_var ] ) ) {
					$format_value_submitted = sanitize_text_field( wp_unslash( $_POST[ $format_var ] ) );
					update_option( $format_var, $format_value_submitted );
				} else {
					update_option( $format_var, '' );
				}
			}

			$pf_draft_post_type = ( ! empty( $_POST[ PF_SLUG . '_draft_post_type' ] ) )
				? sanitize_text_field( wp_unslash( $_POST[ PF_SLUG . '_draft_post_type' ] ) )
				: 'post';
			update_option( PF_SLUG . '_draft_post_type', $pf_draft_post_type );

			$pf_draft_post_status = ( ! empty( $_POST[ PF_SLUG . '_draft_post_status' ] ) )
				? sanitize_text_field( wp_unslash( $_POST[ PF_SLUG . '_draft_post_status' ] ) )
				: 'draft';
			update_option( PF_SLUG . '_draft_post_status', $pf_draft_post_status );

			$pf_force_classic_nominate_this = ! empty( $_POST['pf_force_classic_nominate_this'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['pf_force_classic_nominate_this'] ) ) ? 'yes' : 'no';
			update_option( 'pf_force_classic_nominate_this', $pf_force_classic_nominate_this );

			$notification_options = [
				// PHPCS false positive.
				// phpcs:disable WordPress.Arrays.MultipleStatementAlignment
				'pf-user-nomination-promoted-email-default' => 'pf_user_nomination_promoted_email_default',
				'pf-user-nomination-success-email-default'  => 'pf_user_nomination_success_email_default',
				'pf-user-item-comment-email-default'        => 'pf_user_item_comment_email_default',
				// phpcs:enable WordPress.Arrays.MultipleStatementAlignment
			];

			foreach ( $notification_options as $nopt_post_key => $nopt_option_name ) {
				if ( ! isset( $_POST[ $nopt_post_key ] ) ) {
					continue;
				}

				$nopt_option_value = 'on' === sanitize_text_field( wp_unslash( $_POST[ $nopt_post_key ] ) ) ? 'on' : 'off';

				update_option( $nopt_option_name, $nopt_option_value );
			}

			if ( class_exists( 'The_Alert_Box' ) ) {
				if ( empty( $_POST[ pressforward( 'library.alertbox' )->option_name() ] ) ) {
					update_option( pressforward( 'library.alertbox' )->option_name(), 'false' );
				} else {
					update_option( pressforward( 'library.alertbox' )->option_name(), sanitize_text_field( wp_unslash( $_POST[ pressforward( 'library.alertbox' )->option_name() ] ) ) );
				}
			}

			$pf_author_opt_check = 'no';
			if ( isset( $_POST['pf_use_advanced_user_roles'] ) ) {
				$pf_author_opt_check = 'yes' === sanitize_text_field( wp_unslash( $_POST['pf_use_advanced_user_roles'] ) ) ? 'yes' : 'no';
			}
			update_option( 'pf_use_advanced_user_roles', $pf_author_opt_check );
		}

		do_action( 'pf_admin_op_page_save' );
	}

	/**
	 * Generates an alert box.
	 */
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

	/**
	 * Specifies post types for alert box.
	 *
	 * @param array $post_types Post types.
	 * @return array
	 */
	public function alert_filterer( $post_types ) {
		return array( pressforward( 'schema.feeds' )->post_type );
	}

	/**
	 * Check message for alert box.
	 *
	 * @param string $msg Message.
	 * @return string
	 */
	public function alert_check_message( $msg ) {
		return __( 'Are you sure you want to delete all feeds with alerts?', 'pressforward' );
	}

	/**
	 * Delete All message for alert box.
	 *
	 * @param string $msg Message.
	 * @return string
	 */
	public function alert_delete_all_message( $msg ) {
		return __( 'Delete all feeds with alerts', 'pressforward' );
	}

	/**
	 * Safe message for alert box.
	 *
	 * @param string $safe_msg Safe message.
	 * @return string
	 */
	public function alert_safe_filterer( $safe_msg ) {
		return __( 'All feeds are ok!', 'pressforward' );
	}

	/**
	 * Not used.
	 *
	 * @param array $args Deprecated args.
	 */
	public function include_alerts_in_edit_feeds( $args ) {}

	/**
	 * Filters Alert Box status argument.
	 *
	 * @param array $args Arguments.
	 * @return array
	 */
	public function pf_ab_bug_status_args( $args ) {
		$args['public'] = true;

		return $args;
	}

	/**
	 * Gets the timestame for the user's last visit to the Welcome panel.
	 *
	 * @return int
	 */
	public function get_welcome_panel_last_visit_time() {
		$user_id         = get_current_user_id();
		$last_visit_time = get_user_option( 'pf_welcome_panel_last_visit_time', $user_id );

		return $last_visit_time;
	}

	/**
	 * Updates the timestamp for the user's last visit to the Welcome panel.
	 *
	 * @return void
	 */
	public function update_welcome_panel_last_visit_time() {
		// Don't more than once every 5 minutes.
		$last_visit_time = $this->get_welcome_panel_last_visit_time();
		if ( time() - $last_visit_time < ( 5 * MINUTE_IN_SECONDS ) ) {
			return;
		}

		$user_id = get_current_user_id();
		update_user_option( $user_id, 'pf_welcome_panel_last_visit_time', time() );
	}
}
