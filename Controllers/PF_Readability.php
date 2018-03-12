<?php
namespace PressForward\Controllers;

use WP_Ajax_Response;
/**
 * Readability stuff
 */

class PF_Readability {

	/**
	 * Abstract function to make everything readable.
	 *
	 * Potential arguments to base via array
	 *          $args = array(
	 *          'force'         => $force,
	 *          'descrip'       => $_POST['content'],
	 *          'url'           => $url,
	 *          'authorship'    => $_POST['authorship']
	 *      );
	 */
	public function get_readable_text( $args ) {
			// ob_start();
			extract( $args, EXTR_SKIP );
			set_time_limit( 0 );
			$readability_stat = $url;
			// var_dump($args);
			$url = pressforward( 'controller.http_tools' )->resolve_full_url( $url );
			// var_dump($url); die();
			$descrip = rawurldecode( $descrip );
		if ( get_magic_quotes_gpc() ) {
			$descrip = stripslashes( $descrip ); }

		if ( $authorship == 'aggregation' ) {
			$aggregated = true;
		} else {
			$aggregated = false;
		}
			$stripped_descrip = strip_tags( $descrip );
		if ( ( str_word_count( $stripped_descrip ) <= 150 ) || $aggregated || $force == 'force' ) {
			$itemReadReady = $this->readability_object( $url );
			// print_r(  wp_richedit_pre($itemReadReady));
			if ( $itemReadReady != 'error-secured' ) {
				if ( ! $itemReadReady ) {
					$read_status       = 'failed_readability';
					$readability_stat .= __( ' This content failed Readability.', 'pf' );
					// $itemReadReady .= '<br />';
					$url = str_replace( '&amp;', '&', $url );
					// Try and get the OpenGraph description.
					if ( pressforward( 'library.opengraph' )->fetch( $url ) ) {
						$node          = pressforward( 'library.opengraph' )->fetch( $url );
						$itemReadReady = $node->description;
					} //Note the @ below. This is because get_meta_tags doesn't have a failure state to check, it just throws errors. Thanks PHP...
					elseif ( '' != ( $contentHtml = @get_meta_tags( $url ) ) ) {
						// Try and get the HEAD > META DESCRIPTION tag.
						$read_status = 'failed_readability_og';
						// $itemReadReady .= '<br />';
						$itemReadReady = $contentHtml['description'];

					} else {
						// Ugh... we can't get anything huh?
						$read_status = 'failed_readability_og_meta';
						// $itemReadReady .= '<br />';
						// We'll want to return a false to loop with.
						$itemReadReady = $descrip;

					}
					if ( strlen( $itemReadReady ) < strlen( $descrip ) ) {
						$itemReadReady     = $descrip;
						$readability_stat .= ' Retrieved text is less than original text.';
						$read_status       = 'already_readable';
					}
					$itemReadReady = $this->process_in_oembeds( $url, $itemReadReady );
				} else {
					$read_status   = 'made_readable';
					$itemReadReady = $this->process_in_oembeds( $url, $itemReadReady );
				}
			} else {
				$read_status   = 'secured';
				$itemReadReady = $descrip;
			}
		} else {
			$read_status   = 'already_readable';
			$itemReadReady = $descrip;
		}

			$return_args = array(
				'status'   => $read_status,
				'readable' => $itemReadReady,
				'url'      => $url,
			);
			// ob_end_flush();
			return $return_args;

	}

	/**
	 * Handles a readability request via POST
	 */
	public function make_it_readable( $quickresponse = false ) {

		// Verify nonce
		if ( ! wp_verify_nonce( $_POST[ PF_SLUG . '_nomination_nonce' ], 'nomination' ) ) {
			die( __( "Nonce check failed. Please ensure you're supposed to be nominating stories.", 'pf' ) ); }
		ob_start();
		libxml_use_internal_errors( true );
		$read_status = 'readable';
		$item_id     = $_POST['read_item_id'];
		$post_id     = $_POST['post_id'];
		$force       = $_POST['force'];
		$url         = $_POST['url'];
		// error_reporting(0);
		if ( ( false === ( $itemReadReady = get_transient( 'item_readable_content_' . $item_id ) ) ) || $force == 'force' ) {

			$args = array(
				'force'      => $force,
				'descrip'    => $_POST['content'],
				'url'        => $_POST['url'],
				'authorship' => $_POST['authorship'],
				'post_id'    => $_POST['post_id'],
			);

			$readable_ready = $this->get_readable_text( $args );

			$read_status   = $readable_ready['status'];
			$itemReadReady = $readable_ready['readable'];
			$url           = $readable_ready['url'];
			if ( ! strpos( $itemReadReady, $url ) ) {
				$itemReadReady = $this->process_in_oembeds( $url, $itemReadReady );
			}

			set_transient( 'item_readable_content_' . $item_id, $itemReadReady, 60 * 60 * 24 );
		}

		$contentObj    = pressforward( 'library.htmlchecker' );
		$itemReadReady = $contentObj->closetags( $itemReadReady );
		pf_log( 'Making readable' );

		// BIG FREAKING WARNING: This WILL NOT WORK if you have WP_DEBUG and WP_DEBUG_DISPLAY true and either your theme or plugins have bad functions on the save_post hook.
		if ( $post_id != 0 ) {

			$content = html_entity_decode( $itemReadReady );
			// $content = pressforward('utility.forward_tools')->append_source_statement($post_id, $content, true);
			$update_ready = array(
				'ID'           => $post_id,
				'post_content' => $content,
			);
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
				$responseItemReadReady = $this->get_embed( $_POST['url'] ) . $itemReadReady;
				$source_statement      = pressforward( 'utility.forward_tools' )->append_source_statement( $post_id, '', true );
			} else {
				$error            = 'Not Updated, retrieved content is longer than stored content.';
				$source_statement = pressforward( 'utility.forward_tools' )->append_source_statement( $post_id, '', true );
			}
		}
		$domDocErrors = '';
		$dderrors     = libxml_get_errors();
		foreach ( $dderrors as $dderror ) {
			$domDocErrors .= ' Error: ' . $dderror->code . ' Line:' . $dderror->line . ' ' . $dderror->message;
		}

			$response    = array(
				'what'         => 'full_item_content',
				'action'       => 'make_readable',
				'id'           => $item_id,
				'data'         => htmlspecialchars( $responseItemReadReady ),
				'supplemental' => array(
					'readable_status'         => $read_status,
					'error'                   => $error,
					'buffered'                => ob_get_contents(),
					'domDoc_errors'           => $domDocErrors,
					'readable_applied_to_url' => $_POST['url'],
					'source_statement'        => $source_statement,
				),
			);
			$xmlResponse = new WP_Ajax_Response( $response );
			$xmlResponse->send();
			libxml_clear_errors();
			ob_end_flush();
			die();
	}

	/**
	 * Runs a URL through Readability and hands back the stripped content
	 *
	 * @since 1.7
	 * @see http://www.keyvan.net/2010/08/php-readability/
	 * @param $url
	 */
	public function readability_object( $url ) {

		set_time_limit( 0 );
		$url = pressforward( 'controller.http_tools' )->resolve_full_url( $url );

		$request = pf_de_https(
			$url, 'wp_remote_get', array(
				'timeout'    => '30',
				'user-agent' => 'AdsBot-Google (+http://www.google.com/adsbot.html)',
				'headers'    => array(
					'X-PressForward' => get_site_url(),
				),
			)
		);
		// var_dump($request); die();
		// print_r($url); print_r(' - Readability<br />');
		// change from Boone - use wp_remote_get() instead of file_get_contents()
		// $request = wp_remote_get( $url, array('timeout' => '30') );
		if ( is_wp_error( $request ) ) {
			$content = 'error-secured';
			// print_r($request); die();
			return $content;
		}
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

	public function process_readability( $html, $url ) {
		// check if tidy exists to clean up the input.
		if ( function_exists( 'tidy_parse_string' ) ) {
			$tidy = tidy_parse_string( $html, array( 'wrap' => 0 ), 'UTF8' );
			$tidy->cleanRepair();
			$html = $tidy->value;
		}
		// give it to Readability
		$readabilitizer = pressforward( 'library.readability' );
		$readability    = $readabilitizer( $html, $url );

		// print debug output?
		// useful to compare against Arc90's original JS version -
		// simply click the bookmarklet with FireBug's
		// console window open
		$readability->debug = false;

		// convert links to footnotes?
		$readability->convertLinksToFootnotes = false;

		// process it
		$result = $readability->init();

		if ( $result ) {
			$content = $readability->getContent()->innerHTML;
			// $content = $contentOut->innerHTML;
				// if we've got tidy, let's use it.
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
				$tidy->cleanRepair();
				$content = $tidy->value;
			}

			$content    = balanceTags( $content, true );
			$content    = ent2ncr( $content );
			$content    = convert_chars( $content );
			$domRotated = 0;
			$dom        = new \domDocument( '1.0', 'utf-8' );

			$dom->preserveWhiteSpace = true;
			$dom->substituteEntities = true;
			$dom->resolveExternals   = true;
			$dom->loadXML( '<fullContent>' . $content . '</fullContent>' );
			$images = $dom->getElementsByTagName( 'img' );
			foreach ( $images as $image ) {
				$img = $image->getAttribute( 'src' );
				if ( ( ( strpos( $img, '/' ) ) === 0 ) || ( strpos( $img, 'http' ) != 0 ) ) {
					$urlArray = parse_url( $url );
					if ( ( strpos( $img, 'http' ) != 0 ) ) {
						$urlBase = 'http://' . $urlArray['host'] . '/';
					} else {
						$urlBase = 'http://' . $urlArray['host'];
					}
					if ( ! is_wp_error( wp_remote_head( $urlBase . $img ) ) ) {
						$image->setAttribute( 'src', $urlBase . $img );
						$domRotated++;
					} elseif ( ! is_wp_error( wp_remote_head( $url . $img ) ) ) {
						$image->setAttribute( 'src', $url . $img );
						$domRotated++;
					} else {
						$image->parentNode->removeChild( $image );
						$domRotated++;
					}
				}
			}
			if ( $domRotated > 0 ) {
				$content = $dom->saveXML();
				$rel     = '(<\\?xml version="1\\.0" encoding="utf-8"\\?>)';
				$content = preg_replace( '/' . $rel . '/is', ' ', $content );
				$rel     = '(<\\?xml version="1\\.0"\\?>)';
				$content = preg_replace( '/' . $rel . '/is', ' ', $content );
			}
			if ( 120 > strlen( $content ) ) {
				$content = false;}
			// $content = stripslashes($content);
			// print_r($content);
			// var_dump($content); die();
			// this will also output doctype and comments at top level
			// $content = "";
			// foreach($dom->childNodes as $node){
			// $content .= $dom->saveXML($node)."\n";
			// }
		} else {
			// If Readability can't get the content, send back a FALSE to loop with.
			$content = false;
			// and let's throw up an error via AJAX as well, so we know what's going on.
			// print_r($url . ' fails Readability.<br />');
		}
		if ( $content != false ) {
				$contentObj = pressforward( 'library.htmlchecker' );
				$content    = $contentObj->closetags( $content );
				$content    = $this->process_in_oembeds( $url, $content );
		}

		return $content;
	}

	public function process_in_oembeds( $item_link, $item_content ) {
		$providers = pressforward( 'schema.feed_item' )->oembed_capables();
		foreach ( $providers as $provider ) {
			if ( ( false == strpos( $item_content, $item_link ) ) && ( 0 != strpos( $item_link, $provider ) ) ) {
				$added_content = '

				' . $item_link . '

				';
				$item_content  = $added_content . $item_content;
			}
		}
		return $item_content;

	}

	public function get_embed( $item_link ) {
		$oembed = wp_oembed_get( $item_link );
		if ( false != $oembed ) {
			$providers = pressforward( 'schema.feed_item' )->oembed_capables();
			foreach ( $providers as $provider ) {
				if ( 0 != strpos( $item_link, $provider ) ) {
					return $oembed;
				}
			}
		} else {
			return false;
		}
		return false;
	}
}
