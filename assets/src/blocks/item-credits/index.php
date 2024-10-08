<?php
/**
 * Server-side registration for the item-credits block.
 *
 * @package PressForward
 * @since 5.6.0
 */

namespace PressForward\Core\Blocks\ItemCredits;

add_action( 'init', __NAMESPACE__ . '\register_block' );

/**
 * Registers the item-credits block.
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
 * Renders the item-credits block.
 *
 * @since 5.6.0
 *
 * @param array $attributes The block attributes.
 * @return string
 */
function render_block( $attributes ) {
	$the_post = get_post();
	if ( ! $the_post ) {
		return '';
	}

	$lines = [];

	if ( $attributes['showAuthorName'] ) {
		$lines[] = [
			'prefix'  => $attributes['prefixAuthorName'],
			'content' => get_post_meta( $the_post->ID, 'item_author', true ),
		];
	}

	if ( $attributes['showSourceLink'] ) {
		$item_link = get_post_meta( $the_post->ID, 'item_link', true );

		$lines[] = [
			'prefix'  => $attributes['prefixSourceLink'],
			'content' => sprintf( '<a href="%s">%s</a>', esc_url( $item_link ), esc_html( $item_link ) ),
		];
	}

	// Assemble inline styles.
	$inline_styles = [];
	if ( isset( $attributes['style']['typography']['fontSize'] ) ) {
		$inline_styles[] = 'font-size: ' . process_var_style_property( $attributes['style']['typography']['fontSize'] );
	}

	if ( isset( $attributes['style']['typography']['lineHeight'] ) ) {
		$inline_styles[] = 'line-height: ' . process_var_style_property( $attributes['style']['typography']['lineHeight'] );
	}

	if ( isset( $attributes['style']['color']['background'] ) ) {
		$inline_styles[] = 'background-color: ' . process_var_style_property( $attributes['style']['color']['background'] );
	}

	if ( isset( $attributes['style']['color']['text'] ) ) {
		$inline_styles[] = 'color: ' . process_var_style_property( $attributes['style']['color']['text'] );
	}

	$spacing_types = [ 'margin', 'padding' ];
	foreach ( $spacing_types as $spacing_type ) {
		if ( ! isset( $attributes['style']['spacing'][ $spacing_type ] ) ) {
			continue;
		}

		if ( is_scalar( $attributes['style']['spacing'][ $spacing_type ] ) ) {
			$inline_styles[] = $spacing_type . ': ' . process_var_style_property( $attributes['style']['spacing'][ $spacing_type ] );
			continue;
		}

		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $spacing_direction ) {
			if ( isset( $attributes['style']['spacing'][ $spacing_type ][ $spacing_direction ] ) ) {
				$inline_styles[] = $spacing_type . '-' . $spacing_direction . ': ' . process_var_style_property( $attributes['style']['spacing'][ $spacing_type ][ $spacing_direction ] );
			}
		}
	}

	$extra_attributes = [];
	if ( $inline_styles ) {
		$extra_attributes['style'] = implode( ';', $inline_styles ) . ';';
	}

	wp_enqueue_style( 'pf-blocks-frontend' );

	ob_start();

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<div ' . get_block_wrapper_attributes( $extra_attributes ) . '>';

	foreach ( $lines as $line ) {
		if ( ! $line['content'] ) {
			continue;
		}

		echo '<div class="pf-credits-line">';

		echo '<p class="pf-credits-prefix">';
		echo wp_kses_post( $line['prefix'] );
		echo '</p>';

		echo '<p class="pf-credits">';
		echo wp_kses_post( $line['content'] );
		echo '</p>';

		echo '</div>';
	}

	echo '</div>';

	$block = ob_get_clean();

	return $block;
}

/**
 * Detects and processes style property that may be prefixed with 'var:'.
 *
 * @param string $style_property The style property to process.
 * @return string
 */
function process_var_style_property( $style_property ) {
	if ( 0 !== strpos( $style_property, 'var:' ) ) {
		return $style_property;
	}

	$style_property = substr( $style_property, 4 );

	$style_property = str_replace( '|', '--', $style_property );

	return 'var(--' . $style_property . ')';
}
