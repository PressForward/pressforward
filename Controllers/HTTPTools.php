<?php
/**
 * HTTP utilities.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Interfaces\System;
use mattwright\URLResolver;

/**
 * HTTP utilities.
 */
class HTTPTools implements HasActions {
	/**
	 * URLResolver object.
	 *
	 * @access public
	 * @var \mattwright\URLResolver
	 */
	public $url_resolver;

	/**
	 * Systems object.
	 *
	 * @access public
	 * @var \PressForward\Interfaces\System
	 */
	public $system;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $meta;

	/**
	 * Constructor.
	 *
	 * @param \mattwright\URLResolver         $resolver URLResolver object.
	 * @param \PressForward\Interfaces\System $system   System object.
	 * @param \PressForward\Controllers\Metas $meta     Metas object.
	 */
	public function __construct( URLResolver $resolver, System $system, Metas $meta ) {
		$this->url_resolver = $resolver;
		$this->system       = $system;
		$this->meta         = $meta;
	}

	/**
	 * Sets up action hooks for this class.
	 *
	 * @return array
	 */
	public function action_hooks() {
		$actions = array(
			array(
				'hook'     => 'init',
				'method'   => 'register_non_persistent_cache_groups',
				'priority' => 10,
			),
		);
		return $actions;
	}

	/**
	 * Register non-persistent cache group for external pages.
	 *
	 * We cache external URI fetches, but only for a single pageload.
	 */
	public function register_non_persistent_cache_groups() {
		wp_cache_add_non_persistent_groups(
			array(
				'pressforward_external_pages',
			)
		);
	}

	/**
	 * Resolves a URL.
	 *
	 * @param string $url URL.
	 * @return string|false
	 */
	public function resolve_source_url( $url ) {
		$url       = $this->resolve_a_url( $url );
		$url_array = wp_parse_url( $url );
		if ( empty( $url_array['host'] ) ) {
			return false;
		}
		$source_url = 'http://' . $url_array['host'];
		return $source_url;
	}

	/**
	 * Resolves a URL.
	 *
	 * @param string $url URL to resolve.
	 * @return string|bool
	 */
	public function resolve_full_url( $url ) {
		$url = $this->resolve_a_url( $url );
		return $url;
	}

	/**
	 * Examine a URL and resolve it as needed.
	 *
	 * @since 3.4.5
	 *
	 * @param string $url A web address URI.
	 * @return bool|string True value for a submitted URL that matches an aggregation service.
	 */
	public function resolve_a_url( $url ) {
		$url_array = wp_parse_url( $url );
		if ( empty( $url_array['host'] ) ) {
			return $url;
		} else {
			$check = $this->url_is_aggregation_service( $url );
			if ( $check && in_array( 'curl', get_loaded_extensions(), true ) ) {
				$url = $this->url_resolver->resolveURL( $url )->getURL();
			}
		}

		return $url;
	}

	/**
	 * Return an array of known aggregation services.
	 *
	 * @since 3.4.5
	 *
	 * @return array An array of URLs with aggregation URL host parts.
	 */
	public function aggregation_services() {
		return array(
			'Google'        => 'google.com',
			'Tweeted Times' => 'tweetedtimes.com',
		);
	}

	/**
	 * Check a URL for an aggregation service's forward and return true or false.
	 *
	 * @since 3.4.5
	 *
	 * @param string $url A web address URI.
	 * @return bool True value for a submitted URL that matches an aggregation service.
	 */
	public function url_is_aggregation_service( $url ) {
		$check    = false;
		$services = $this->aggregation_services();
		foreach ( $services as $service ) {
			$pos = strpos( $url, $service );
			if ( ! empty( $pos ) ) {
				$check = true;
			}
		}
		return $check;
	}

	/**
	 * Gets the content from a URL.
	 *
	 * @since 5.4.0 Uses wp_remote_get() internally, rather than cURL or file_get_contents().
	 *
	 * @param string $url        URL.
	 * @param string $deprecated No longer used.
	 * @return array
	 */
	public function get_url_content( $url, $deprecated = '' ) {
		$url = str_replace( '&amp;', '&', $url );

		$cached = wp_cache_get( $url, 'pressforward_external_pages' );
		if ( false !== $cached ) {
			return [
				'body'          => $cached,
				'response_code' => 200,
			];
		}

		// @todo Allow some overrides, via an `$args` param and/or a filter.
		$request_args = [
			'timeout' => 30,
		];

		$response = wp_remote_get( $url, $request_args );

		$response_code = wp_remote_retrieve_response_code( $response );

		// In case of failures, attempt a non-HTTPS request. See #356p.
		if ( $response_code >= 500 ) {
			$non_ssl_response = wp_remote_get( set_url_scheme( $url, 'http' ), $request_args );

			if ( 200 === wp_remote_retrieve_response_code( $non_ssl_response ) ) {
				$response      = $non_ssl_response;
				$response_code = 200;
			}
		}

		$response_body = wp_remote_retrieve_body( $response );

		// We cache regardless of response code, to avoid multiple pings for 404s, etc.
		wp_cache_set( $url, $response_body, 'pressforward_external_pages' );

		return [
			'body'          => $response_body,
			'response_code' => $response_code,
		];
	}
}
