<?php
namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Interfaces\SystemUsers;

use PressForward\Core\Admin\PFTemplater as PFTemplater;
use PressForward\Core\Utility\Forward_Tools as Forward_Tools;
use PressForward\Core\Schema\Nominations as Nominations;
use PressForward\Controllers\Metas;
use AlertBox\The_Alert_Box as The_Alert_Box;

class SubscribedFeeds implements HasActions, HasFilters {

	function __construct( SystemUsers $user_interface, The_Alert_Box $alertbox, Metas $metas ) {
		$this->user_interface = $user_interface;
		$this->alertbox       = $alertbox;
		$this->metas          = $metas;
	}

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
		);
	}

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
			// add_filter( 'heartbeat_received', array( $this, 'hb_check_feed_retrieve_status' ), 10, 2 );
			array(
				'hook'     => 'heartbeat_received',
				'method'   => 'hb_check_feed_retrieve_status',
				'priority' => 10,
				'args'     => 2,
			),
		);
	}


	public function add_plugin_admin_menu() {

		if ( $alert_count = $this->alertbox->alert_count() ) {
			$alert_count_notice         = '<span class="feed-alerts count-' . intval( $alert_count ) . '"><span class="alert-count">' . number_format_i18n( $alert_count ) . '</span></span>';
			$subscribed_feeds_menu_text = sprintf( __( 'Subscribed Feeds %s', 'pf' ), $alert_count_notice );
		} else {
			$subscribed_feeds_menu_text = __( 'Subscribed Feeds', 'pf' );
		}

		add_submenu_page(
			PF_MENU_SLUG,
			__( 'Subscribed Feeds', 'pf' ),
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
		// unset( $posts_columns['date'] );
		$posts_columns['last_retrieved'] = __( 'Last Time Feed Item Retrieved', 'pf' );
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
		// unset( $posts_columns['date'] );
		$posts_columns['last_checked'] = __( 'Last Time Feed Checked', 'pf' );
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
			// Modified from WP_Posts_List_Table
			$lr_unix   = mysql2date( 'G', $last_retrieved, false );
			$time_diff = time() - $lr_unix;
			$t_time    = date( 'Y/m/d g:i:s A', $lr_unix );

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$lr_text = sprintf( __( '%s ago' ), human_time_diff( $lr_unix ) );
			} else {
				$lr_text = mysql2date( __( 'Y/m/d' ), $last_retrieved );
			}

			$lr_text = '<abbr title="' . $t_time . '">' . $lr_text . '</abbr>';
		}

		$feed_url = $this->metas->get_post_pf_meta( $post_id, 'feedUrl', true );
		$lr_text .= sprintf( '<input type="hidden" id="pf-feed-%d-url" value="%s" />', intval( $post_id ), esc_attr( $feed_url ) );

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
			// Modified from WP_Posts_List_Table
			$lr_unix   = mysql2date( 'G', $last_retrieved, false );
			$time_diff = time() - $lr_unix;
			$t_time    = date( 'Y/m/d g:i:s A', $lr_unix );

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$lr_text = sprintf( __( '%s ago' ), human_time_diff( $lr_unix ) );
			} else {
				$lr_text = mysql2date( __( 'Y/m/d' ), $last_retrieved );
			}

			$lr_text = '<abbr title="' . $t_time . '">' . $lr_text . '</abbr>';
		}

		$feed_url = $this->metas->get_post_pf_meta( $post_id, 'feedUrl', true );
		$lr_text .= sprintf( '<input type="hidden" id="pf-feed-%d-url" value="%s" />', intval( $post_id ), esc_attr( $feed_url ) );

		echo $lr_text;
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
	 * @param WP_Query
	 */
	public function sort_by_last_retrieved( $query ) {
		// For now, only enable this sorting when on the edit-pf_feed screen
		// This could be lifted in the future to enable last_retrieved
		// sorting throughout PF
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen->id ) || 'edit-pf_feed' !== $screen->id ) {
			return;
		}

		// Sanity check: only modify pf_feed queries
		$feed_post_type = '';
		if ( ! empty( pressforward( 'schema.feeds' )->post_type ) ) {
			$feed_post_type = pressforward( 'schema.feeds' )->post_type;
		}

		if ( empty( $query->query_vars['post_type'] ) || $feed_post_type !== $query->query_vars['post_type'] ) {
			return;
		}

		// Only touch if we're sorting by last_retrieved
		if ( ! isset( $query->query_vars['orderby'] ) || 'last_retrieved' !== $query->query_vars['orderby'] ) {
			return;
		}

		// Should never happen, but if someone's doing a meta_query,
		// bail or we'll mess it up
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			return;
		}

		$query->set( 'orderby', 'pf_feed_last_retrieved' );

		// In order to ensure that we get the items without a
		// Last Retrieved key set, force the meta_query to an OR with
		// NOT EXISTS
		$query->set(
			'meta_query', array(
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

		// var_dump($query); die();
	}

	/**
	 * Enable 'last_checked' sorting.
	 *
	 * @since 3.5.0
	 *
	 * @param WP_Query
	 */
	public function sort_by_last_checked( $query ) {
		// For now, only enable this sorting when on the edit-pf_feed screen
		// This could be lifted in the future to enable last_retrieved
		// sorting throughout PF
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen->id ) || 'edit-pf_feed' !== $screen->id ) {
			return;
		}

		// Sanity check: only modify pf_feed queries
		$feed_post_type = '';
		if ( ! empty( pressforward( 'schema.feeds' )->post_type ) ) {
			$feed_post_type = pressforward( 'schema.feeds' )->post_type;
		}

		if ( empty( $query->query_vars['post_type'] ) || $feed_post_type !== $query->query_vars['post_type'] ) {
			return;
		}

		// Only touch if we're sorting by last_retrieved
		if ( ! isset( $query->query_vars['orderby'] ) || 'last_checked' !== $query->query_vars['orderby'] ) {
			return;
		}

		// Should never happen, but if someone's doing a meta_query,
		// bail or we'll mess it up
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			return;
		}

		$query->set( 'orderby', 'pf_feed_last_checked' );

		// In order to ensure that we get the items without a
		// Last Retrieved key set, force the meta_query to an OR with
		// NOT EXISTS
		$query->set(
			'meta_query', array(
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


	public function pf_delete_children_of_feeds( $post_id ) {
		if ( pressforward( 'schema.feeds' )->post_type == get_post_type( $post_id ) ) {
			pf_log( 'Delete a feed and all its children.' );
			pf_delete_item_tree( $post_id );
		}
	}


	public function pf_trash_children_of_feeds( $post_id ) {
		if ( pressforward( 'schema.feeds' )->post_type == get_post_type( $post_id ) ) {
			pf_log( 'Trash a feed and all its children.' );
			$this->pf_thing_trasher( $post_id, true, pressforward( 'schema.feeds' )->post_type );
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
					<span class="title"><?php _e( 'Feed URL', 'pressforward' ); ?></span>
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
	 * @param int     $post_id ID of the post being edited.
	 * @param WP_Post $post    Post object.
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

		if ( ! wp_verify_nonce( $_POST['_pf_quick_edit_nonce'], 'pf-quick-edit' ) ) {
			return;
		}

		// Don't process if the URL field is not present in the request.
		if ( ! isset( $_POST['pf-quick-edit-feed-url'] ) ) {
			return;
		}

		$feed_url = stripslashes( $_POST['pf-quick-edit-feed-url'] );

		$this->metas->update_pf_meta( $post_id, 'feedUrl', $feed_url );
	}

	public function hb_check_feed_retrieve_status( $response, $data, $screen_id = '' ) {
		/**
		 * $feed_hb_state = array(
		 * 'feed_id'    =>  $aFeed->ID,
		 * 'feed_title' => $aFeed->post_title,
		 * 'last_key'   => $last_key,
		 * 'feeds_iteration'    =>  $feeds_iteration,
		 * 'total_feeds'    =>  count($feedlist)
		 * );
		*/
		if ( ( array_key_exists( 'pf_heartbeat_request', $data ) ) && ( 'feed_state' == $data['pf_heartbeat_request'] ) ) {
			$feed_hb_state = get_option( PF_SLUG . '_feeds_hb_state' );
			foreach ( $feed_hb_state as $key => $state ) {
				$response[ 'pf_' . $key ] = $state;
			}
		}

		return $response;

	}

	function pf_thing_trasher( $id = 0, $readability_status = false, $item_type = 'feed_item' ) {
		if ( $id == 0 ) {
			return new WP_Error( 'noID', __( 'No ID supplied for deletion', 'pf' ) ); }

		pf_log( 'On trash hook:' );
		// Note: this will also remove feed items if a feed is deleted, is that something we want?
		if ( $readability_status || $readability_status > 0 ) {
			if ( 'feed_item' == $item_type ) {
				$post_type = pf_feed_item_post_type();
			} else {
				$post_type = $item_type;
			}
			$args        = array(
				'post_parent' => $id,
				'post_type'   => $post_type,
			);
			$attachments = get_children( $args );
			pf_log( 'Get Children of ' . $id );
			pf_log( $attachments );
			foreach ( $attachments as $attachment ) {
				wp_trash_post( $attachment->ID, true );
			}
		}

		return $id;

	}


}
