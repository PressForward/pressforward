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
class NominateThisCore implements HasActions, HasFilters {
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
	 * Sets up filter hooks for registering meta boxes for the Nominate This interface.
	 *
	 * @return array
	 */
	public function filter_hooks() {
		return [
			[
				'hook'   => 'ajax_query_attachments_args',
				'method' => 'filter_media_library_query',
			],
		];
	}

	/**
	 * Registers meta boxes for the Nominate This interface.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'pf-nomthis-submit',
			__( 'Nominate This', 'pressforward' ),
			array( $this, 'submit_meta_box' ),
			'nomthis',
			'side'
		);

		add_meta_box(
			'pf-categorydiv',
			__( 'Categories', 'pressforward' ),
			'post_categories_meta_box',
			'nomthis',
			'side'
		);

		add_meta_box(
			'pf-tagsdiv',
			__( 'Tags', 'pressforward' ),
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

			$pf_draft_post_type_value = pressforward_draft_post_type();

			if ( 'draft' === $publish_type ) {
				$cap = 'edit_posts';
			} else {
				$cap = 'publish_posts';
			}

			$create_post_cap_test = current_user_can( get_post_type_object( $pf_draft_post_type_value )->cap->$cap );

			if ( $create_nom_post_cap_test ) {
				submit_button( __( 'Nominate', 'pressforward' ), 'button', 'draft', false, array( 'id' => 'save' ) );
			} else {
				esc_html_e( 'You do not have the ability to create nominations.', 'pressforward' );
			}

			if ( $create_post_cap_test ) {
				// @todo Fix i18n for this button text.
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				submit_button( __( 'Send to ' . ucwords( $publish_type ), 'pressforward' ), 'primary', 'publish', false );
			} else {
				echo '<!-- User cannot ' . esc_html( $publish_type ) . ' posts -->';
			}

			?>
			<span class="spinner" style="display: none;"></span>

		</p>

		<p>
			<label for="item_author"><input type="text" id="item_author" name="item_author" value="" /><br />&nbsp;<?php echo esc_html( apply_filters( 'pf_author_nominate_this_prompt', __( 'Enter Authors', 'pressforward' ) ) ); ?></label>
		</p>

		<p>
			<label for="pf-feed-subscribe"><input type="checkbox" id="pf-feed-subscribe" name="pf-feed-subscribe" value="subscribe" />&nbsp;&nbsp;<?php esc_html_e( 'Nominate feed associated with item.', 'pressforward' ); ?></label>
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
				<label for="post_tags"><input type="text" id="post_tags" name="post_tags" value="" /><br />&nbsp;<?php echo esc_attr( apply_filters( 'pf_tags_prompt', __( 'Enter Tags', 'pressforward' ) ) ); ?></label>
			</p>
		</div>
		<?php
	}

	/**
	 * Ensures that Media Library tab on Nominate This includes only those images the current user should see.
	 *
	 * Authors and lower should not see media uploaded by others.
	 *
	 * @since 5.5.0
	 *
	 * @param array $query WP_Query arguments.
	 * @return array
	 */
	public function filter_media_library_query( $query ) {
		// Only modify requests that originate in Nominate This.
		$referer = wp_get_referer();
		if ( ! $referer || false === strpos( $referer, 'pf-nominate-this' ) ) {
			return $query;
		}

		// Editors and up can see everything.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return $query;
		}

		$query['author__in'] = [ get_current_user_id() ];

		return $query;
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
				wp_die( esc_html_e( 'You do not have access to the Nominate This bookmarklet.', 'pressforward' ) );
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
			$create_started = __( 'Attempting to nominate a feed with the result of:', 'pressforward' ) . '<br />';
			if ( current_user_can( 'edit_posts' ) ) {
				$create = pressforward( 'schema.feeds' )->create( $source_link, array( 'post_status' => 'under_review' ) );
				if ( is_numeric( $create ) ) {
					$feed_nom['id'] = $create;

					// translators: feed ID.
					$create             = sprintf( __( 'Feed created with ID: %s', 'pressforward' ), $create );
					$feed_nom['simple'] = __( 'The feed has been nominated successfully.', 'pressforward' );
					$error_check        = pressforward( 'controller.metas' )->get_post_pf_meta( $feed_nom['id'], 'ab_alert_msg', true );
					if ( ! empty( $error_check ) ) {
						// translators: Error text.
						$create            .= ' ' . sprintf( __( 'But the following error occured: %s', 'pressforward' ), $error_check );
						$feed_nom['simple'] = __( 'There is a problem with the feed associated with this post. The feed could not be verified.', 'pressforward' );
					}
					$feed_nom['error'] = $error_check;
				} else {
					$feed_nom['id']     = 0;
					$feed_nom['simple'] = __( "PressForward was unable to identify a feed associated with this site. Please contact the site administrator or add the feed manually in the 'Add Feeds' panel.", 'pressforward' );
					$message_one        = pf_message( __( 'An error occured when adding the feed: ', 'pressforward' ) );
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
				$create             = __( 'User doesn\'t have permission to create feeds.', 'pressforward' );
				$feed_nom['id']     = 0;
				$feed_nom['error']  = $create;
				$feed_nom['simple'] = $create;
			}
			$feed_nom['msg'] = $create_started . $create;

			update_option( 'pf_last_nominated_feed', $feed_nom );

		} else {
			$feed_nom = array(
				'id'     => 0,
				'msg'    => __( 'No feed was nominated.', 'pressforward' ),
				'simple' => __( 'User hasn\'t nominated a feed.', 'pressforward' ),
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
		if ( isset( $_POST['publish'] ) && ( ( sanitize_text_field( wp_unslash( $_POST['publish'] ) ) === __( 'Last Step', 'pressforward' ) ) || ( sanitize_text_field( wp_unslash( $_POST['publish'] ) ) === 'Send to ' . ucwords( get_option( PF_SLUG . '_draft_post_status', 'draft' ) ) ) ) ) {
			$post['post_type'] = 'nomination';
			$post_ID           = pressforward( 'utility.forward_tools' )->bookmarklet_to_last_step( '', $post );

		} else {
			$post_ID = pressforward( 'utility.forward_tools' )->bookmarklet_to_nomination( '', $post );
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

		if ( $post_ID ) {
			$this->maybe_send_success_notification( $post_ID );
		}

		return $post_ID;
	}

	/**
	 * Sends "success" notification to nomination author after successful nomination.
	 *
	 * @param int $wp_post_id WP post ID of the newly created item.
	 */
	protected function maybe_send_success_notification( $wp_post_id ) {
		$wp_post = get_post( $wp_post_id );
		if ( ! $wp_post ) {
			return;
		}

		$post_author = $wp_post->post_author;
		if ( ! pressforward()->fetch( 'controller.users' )->get_user_setting( $post_author, 'nomination-success-email-toggle' ) ) {
			return;
		}

		$user = get_userdata( $post_author );
		if ( ! $user ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );

		$subject = sprintf(
			// translators: Name of the site.
			__( '[%s] You have successfully nominated an item', 'pressforward' ),
			$site_name
		);

		$message = sprintf(
			// translators: 1. Name of the site; 2. URL of the site.
			__( 'You have successfully nominated an item on %1$s (%2$s).', 'pressforward' ),
			$site_name,
			get_bloginfo( 'url' )
		);

		$message .= "\n\n";

		$message .= sprintf(
			// translators: 1. Title of the source item. 2. URL of the nomination source item.
			__( 'Source item: %1$s (%2$s)', 'pressforward' ),
			get_the_title( $wp_post ),
			pressforward( 'controller.metas' )->get_post_pf_meta( $wp_post_id, 'item_link' )
		);

		$nominations_cap = get_option( 'pf_menu_under_review_access', pressforward( 'controller.users' )->pf_get_defining_capability_by_role( 'contributor' ) );

		if ( user_can( $post_author, $nominations_cap ) ) {
			$message .= "\n\n";

			$message .= sprintf(
				// translators: URL of the Nominations panel.
				__( 'Manage nominations: %s', 'pressforward' ),
				admin_url( 'admin.php?page=pf-review' )
			);
		}

		$message .= "\n\n" . pressforward( 'controller.users' )->get_email_notification_footer();

		/**
		 * Filters the subject line of the "nomination success" notification email.
		 *
		 * @since 5.4.0
		 *
		 * @param string $subject     Subject line.
		 * @param int    $wp_post_id  ID of the nominated item.
		 * @param int    $post_author ID of the user receiving the email.
		 */
		$subject = apply_filters( 'pf_nomination_success_email_subject', $subject, $wp_post_id, (int) $post_author );

		/**
		 * Filters the content of the "nomination success" notification email.
		 *
		 * @since 5.4.0
		 *
		 * @param string $message     Message content.
		 * @param int    $wp_post_id  ID of the nominated item.
		 * @param int    $post_author ID of the user receiving the email.
		 */
		$message = apply_filters( 'pf_nomination_success_email_content', $message, $wp_post_id, (int) $post_author );

		wp_mail( $user->user_email, $subject, $message );
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
		$response = pressforward( 'controller.http_tools' )->get_url_content( $url );

		if ( 200 !== $response['response_code'] ) {
			wp_send_json_error();
		}

		$providers = pressforward( 'schema.feed_item' )->oembed_capables();

		$retval = [
			'body'   => $response['body'],
			'embeds' => $this->detect_embeds( $response['body'] ),
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
