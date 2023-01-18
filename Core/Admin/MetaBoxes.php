<?php
namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;

/**
 * Generic handler for meta boxes.
 *
 * @since 5.3.0
 */
class MetaBoxes implements HasActions {
	/**
	 * Registers action hooks.
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function action_hooks() {
		return [
			[
				'hook'   => 'add_meta_boxes',
				'method' => 'add_meta_boxes',
			],
			[
				'hook'   => 'save_post',
				'method' => 'save_source_meta_box',
			],
		];
	}

	/**
	 * Adds meta boxes.
	 *
	 * @since 5.3.0
	 */
	public function add_meta_boxes() {
		$draft_post_type = get_option( PF_SLUG . '_draft_post_type', 'post' );

		add_meta_box(
			'pf_source',
			__( 'Source', 'pressforward' ),
			[ $this, 'source_meta_box' ],
			[ $draft_post_type ],
			'advanced'
		);
	}

	/**
	 * Callback for the 'Source' meta box.
	 *
	 * @since 5.3.0
	 *
	 * @param WP_Post $post Post object.
	 */
	public function source_meta_box( $post ) {
		$item_link  = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'item_link', true );
		$item_title = html_entity_decode( get_the_title( $post ) );

		$source_statement = pressforward( 'admin.nominated' )->get_the_source_statement( $post->ID );

		$link = sprintf( '<a href="%s">%s</a>', $item_link, $item_title );

		?>

		<label for="pressforward-source-statement"><?php esc_html_e( 'The following source statement will be appended to this item:', 'pressforward' ); ?></label>

		<?php

		$height_cb = function( $settings, $id ) {
			if ( 'pressforward-source-statement' === $id ) {
				$settings['editor_height'] = 2;
			}

			return $settings;
		};

		add_filter( 'wp_editor_settings', $height_cb, 10, 2 );

		wp_editor(
			$source_statement,
			'pressforward-source-statement',
			[
				'wpautop' => false,
				'media_buttons' => false,
				'teeny' => true,
			]
		);

		remove_filter( 'wp_editor_settings', $height_cb, 10, 2 );

		wp_nonce_field( 'pressforward-source-' . $post->ID, 'pressforward-source-nonce', false );

		?>

		<?php
	}

	/**
	 * Save callback for the Source meta box.
	 *
	 * @param int $post_id ID of the post.
	 */
	public function save_source_meta_box( $post_id ) {
		$nonce_key = 'pressforward-source-nonce';
		if ( ! isset( $_POST[ $nonce_key ] ) ) {
			return;
		}

		check_admin_referer( 'pressforward-source-' . $post_id, $nonce_key );

		if ( ! isset( $_POST['pressforward-source-statement'] ) ) {
			return;
		}

		$statement = wp_kses_post( wp_unslash( $_POST['pressforward-source-statement'] ) );

		update_post_meta( $post_id, 'pf_source_statement', $statement );
	}
}
