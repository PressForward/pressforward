<?php
/**
 * Subscribed Feeds admin panel.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\PFtoWPUsers as Users;

use PressForward\Core\Models\Feed;

use PressForward\Controllers\Metas;
use AlertBox\The_Alert_Box;

/**
 * Subscribed Feeds admin panel.
 */
class SubscribedFeeds implements HasActions, HasFilters {
	/**
	 * PFtoWPUsers object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PFtoWPUsers
	 */
	public $user_interface;

	/**
	 * The_Alert_Box object.
	 *
	 * @access public
	 * @var \AlertBox\The_Alert_Box
	 */
	public $alertbox;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $metas;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\PFtoWPUsers $user_interface Users object.
	 * @param \AlertBox\The_Alert_Box               $alertbox       The_Alert_Box object.
	 * @param \PressForward\Controllers\Metas       $metas          Metas object.
	 */
	public function __construct( Users $user_interface, The_Alert_Box $alertbox, Metas $metas ) {
		$this->user_interface = $user_interface;
		$this->alertbox       = $alertbox;
		$this->metas          = $metas;
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'     => 'admin_menu',
				'method'   => 'add_plugin_admin_menu',
				'priority' => 14,
			),
			array(
				'hook'   => 'manage_edit-pf_feed_sortable_columns',
				'method' => 'make_last_retrieved_column_sortable',
			),
			array(
				'hook'   => 'pre_get_posts',
				'method' => 'sort_by_last_retrieved',
			),
			array(
				'hook'     => 'manage_pf_feed_posts_custom_column',
				'method'   => 'last_checked_date_column_content',
				'priority' => 10,
				'args'     => 2,
			),
			[
				'hook'     => 'manage_pf_feed_posts_custom_column',
				'method'   => 'next_retrieval_column_content',
				'priority' => 10,
				'args'     => 2,
			],
			array(
				'hook'   => 'manage_edit-pf_feed_sortable_columns',
				'method' => 'make_last_checked_column_sortable',
			),
			array(
				'hook'   => 'pre_get_posts',
				'method' => 'sort_by_last_checked',
			),
			array(
				'hook'   => 'before_delete_post',
				'method' => 'pf_delete_children_of_feeds',
			),
			array(
				'hook'   => 'wp_trash_post',
				'method' => 'pf_trash_children_of_feeds',
			),
			array(
				'hook'     => 'quick_edit_custom_box',
				'method'   => 'quick_edit_field',
				'priority' => 10,
				'args'     => 2,
			),
			array(
				'hook'     => 'save_post',
				'method'   => 'quick_edit_save',
				'priority' => 10,
				'args'     => 2,
			),
			array(
				'hook'     => 'manage_pf_feed_posts_custom_column',
				'method'   => 'last_retrieved_date_column_content',
				'priority' => 10,
				'args'     => 2,
			),
			[
				'hook'     => 'admin_enqueue_scripts',
				'method'   => 'enqueue_assets',
				'priority' => 10,
			],
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_hooks() {
		return array(
			array(
				'hook'   => 'manage_pf_feed_posts_columns',
				'method' => 'add_last_checked_date_column',
			),
			array(
				'hook'   => 'manage_pf_feed_posts_columns',
				'method' => 'add_last_retrieved_date_column',
			),
			array(
				'hook'   => 'manage_pf_feed_posts_columns',
				'method' => 'add_retrieval_check_date_column',
			),
		);
	}

	/**
	 * Enqueues assets on Subscribed Feeds panel.
	 *
	 * @since 5.6.0
	 *
	 * @param string $page_hook Page hook.
	 * @return void
	 */
	public function enqueue_assets( $page_hook ) {
		global $typenow;

		if ( 'edit.php' !== $page_hook || 'pf_feed' !== $typenow ) {
			return;
		}

		wp_enqueue_style( 'pf-subscribed-styles' );
	}

	/**
	 * Adds admin menu for Subscribed Feeds.
	 */
	public function add_plugin_admin_menu() {
		$alert_count = $this->alertbox->alert_count();
		if ( $alert_count ) {
			$alert_count_notice = '<span class="menu-counter feed-alerts count-' . intval( $alert_count ) . '"><span class="alert-count">' . number_format_i18n( $alert_count ) . '</span></span>';

			// translators: element containing an alert count.
			$subscribed_feeds_menu_text = sprintf( __( 'Subscribed Feeds %s', 'pressforward' ), $alert_count_notice );
		} else {
			$subscribed_feeds_menu_text = __( 'Subscribed Feeds', 'pressforward' );
		}

		add_submenu_page(
			PF_MENU_SLUG,
			__( 'Subscribed Feeds', 'pressforward' ),
			$subscribed_feeds_menu_text,
			get_option( 'pf_menu_feeder_access', $this->user_interface->pf_get_defining_capability_by_role( 'editor' ) ),
			'edit.php?post_type=' . pressforward( 'schema.feeds' )->post_type
		);
	}

	/**
	 * Add a Last Retrieved column to the pf_feed table.
	 *
	 * @since 3.4.0
	 *
	 * @param array $posts_columns Column headers.
	 * @return array
	 */
	public function add_last_retrieved_date_column( $posts_columns ) {
		$posts_columns['last_retrieved'] = __( 'Last Time Feed Item Retrieved', 'pressforward' );
		return $posts_columns;
	}

	/**
	 * Add a Last Checked column to the pf_feed table.
	 *
	 * @since 3.5.0
	 *
	 * @param array $posts_columns Column headers.
	 * @return array
	 */
	public function add_last_checked_date_column( $posts_columns ) {
		$posts_columns['last_checked'] = __( 'Last Time Feed Checked', 'pressforward' );
		return $posts_columns;
	}

	/**
	 * Add a Next Retrieval column to the pf_feed list table.
	 *
	 * @since 5.6.0
	 *
	 * @param array $posts_columns Column headers.
	 * @return array
	 */
	public function add_retrieval_check_date_column( $posts_columns ) {
		$posts_columns['next_retrieval'] = __( 'Next Retrieval', 'pressforward' );
		return $posts_columns;
	}

	/**
	 * Content of the Last Retrieved column.
	 *
	 * We also hide the feed URL in this column, so we can reveal it on Quick Edit.
	 *
	 * @since 3.4.0
	 *
	 * @param string $column_name Column ID.
	 * @param int    $post_id ID of the post for the current row in the table.
	 */
	public function last_retrieved_date_column_content( $column_name, $post_id ) {
		if ( 'last_retrieved' !== $column_name ) {
			return;
		}

		$last_retrieved = $this->metas->get_post_pf_meta( $post_id, 'pf_feed_last_retrieved', true );

		if ( '' === $last_retrieved ) {
			$lr_text = '-';
		} else {
			// Modified from WP_Posts_List_Table.
			$lr_unix   = mysql2date( 'G', $last_retrieved, false );
			$time_diff = time() - $lr_unix;
			$t_time    = gmdate( 'Y/m/d g:i:s A', $lr_unix );

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				// translators: Time difference.
				$lr_text = sprintf( __( '%s ago', 'pressforward' ), human_time_diff( $lr_unix ) );
			} else {
				$lr_text = mysql2date( __( 'Y/m/d', 'pressforward' ), $last_retrieved );
			}

			$lr_text = '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( $lr_text ) . '</abbr>';
		}

		$feed_url = $this->metas->get_post_pf_meta( $post_id, 'feedUrl', true );
		$lr_text .= sprintf( '<input type="hidden" id="pf-feed-%d-url" value="%s" />', intval( $post_id ), esc_attr( $feed_url ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $lr_text;
	}

	/**
	 * Content of the Last Checked column.
	 *
	 * We also hide the feed URL in this column, so we can reveal it on Quick Edit.
	 *
	 * @since 3.5.0
	 *
	 * @param string $column_name Column ID.
	 * @param int    $post_id ID of the post for the current row in the table.
	 */
	public function last_checked_date_column_content( $column_name, $post_id ) {
		if ( 'last_checked' !== $column_name ) {
			return;
		}

		$last_retrieved = $this->metas->get_post_pf_meta( $post_id, 'pf_feed_last_checked', true );

		if ( '' === $last_retrieved ) {
			$lr_text = '-';
		} else {
			// Modified from WP_Posts_List_Table.
			$lr_unix   = mysql2date( 'G', $last_retrieved, false );
			$time_diff = time() - $lr_unix;
			$t_time    = gmdate( 'Y/m/d g:i:s A', $lr_unix );

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				// translators: Time difference.
				$lr_text = sprintf( __( '%s ago', 'pressforward' ), human_time_diff( $lr_unix ) );
			} else {
				$lr_text = mysql2date( __( 'Y/m/d', 'pressforward' ), $last_retrieved );
			}

			$lr_text = '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( $lr_text ) . '</abbr>';
		}

		$feed_url = $this->metas->get_post_pf_meta( $post_id, 'feedUrl', true );
		$lr_text .= sprintf( '<input type="hidden" id="pf-feed-%d-url" value="%s" />', intval( $post_id ), esc_attr( $feed_url ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $lr_text;
	}

	/**
	 * Content of the Next Retrieval column.
	 *
	 * @since 5.6.0
	 *
	 * @param string $column_name Column ID.
	 * @param int    $post_id ID of the post for the current row in the table.
	 */
	public function next_retrieval_column_content( $column_name, $post_id ) {
		if ( 'next_retrieval' !== $column_name ) {
			return;
		}

		$feed_object = Feed::get_instance_by_id( $post_id );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $feed_object->get_next_scheduled_retrieval_el();
	}

	/**
	 * Add the Last Retrieved column to the list of sortable columns.
	 *
	 * @since 3.4.0
	 *
	 * @param array $sortable Sortable column identifiers.
	 * @return array
	 */
	public function make_last_retrieved_column_sortable( $sortable ) {
		$sortable['last_retrieved'] = array( 'last_retrieved', true );
		return $sortable;
	}

	/**
	 * Add the Last Checked column to the list of sortable columns.
	 *
	 * @since 3.5.0
	 *
	 * @param array $sortable Sortable column identifiers.
	 * @return array
	 */
	public function make_last_checked_column_sortable( $sortable ) {
		$sortable['last_checked'] = array( 'last_checked', true );
		return $sortable;
	}

	/**
	 * Enable 'last_retrieved' sorting.
	 *
	 * @since 3.4.0
	 *
	 * @param \WP_Query $query Query object.
	 */
	public function sort_by_last_retrieved( $query ) {
		/*
		 * For now, only enable this sorting when on the edit-pf_feed screen
		 * This could be lifted in the future to enable last_retrieved
		 * sorting throughout PF.
		 */
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen->id ) || 'edit-pf_feed' !== $screen->id ) {
			return;
		}

		// Sanity check: only modify pf_feed queries.
		$feed_post_type = '';
		if ( ! empty( pressforward( 'schema.feeds' )->post_type ) ) {
			$feed_post_type = pressforward( 'schema.feeds' )->post_type;
		}

		if ( empty( $query->query_vars['post_type'] ) || $feed_post_type !== $query->query_vars['post_type'] ) {
			return;
		}

		// Only touch if we're sorting by last_retrieved.
		if ( ! isset( $query->query_vars['orderby'] ) || 'last_retrieved' !== $query->query_vars['orderby'] ) {
			return;
		}

		// Should never happen, but if someone's doing a meta_query,  bail or we'll mess it up.
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			return;
		}

		$query->set( 'orderby', 'pf_feed_last_retrieved' );

		/*
		 * In order to ensure that we get the items without a
		 * Last Retrieved key set, force the meta_query to an OR with NOT EXISTS.
		 */
		$query->set(
			'meta_query',
			array(
				'relation' => 'OR',
				array(
					'key'     => 'pf_feed_last_retrieved',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'pf_feed_last_retrieved',
					'compare' => 'EXISTS',
				),
			)
		);
	}

	/**
	 * Enable 'last_checked' sorting.
	 *
	 * @since 3.5.0
	 *
	 * @param \WP_Query $query Query object.
	 */
	public function sort_by_last_checked( $query ) {
		/*
		 * For now, only enable this sorting when on the edit-pf_feed screen
		 * This could be lifted in the future to enable last_retrieved
		 * sorting throughout PF.
		 */
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen->id ) || 'edit-pf_feed' !== $screen->id ) {
			return;
		}

		// Sanity check: only modify pf_feed queries.
		$feed_post_type = '';
		if ( ! empty( pressforward( 'schema.feeds' )->post_type ) ) {
			$feed_post_type = pressforward( 'schema.feeds' )->post_type;
		}

		if ( empty( $query->query_vars['post_type'] ) || $feed_post_type !== $query->query_vars['post_type'] ) {
			return;
		}

		// Only touch if we're sorting by last_retrieved.
		if ( ! isset( $query->query_vars['orderby'] ) || 'last_checked' !== $query->query_vars['orderby'] ) {
			return;
		}

		// Should never happen, but if someone's doing a meta_query, bail or we'll mess it up.
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			return;
		}

		$query->set( 'orderby', 'pf_feed_last_checked' );

		/*
		 * In order to ensure that we get the items without a
		 * Last Retrieved key set, force the meta_query to an OR with NOT EXISTS.
		 */
		$query->set(
			'meta_query',
			array(
				'relation' => 'OR',
				array(
					'key'     => 'pf_feed_last_checked',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'pf_feed_last_checked',
					'compare' => 'EXISTS',
				),
			)
		);
	}

	/**
	 * Pre-delete processing of feed children.
	 *
	 * @param int $post_id Post ID. Passed from 'before_delete_post' hook.
	 */
	public function pf_delete_children_of_feeds( $post_id ) {
		if ( pressforward( 'schema.feeds' )->post_type === get_post_type( $post_id ) ) {
			pf_log( 'Delete a feed and all its children.' );
			pf_delete_item_tree( $post_id );
		}
	}

	/**
	 * Pre-trash processing of feed children.
	 *
	 * @param int $post_id Post ID. Passed from 'wp_trash_post' hook.
	 */
	public function pf_trash_children_of_feeds( $post_id ) {
		if ( pressforward( 'schema.feeds' )->post_type === get_post_type( $post_id ) ) {
			pf_log( 'Trash a feed and all its children.' );
			$this->pf_thing_trasher( $post_id, 1, pressforward( 'schema.feeds' )->post_type );
		}
	}

	/**
	 * Echo the output for the Feed URL field on Quick Edit.
	 *
	 * @since 3.5.0
	 *
	 * @param string $column_name Name of the Quick Edit column being output.
	 * @param string $post_type   Name of the current post type.
	 */
	public function quick_edit_field( $column_name, $post_type ) {
		if ( 'pf_feed' !== $post_type || 'last_retrieved' !== $column_name ) {
			return;
		}

		wp_nonce_field( 'pf-quick-edit', '_pf_quick_edit_nonce', false );

		?>
		<fieldset class="inline-edit-pressforward">
			<div class="inline-edit-col">
				<label for="pf-feed-url">
					<span class="title"><?php esc_html_e( 'Feed URL', 'pressforward' ); ?></span>
					<span class="input-text-wrap">
						<input class="inline-edit-pf-feed-input" type="text" value="" name="pf-quick-edit-feed-url" id="pf-quick-edit-feed-url" />
					</span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Process Quick Edit saves.
	 *
	 * Feed URL can be edited via Quick Save.
	 *
	 * @since 3.5.0
	 *
	 * @param int      $post_id ID of the post being edited.
	 * @param \WP_Post $post    Post object.
	 */
	public function quick_edit_save( $post_id, $post ) {
		// Only process on the correct post type.
		if ( 'pf_feed' !== $post->post_type ) {
			return;
		}

		// Nonce check.
		if ( ! isset( $_POST['_pf_quick_edit_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pf_quick_edit_nonce'] ) ), 'pf-quick-edit' ) ) {
			return;
		}

		// Don't process if the URL field is not present in the request.
		if ( ! isset( $_POST['pf-quick-edit-feed-url'] ) ) {
			return;
		}

		$feed_url = sanitize_text_field( wp_unslash( $_POST['pf-quick-edit-feed-url'] ) );

		$this->metas->update_pf_meta( $post_id, 'feedUrl', $feed_url );
	}

	/**
	 * Delete callback for PF items.
	 *
	 * @param int    $id                 ID of the deleted item.
	 * @param int    $readability_status Readability status.
	 * @param string $item_type          Default 'feed item'.
	 */
	public function pf_thing_trasher( $id = 0, $readability_status = 0, $item_type = 'feed_item' ) {
		if ( ! $id ) {
			return new \WP_Error( 'noID', __( 'No ID supplied for deletion', 'pressforward' ) );
		}

		pf_log( 'On trash hook:' );

		// Note: this will also remove feed items if a feed is deleted, is that something we want?
		if ( $readability_status ) {
			if ( 'feed_item' === $item_type ) {
				$post_type = pf_feed_item_post_type();
			} else {
				$post_type = $item_type;
			}

			$args = array(
				'post_parent' => $id,
				'post_type'   => $post_type,
			);

			$attachments = get_children( $args );
			pf_log( 'Get Children of ' . $id );
			pf_log( $attachments );

			foreach ( $attachments as $attachment ) {
				wp_trash_post( $attachment->ID );
			}
		}

		return $id;
	}
}
