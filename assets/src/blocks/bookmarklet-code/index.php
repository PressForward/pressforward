<?php
/**
 * Server-side registration for the bookmarklet-code block.
 *
 * @package PressForward
 * @since 5.6.0
 */

namespace PressForward\Core\Blocks\BookmarkletCode;

add_action( 'init', __NAMESPACE__ . '\register_block' );

/**
 * Registers the bookmarklet-code block.
 *
 * @since 5.6.0
 *
 * @return void
 */
function register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		__DIR__ . '/block.json',
		[
			'render_callback' => __NAMESPACE__ . '\render_block',
		]
	);
}

/**
 * Renders the bookmarklet-code block.
 *
 * @since 5.6.0
 *
 * @return string
 */
function render_block() {
	$bookmarklet_code = get_option( 'pf_bookmarklet_code' );

	if ( ! $bookmarklet_code ) {
		return '';
	}

	return $bookmarklet_code;
}
