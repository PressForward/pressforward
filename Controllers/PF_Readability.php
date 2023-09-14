<?php
/**
 * Readability wrapper.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

use WP_Ajax_Response;

/**
 * Readability stuff
 */
class PF_Readability {

	/**
	 * Abstract function to make everything readable.
	 *
	 * @param array $args {
	 *   Potential arguments to base via array.
	 *   @var string $force      'force' to force through.
	 *   @var string $descrip    Post content.
	 *   @var string $url        Content URL.
	 *   @var string $authorship Authorship string.
	 * }
	 */
	public function get_readable_text( $args ) {
		$force      = ! empty( $args['force'] ) ? $args['force'] : '';
		$descrip    = ! empty( $args['descrip'] ) ? $args['descrip'] : '';
		$url        = ! empty( $args['url'] ) ? $args['url'] : '';
		$authorship = ! empty( $args['authorship'] ) ? $args['authorship'] : '';

		set_time_limit( 0 );
		$readability_stat = $url;
		$url              = pressforward( 'controller.http_tools' )->resolve_full_url( $url );
		$descrip          = rawurldecode( $descrip );

		if ( 'aggregation' === $authorship ) {
			$aggregated = true;
		} else {
			$aggregated = false;
		}

		$stripped_descrip = wp_strip_all_tags( $descrip );
		if ( ( str_word_count( $stripped_descrip ) <= 150 ) || $aggregated || 'force' === $force ) {
			$item_read_ready = $this->readability_object( $url );

			if ( 'error-secured' !== $item_read_ready ) {
				if ( ! $item_read_ready ) {
					$read_status       = 'failed_readability';
					$readability_stat .= __( ' This content failed Readability.', 'pressforward' );

					$url = str_replace( '&amp;', '&', $url );
					// Try and get the OpenGraph description.
					$node = pressforward( 'library.opengraph' )->fetch( $url );
					if ( $node ) {
						$item_read_ready = $node->description;
					} else {
						/*
						 * Note the @ below. This is because get_meta_tags doesn't have a
						 * failure state to check, it just throws errors. Thanks PHP...
						 */
						// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
						$content_html = @get_meta_tags( $url );
						if ( $content_html ) {
							// Try and get the HEAD > META DESCRIPTION tag.
							$read_status     = 'failed_readability_og';
							$item_read_ready = $content_html['description'];

						} else {
							// Ugh... we can't get anything huh?
							$read_status = 'failed_readability_og_meta';

							// We'll want to return a false to loop with.
							$item_read_ready = $descrip;

						}
					}

					if ( strlen( $item_read_ready ) < strlen( $descrip ) ) {
						$item_read_ready   = $descrip;
						$readability_stat .= ' Retrieved text is less than original text.';
						$read_status       = 'already_readable';
					}
					$item_read_ready = $this->process_in_oembeds( $url, $item_read_ready );
				} else {
					$read_status     = 'made_readable';
					$item_read_ready = $this->process_in_oembeds( $url, $item_read_ready );
				}
			} else {
				$read_status     = 'secured';
				$item_read_ready = $descrip;
			}
		} else {
			$read_status     = 'already_readable';
			$item_read_ready = $descrip;
		}

		$return_args = array(
			'status'   => $read_status,
			'readable' => $item_read_ready,
			'url'      => $url,
		);

		return $return_args;
	}

	/**
	 * Handles a readability request via POST
	 */
	public function make_it_readable() {

		// Verify nonce.
		if ( ! isset( $_POST[ PF_SLUG . '_nomination_nonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ PF_SLUG . '_nomination_nonce' ] ) ), 'nomination' ) ) {
			die( esc_html__( "Nonce check failed. Please ensure you're supposed to be nominating stories.", 'pressforward' ) ); }
		ob_start();
		libxml_use_internal_errors( true );
		$read_status = 'readable';
		$item_id     = isset( $_POST['read_item_id'] ) ? intval( $_POST['read_item_id'] ) : 0;
		$post_id     = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$force       = isset( $_POST['force'] ) ? sanitize_text_field( wp_unslash( $_POST['force'] ) ) : '';
		$url         = isset( $_POST['url'] ) ? sanitize_text_field( wp_unslash( $_POST['url'] ) ) : '';

		$item_read_ready = get_transient( 'item_readable_content_' . $item_id );
		if ( false === $item_read_ready || 'force' === $force ) {

			$authorship = isset( $_POST['authorship'] ) ? sanitize_text_field( wp_unslash( $_POST['authorship'] ) ) : '';

			$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

			$args = array(
				'force'      => $force,
				'descrip'    => $content,
				'url'        => $url,
				'authorship' => $authorship,
				'post_id'    => $post_id,
			);

			$readable_ready = $this->get_readable_text( $args );

			$read_status     = $readable_ready['status'];
			$item_read_ready = $readable_ready['readable'];
			$readable_url    = $readable_ready['url'];
			if ( ! strpos( $item_read_ready, $readable_url ) ) {
				$item_read_ready = $this->process_in_oembeds( $readable_url, $item_read_ready );
			}

			set_transient( 'item_readable_content_' . $item_id, $item_read_ready, 60 * 60 * 24 );
		}

		$content_obj     = pressforward( 'library.htmlchecker' );
		$item_read_ready = $content_obj->closetags( $item_read_ready );
		pf_log( 'Making readable' );

		$source_statement         = '';
		$error                    = '';
		$response_item_read_ready = '';

		/*
		 * BIG FREAKING WARNING: This WILL NOT WORK if you have WP_DEBUG and
		 * WP_DEBUG_DISPLAY true and either your theme or plugins have bad functions
		 * on the save_post hook.
		 */
		if ( 0 !== $post_id ) {

			$content      = html_entity_decode( $item_read_ready );
			$update_ready = array(
				'ID'           => $post_id,
				'post_content' => $content,
			);
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( strlen( $_POST['content'] ) < strlen( $content ) ) {
				$update_check = wp_update_post( $update_ready, true );
				if ( ! is_wp_error( $update_check ) ) {
					pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'readable_status', 1 );
					$error = 'no error';
				} else {
					$read_status = 'post_not_updated_readable';
					pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'readable_status', 0 );
					$error = $update_check->get_error_message();
				}
				$response_item_read_ready = $this->get_embed( $url ) . $item_read_ready;
				$source_statement         = pressforward( 'utility.forward_tools' )->append_source_statement( $post_id, '', true );
			} else {
				$error            = 'Not Updated, retrieved content is longer than stored content.';
				$source_statement = pressforward( 'utility.forward_tools' )->append_source_statement( $post_id, '', true );
			}
		}
		$dom_doc_errors = '';
		$dderrors       = libxml_get_errors();
		foreach ( $dderrors as $dderror ) {
			$dom_doc_errors .= ' Error: ' . $dderror->code . ' Line:' . $dderror->line . ' ' . $dderror->message;
		}

		$response = array(
			'what'         => 'full_item_content',
			'action'       => 'make_readable',
			'id'           => $item_id,
			'data'         => htmlspecialchars( $response_item_read_ready ),
			'supplemental' => array(
				'readable_status'         => $read_status,
				'error'                   => $error,
				'buffered'                => ob_get_contents(),
				'domDoc_errors'           => $dom_doc_errors,
				'readable_applied_to_url' => $url,
				'source_statement'        => $source_statement,
			),
		);

		$xml_response = new WP_Ajax_Response( $response );
		$xml_response->send();
	}

	/**
	 * Runs a URL through Readability and hands back the stripped content.
	 *
	 * @since 1.7
	 * @see http://www.keyvan.net/2010/08/php-readability/
	 * @param string $url URL to fetch.
	 */
	public function readability_object( $url ) {

		set_time_limit( 0 );
		$url = pressforward( 'controller.http_tools' )->resolve_full_url( $url );

		$request = pf_de_https( $url, 'wp_remote_get' );

		if ( ! empty( $request['body'] ) ) {
			$html = $request['body'];
		} elseif ( ! empty( $request ) && ( ! is_array( $request ) ) ) {
			$html = $request;
		} else {
			$content = false;
			return $content;
		}

		$content = $this->process_readability( $html, $url );

		return $content;
	}

	/**
	 * Processes content through Readability.
	 *
	 * @param string $html HTML content.
	 * @param string $url  URL.
	 * @return string
	 */
	public function process_readability( $html, $url ) {
		// Check if tidy exists to clean up the input.
		if ( function_exists( 'tidy_parse_string' ) ) {
			$tidy = tidy_parse_string( $html, array( 'wrap' => 0 ), 'UTF8' );

			if ( $tidy instanceof \tidy ) {
				$tidy->cleanRepair();
				$html = $tidy->value;
			}
		}

		$content = null;

		// Readability requirements: PHP 7.4, ext-dom, ext-mbstring, ext-xml.
		$use_upstream_readability = version_compare( phpversion(), '7.4.0', '>=' ) && extension_loaded( 'mbstring' ) && extension_loaded( 'xml' ) && extension_loaded( 'dom' );
		if ( $use_upstream_readability ) {
			$configuration = new \fivefilters\Readability\Configuration();
			$readability   = new \fivefilters\Readability\Readability( $configuration );

			try {
				$readability->parse( $html );
				$content = $readability->getContent();
			} catch ( \fivefilters\Readability\ParseException $e ) {
				$content = null;
			}
		} else {
			// Give it to Readability.
			$readabilitizer = pressforward( 'library.readability' );
			$readability    = $readabilitizer( $html, $url );

			// Print debug output?
			// Useful to compare against Arc90's original JS version -
			// simply click the bookmarklet with FireBug's console window open.
			$readability->debug = false;

			// Convert links to footnotes?
			// phpcs:disable WordPress.NamingConventions
			$readability->convertLinksToFootnotes = false;

			// Process it.
			$result = $readability->init();

			$content = $result ? $readability->getContent()->innerHTML : '';
		}

		if ( null !== $content ) {
			// If we've got tidy, let's use it.
			if ( function_exists( 'tidy_parse_string' ) ) {
				$tidy = tidy_parse_string(
					$content,
					array(
						'indent'         => true,
						'show-body-only' => true,
						'wrap'           => 0,
					),
					'UTF8'
				);

				if ( $tidy instanceof \tidy ) {
					$tidy->cleanRepair();
					$content = $tidy->value;
				}
			}

			$content     = balanceTags( $content, true );
			$content     = ent2ncr( $content );
			$content     = convert_chars( $content );
			$dom_rotated = 0;
			$dom         = new \domDocument( '1.0', 'utf-8' );

			// phpcs:disable WordPress.NamingConventions
			$dom->preserveWhiteSpace = true;
			$dom->substituteEntities = true;
			$dom->resolveExternals   = true;
			$dom->preserveWhiteSpace = true;
			// phpcs:enable WordPress.NamingConventions

			$dom->loadXML( '<fullContent>' . $content . '</fullContent>' );
			$images = $dom->getElementsByTagName( 'img' );
			foreach ( $images as $image ) {
				$img = $image->getAttribute( 'src' );
				if ( ( ( strpos( $img, '/' ) ) === 0 ) || ( strpos( $img, 'http' ) !== 0 ) ) {
					$url_array = wp_parse_url( $url );
					if ( ( strpos( $img, 'http' ) !== 0 ) ) {
						$url_base = 'http://' . $url_array['host'] . '/';
					} else {
						$url_base = 'http://' . $url_array['host'];
					}
					if ( ! is_wp_error( wp_remote_head( $url_base . $img ) ) ) {
						$image->setAttribute( 'src', $url_base . $img );
						++$dom_rotated;
					} elseif ( ! is_wp_error( wp_remote_head( $url . $img ) ) ) {
						$image->setAttribute( 'src', $url . $img );
						++$dom_rotated;
					} else {
						// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$image->parentNode->removeChild( $image );
						++$dom_rotated;
					}
				}
			}
			if ( $dom_rotated > 0 ) {
				$content = $dom->saveXML();
				$rel     = '(<\\?xml version="1\\.0" encoding="utf-8"\\?>)';
				$content = preg_replace( '/' . $rel . '/is', ' ', $content );
				$rel     = '(<\\?xml version="1\\.0"\\?>)';
				$content = preg_replace( '/' . $rel . '/is', ' ', $content );
			}
			if ( 120 > strlen( $content ) ) {
				$content = false;
			}
		} else {
			// If Readability can't get the content, send back a FALSE to loop with.
			$content = false;
			// and let's throw up an error via AJAX as well, so we know what's going on.
		}

		if ( false !== $content ) {
			$content_obj = pressforward( 'library.htmlchecker' );
			$content     = $content_obj->closetags( $content );
			$content     = $this->process_in_oembeds( $url, $content );
		}

		return $content;
	}

	/**
	 * Processes embed content into post content.
	 *
	 * @param string $item_link    URL of the embed.
	 * @param string $item_content Item content.
	 * @return string
	 */
	public function process_in_oembeds( $item_link, $item_content ) {
		$providers = pressforward( 'schema.feed_item' )->oembed_capables();
		foreach ( $providers as $provider ) {
			if ( false !== strpos( $item_content, $item_link ) ) {
				$added_content = '

				' . $item_link . '

				';
				$item_content  = $added_content . $item_content;
			}
		}
		return $item_content;
	}

	/**
	 * Gets an embed for a URL.
	 *
	 * @param string $item_link URL of item to embed.
	 * @return string|bool
	 */
	public function get_embed( $item_link ) {
		// Only bother checking if this looks like an embeddable URL.
		$wp_oembed = _wp_oembed_get_object();
		$provider  = $wp_oembed->get_provider( $item_link, [ 'discover' => false ] );

		if ( ! $provider ) {
			return '';
		}

		$transient_key = 'pressforward_oembed_' . md5( $item_link );

		$oembed = get_transient( $transient_key );
		if ( false === $oembed ) {
			$oembed = wp_oembed_get( $item_link );
			set_transient( $transient_key, $oembed, WEEK_IN_SECONDS );
		}

		if ( false !== $oembed ) {
			$providers = pressforward( 'schema.feed_item' )->oembed_capables();
			foreach ( $providers as $provider ) {
				if ( 0 !== strpos( $item_link, $provider ) ) {
					return $oembed;
				}
			}
		} else {
			return false;
		}
		return false;
	}
}
