<?php
/**
 * Internal commenting module.
 *
 * Note: This borrows pretty strongly from the great work in the Edit Flow Editorial Comments module by batmoo. And by strongly, I mean almost entirely.
 * See http://editflow.org/features/editorial-comments/ for more info about Edit Flow Comments
 *
 * @package PressForward
 */

/**
 * PF_Comments class.
 */
class PF_Comments extends PF_Module {

	// This is comment type used to differentiate editorial comments.
	const COMMENT_TYPE = 'pressforward-comment';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::start();

		add_action( 'pf_modal_comments', array( $this, 'the_comment_box' ) );
		add_action( 'wp_ajax_editflow_ajax_insert_comment', array( $this, 'ajax_insert_comment' ) );
		add_action( 'pf_comment_action_button', array( $this, 'show_comment_count_button' ) );
		add_action( 'pf_comment_action_modal', array( $this, 'show_comment_modal' ) );
		add_filter( 'pf_setup_admin_rights', array( $this, 'control_menu_access' ) );

		add_action( 'ef_post_insert_editorial_comment', array( $this, 'maybe_send_comment_notifications' ) );
	}

	/**
	 * Default settings for the module.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return array(
			'name'        => __( 'Internal Commenting', 'pressforward' ),
			'slug'        => 'comments',
			'description' => __( 'This module provides a way for users to comment on posts throughout the editorial process. Internal commenting is only visible in the PressForward plugin and will not be publicly visible when published as a Post.', 'pressforward' ),
			'thumbnail'   => '',
			'options'     => '',
		);
	}

	/**
	 * Gets editorial comment count for an item.
	 *
	 * @param int $id ID of the item.
	 * @return int
	 */
	public function get_editorial_comment_count( $id ) {
		$query         = new \WP_Comment_Query();
		$comment_count = $query->query(
			array(
				'count'   => true,
				'post_id' => $id,
				'type'    => self::COMMENT_TYPE,
				'status'  => 'any',
			)
		);

		if ( ! $comment_count ) {
			$comment_count = 0;
		}

		return $comment_count;
	}

	/**
	 * Generates comment count button.
	 *
	 * @param array $comment_set Comment data.
	 */
	public function show_comment_count_button( $comment_set ) {
		$btnstate  = 'btn-small';
		$iconstate = 'icon-comment';

		$count = self::get_editorial_comment_count( $comment_set['id'] );
		if ( $count >= 1 ) {
			$btnstate  .= ' btn-info';
			$iconstate .= ' icon-white';
		}

		if ( ! $comment_set['modal_state'] ) {

			echo '<a role="button" class="btn ' . esc_attr( $btnstate ) . ' itemCommentModal comments-expander" title="' . esc_attr__( 'Comment', 'pressforward' ) . '" data-toggle="modal" href="#comment_modal_' . esc_attr( $comment_set['id'] ) . '" id="comments-expander-' . esc_attr( $comment_set['id'] ) . '" ><span class="comments-expander-count">' . esc_html( (string) $count ) . '</span><i class="' . esc_attr( $iconstate ) . '"></i></a>';

		} else {
			echo '<a role="button" class="btn ' . esc_attr( $btnstate ) . ' btn-small itemCommentModal comments-expander active" ><span class="comments-expander-count">' . esc_html( (string) $count ) . '</span><i class="' . esc_attr( $iconstate ) . '"></i></a>';
		}
	}

	/**
	 * Registers admin rights for comments module.
	 *
	 * @param array $admin_rights Admin rights.
	 * @return array
	 */
	public function control_menu_access( $admin_rights ) {
		$admin_rights['pf_feature_comments_access'] = array(
			'default' => 'contributor',
			'title'   => __( 'Internal Commenting Feature', 'pressforward' ),
			'details' => __( 'This module provides a text box for users to comment on posts throughout the editorial process. Comments are not publicly visible when the post is published.', 'pressforward' ),
		);

		return $admin_rights;
	}

	/**
	 * Builds comment modal.
	 *
	 * @param array $comment_set Comment info.
	 */
	public function show_comment_modal( $comment_set ) {
		if ( ! $comment_set['modal_state'] ) {

			?>
			<div id="comment_modal_<?php echo esc_html( $comment_set['id'] ); ?>" class="modal fade comment-modal pfmodal" tabindex="-1" role="dialog" aria-labelledby="comment_modal_<?php echo esc_attr( $comment_set['id'] ); ?>_label" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header">
							<h3 id="comment_modal_<?php echo esc_attr( $comment_set['id'] ); ?>_label"><?php esc_html_e( 'Comments', 'pressforward' ); ?></h3>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'pressforward' ); ?>"></button>
						</div>

						<div class="modal-body">
							<?php esc_html_e( 'Loading comments...', 'pressforward' ); ?>
						</div>

						<div class="modal-footer">
							<button class="btn" data-bs-dismiss="modal" aria-hidden="true"><?php esc_html_e( 'Close', 'pressforward' ); ?></button>
						</div>
					</div>
				</div>
			</div>
			<?php

		}
	}

	/**
	 * Builds comment box.
	 *
	 * @param int $id_for_comments Item ID.
	 */
	public function the_comment_box( $id_for_comments ) {
		echo '<script>
		PFEditorialCommentReply.init();
		</script>';

		$comments_allowed = get_option( 'pf_feature_comments_access', pf_get_defining_capability_by_role( 'contributor' ) );

		if ( ! current_user_can( $comments_allowed ) ) {
			esc_html_e( 'You do not have permission to access this area.', 'pressforward' );
			echo '<div class="clear"></div>';
			return;
		}

		?>
		<div id="ef-comments_wrapper">
			<a name="editorialcomments"></a>

			<?php
			// Show comments only if not a new post.
			// Unused since switched to wp_list_comments.
			$editorial_comments = $this->ef_get_comments_plus(
				array(
					'post_id'      => $id_for_comments,
					'comment_type' => self::COMMENT_TYPE,
					'orderby'      => 'comment_date',
					'order'        => 'ASC',
					'status'       => self::COMMENT_TYPE,
				)
			);
			?>

			<ul id="ef-comments">
				<?php
					// We use this so we can take advantage of threading and such.
					wp_list_comments(
						array(
							'type'         => self::COMMENT_TYPE,
							'callback'     => array( $this, 'the_comment' ),
							'end-callback' => '__return_false',
						),
						$editorial_comments
					);
				?>
			</ul>

			<?php $this->the_comment_form( $id_for_comments ); ?>

			<div class="clear"></div>
		</div>
		<div class="clear"></div>
		<?php
	}

	/**
	 * Displays the main commenting form.
	 *
	 * @param int $id_for_comments Item ID.
	 */
	public function the_comment_form( $id_for_comments ) {
		$comments_allowed = get_option( 'pf_feature_comments_access', pf_get_defining_capability_by_role( 'contributor' ) );

		if ( ( current_user_can( $comments_allowed ) ) ) {

			?>
			<a href="#" id="ef-comment_respond" onclick="PFEditorialCommentReply.open();return false;" class="button-primary alignright hide-if-no-js" title=" <?php esc_html_e( 'Respond to this post', 'pressforward' ); ?>"><span><?php esc_html_e( 'Add Comment', 'pressforward' ); ?></span></a>

			<!-- Reply form, hidden until reply clicked by user. -->
			<div id="ef-replyrow" style="display: none;">
				<div id="ef-replycontainer">
					<textarea id="ef-replycontent" name="replycontent" cols="40" rows="5"></textarea>
				</div>

				<p id="ef-replysubmit">
					<a class="ef-replysave button-primary alignright" href="#comments-form">
						<span id="ef-replybtn"><?php esc_html_e( 'Submit Comment', 'pressforward' ); ?></span>
					</a>
					<a class="ef-replycancel button-secondary alignright" href="#comments-form"><?php esc_html_e( 'Cancel', 'pressforward' ); ?></a>
					<img alt="<?php esc_attr_e( 'Sending comment...', 'pressforward' ); ?>" src="<?php echo esc_attr( admin_url( '/images/wpspin_light.gif' ) ); ?>" class="alignright" style="display: none;" id="ef-comment_loading" />
					<br class="clear" style="margin-bottom:35px;" />
					<span style="display: none;" class="error"></span>
				</p>

				<input type="hidden" value="" id="ef-comment_parent" name="ef-comment_parent" />
				<input type="hidden" name="ef-post_id" id="ef-post_id" value="<?php echo esc_attr( (string) $id_for_comments ); ?>" />

				<?php wp_nonce_field( 'comment', 'ef_comment_nonce', false ); ?>

				<br class="clear" />
			</div>

			<?php
		}
	}


	/**
	 * Displays a single comment.
	 *
	 * @param WP_Comment $comment Comment object.
	 * @param array      $args    Arguments.
	 * @param int        $depth   Current depth.
	 */
	public function the_comment( $comment, $args, $depth ) {
		global $userdata;

		// Get current user.
		$current_user = wp_get_current_user();

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['comment'] = $comment;

		// Deleting editorial comments is not enabled for now for the sake of transparency. However, we could consider
		// EF comment edits (with history, if possible). P2 already allows for edits without history, so even that might work.
		// Pivotal ticket: https://www.pivotaltracker.com/story/show/18483757.
		$actions = array();

		$actions_string   = '';
		$comments_allowed = get_option( 'pf_feature_comments_access', pf_get_defining_capability_by_role( 'contributor' ) );

		// Comments can only be added by users that can edit the post.
		if ( current_user_can( $comments_allowed, $comment->comment_post_ID ) ) {
			$actions['reply'] = '<a onclick="PFEditorialCommentReply.open(\'' . esc_attr( $comment->comment_ID ) . '\',\'' . esc_attr( $comment->comment_post_ID ) . '\');return false;" class="vim-r hide-if-no-js" title="' . esc_attr__( 'Reply to this comment', 'pressforward' ) . '" href="#">' . esc_html__( 'Reply', 'pressforward' ) . '</a>';

			$sep = ' ';
			$i   = 0;
			foreach ( $actions as $action => $link ) {
				++$i;

				$action .= ' hide-if-no-js';

				$actions_string .= "<span class='$action'>$sep$link</span>";
			}
		}

		?>

		<li id="comment-<?php echo esc_attr( $comment->comment_ID ); ?>" <?php comment_class( array( 'comment-item', wp_get_comment_status( (int) $comment->comment_ID ) ) ); ?>>

			<?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo get_avatar( $comment->comment_author_email, 50 ); ?>

			<div class="post-comment-wrap">
				<h5 class="comment-meta">
					<?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php
					printf(
						// translators: 1. Comment author name; 2. Comment date; 3. Comment time.
						wp_kses_post( __( '<span class="comment-author">%1$s</span><span class="meta"> said on %2$s at %3$s</span>', 'pressforward' ) ),
						esc_html( comment_author_email_link( $comment->comment_author ) ),
						esc_html( get_comment_date( get_option( 'date_format' ) ) ),
						esc_html( get_comment_time() )
					);
					?>
				</h5>

				<div class="comment-content"><?php comment_text(); ?></div>
				<?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p class="row-actions"><?php echo $actions_string; ?></p>

			</div>
		</li>
		<?php
	}

	/**
	 * Handles AJAX insert comment.
	 */
	public function ajax_insert_comment() {
		global $user_ID;

		// Verify nonce.
		if ( empty( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ), 'comment' ) ) {
			die( esc_html__( "Nonce check failed. Please ensure you're supposed to be adding editorial comments.", 'pressforward' ) );
		}

		// Get user info.
		$current_user     = wp_get_current_user();
		$comments_allowed = get_option( 'pf_feature_comments_access', pf_get_defining_capability_by_role( 'contributor' ) );

		// Set up comment data.
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$parent  = isset( $_POST['parent'] ) ? absint( $_POST['parent'] ) : 0;

		// Only allow the comment if user can edit post.
		// @TODO: allow contributers to add comments as well.
		if ( ! current_user_can( $comments_allowed, $post_id ) ) {
			die( esc_html__( 'Sorry, you don\'t have the privileges to add editorial comments. Please talk to your Administrator.', 'pressforward' ) );
		}

		// Verify that comment was actually entered.
		$comment_content = ! empty( $_POST['content'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) ) : '';
		if ( ! $comment_content ) {
			die( esc_html__( 'Please enter a comment.', 'pressforward' ) );
		}

		// Check that we have a post_id and user logged in.
		if ( $post_id && is_user_logged_in() ) {

			// Set current time.
			$time = current_time( 'mysql', $gmt = 0 );

			$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$user_agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

			// Set comment data.
			$data = array(
				'comment_post_ID'      => (int) $post_id,
				'comment_author'       => esc_sql( $current_user->display_name ),
				'comment_author_email' => esc_sql( $current_user->user_email ),
				'comment_author_url'   => esc_sql( $current_user->user_url ),
				'comment_content'      => wp_kses(
					$comment_content,
					array(
						'a'          => array(
							'href'  => array(),
							'title' => array(),
						),
						'b'          => array(),
						'i'          => array(),
						'strong'     => array(),
						'em'         => array(),
						'u'          => array(),
						'del'        => array(),
						'blockquote' => array(),
						'sub'        => array(),
						'sup'        => array(),
					)
				),
				'comment_type'         => self::COMMENT_TYPE,
				'comment_parent'       => (int) $parent,
				'user_id'              => (int) $user_ID,
				'comment_author_IP'    => $remote_addr,
				'comment_agent'        => $user_agent,
				'comment_date'         => $time,
				'comment_date_gmt'     => $time,
				// Set to -1?
				'comment_approved'     => self::COMMENT_TYPE,
			);

			apply_filters( 'ef_pre_insert_editorial_comment', $data );

			// Insert Comment.
			$comment_id = wp_insert_comment( $data );
			$comment    = get_comment( $comment_id );

			// Register actions -- will be used to set up notifications and other modules can hook into this.
			if ( $comment_id ) {
				do_action( 'ef_post_insert_editorial_comment', $comment );
			}

			// Prepare response.
			$response = new WP_Ajax_Response();

			ob_start();
			$this->the_comment( $comment, [], 0 );
			$comment_list_item = ob_get_contents();
			ob_end_clean();

			$comment_count = self::get_editorial_comment_count( $post_id );

			$response->add(
				array(
					'what'         => 'comment',
					'id'           => $comment_id,
					'data'         => $comment_list_item,
					'action'       => ( $parent ) ? 'reply' : 'new',
					'supplemental' => array(
						'post_comment_count' => $comment_count,
						'post_id'            => $post_id,
					),
				)
			);

			$response->send();

		} else {
			die( esc_html__( 'There was a problem of some sort. Try again or contact your administrator.', 'pressforward' ) );
		}
	}

	/**
	 * Admin menu callback.
	 */
	public function admin_menu_callback() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Internal Commenting Options', 'pressforward' ); ?></h2>
			<br /><br />
		</div>
		<?php
	}

	/**
	 * Enqueues scripts for the admin.
	 */
	public function admin_enqueue_scripts() {
		global $pagenow;

		$hook = 0 !== func_num_args() ? func_get_arg( 0 ) : '';

		if ( ! in_array( $pagenow, array( 'admin.php' ), true ) ) {
			return;
		}

		if ( ! in_array( $hook, array( 'pressforward_page_pf-review', 'toplevel_page_pf-menu', 'edit.php', 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_script( 'pressforward-internal-comments', PF_URL . 'modules/comments/assets/js/editorial-comments.js', array( 'jquery', 'post' ), PF_VERSION, true );

		// Enforce that it's numeric, but then convert back to string for echoing.
		$thread_comments = (string) intval( get_option( 'thread_comments' ) );

		?>
		<script type="text/javascript">
			var ef_thread_comments = <?php echo ( $thread_comments ) ? esc_js( $thread_comments ) : 0; ?>;
		</script>
		<?php
	}

	/**
	 * If this module has any styles to enqueue, do it in a method.
	 *
	 * If you have no styles, etc, just ignore this.
	 */
	public function admin_enqueue_styles() {
		wp_enqueue_style( PF_SLUG . '-internal-comments-css', PF_URL . 'modules/comments/assets/css/editorial-comments.css', [], PF_VERSION );
	}

	/**
	 * Retrieve a list of comments -- overloaded from get_comments and with mods by filosofo (SVN Ticket #10668)
	 *
	 * @param mixed $args Optional. Array or string of options to override defaults.
	 * @return array List of comments.
	 */
	public function ef_get_comments_plus( $args = '' ) {
		global $wpdb;

		$defaults = array(
			'author_email' => '',
			'ID'           => '',
			'karma'        => '',
			'number'       => '',
			'offset'       => '',
			'orderby'      => '',
			'order'        => 'DESC',
			'parent'       => '',
			'post_ID'      => '',
			'post_id'      => 0,
			'status'       => '',
			'type'         => '',
			'user_id'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$key          = md5( wp_json_encode( $args ) );
		$last_changed = wp_cache_get( 'last_changed', 'comment' );
		if ( ! $last_changed ) {
			$last_changed = time();
			wp_cache_set( 'last_changed', $last_changed, 'comment' );
		}
		$cache_key = "get_comments:$key:$last_changed";

		$cache = wp_cache_get( $cache_key, 'comment' );
		if ( false !== $cache ) {
			return $cache;
		}

		$post_id = absint( $args['post_id'] );

		if ( 'hold' === $args['status'] ) {
			$approved = "comment_approved = '0'";
		} elseif ( 'approve' === $args['status'] ) {
			$approved = "comment_approved = '1'";
		} elseif ( 'spam' === $args['status'] ) {
			$approved = "comment_approved = 'spam'";
		} elseif ( ! empty( $args['status'] ) ) {
			$approved = $wpdb->prepare( 'comment_approved = %s', $args['status'] );
		} else {
			$approved = "( comment_approved = '0' OR comment_approved = '1' )";
		}

		$order = ( 'ASC' === $args['order'] ) ? 'ASC' : 'DESC';

		if ( ! empty( $args['orderby'] ) ) {
			$ordersby = is_array( $args['orderby'] ) ? $args['orderby'] : preg_split( '/[,\s]/', $args['orderby'] );
			$ordersby = array_intersect(
				$ordersby,
				array(
					'comment_agent',
					'comment_approved',
					'comment_author',
					'comment_author_email',
					'comment_author_IP',
					'comment_author_url',
					'comment_content',
					'comment_date',
					'comment_date_gmt',
					'comment_ID',
					'comment_karma',
					'comment_parent',
					'comment_post_ID',
					'comment_type',
					'user_id',
				)
			);

			$orderby = empty( $ordersby ) ? 'comment_date_gmt' : implode( ', ', $ordersby );
		} else {
			$orderby = 'comment_date_gmt';
		}

		$number = absint( $args['number'] );
		$offset = absint( $args['offset'] );

		if ( ! empty( $number ) ) {
			if ( $offset ) {
				$number = 'LIMIT ' . $offset . ',' . $number;
			} else {
				$number = 'LIMIT ' . $number;
			}
		} else {
			$number = '';
		}

		$post_where = '';

		if ( ! empty( $post_id ) ) {
			$post_where .= $wpdb->prepare( 'comment_post_ID = %d AND ', $post_id );
		}

		if ( '' !== $args['author_email'] ) {
			$post_where .= $wpdb->prepare( 'comment_author_email = %s AND ', $args['author_email'] );
		}

		if ( '' !== $args['karma'] ) {
			$post_where .= $wpdb->prepare( 'comment_karma = %d AND ', $args['karma'] );
		}

		if ( 'comment' === $args['type'] ) {
			$post_where .= "comment_type = '' AND ";
		} elseif ( ! empty( $args['type'] ) ) {
			$post_where .= $wpdb->prepare( 'comment_type = %s AND ', $args['type'] );
		}

		if ( '' !== $args['parent'] ) {
			$post_where .= $wpdb->prepare( 'comment_parent = %d AND ', $args['parent'] );
		}

		if ( '' !== $args['user_id'] ) {
			$post_where .= $wpdb->prepare( 'user_id = %d AND ', $args['user_id'] );
		}

		// phpcs:ignore WordPress.DB
		$comments = $wpdb->get_results( "SELECT * FROM $wpdb->comments WHERE $post_where $approved ORDER BY $orderby $order $number" );

		wp_cache_add( $cache_key, $comments, 'comment' );

		return $comments;
	}

	/**
	 * Sends email notifications after a comment has been posted.
	 *
	 * Sends to the following, as long as their user preferences allow:
	 * - The creator of the post or nomination being commented on.
	 * - Any other users who have commented on the post or nomination.
	 *
	 * @param WP_Comment $comment Comment object.
	 * @return void
	 */
	public function maybe_send_comment_notifications( $comment ) {
		$recipient_ids = [];

		// Get the author of the post or nomination.
		$post_id         = $comment->comment_post_ID;
		$post            = get_post( (int) $post_id );
		$recipient_ids[] = $post->post_author;

		// Get all other users who have commented on the post or nomination.
		$comments = $this->ef_get_comments_plus(
			array(
				'post_id' => $post_id,
				'type'    => self::COMMENT_TYPE,
			)
		);

		foreach ( $comments as $comment ) {
			$recipient_ids[] = $comment->user_id;
		}

		// Remove duplicates.
		$recipient_ids = array_unique( $recipient_ids );

		// Remove users who have disabled comment notifications.
		$recipient_ids = array_filter(
			$recipient_ids,
			function ( $user_id ) {
				return pressforward()->fetch( 'controller.users' )->get_user_setting( $user_id, 'item-comment-email-toggle' );
			}
		);

		$item_pf_uid = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'item_id', true );

		// Nominations and feed items have different URLs.
		switch ( $post->post_type ) {
			case pressforward_nomination_post_type():
				$intro_text = __( 'A new comment has been posted on a nomination you have interacted with.', 'pressforward' );
				$view_url   = pressforward( 'admin.templates' )->get_modal_url( $item_pf_uid );
				break;

			case pf_feed_item_post_type():
				$intro_text = __( 'A new comment has been posted on a feed item you have interacted with.', 'pressforward' );
				$view_url   = pressforward( 'admin.templates' )->get_modal_url( $item_pf_uid );
				break;

			default:
				$intro_text = __( 'A new comment has been posted on an item you have interacted with.', 'pressforward' );
				$view_url   = get_edit_post_link( (int) $post_id );
				break;
		}

		// Send the email.
		foreach ( $recipient_ids as $recipient_id ) {
			$user = get_userdata( $recipient_id );
			if ( ! $user ) {
				continue;
			}

			$site_name = get_bloginfo( 'name' );

			$subject = sprintf(
				// translators: 1. Site name; 2. Post title.
				__( '[%1$s] New comment on "%2$s"', 'pressforward' ),
				$site_name,
				$post->post_title
			);

			$message = sprintf(
				// translators: 1. Introductory text; 2. Post title; 3. Comment author; 4. Comment content; 5. View URL.
				__( "%1\$s\n\nPost: %2\$s\n\nComment by: %3\$s\n\n%4\$s\n\nView and reply to the comment:\n%5\$s", 'pressforward' ),
				$intro_text,
				$post->post_title,
				$comment->comment_author,
				$comment->comment_content,
				$view_url
			);

			wp_mail( $user->user_email, $subject, $message );
		}
	}
}
