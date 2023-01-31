<?php
/**
 * HTTP utilities.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Interfaces\System;
use URLResolver;

/**
 * HTTP utilities.
 */
class HTTPTools implements HasActions {
	/**
	 * URLResolver object.
	 *
	 * @access public
	 * @var URLResolver
	 */
	public $url_resolver;

	/**
	 * Systems object.
	 *
	 * @access public
	 * @var PressForward\Interfaces\System
	 */
	public $system;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var PressForward\Controllers\Meta
	 */
	public $meta;

	/**
	 * Constructor.
	 *
	 * @param URLResolver                    $resolver URLResolver object.
	 * @param PressForward\Interfaces\System $system   System object.
	 * @param PressForward\Controllers\Metas $meta     Metas object.
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
	 * @return string
	 */
	public function resolve_source_url( $url ) {
		$url       = $this->resolve_a_url( $url );
		$url_array = wp_parse_url( $url );
		if ( empty( $url_array['host'] ) ) {
			return;
		}
		$source_url = 'http://' . $url_array['host'];
		return $source_url;
	}

	/**
	 * Resolves a URL.
	 *
	 * @param string $url URL to resolve.
	 * @return string
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
	 * @return bool True value for a submitted URL that matches an aggregation service.
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
	 * Attempts to get a path for the cookie.
	 *
	 * @return string
	 */
	public function attempt_to_get_cookiepath() {
		$reset       = true;
		$upload_dir  = wp_upload_dir();
		$cookie_path = $upload_dir['basedir'] . 'cookie.txt';
		if ( ! is_file( $cookie_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions
			touch( $cookie_path );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! is_writable( $cookie_path ) ) {
			pf_log( "Can't write to the cookie at $cookie_path." );
			return false;
		} else {
			$debug = 1;
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions
		if ( $reset ) {
			$fo = fopen( $cookie_path, 'w' ) || pf_log( 'Can\'t open cookie file.' );
			fwrite( $fo, '' );
			fclose( $fo );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions

		return $cookie_path;
	}

	/**
	 * Gets the content from a URL.
	 *
	 * @param string   $url      URL.
	 * @param callable $the_function Callback for fetching.
	 * @return string
	 */
	public function get_url_content( $url, $the_function = false ) {
		$args      = func_get_args();
		$url       = str_replace( '&amp;', '&', $url );
		$url_first = $url;
		$r         = false;
		if ( ! $the_function ) {
			$url = set_url_scheme( $url, 'http' );
			$r   = false;
		} else {
			$args[0] = $url;
			unset( $args[1] );

			$cache_key = $the_function . '_' . $url;
			$cached    = wp_cache_get( $cache_key, 'pressforward_external_pages' );
			if ( false === $cached ) {
				$args[1] = [ 'timeout' => 30 ];
				$r       = call_user_func_array( $the_function, $args );
				if ( is_wp_error( $r ) || empty( $r ) ) {
					$non_ssl_url = set_url_scheme( $url, 'http' );
					if ( $non_ssl_url !== $url ) {
						$args[0] = $non_ssl_url;
						$r       = call_user_func_array( $the_function, $args );
					}

					if ( ! $r || is_wp_error( $r ) ) {
						// Last Chance!
						if ( 'file_get_contents' !== $the_function ) {
							// phpcs:ignore WordPress.WP.AlternativeFunctions
							$response = file_get_contents( $url_first );
						} else {
							$response = false;
						}
					}
					wp_cache_set( $cache_key, $r, 'pressforward_external_pages' );
				} else {
					$r = $cached;
				}
			}
		}
		$response          = $r;
		$loaded_extensions = get_loaded_extensions();
		if ( ( false === $response ) || empty( $response ) || is_wp_error( $response ) || ( ! empty( $response ) && ! empty( $response['headers'] ) && isset( $response['headers']['content-length'] ) && ( 50 > strlen( $response['headers']['content-length'] ) ) ) && in_array( 'curl', $loaded_extensions, true ) ) {
			$cookie_path = 'cookie.txt';
			if ( defined( 'COOKIE_PATH_FOR_CURL' ) ) {
				$cookie_path = constant( 'COOKIE_PATH_FOR_CURL' );
				if ( ! isset( $cookie_path ) || false === $cookie_path ) {
					$cookie_path = $this->attempt_to_get_cookiepath();
					if ( false === $cookie_path ) {
						return false;
					}
				}
			} else {
				$cookie_path = $this->attempt_to_get_cookiepath();
				if ( ! $cookie_path ) {
					return false;
				}
			}

			// @todo See https://github.com/PressForward/pressforward/issues/1135.
			// phpcs:disable WordPress.WP.AlternativeFunctions
			$curl = curl_init( $args[0] );

			curl_setopt( $curl, constant( 'CURLOPT_FAILONERROR' ), true );
			curl_setopt( $curl, constant( 'CURLOPT_FOLLOWLOCATION' ), true );
			curl_setopt( $curl, constant( 'CURLOPT_RETURNTRANSFER' ), true );
			curl_setopt( $curl, constant( 'CURLOPT_TIMEOUT' ), 15 );
			curl_setopt( $curl, constant( 'CURLOPT_SSL_VERIFYHOST' ), false );
			curl_setopt( $curl, constant( 'CURLOPT_SSL_VERIFYPEER' ), false );
			$fetch_ua = apply_filters( 'pf_useragent_retrieval_control', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)' );
			curl_setopt( $curl, constant( 'CURLOPT_USERAGENT' ), $fetch_ua );
			// The following 2 set up lines work with sites like www.nytimes.com.
			curl_setopt( $curl, constant( 'CURLOPT_COOKIEFILE' ), $cookie_path ); // you can change this path to whetever you want.
			curl_setopt( $curl, constant( 'CURLOPT_COOKIEJAR' ), $cookie_path ); // you can change this path to whetever you want.
			$encode = apply_filters( 'pf_encoding_retrieval_control', true );
			if ( $encode ) {
				$response = mb_convert_encoding( curl_exec( $curl ), 'HTML-ENTITIES', 'UTF-8' );
			} else {
				$response = curl_exec( $curl );
			}
			// Will return false or the content.
			curl_close( $curl );

			// phpcs:enable WordPress.WP.AlternativeFunctions
			return array( 'body' => $response );
		} else {
			return $response;
		}
	}
}
