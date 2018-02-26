<?php
namespace PressForward\Controllers;

// use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Interfaces\System;
use URLResolver;
/**
 * Readability stuff
 */

class HTTPTools {

	function __construct( URLResolver $resolver, System $system, Metas $meta ) {
		$this->url_resolver = $resolver;
		$this->system       = $system;
		$this->meta         = $meta;
	}

	public function resolve_source_url( $url ) {
		$url       = $this->resolve_a_url( $url );
		$url_array = parse_url( $url );
		if ( empty( $url_array['host'] ) ) {
			return;
		}
		$source_url = 'http://' . $url_array['host'];
		return $source_url;
	}

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
		$url_array = parse_url( $url );
		if ( empty( $url_array['host'] ) ) {
			return $url;
		} else {
			$check = $this->url_is_aggregation_service( $url );
			if ( $check && in_array( 'curl', get_loaded_extensions() ) ) {
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

	function attempt_to_get_cookiepath() {
		$reset       = true;
		$upload_dir  = wp_upload_dir();
		$cookie_path = $upload_dir['basedir'] . 'cookie.txt';
		if ( ! is_file( $cookie_path ) ) {
			touch( $cookie_path );
		}
		if ( ! is_writable( $cookie_path ) ) {
			pf_log( "Can't write to the cookie at $cookie_path." );
			return false;
		} else {
			$debug = 1;
		}
		if ( $reset ) {
			$fo = fopen( $cookie_path, 'w' ) or pf_log( 'Can\'t open cookie file.' );
			fwrite( $fo, '' );
			fclose( $fo );

		}
		return $cookie_path;
	}

	function get_url_content( $url, $function = false ) {
		$args      = func_get_args();
		$url       = str_replace( '&amp;', '&', $url );
		$url_first = $url;
		if ( ! $function ) {
			$url = set_url_scheme( $url, 'http' );
			$r = false;
		} else {
			$args[0] = $url;
			unset( $args[1] );
			// var_dump($args);
			$r = call_user_func_array( $function, $args );
			// var_dump($r); die();
			// "A variable is considered empty if it does not exist or if its value equals FALSE"
			if ( is_wp_error( $r ) || empty( $r ) ) {
				$non_ssl_url = set_url_scheme( $url, 'http' );
				if ( $non_ssl_url != $url ) {
							$args[0] = $non_ssl_url;
					$r               = call_user_func_array( $function, $args );
							// var_dump($url); die();
				}
					// $r = false;
				if ( ! $r || is_wp_error( $r ) ) {
					// Last Chance!
					if ( 'file_get_contents' != $function ) {
						$response = file_get_contents( $url_first );
						// var_dump($r); die();
					} else {
						// bail
						$response = false;
					}
				}
			}
		}
		$response          = $r;
		$loaded_extensions = get_loaded_extensions();
		if ( (false === $response) || empty( $response ) || is_wp_error( $response ) || ( ! empty( $response ) && ! empty( $response['headers'] ) && isset( $response['headers']['content-length'] ) && ( 50 > strlen( $response['headers']['content-length'] ) ) ) && in_array( 'curl', $loaded_extensions ) ) {
			$cookie_path = 'cookie.txt';
			if ( defined( 'COOKIE_PATH_FOR_CURL' ) ) {
				$cookie_path = constant( 'COOKIE_PATH_FOR_CURL' );
				if ( ! isset( $cookie_path ) || false == $cookie_path ) {
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
			$curl = curl_init( $args[0] );

			curl_setopt( $curl, constant( 'CURLOPT_FAILONERROR' ), true );
			curl_setopt( $curl, constant( 'CURLOPT_FOLLOWLOCATION' ), true );
			curl_setopt( $curl, constant( 'CURLOPT_RETURNTRANSFER' ), true );
			curl_setopt( $curl, constant( 'CURLOPT_TIMEOUT' ), 15 );
			curl_setopt( $curl, constant( 'CURLOPT_SSL_VERIFYHOST' ), false );
			curl_setopt( $curl, constant( 'CURLOPT_SSL_VERIFYPEER' ), false );
			$fetch_ua = apply_filters( 'pf_useragent_retrieval_control', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)' );
			curl_setopt( $curl, constant( 'CURLOPT_USERAGENT' ), $fetch_ua );
			// The following 2 set up lines work with sites like www.nytimes.com
			curl_setopt( $curl, constant( 'CURLOPT_COOKIEFILE' ), $cookie_path ); // you can change this path to whetever you want.
			curl_setopt( $curl, constant( 'CURLOPT_COOKIEJAR' ), $cookie_path ); // you can change this path to whetever you want.
			$encode = apply_filters( 'pf_encoding_retrieval_control', true );
			if ($encode){
				$response = mb_convert_encoding( curl_exec( $curl ), 'HTML-ENTITIES', 'UTF-8' );
			} else {
				$response = curl_exec( $curl );
			}
			// Will return false or the content.
			curl_close( $curl );
			return array( 'body' => $response );
		} else {
			return $response;
		}
	}
}
