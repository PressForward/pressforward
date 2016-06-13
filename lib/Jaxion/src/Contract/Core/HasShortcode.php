<?php
namespace Intraxia\Jaxion\Contract\Core;

interface HasShortcode {
	/**
	 * Get the shortcode string to interpret.
	 *
	 * @return string
	 */
	public function shortcode_name();

	/**
	 * WordPress callback to fire on the shortcode.
	 *
	 * @param array  $atts
	 * @param string $content
	 */
	public function do_shortcode( array $atts, $content = '' );
}
