<?php
/**
 * Utilities for the edit post panel.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;

/**
 * Utilities for the edit post panel.
 */
class EditPost implements HasActions {
	/**
	 * Post type.
	 *
	 * @access public
	 * @var string
	 */
	public $post_type;

	/**
	 * Contstructor.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->post_type = pressforward_draft_post_type();
	}

	/**
	 * Sets up action hooks for this class.
	 *
	 * @return array
	 */
	public function action_hooks() {
		// Modify the Singleton Edit page.
		$hooks = array(
			array(
				'hook'   => 'post_submitbox_misc_actions',
				'method' => 'posted_submitbox_pf_actions',
			),
			array(
				'hook'   => 'post_submitbox_misc_actions',
				'method' => 'default_feed_author',
			),
			array(
				'hook'   => 'save_post',
				'method' => 'save_submitbox_pf_actions',
			),
			array(
				'hook'   => 'save_post_pf_feed',
				'method' => 'save_submitbox_pf_feed_actions',
			),
		);
		return $hooks;
	}

	/**
	 * Display callback for 'default author' area.
	 */
	public function default_feed_author() {
		global $post, $pagenow;
		if ( 'pf_feed' !== $post->post_type ) {
			return;
		}

		// New post check.
		if ( in_array( $pagenow, array( 'post-new.php' ), true ) ) {
			$value = '';
		} else {
			$value = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'pf_feed_default_author', true );
			if ( empty( $value ) ) {
				$value = '';
			}
		}

		echo '<div class="misc-pub-section misc-pub-section-last">
			<label>
			<input type="text" id="pf_feed_default_author" name="pf_feed_default_author"
               placeholder="' . esc_attr__( 'Default feed author', 'pressforward' ) . '" value="' . esc_attr( $value ) . '" />
			<br />
			' . esc_html__( 'Enter default author for this feed.', 'pressforward' ) . '</label></div>';
	}

	/**
	 * Show 'Forward' options for submit box.
	 */
	public function posted_submitbox_pf_actions() {
		global $post, $pagenow;
		if ( 'pf_feed' === $post->post_type ) {
			return;
		}

		/**
		 * Filters whether PF's "Link to source" dropdown should appear in the Publish actions area.
		 *
		 * @since 5.4.0
		 *
		 * @param bool $show Defaults to true.
		 */
		if ( ! apply_filters( 'pressforward_show_link_to_source_dropdown', true ) ) {
			return;
		}

		// New post check.
		if ( in_array( $pagenow, array( 'post-new.php' ), true ) ) {
			$option_value = get_option( 'pf_link_to_source' );
			if ( empty( $option_value ) ) {
				$value = 'no-forward';
			} else {
				$value = 'forward';
			}
		} else {
			$check = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'item_link', true );
			if ( empty( $check ) ) {
				return;
			}
			$value = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'pf_forward_to_origin', true );
			if ( empty( $value ) ) {

				$option_value = get_option( 'pf_link_to_source' );
				if ( empty( $option_value ) ) {
					$value = 'no-forward';
				} else {
					$value = 'forward';
				}
			}
		}

		echo '<div class="misc-pub-section misc-pub-section-last">
				<label for="pf_forward_to_origin_single">' . esc_html__( "Forward to item's original URL?", 'pressforward' ) . '</label>' .
				'<select id="pf_forward_to_origin_single" name="pf_forward_to_origin">
					<option value="forward"' . ( 'forward' === $value ? ' selected ' : '' ) . '>' . esc_html__( 'Forward', 'pressforward' ) . '</option>
				  <option value="no-forward"' . ( 'no-forward' === $value ? ' selected ' : '' ) . '>' . esc_html__( "Don't Forward", 'pressforward' ) . '</option>
				</select>'
		. '</div>';
	}

	/**
	 * Save callback for 'Default Author' submit box actions.
	 *
	 * @param int $post_id ID of the post being saved.
	 */
	public function save_submitbox_pf_feed_actions( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_posts', $post_id ) ) {
			return $post_id;
		}

		if ( array_key_exists( 'pf_feed_default_author', $_POST ) ) {
			pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_feed_default_author', sanitize_text_field( wp_unslash( $_POST['pf_feed_default_author'] ) ) );
		}

		return $post_id;
	}

	/**
	 * Save callback for 'forward to origin' submit box actions.
	 *
	 * @param int $post_id ID of the post being saved.
	 */
	public function save_submitbox_pf_actions( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_posts', $post_id ) ) {
			return $post_id;
		}

		if ( array_key_exists( 'pf_forward_to_origin', $_POST ) ) {
			pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_forward_to_origin', sanitize_text_field( wp_unslash( $_POST['pf_forward_to_origin'] ) ) );
		}

		return $post_id;
	}
}
