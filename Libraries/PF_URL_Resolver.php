<?php
/**
 * URL Resolver for PressForward.
 *
 * A simple URL resolver that follows redirects to find the final URL.
 * Replaces the abandoned mattwright/urlresolver library.
 *
 * @package PressForward
 */

namespace PressForward\Libraries;

/**
 * URL Resolver class.
 *
 * Resolves URLs by following redirects using WordPress HTTP API.
 * This class provides a compatible interface with mattwright\URLResolver.
 */
class PF_URL_Resolver {
	/**
	 * The resolved final URL.
	 *
	 * @var string
	 */
	protected $final_url;

	/**
	 * Resolve a URL by following redirects.
	 *
	 * @param string $url The URL to resolve.
	 * @return self Returns this instance for method chaining.
	 */
	public function resolveURL( $url ) {
		$this->final_url = $this->resolve( $url );
		return $this;
	}

	/**
	 * Get the resolved URL.
	 *
	 * @return string The final resolved URL.
	 */
	public function getURL() {
		return $this->final_url;
	}

	/**
	 * Actually resolve the URL using wp_remote_head.
	 *
	 * Uses WordPress HTTP API to follow redirects and find the final URL.
	 * This is a simplified replacement for the abandoned mattwright/urlresolver
	 * library that uses wp_remote_head() instead of raw cURL.
	 *
	 * Since WordPress's wp_remote_head() doesn't easily expose the final URL
	 * after following redirects, we manually follow them by checking Location
	 * headers.
	 *
	 * @param string $url The URL to resolve.
	 * @return string The final URL after following redirects.
	 */
	protected function resolve( $url ) {
		$max_redirects = 10;
		$current_url   = $url;

		for ( $i = 0; $i < $max_redirects; $i++ ) {
			// Make a HEAD request but don't auto-follow redirects.
			// We want to manually follow them so we can track the final URL.
			$response = wp_remote_head(
				$current_url,
				array(
					'redirection' => 0,  // Don't auto-follow redirects.
					'timeout'     => 30, // 30 second timeout.
				)
			);

			// If there was an error, return the current URL.
			if ( is_wp_error( $response ) ) {
				return $current_url;
			}

			$response_code = wp_remote_retrieve_response_code( $response );

			// If we got a redirect status code (301, 302, 303, 307, 308),
			// follow the Location header.
			if ( in_array( $response_code, array( 301, 302, 303, 307, 308 ), true ) ) {
				$location = wp_remote_retrieve_header( $response, 'location' );

				if ( empty( $location ) ) {
					// Redirect without Location header - return current URL.
					return $current_url;
				}

				// Handle relative URLs.
				if ( 0 === strpos( $location, '/' ) ) {
					$parsed_url = wp_parse_url( $current_url );
					$scheme     = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] : 'http';
					$host       = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
					$location   = $scheme . '://' . $host . $location;
				}

				// Update current URL and continue loop.
				$current_url = $location;
			} else {
				// Not a redirect - we've reached the final URL.
				return $current_url;
			}
		}

		// If we've exhausted max redirects, return the current URL.
		return $current_url;
	}
}
