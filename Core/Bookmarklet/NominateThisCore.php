<?php
/**
 * Bookmarklet utilities.
 *
 * @package PressForward
 */

namespace PressForward\Core\Bookmarklet;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

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
			array(
				'hook'   => 'rest_after_insert_nomination',
				'method' => 'maybe_set_featured_image',
			),
			array(
				'hook'     => 'rest_after_insert_nomination',
				'method'   => 'post_nomination_actions',
				'priority' => 50,
			),
			array(
				'hook'   => 'admin_menu',
				'method' => 'register_nomination_success_panel',
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

		$post_author = (int) $wp_post->post_author;
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
	 * Sets the featured image for a nomination, if one is not already set.
	 *
	 * This is hooked to the REST API-specific rest_after_insert_nomination_hook,
	 * as it's meant to work specifically with the block editor nomination flow. See
	 * `nominate_it()` for the corresponding logic for the Classic nomination flow.
	 *
	 * @param \WP_Post $post The newly created nomination.
	 * @return void
	 */
	public function maybe_set_featured_image( $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		// If the post already has a thumbnail, don't do anything.
		if ( has_post_thumbnail( $post->ID ) ) {
			return;
		}

		$item_feat_img = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'item_feat_img', true );
		if ( ! $item_feat_img ) {
			return;
		}

		pressforward( 'schema.feed_item' )->set_ext_as_featured( $post->ID, sanitize_text_field( $item_feat_img ) );
	}

	/**
	 * Performs actions after a nomination is created.
	 *
	 * This includes promotion to Draft, if 'Send to Draft' is checked.
	 *
	 * @since 5.6.0
	 *
	 * @param \WP_Post $post The newly created nomination.
	 * @return void
	 */
	public function post_nomination_actions( $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		$item_link = get_post_meta( $post->ID, 'item_link', true );
		$item_id   = pressforward_create_feed_item_id( $item_link, $post->post_title );

		$nomination_post_id = $post->ID;

		$subscribe_to_feed = get_post_meta( $nomination_post_id, 'subscribe_to_feed', true );

		if ( $subscribe_to_feed ) {
			$url_array      = wp_parse_url( esc_url( $item_link ) );
			$source_link    = $url_array['scheme'] . '://' . $url_array['host'];
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

		// Detect if there's an existing nomination for this item.
		// If so, we must remove the one just created and increment the existing one.
		$existing_nomination_post_id = pressforward( 'utility.forward_tools' )->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );

		if ( ! $existing_nomination_post_id ) {
			$canonical_nomination_post_id = $nomination_post_id;

			// Avoid duplicating a feed item.
			$item_check = pressforward( 'utility.forward_tools' )->is_a_pf_type( $item_id, pressforward( 'schema.feed_item' )->post_type );
			if ( $item_check ) {
				$nomination_id = pressforward( 'utility.forward_tools' )->item_to_nomination( $item_id, $item_check );
				pressforward( 'utility.relate' )->basic_relate( 'nominate', $item_check, 'on' );
			}

			// submitted_by.
			$user_data   = pressforward( 'utility.forward_tools' )->find_nominating_user( $nomination_post_id );
			$user_id     = $user_data['user_id'];
			$user_string = $user_data['user_string'];
			pressforward( 'controller.metas' )->update_pf_meta( $nomination_post_id, 'submitted_by', $user_string );

			// item_id.
			pressforward( 'controller.metas' )->update_pf_meta( $nomination_post_id, 'item_id', $item_id );

			// source_title.
			pressforward( 'controller.metas' )->update_pf_meta( $nomination_post_id, 'source_title', 'Bookmarklet' );

			$send_to_draft = get_post_meta( $nomination_post_id, 'send_to_draft', true );

			if ( $send_to_draft ) {
				$user_data = pressforward( 'utility.forward_tools' )->nomination_to_last_step( $item_id, $nomination_post_id );
			}

			// Add the new nomination event to the existing nomination item.
			pressforward( 'utility.forward_tools' )->add_user_to_nominator_array( $nomination_post_id, $post->post_author );
		} else {
			$canonical_nomination_post_id = $existing_nomination_post_id;

			// Add the new nomination event to the existing nomination item.
			pressforward( 'utility.forward_tools' )->add_user_to_nominator_array( $existing_nomination_post_id, $post->post_author );

			// Remove the newly created nomination.
			wp_delete_post( $nomination_post_id, true );
		}

		// If comment text was submitted, create a post comment.
		$nomthis_comment = get_post_meta( $nomination_post_id, 'pf_nomthis_comment', true );
		if ( $nomthis_comment ) {
			$commentdata = array(
				'comment_post_ID'      => $canonical_nomination_post_id,
				'comment_author'       => get_the_author_meta( 'display_name', (int) $post->post_author ),
				'comment_author_email' => get_the_author_meta( 'user_email', (int) $post->post_author ),
				'comment_author_url'   => get_the_author_meta( 'user_url', (int) $post->post_author ),
				'comment_content'      => $nomthis_comment,
				'comment_type'         => 'pressforward-comment',
				'comment_approved'     => 'pressforward-comment',
				'user_id'              => (int) $post->post_author,
			);

			$comment_id = wp_insert_comment( $commentdata );
		}
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
			'#https?://(www.)?youtube\.com/(?:v|embed)/([^/\?]+)(.*)#i' => function ( $matches, $url ) {
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

	/**
	 * Registers the Nomination Success panel.
	 *
	 * @since 5.6.0
	 *
	 * @return void
	 */
	public function register_nomination_success_panel() {
		add_submenu_page(
			'',
			__( 'Nomination Success', 'pressforward' ),
			__( 'Nomination Success', 'pressforward' ),
			'edit_posts',
			'pf-nomination-success',
			[ $this, 'nomination_success_panel' ]
		);
	}

	/**
	 * Renders the Nomination Success panel.
	 *
	 * @since 5.6.0
	 *
	 * @return void
	 */
	public function nomination_success_panel() {
		wp_enqueue_style( 'pf-nomination-success' );
		?>

		<div id="message" class="updated">
			<p><strong><?php esc_html_e( 'Your nomination has been saved.', 'pressforward' ); ?></strong>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pf-review' ) ); ?>"><?php esc_html_e( 'See all nominations', 'pressforward' ); ?></a>
			| <a href="#" onclick="window.close();"><?php esc_html_e( 'Close Window', 'pressforward' ); ?></a>
			</p>
		</div>

		<?php
	}
}
