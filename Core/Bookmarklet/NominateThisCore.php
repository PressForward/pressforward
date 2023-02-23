<?php
/**
 * Bookmarklet utilities.
 *
 * @package PressForward
 */

namespace PressForward\Core\Bookmarklet;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use WP_Ajax_Response;

/**
 * NominateThisCore class.
 */
class NominateThisCore implements HasActions {
	/**
	 * Basename.
	 *
	 * @access protected
	 * @var string
	 */
	protected $basename;

	/**
	 * Sets up action hooks for registering meta boxes for the Nominate This interface.
	 *
	 * @return array
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'add_meta_boxes_nomthis',
				'method' => 'add_meta_boxes',
			),
			array(
				'hook'   => 'wp_ajax_pf_fetch_url_content',
				'method' => 'fetch_url_content',
			),
		);
	}
	/**
	 * Registers meta boxes for the Nominate This interface.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'pf-nomthis-submit',
			__( 'Nominate This', 'pf' ),
			array( $this, 'submit_meta_box' ),
			'nomthis',
			'side'
		);

		add_meta_box(
			'pf-categorydiv',
			__( 'Categories', 'pf' ),
			'post_categories_meta_box',
			'nomthis',
			'side'
		);

		add_meta_box(
			'pf-tagsdiv',
			__( 'Tags', 'pf' ),
			array( $this, 'tags_meta_box' ),
			'nomthis',
			'side'
		);
	}

	/**
	 * Generates markup for the Submit meta box on the Nominate This interface.
	 */
	public function submit_meta_box() {

		?>

		<p id="publishing-actions">
			<?php

			$publish_type = get_option( PF_SLUG . '_draft_post_status', 'draft' );

			$create_nom_post_cap_test = current_user_can( get_post_type_object( pressforward( 'schema.nominations' )->post_type )->cap->create_posts );

			$pf_draft_post_type_value = get_option( PF_SLUG . '_draft_post_type', 'post' );

			if ( 'draft' === $publish_type ) {
				$cap = 'edit_posts';
			} else {
				$cap = 'publish_posts';
			}

			$create_post_cap_test = current_user_can( get_post_type_object( $pf_draft_post_type_value )->cap->$cap );

			if ( $create_nom_post_cap_test ) {
				submit_button( __( 'Nominate', 'pf' ), 'button', 'draft', false, array( 'id' => 'save' ) );
			} else {
				esc_html_e( 'You do not have the ability to create nominations.', 'pf' );
			}

			if ( $create_post_cap_test ) {
				// @todo Fix i18n for this button text.
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				submit_button( __( 'Send to ' . ucwords( $publish_type ) ), 'primary', 'publish', false );
			} else {
				echo '<!-- User cannot ' . esc_html( $publish_type ) . ' posts -->';
			}

			?>
			<span class="spinner" style="display: none;"></span>

		</p>

		<p>
			<label for="item_author"><input type="text" id="item_author" name="item_author" value="" /><br />&nbsp;<?php echo esc_html( apply_filters( 'pf_author_nominate_this_prompt', __( 'Enter Authors', 'pf' ) ) ); ?></label>
		</p>

		<p>
			<label for="pf-feed-subscribe"><input type="checkbox" id="pf-feed-subscribe" name="pf-feed-subscribe" value="subscribe" />&nbsp;&nbsp;<?php esc_html_e( 'Nominate feed associated with item.', 'pf' ); ?></label>
		</p>

		<?php

		// @deprecated 5.2.2 - Do not use this hook, use the following hook. This hook added to support existing plugins that use it.
		do_action( 'nominate_this_sidebar_top' );
		do_action( 'nominate_this_sidebar_head' );
		?>

		<!-- Addressing things that come in under old action -->
		<script>jQuery('.postbox .postbox').insertAfter(jQuery('.postbox').first());</script>
		<?php
	}

	/**
	 * Generates markup for the Tags meta box on the Nominate This interface.
	 */
	public function tags_meta_box() {
		?>
		<div id="taxonomy-tags" class="tagdiv">
			<p>
				<label for="post_tags"><input type="text" id="post_tags" name="post_tags" value="" /><br />&nbsp;<?php echo esc_attr( apply_filters( 'pf_tags_prompt', __( 'Enter Tags', 'pf' ) ) ); ?></label>
			</p>
		</div>
		<?php
	}

	/**
	 * Press It form handler.
	 *
	 * @package WordPress
	 * @subpackage Press_This
	 * @since 2.6.0
	 *
	 *  $_POST should contain:
	 *      'post_category'
	 *      'tax_input'
	 *      'title'
	 *      'post_title'
	 *      'content' / 'post_content'
	 *      'item_link'
	 *      ?'publish'
	 *
	 * @param bool $internal Whether this is an internal nomination.
	 * @return int Post ID
	 */
	public function nominate_it( $internal = true ) {
		$post = array();
		if ( $internal ) {
			if ( ! current_user_can( get_option( 'pf_menu_nominate_this_access', pressforward( 'controller.users' )->pf_get_defining_capability_by_role( 'contributor' ) ) ) ) {
				wp_die( esc_html_e( 'You do not have access to the Nominate This bookmarklet.', 'pf' ) );
			}
		}

		pf_log( 'POST Input' );
		pf_log( $_POST );
		pf_log( 'Continue with data' );

		$post['post_category'] = isset( $_POST['post_category'] ) ? array_map( 'intval', $_POST['post_category'] ) : [];
		$post['tax_input']     = isset( $_POST['tax_input'] ) ? sanitize_text_field( wp_unslash( $_POST['tax_input'] ) ) : '';
		$post['post_title']    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( empty( $post['post_title'] ) ) {
			$post['post_title'] = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '';
		}
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		// Set the post_content and status.
		$post['post_content'] = $content;

		$item_link    = isset( $_POST['item_link'] ) ? sanitize_text_field( wp_unslash( $_POST['item_link'] ) ) : '';
		$post['guid'] = $item_link;

		if ( isset( $_POST['publish'] ) && current_user_can( 'publish_posts' ) ) {
			$post['post_status'] = 'publish';
		} elseif ( isset( $_POST['review'] ) ) {
			$post['post_status'] = 'pending';
		} else {
			$post['post_status'] = get_option( PF_SLUG . '_draft_post_status', 'draft' );
		}

		$nom_check = false;
		$feed_nom  = array(
			'error'  => false,
			'simple' => '',
		);

		if ( ! empty( $_POST['pf-feed-subscribe'] ) && ( 'subscribe' === sanitize_text_field( wp_unslash( $_POST['pf-feed-subscribe'] ) ) ) ) {
			$url_array      = wp_parse_url( esc_url( $item_link ) );
			$source_link    = 'http://' . $url_array['host'];
			$create_started = __( 'Attempting to nominate a feed with the result of:', 'pf' ) . '<br />';
			if ( current_user_can( 'edit_posts' ) ) {
				$create = pressforward( 'schema.feeds' )->create( $source_link, array( 'post_status' => 'under_review' ) );
				if ( is_numeric( $create ) ) {
					$feed_nom['id'] = $create;

					// translators: feed ID.
					$create             = sprintf( __( 'Feed created with ID: %s', 'pf' ), $create );
					$feed_nom['simple'] = __( 'The feed has been nominated successfully.', 'pf' );
					$error_check        = pressforward( 'controller.metas' )->get_post_pf_meta( $feed_nom['id'], 'ab_alert_msg', true );
					if ( ! empty( $error_check ) ) {
						// translators: Error text.
						$create            .= ' ' . sprintf( __( 'But the following error occured: %s', 'pf' ), $error_check );
						$feed_nom['simple'] = __( 'There is a problem with the feed associated with this post. The feed could not be verified.', 'pf' );
					}
					$feed_nom['error'] = $error_check;
				} else {
					$feed_nom['id']     = 0;
					$feed_nom['simple'] = __( "PressForward was unable to identify a feed associated with this site. Please contact the site administrator or add the feed manually in the 'Add Feeds' panel.", 'pf' );
					$message_one        = pf_message( __( 'An error occured when adding the feed: ', 'pf' ) );
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
				$create             = __( 'User doesn\'t have permission to create feeds.', 'pf' );
				$feed_nom['id']     = 0;
				$feed_nom['error']  = $create;
				$feed_nom['simple'] = $create;
			}
			$feed_nom['msg'] = $create_started . $create;

			update_option( 'pf_last_nominated_feed', $feed_nom );

		} else {
			$feed_nom = array(
				'id'     => 0,
				'msg'    => __( 'No feed was nominated.', 'pf' ),
				'simple' => __( 'User hasn\'t nominated a feed.', 'pf' ),
			);
			update_option( 'pf_last_nominated_feed', $feed_nom );
		}

		// Why does this hinge on $upload?
		// Post formats.

		if ( 0 !== (int) $feed_nom['id'] ) {
			$post['post_parent'] = $feed_nom['id'];
		}
		$post['post_author'] = get_current_user_id();

		pf_log( $post );

		$_POST = array_merge( $_POST, $post );
		// @todo Fix button text with i18n issue mentioned earlier in this file.
		if ( isset( $_POST['publish'] ) && ( ( sanitize_text_field( wp_unslash( $_POST['publish'] ) ) === __( 'Last Step', 'pf' ) ) || ( sanitize_text_field( wp_unslash( $_POST['publish'] ) ) === 'Send to ' . ucwords( get_option( PF_SLUG . '_draft_post_status', 'draft' ) ) ) ) ) {
			$post['post_type'] = 'nomination';
			$post_ID           = pressforward( 'utility.forward_tools' )->bookmarklet_to_last_step( false, $post );

		} else {
			$post_ID = pressforward( 'utility.forward_tools' )->bookmarklet_to_nomination( false, $post );
		}

		if ( ! empty( $_POST['item_feat_img'] ) ) {
			pressforward( 'schema.feed_item' )->set_ext_as_featured( $post_ID, sanitize_text_field( wp_unslash( $_POST['item_feat_img'] ) ) );
		}

		$upload = false;

		if ( ! empty( $_POST['photo_src'] ) && current_user_can( 'upload_files' ) ) {
			$photo_src = map_deep( wp_unslash( $_POST['photo_src'] ), 'sanitize_text_field' );
			foreach ( (array) $photo_src as $key => $image ) {
				// see if files exist in content - we don't want to upload non-used selected files.
				if ( strpos( $content, htmlspecialchars( $image ) ) !== false ) {
					$desc   = isset( $_POST['photo_description'][ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST['photo_description'][ $key ] ) ) : '';
					$upload = media_sideload_image( $image, $post_ID, $desc );

					// Replace the POSTED content <img> with correct uploaded ones. Regex contains fix for Magic Quotes.
					if ( ! is_wp_error( $upload ) ) {
						$content = preg_replace( '/<img ([^>]*)src=\\\?(\"|\')' . preg_quote( htmlspecialchars( $image ), '/' ) . '\\\?(\2)([^>\/]*)\/*>/is', $upload, $content );
					}
				}
			}
		}

		// error handling for media_sideload.
		if ( is_wp_error( $upload ) ) {
			wp_delete_post( $post_ID );
		}

		return $post_ID;
	}

	/**
	 * Callback for 'wp_ajax_pf_fetch_url_content' AJAX request.
	 *
	 * @return void
	 */
	public function fetch_url_content() {
		$url = isset( $_GET['url'] ) ? sanitize_text_field( wp_unslash( $_GET['url'] ) ) : '';

		if ( ! $url ) {
			wp_send_json_error();
		}

		// @todo Consolidate with cached requests. See Controllers\HTTPTools\get_url_content().
		$request = wp_remote_get(
			$url,
			[
				'timeout' => 20,
			]
		);

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			wp_send_json_error();
		}

		$request_body = wp_remote_retrieve_body( $request );

		$providers = pressforward( 'schema.feed_item' )->oembed_capables();

		$retval = [
			'body'   => $request_body,
			'embeds' => $this->detect_embeds( $request_body ),
		];

		wp_send_json_success( $retval );
	}

	/**
	 * Detects embeds in an XML string and prepares a list of replacement URLs.
	 *
	 * @param string $body XML string fetched from remote URL.
	 * @return array
	 */
	protected function detect_embeds( $body ) {
		global $wp_embed;

		libxml_use_internal_errors( true );

		$doc = new \DOMDocument();
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $body );

		$embed_providers = [
			'#https?://(www.)?youtube\.com/(?:v|embed)/([^/\?]+)(.*)#i' => function( $matches, $url ) {
				$retval = sprintf( 'https://youtube.com/watch?v=%s', rawurlencode( $matches[2] ) );

				// If any query parameters were present, we re-add them.
				if ( ! empty( $matches[3] ) && '?' === substr( $matches[3], 0, 1 ) ) {
					parse_str( substr( $matches[3], 1 ), $query_vars );
					foreach ( $query_vars as $key => $value ) {
						$retval = add_query_arg( $key, $value, $retval );
					}
				}

				return $retval;
			},
		];

		$embeds = [];

		$iframes = $doc->getElementsByTagName( 'iframe' );
		foreach ( $iframes as $iframe_node ) {
			foreach ( $embed_providers as $regex => $callback ) {
				$iframe_src = $iframe_node->getAttribute( 'src' );

				if ( preg_match( $regex, $iframe_src, $matches ) ) {
					$embeds[] = [
						'embedSrc' => $iframe_src,
						'embedUrl' => $callback( $matches, $iframe_src ),
					];
				}
			}
		}

		return $embeds;
	}
}
