<?php
/**
 * Nominations data.
 *
 * @package PressForward
 */

namespace PressForward\Core\Schema;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

/**
 * Functionality related to nominations
 */
class Nominations implements HasActions, HasFilters {
	/**
	 * Post type.
	 *
	 * @access public
	 * @var string
	 */
	public $post_type;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->post_type = 'nomination';
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'transition_post_status',
				'method' => 'maybe_send_promotion_notifications',
				'args'   => 3,
			),
			array(
				'hook'   => 'init',
				'method' => 'register_post_type',
			),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_hooks() {
		return array(
			array(
				'hook'   => 'manage_edit-nomination_columns',
				'method' => 'edit_nominations_columns',
			),
			array(
				'hook'   => 'manage_edit-nomination_sortable_columns',
				'method' => 'nomination_sortable_columns',
			),
		);
	}

	/**
	 * Registers the 'nomination' post type.
	 *
	 * @since 1.7
	 */
	public function register_post_type() {
		$args = array(
			'labels'               => array(
				'name'               => __( 'Nominations', 'pressforward' ),
				'singular_name'      => __( 'Nomination', 'pressforward' ),
				'add_new'            => __( 'Nominate', 'pressforward' ),
				'add_new_item'       => __( 'Add New Nomination', 'pressforward' ),
				'edit_item'          => __( 'Edit Nomination', 'pressforward' ),
				'new_item'           => __( 'New Nomination', 'pressforward' ),
				'view_item'          => __( 'View Nomination', 'pressforward' ),
				'search_items'       => __( 'Search Nominations', 'pressforward' ),
				'not_found'          => __( 'No nominations found', 'pressforward' ),
				'not_found_in_trash' => __( 'No nominations found in Trash', 'pressforward' ),
			),
			'description'          => __( 'Posts from around the internet nominated for consideration to public posting', 'pressforward' ),
			// Not available to non-users.
			'public'               => false,
			// I want a UI for users to use, so true.
			'show_ui'              => true,
			// But not the default UI, we want to attach it to the plugin menu.
			'show_in_menu'         => false,
			'show_in_rest'         => true,
			// Linking in the metabox building function.
			'register_meta_box_cb' => array( $this, 'nominations_meta_boxes' ),
			'capability_type'      => 'post',
			// The type of input (besides the metaboxes) that it supports.
			'supports'             => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
			// I think this is set to false by the public argument, but better safe.
			'has_archive'          => false,
			'taxonomies'           => array( 'category', 'post_tag' ),
		);

		register_post_type( $this->post_type, $args );
	}

	/**
	 * Callback for registering meta boxes on 'nomination' post type
	 *
	 * @since 1.7
	 */
	public function nominations_meta_boxes() {
		// Don't add in Nominate This.
		if ( ! empty( $_GET['pf-nominate-this'] ) ) {
			return;
		}

		add_meta_box(
			'pf-nominations',
			__( 'Nomination Data', 'pressforward' ),
			array( $this, 'nominations_box_builder' ),
			'nomination',
			'side',
			'high'
		);
	}

	/**
	 * Adds custom columns to Nominations view.
	 *
	 * This and the next few functions are to modify the table that shows up when you click "Nominations".
	 *
	 * @param array $columns Column names and headers.
	 * @return array
	 */
	public function edit_nominations_columns( $columns ) {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'title'           => __( 'Title', 'pressforward' ),
			'date'            => __( 'Last Modified', 'pressforward' ),
			'nomcount'        => __( 'Nominations', 'pressforward' ),
			'nominatedby'     => __( 'Nominated By', 'pressforward' ),
			'original_author' => __( 'Original Author', 'pressforward' ),
			'date_nominated'  => __( 'Date Nominated', 'pressforward' ),
		);

		return $columns;
	}

	/**
	 * Make these Nominations columns sortable.
	 *
	 * @return array
	 */
	public function nomination_sortable_columns() {
		return array(
			'title'           => 'title',
			'date'            => 'date',
			'nomcount'        => 'nomcount',
			'nominatedby'     => 'nominatedby',
			'original_author' => 'original_author',
			'date_nominated'  => 'date_nominated',
		);
	}

	/**
	 * The builder for the box that shows us the nomination metadata.
	 */
	public function nominations_box_builder() {
		do_action( 'nominations_box' );
	}

	/**
	 * Sends notifications to nominating users when an item is published.
	 *
	 * @since 5.4.0
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 */
	public function maybe_send_promotion_notifications( $new_status, $old_status, \WP_Post $post ) {
		if ( 'publish' !== $new_status ) {
			return;
		}

		if ( pressforward()->fetch( 'controller.advancement' )->last_step_post_type() !== $post->post_type ) {
			return;
		}

		$nominators = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'nominator_array' );
		if ( ! $nominators ) {
			return;
		}

		foreach ( (array) $nominators as $nominator ) {
			if ( ! pressforward()->fetch( 'controller.users' )->get_user_setting( $nominator['user_id'], 'nomination-promoted-email-toggle' ) ) {
				continue;
			}

			$user = get_userdata( $nominator['user_id'] );
			if ( ! $user ) {
				continue;
			}

			$site_name = get_bloginfo( 'name' );

			$subject = sprintf(
				// translators: Name of the site.
				__( 'An item you nominated on %s has been published', 'pressforward' ),
				$site_name
			);

			$message = $subject . "\n\n";

			$message .= sprintf(
				// translators: Title of the post.
				__( 'Title: %s', 'pressforward' ),
				get_the_title( $post )
			);

			$message .= "\n";

			$message .= sprintf(
				// translators: URL of the post.
				__( 'URL: %s', 'pressforward' ),
				get_permalink( $post )
			);

			$message .= "\n\n" . pressforward( 'controller.users' )->get_email_notification_footer();

			/**
			 * Filters the subject line of the "nomination promoted" notification email.
			 *
			 * @since 5.4.0
			 *
			 * @param string $subject    Subject line.
			 * @param int    $wp_post_id ID of the nominated item.
			 * @param int    $user_id    ID of the user receiving the email.
			 */
			$subject = apply_filters( 'pf_nomination_promoted_email_subject', $subject, $post->ID, $user->ID );

			/**
			 * Filters the content of the "nomination promoted" notification email.
			 *
			 * @since 5.4.0
			 *
			 * @param string $message    Message content.
			 * @param int    $wp_post_id ID of the nominated item.
			 * @param int    $user_id    ID of the user receiving the email.
			 */
			$message = apply_filters( 'pf_nomination_promoted_email_content', $message, $post->ID, $user->ID );

			wp_mail( $user->user_email, $subject, $message );
		}
	}
}
