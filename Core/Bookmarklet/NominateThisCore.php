<?php
namespace PressForward\Core\Bookmarklet;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use WP_Ajax_Response;

class NominateThisCore {
	// implements HasActions, HasFilters
	protected $basename;

	function __construct() {

	}

	/**
	 * Press It form handler.
	 *
	 * @package WordPress
	 * @subpackage Press_This
	 * @since 2.6.0
	 *
	 * @return int Post ID
	 */
	public function nominate_it() {

		$post = array();
		// $post_ID = $post['ID'] = (int) $_POST['post_id'];
		if ( ! current_user_can( get_option( 'pf_menu_nominate_this_access', pressforward( 'controller.users' )->pf_get_defining_capability_by_role( 'contributor' ) ) ) ) {
			wp_die( __( 'You do not have access to the Nominate This bookmarklet.' ) ); }

		$post['post_category'] = isset( $_POST['post_category'] ) ? $_POST['post_category'] : '';
		$post['tax_input']     = isset( $_POST['tax_input'] ) ? $_POST['tax_input'] : '';
		$post['post_title']    = isset( $_POST['title'] ) ? $_POST['title'] : '';
		if ( empty( $post['post_title'] ) ) {
			$post['post_title'] = isset( $_POST['post_title'] ) ? $_POST['post_title'] : '';
		}
		$content = isset( $_POST['content'] ) ? $_POST['content'] : '';

		// var_dump('<pre>'); var_dump($_POST);
		// set the post_content and status
		$post['post_content'] = $content;
		if ( isset( $_POST['publish'] ) && current_user_can( 'publish_posts' ) ) {
			$post['post_status'] = 'publish'; } elseif ( isset( $_POST['review'] ) ) {
			$post['post_status'] = 'pending';
			} else {
				$post['post_status'] = get_option( PF_SLUG . '_draft_post_status', 'draft' ); }

			$nom_check    = false;
			$feed_nom     = array(
				'error'  => false,
				'simple' => '',
			);
			$post['guid'] = $_POST['item_link'];
			// var_dump('<pre>'); var_dump($_POST['pf-feed-subscribe']); die();
		if ( ! empty( $_POST['pf-feed-subscribe'] ) && ( 'subscribe' == $_POST['pf-feed-subscribe'] ) ) {
			$url_array      = parse_url( esc_url( $_POST['item_link'] ) );
			$sourceLink     = 'http://' . $url_array['host'];
			$create_started = 'Attempting to nominate a feed with the result of: <br />';
			if ( current_user_can( 'edit_posts' ) ) {
				$create = pressforward( 'schema.feeds' )->create( $sourceLink, array( 'post_status' => 'under_review' ) );
				if ( is_numeric( $create ) ) {
					$feed_nom['id']     = $create;
					$create             = 'Feed created with ID of ' . $create;
					$feed_nom['simple'] = 'The feed has been nominated successfully.';
					$error_check        = pressforward( 'controller.metas' )->get_post_pf_meta( $feed_nom['id'], 'ab_alert_msg', true );
					if ( ! empty( $error_check ) ) {
						$create            .= ' But the following error occured: ' . $error_check;
						$feed_nom['simple'] = 'There is a problem with the feed associated with this post. The feed could not be verified.';
					}
					$feed_nom['error'] = $error_check;
				} else {
					$feed_nom['id']     = 0;
					$feed_nom['simple'] = "PressForward was unable to identify a feed associated with this site. Please contact the site administrator or add the feed manually in the 'Add Feeds' panel.";
					$message_one        = pf_message( 'An error occured when adding the feed: ' );
					if ( is_wp_error( $create ) ) {
						$create_wp_error   = $create->get_error_message();
						$message_two       = pf_message( $create_wp_error );
						$feed_nom['error'] = $message_two;
					} else {
						$message_two = pf_message( $create );
					}
					$create = $message_one . $message_two;
				}
			} else {
				$create             = 'User doesn\'t have permission to create feeds.';
				$feed_nom['id']     = 0;
				$feed_nom['error']  = $create;
				$feed_nom['simple'] = $create;
			}
			$feed_nom['msg'] = $create_started . $create;

			update_option( 'pf_last_nominated_feed', $feed_nom );

		} else {
			$feed_nom = array(
				'id'     => 0,
				'msg'    => 'No feed was nominated.',
				'simple' => 'User hasn\'t nominated a feed.',
			);
			update_option( 'pf_last_nominated_feed', $feed_nom );
		}

			// Why does this hinge on $upload?
			// Post formats
		if ( 0 != $feed_nom['id'] ) {
			$post['post_parent'] = $feed_nom['id'];
		}
			$post['post_author'] = get_current_user_id();
			$post['post_type']   = 'nomination';
			pf_log( $post );
			$_POST = array_merge( $_POST, $post );
			// var_dump($post); die();
		if ( isset( $_POST['publish'] ) && ( $_POST['publish'] == 'Send to ' . ucwords( get_option( PF_SLUG . '_draft_post_status', 'draft' ) ) ) ) {

			$post_ID = pressforward( 'utility.forward_tools' )->bookmarklet_to_last_step( false, $post );

		} else {
			$post_ID = pressforward( 'utility.forward_tools' )->bookmarklet_to_nomination( false, $post );
		}

		if ( ! empty( $_POST['item_feat_img'] ) && ( $_POST['item_feat_img'] != '' ) ) {
			pressforward( 'schema.feed_item' )->set_ext_as_featured( $post_ID, $_POST['item_feat_img'] );
		}
			$upload = false;
		if ( ! empty( $_POST['photo_src'] ) && current_user_can( 'upload_files' ) ) {
			foreach ( (array) $_POST['photo_src'] as $key => $image ) {
				// see if files exist in content - we don't want to upload non-used selected files.
				if ( strpos( $_POST['content'], htmlspecialchars( $image ) ) !== false ) {
					$desc   = isset( $_POST['photo_description'][ $key ] ) ? $_POST['photo_description'][ $key ] : '';
					$upload = media_sideload_image( $image, $post_ID, $desc );

					// Replace the POSTED content <img> with correct uploaded ones. Regex contains fix for Magic Quotes
					if ( ! is_wp_error( $upload ) ) {
						$content = preg_replace( '/<img ([^>]*)src=\\\?(\"|\')' . preg_quote( htmlspecialchars( $image ), '/' ) . '\\\?(\2)([^>\/]*)\/*>/is', $upload, $content ); }
				}
			}
		}
			// error handling for media_sideload
		if ( is_wp_error( $upload ) ) {
			wp_delete_post( $post_ID );
			wp_die( $upload );
			// Why is this here?
			// Oh, because it is trying to upload the images in the item into our
			// system. But if that doesn't work, something has gone pretty wrong.
			// $nom_check = true;
		}
			// var_dump($post); die();
			return $post_ID;
	}


}
