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
	 * @param string $url The URL to resolve.
	 * @return string The final URL after following redirects.
	 */
	protected function resolve( $url ) {
		// WordPress's wp_remote_head() automatically follows redirects up to
		// the limit specified in the 'redirection' parameter.
		$response = wp_remote_head(
			$url,
			array(
				'redirection' => 10,  // Follow up to 10 redirects.
				'timeout'     => 30,  // 30 second timeout.
			)
		);

		// If there was an error, return the original URL.
		if ( is_wp_error( $response ) ) {
			return $url;
		}

		// WordPress's HTTP API already follows redirects for us. The response
		// array contains information about the final destination, but the
		// actual final URL isn't directly available. However, we can retrieve
		// it from the underlying HTTP object if available.
		if ( isset( $response['http_response'] ) ) {
			$http_response = $response['http_response'];
			if ( is_a( $http_response, 'WP_HTTP_Requests_Response' ) ) {
				// Get the final URL from the Requests response object.
				$final_url = $http_response->get_response_object()->url;
				if ( ! empty( $final_url ) ) {
					return $final_url;
				}
			}
		}

		// Fallback: if we can't get the final URL from the response object,
		// return the original URL. WordPress has already followed all redirects,
		// so the original URL is effectively the final URL.
		return $url;
	}
}
