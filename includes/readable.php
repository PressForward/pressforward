<?php

/**
 * Readability stuff
 */

class PF_Readability {
	/**
	 * Handles a readability request via POST
	 */
	public function make_it_readable($quickresponse = false){

		// Verify nonce
		if ( !wp_verify_nonce($_POST[PF_SLUG . '_nomination_nonce'], 'nomination') )
			die( __( "Nonce check failed. Please ensure you're supposed to be nominating stories.", 'pf' ) );
		ob_start();
		$read_status = 'readable';
		$item_id = $_POST['read_item_id'];
		$post_id = $_POST['post_id'];
		$force = $_POST['force'];
		//error_reporting(0);
		if ( (false === ( $itemReadReady = get_transient( 'item_readable_content_' . $item_id ) )) || $force == 'force' ) {

			set_time_limit(0);
			$url = pf_de_https($_POST['url']);
			$readability_stat = $url;
			$descrip = $_POST['content'];
			$descrip = rawurldecode($descrip);
			if (get_magic_quotes_gpc())  
				$descrip = stripslashes($descrip);

			if ($_POST['authorship'] == 'aggregation') {
				$aggregated = true;
			} else {
				$aggregated = false;
			}

			if ((str_word_count($descrip) <= 150) || $aggregated || $force == 'force') {
				$itemReadReady = self::readability_object($url);
				if ($itemReadReady != 'error-secured') {
					if (!$itemReadReady) {
						$readability_stat .= __( " This content failed Readability.", 'pf' );
						//$itemReadReady .= '<br />';
						$url = str_replace('&amp;','&', $url);
						#Try and get the OpenGraph description.
						if (OpenGraph::fetch($url)){
							$node = OpenGraph::fetch($url);
							$itemReadReady .= $node->description;
						} //Note the @ below. This is because get_meta_tags doesn't have a failure state to check, it just throws errors. Thanks PHP...
						elseif ('' != ($contentHtml = @get_meta_tags($url))) {
							# Try and get the HEAD > META DESCRIPTION tag.
							$readability_stat .= __( " This content failed an OpenGraph check.", 'pf' );
							//$itemReadReady .= '<br />';
							$itemReadReady = $contentHtml['description'];

						}
						else
						{
							# Ugh... we can't get anything huh?
							$readability_stat .= __( " This content has no description we can find.", 'pf' );
							//$itemReadReady .= '<br />';
							# We'll want to return a false to loop with.
							$itemReadReady = $descrip;

						}
						if(strlen($itemReadReady) < strlen($descrip)){
							$itemReadReady = $descrip;
							$readability_stat .= ' Retrieved text is less than original text.';
							$read_status = 'already_readable';
						}
						
					}
				} else {
					$read_status = 'secured';
				}
			} else {
				$read_status = 'already_readable';
				$itemReadReady = $descrip;
			}

			set_transient( 'item_readable_content_' . $item_id, $itemReadReady, 60*60*24 );
		}
		
		# BIG FREAKING WARNING: This WILL NOT WORK if you have WP_DEBUG and WP_DEBUG_DISPLAY true and either your theme or plugins have bad functions on the save_post hook. 
		if ($post_id != 0){
			$update_ready = array(
				'ID' => $post_id,
				'post_content' => html_entity_decode($itemReadReady)
			);
			$update_check = wp_update_post($update_ready, true);
			if (!is_wp_error($update_check)){
				update_post_meta($post_id, 'readable_status', 1);
				$error = 'no error';
			} else {
				$read_status = 'post_not_updated_readable';
				update_post_meta($post_id, 'readable_status', 0);
				$error = $update_check->get_error_message();
			}
		}
		
		if ($quickresponse == true){
			print_r($itemReadReady);
			die($read_status);
		} else {
			$response = array(
				'what' => 'full_item_content',
				'action' => 'make_readable',
				'id' => $item_id,
				'data' => htmlspecialchars($itemReadReady),
				'supplemental' => array(
					'readable_status' => $read_status,
					'error' => $error,
					'buffered' => ob_get_contents(),
					'readable_response' => $readability_stat
				)
			);
			$xmlResponse = new WP_Ajax_Response($response);
			$xmlResponse->send();
			ob_end_flush();
			die();
		}
	}

	/**
	 * Runs a URL through Readability and hands back the stripped content
	 *
	 * @since 1.7
	 * @see http://www.keyvan.net/2010/08/php-readability/
	 * @param $url
	 */
	public static function readability_object($url) {

		set_time_limit(0);

		$url = pf_de_https($url);
		$url = str_replace('&amp;','&', $url);
		//print_r($url); print_r(' - Readability<br />');
		// change from Boone - use wp_remote_get() instead of file_get_contents()
		$request = wp_remote_get( $url, array('timeout' => '30') );
		if (is_wp_error($request)) {
			$content = 'error-secured';
			//print_r($request); die();
			return $content;
		}
		if ( ! empty( $request['body'] ) ){
			$html = $request['body'];
		} else {
			$content = false;
			return $content;
		}

		//check if tidy exists to clean up the input.
		if (function_exists('tidy_parse_string')) {
			$tidy = tidy_parse_string($html, array(), 'UTF8');
			$tidy->cleanRepair();
			$html = $tidy->value;
		}
		// give it to Readability
		$readability = new Readability($html, $url);

		// print debug output?
		// useful to compare against Arc90's original JS version -
		// simply click the bookmarklet with FireBug's
		// console window open
		$readability->debug = false;

		// convert links to footnotes?
		$readability->convertLinksToFootnotes = false;

		// process it
		$result = $readability->init();

		if ($result){
			$content = $readability->getContent()->innerHTML;
			//$content = $contentOut->innerHTML;
				//if we've got tidy, let's use it.
				if (function_exists('tidy_parse_string')) {
					$tidy = tidy_parse_string($content,
						array('indent'=>true, 'show-body-only'=>true),
						'UTF8');
					$tidy->cleanRepair();
					$content = $tidy->value;
				}

			
			$dom = new domDocument;
			$dom->loadHTML($content);
			$dom->preserveWhiteSpace = true;
			$images = $dom->getElementsByTagName('img');
			foreach ($images as $image) {
			  $img = $image->getAttribute('src');
			  if (((strpos($img, '/')) === 0) || (strpos($img, 'http') != 0)){
				$urlArray = parse_url($url);
				$urlBase = 'http://' . $urlArray['host'];
				if (!is_wp_error(wp_remote_head($urlBase . $img))){
					$image->setAttribute('src', $urlBase . $img);
				} elseif (!is_wp_error(wp_remote_head($url . $img))){
					$image->setAttribute('src', $url . $img);
				} else {
					$image->parentNode->removeChild($image);
				}
			  }
			}
			$content = $dom->saveHTML();
			
		} else {
			# If Readability can't get the content, send back a FALSE to loop with.
			$content = false;
			# and let's throw up an error via AJAX as well, so we know what's going on.
			//print_r($url . ' fails Readability.<br />');
		}

		return $content;
	}
}
