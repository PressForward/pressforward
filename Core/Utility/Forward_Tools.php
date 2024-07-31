<?php
/**
 * Utilities for item lifecycle transitions.
 *
 * @package PressForward
 */

namespace PressForward\Core\Utility;

use PressForward\Interfaces\Items;
use PressForward\Interfaces\Advance_System;

use PressForward\Controllers\Metas;

/**
 * PressForward object lifecycle tools
 * was once form_of
 */
class Forward_Tools {
	/**
	 * Items object.
	 *
	 * @access public
	 * @var \PressForward\Interfaces\Items
	 */
	public $item_interface;

	/**
	 * Advance_System object.
	 *
	 * @access public
	 * @var \PressForward\Interfaces\Advance_System
	 */
	public $advance_interface;

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
	 * @param \PressForward\Interfaces\Items          $item_interface    Items object.
	 * @param \PressForward\Interfaces\Advance_System $advance_interface Advance_System object.
	 * @param \PressForward\Controllers\Metas         $meta_interface    Metas object.
	 */
	public function __construct( Items $item_interface, Advance_System $advance_interface, Metas $meta_interface ) {
		$this->item_interface    = $item_interface;
		$this->advance_interface = $advance_interface;
		$this->metas             = $meta_interface;
	}

	/**
	 * Gets a user ID.
	 *
	 * Falls back on current user if none is provided.
	 *
	 * @param int $user_id ID of the user.
	 * @return int
	 */
	public function assure_user_id( $user_id = 0 ) {
		if ( ! $user_id ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		}

		pf_log( 'by ' . $user_id );
		return $user_id;
	}

	/**
	 * Gets the nominator array for a nomination.
	 *
	 * @param int $nomination_id WP post ID of the nomination.
	 * @return array
	 */
	public function get_nomination_nominator_array( $nomination_id ) {
		$nominators = $this->metas->get_post_pf_meta( $nomination_id, 'nominator_array' );
		if ( ! $nominators ) {
			$nominators = [];
		}

		return $nominators;
	}

	/**
	 * Gets the nominator array for a promoted item.
	 *
	 * Nominators are stored canonically on the nomination object.
	 *
	 * @param int $post_id WP post ID of the promoted item.
	 * @return array
	 */
	public function get_post_nominator_array( $post_id ) {
		$nomination_id = $this->get_post_nomination_id( $post_id );
		if ( ! $nomination_id ) {
			return [];
		}

		return $this->get_nomination_nominator_array( $nomination_id );
	}

	/**
	 * Gets the nomination corresponding to a promoted item.
	 *
	 * @param int $post_id WP post ID of the promoted item.
	 * @return int
	 */
	public function get_post_nomination_id( $post_id ) {
		$nomination_id = $this->metas->get_post_pf_meta( $post_id, 'nom_id', true );
		if ( ! $nomination_id ) {
			return 0;
		}

		return (int) $nomination_id;
	}

	/**
	 * Adds a user to the list of stored nominators for an item.
	 *
	 * @param int $post_id ID of the nomination.
	 * @param int $user_id ID of the user.
	 * @return bool
	 */
	public function add_user_to_nominator_array( $post_id, $user_id ) {
		$nominators = $this->metas->get_post_pf_meta( $post_id, 'nominator_array' );
		if ( ! $nominators ) {
			$nominators = [];
		}

		if ( isset( $nominators[ $user_id ] ) ) {
			return false;
		}

		$nominators[ $user_id ] = [
			'user_id'             => $user_id,
			'nomination_datetime' => gmdate( 'Y-m-d H:i:s' ),
			'nomination_unixtime' => time(),
		];

		$this->metas->update_pf_meta( $post_id, 'nominator_array', $nominators );

		return true;
	}

	/**
	 * Applies a nomination event.
	 *
	 * @param int $id      ID of the nomination.
	 * @param int $user_id ID of the user.
	 * @return array
	 */
	public function apply_nomination_array( $id, $user_id = 0 ) {
		pf_log( 'Processing Nominator Array on post ' );
		pf_log( $id );
		$nominators = $this->metas->get_post_pf_meta( $id, 'nominator_array' );

		$value_array = array(
			'user_id'             => $user_id,
			'nomination_datetime' => gmdate( 'Y-m-d H:i:s' ),
			'nomination_unixtime' => time(),
		);

		if ( empty( $nominators ) ) {
			$nominators = array();
		} elseif ( ! is_array( $nominators ) ) {
			$nominators = array( $nominators );
		}

		// We are doing a removal.
		if ( array_key_exists( $user_id, $nominators ) ) {
			unset( $nominators[ $user_id ] );
			$applied = false;
		} else {
			$nominators[ $user_id ] = $value_array;
			$applied                = true;

		}

		return array(
			'nominators' => $nominators,
			'applied'    => $applied,
		);
	}

	/**
	 * Increments counts when a nomination is created.
	 *
	 * @param int  $id      ID of the nomination.
	 * @param int  $user_id ID of the user.
	 * @param bool $is_post Whether this is a post. Default false.
	 * @return int
	 */
	public function apply_nomination_count( $id, $user_id = 0, $is_post = false ) {
		$nom_count = $this->metas->get_post_pf_meta( $id, 'nomination_count', true );
		if ( empty( $nom_count ) ) {
			$nom_count = 0;
		}
		$parent_id = wp_get_post_parent_id( $id );
		if ( false !== $parent_id && ! $is_post ) {
			$feed_nom_count = $this->metas->get_post_pf_meta( $parent_id, 'pf_nominations_in_feed', true );
			if ( empty( $feed_nom_count ) ) {
				$feed_nom_count = 0;
			}
			$check_meta = $this->metas->update_pf_meta( $parent_id, 'pf_nominations_in_feed', ++$feed_nom_count );
		}

		$check_meta = $this->metas->update_pf_meta( $id, 'nomination_count', ++$nom_count );
		pf_log( 'Attempt to update the meta for nomination_count resulted in: ' );
		pf_log( $check_meta );
		return $nom_count;
	}

	/**
	 * Decrements counts when a nomination is revoked.
	 *
	 * @param int  $id      ID of the nomination.
	 * @param int  $user_id ID of the user.
	 * @param bool $is_post Whether this is a post. Default false.
	 * @return int
	 */
	public function revoke_nomination_count( $id, $user_id = 0, $is_post = false ) {
		$nom_count = $this->metas->get_post_pf_meta( $id, 'nomination_count', true );
		if ( empty( $nom_count ) ) {
			$nom_count = 0;
		}

		$parent_id = wp_get_post_parent_id( $id );
		if ( false !== $parent_id && ! $is_post ) {
			$feed_nom_count = $this->metas->get_post_pf_meta( $parent_id, 'pf_nominations_in_feed', true );
			if ( empty( $feed_nom_count ) ) {
				$feed_nom_count = 0;
			}

			$check_meta = $this->metas->update_pf_meta( $parent_id, 'pf_nominations_in_feed', max( 0, --$feed_nom_count ) );
		}

		$check_meta = $this->metas->update_pf_meta( $id, 'nomination_count', max( 0, --$nom_count ) );
		pf_log( 'Attempt to update the meta for nomination_count resulted in: ' );
		pf_log( $check_meta );
		return $nom_count;
	}

	/**
	 * Gets the count of nominated items.
	 *
	 * @param bool $exclude_promoted Whether to exclude promoted items. Default false.
	 * @param int  $since            Unix timestamp.
	 * @return int
	 */
	public function get_nominated_count( $exclude_promoted = false, $since = null ) {
		$args = array(
			'post_type'      => pressforward( 'schema.nominations' )->post_type,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		if ( $exclude_promoted ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'pf_final_step_id',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( $since ) {
			$args['date_query'] = array(
				array(
					'after' => gmdate( 'Y-m-d H:i:s', $since ),
				),
			);
		}

		$query = new \WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Adjusts user data on nomination event.
	 *
	 * @param int $id      ID of the nomination.
	 * @param int $user_id ID of the user.
	 */
	public function apply_nomination_user_data( $id, $user_id = 0 ) {
		$nom_stats       = get_user_meta( $user_id, 'nom_stats', true );
		$noms_counted    = get_user_meta( $user_id, 'nom_count', true );
		$function_to_use = 'update_user_meta';
		if ( ! is_array( $nom_stats ) ) {
			$nom_stats       = array();
			$function_to_use = 'add_user_meta';
		}
		if ( empty( $noms_counted ) || $noms_counted <= 0 ) {
			$noms_counted = 0;
		}
		if ( ! array_key_exists( $id, $nom_stats ) ) {
			$nom_stats[ $id ] = array(
				'nomination_id'       => $id,
				'nomination_datetime' => gmdate( 'Y-m-d H:i:s' ),
				'nomination_unixtime' => time(),
			);
		}

		pf_log( 'Create nom_count in user meta for user ' . $user_id );
		$function_to_use( $user_id, 'nom_count', ++$noms_counted, true );
		$function_to_use( $user_id, 'nom_stats', $nom_stats, true );

		return $noms_counted;
	}

	/**
	 * Decrements metadata on revoked nomination.
	 *
	 * @param int $id      ID of the nomination.
	 * @param int $user_id ID of the user.
	 * @return int|bool
	 */
	public function revoke_nomination_user_data( $id, $user_id = 0 ) {
		$nom_stats    = get_user_meta( $user_id, 'nom_stats', true );
		$noms_counted = get_user_meta( $user_id, 'nom_count', true );
		if ( ! is_array( $nom_stats ) ) {
			$nom_stats = array();
			return false;
		}
		if ( empty( $noms_counted ) || $noms_counted < 0 ) {
			$noms_counted = 0;
		}
		if ( array_key_exists( $id, $nom_stats ) ) {
			unset( $nom_stats[ $id ] );
		}
		pf_log( 'Create nom_count in user meta for user ' . $user_id );
		update_user_meta( $user_id, 'nom_count', max( 0, --$noms_counted ) );
		update_user_meta( $user_id, 'nom_stats', $nom_stats );
		return $noms_counted;
	}

	/**
	 * Increments metadata on nomination.
	 *
	 * @param int  $id      WP post ID of the nomination.
	 * @param int  $user_id ID of the user.
	 * @param bool $is_post Whether this is a post. Default false.
	 * @return array
	 */
	public function apply_nomination_data( $id, $user_id = 0, $is_post = false ) {
		$user_id    = $this->assure_user_id( $user_id );
		$nominators = $this->apply_nomination_array( $id, $user_id );
		if ( $nominators['applied'] && ! $is_post ) {
			$this->apply_nomination_user_data( $id, $user_id );
			$this->apply_nomination_count( $id, $user_id );
		} elseif ( ! $is_post ) {
			$this->revoke_nomination_user_data( $id, $user_id );
			$this->revoke_nomination_count( $id, $user_id );
		} elseif ( $nominators['applied'] && $is_post ) {
			$this->apply_nomination_count( $id, $user_id, true );
		}

		return $nominators['nominators'];
	}

	/**
	 * Transitions an item to post draft.
	 *
	 * @param int $nomination_id WP post ID of the nomination.
	 * @return int|bool
	 */
	public function transition_to_last_step( $nomination_id ) {
		$post         = $this->item_interface->get_post( $nomination_id, ARRAY_A );
		$d_post       = $post;
		$current_user = wp_get_current_user();

		pf_log( 'User: ' );
		pf_log( $current_user );

		if ( $current_user->ID > 0 ) {
			$d_post['post_author'] = $current_user->ID;
		}

		$new_post_id = $this->advance_interface->to_last_step( $d_post );
		pf_log( $new_post_id );
		$this->metas->update_pf_meta( $nomination_id, 'pf_final_step_id', $new_post_id );

		if ( $this->item_interface->is_error( $new_post_id ) ) {
			pf_log( $new_post_id );
			return false;
		} else {
			$this->advance_interface->transition( $nomination_id, $new_post_id );
			$this->metas->update_pf_meta( $new_post_id, 'nom_id', $nomination_id );
			return $new_post_id;
		}
	}

	/**
	 * Transitions an item to a nomination.
	 *
	 * @param int  $item_post_id         Item post ID.
	 * @param bool $from_meta_added_item From meta added item.
	 * @return int|bool
	 */
	public function transition_to_nomination( $item_post_id, $from_meta_added_item = false ) {
		// Create.
		$post          = $this->item_interface->get_post( $item_post_id, ARRAY_A );
		$nomination_id = $this->advance_interface->to_nomination( $post );

		if ( $this->item_interface->is_error( $nomination_id ) ) {
			pf_log( $nomination_id );
			return false;
		} else {
			$this->advance_interface->transition( $item_post_id, $nomination_id );
			if ( ! $from_meta_added_item ) {
				$nominators = pressforward( 'utility.forward_tools' )->apply_nomination_data( $nomination_id );
				pressforward( 'controller.metas' )->update_pf_meta( $nomination_id, 'nominator_array', $nominators );
			}
			return $nomination_id;
		}
	}

	/**
	 * Appends source statement to a post content.
	 *
	 * @param int    $post_id      ID of the post.
	 * @param string $item_content Item content.
	 * @param string $source       Source string.
	 * @return string
	 */
	public function append_source_statement( $post_id, $item_content, $source ) {
		$source_position = get_option( 'pf_source_statement_position', 'bottom' );
		if ( ( 'bottom' === $source_position ) && $source ) {
			$item_content = $item_content . pressforward( 'admin.nominated' )->get_the_source_statement( $post_id );
		} elseif ( $source ) {
			$item_content = pressforward( 'admin.nominated' )->get_the_source_statement( $post_id ) . $item_content;
		}
		return $item_content;
	}

	/**
	 * Transition post content to readable text.
	 *
	 * Call me before transitioning the post.
	 *
	 * @param int  $post_id ID of the post.
	 * @param bool $source  Not used.
	 */
	public function transition_to_readable_text( $post_id, $source = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$post            = $this->item_interface->get_post( $post_id, ARRAY_A );
		$item_content    = $post['post_content'];
		$readable_status = (int) $this->metas->get_post_pf_meta( $post_id, 'readable_status', true );
		if ( 1 === $readable_status ) {
			return false;
		}

		$read_args = array(
			'force'      => false,
			'descrip'    => htmlspecialchars_decode( $item_content ),
			'url'        => $this->metas->get_post_pf_meta( $post_id, 'item_link', true ),
			'authorship' => 'auto',
		);

		$item_content_obj = pressforward( 'controller.readability' )->get_readable_text( $read_args );
		$item_content     = htmlspecialchars_decode( $item_content_obj['readable'] );
		$word_count       = str_word_count( $item_content );

		$saved_post = $_POST;
		$_POST      = array();

		$post_id = $this->item_interface->update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $item_content,
			),
			true
		);

		$_POST = $saved_post;

		if ( is_numeric( $post_id ) ) {
			if ( ( ! empty( $item_content_obj['status'] ) ) && ( 'secured' !== $item_content_obj['status'] ) ) {
				$this->metas->update_pf_meta( $post_id, 'readable_status', 1 );
				$this->metas->update_pf_meta( $post_id, 'pf_word_count', $word_count );
			} elseif ( 1 !== $readable_status ) {
				$this->metas->update_pf_meta( $post_id, 'readable_status', 0 );
			}
			return $item_content;
		} else {
			return false;
		}
	}

	/**
	 * Increment or decrement user nomination count.
	 *
	 * @param int  $user_id  ID of the user.
	 * @param bool $increase Whether to increase. False to decrease. Default true.
	 */
	public function user_meta_nomination_counter_change( $user_id, $increase = true ) {
		if ( get_user_meta( $user_id, 'nom_count', true ) ) {
			$nom_counter = get_user_meta( $user_id, 'nom_count', true );

			if ( $increase ) {
				++$nom_counter;
			} else {
				--$nom_counter;
			}

			update_user_meta( $user_id, 'nom_count', $nom_counter, true );
		} elseif ( $increase ) {
			add_user_meta( $user_id, 'nom_count', 1, true );
		}
	}

	/**
	 * Process nominator info on nomination.
	 *
	 * @param int    $id         WP Item ID, or false if none was found.
	 * @param string $item_id    PF Item ID.
	 * @param bool   $can_delete Can delete.
	 * @return array
	 */
	public function nomination_user_transition_check( $id, $item_id, $can_delete = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$nominators = $this->apply_nomination_data( $id );
		$this->metas->update_pf_meta( $id, 'nominator_array', $nominators );
		$final_step_parent = $this->metas->get_post_pf_meta( $id, 'pf_final_step_id' );
		if ( 0 !== $final_step_parent && false !== $final_step_parent ) {
			// The nomination has already been pushed to final step.
			// Increment it as well.
			$nominators = $this->apply_nomination_data( $final_step_parent, 0, true );
			$this->metas->update_pf_meta( $final_step_parent, 'nominator_array', $nominators );
		}
		return $nominators;
	}

	/**
	 * Gets nomination metadata about a nomination.
	 *
	 * @param int  $nomination_id ID of the nomination.
	 * @param bool $increase      Whether to increment.
	 */
	public function user_nomination_meta( $nomination_id, $increase = true ) {
		$current_user   = wp_get_current_user();
		$user_id        = $current_user->ID;
		$user_nom_count = get_user_meta( $user_id, 'nom_count', true );
		if ( ! empty( $user_nom_count ) ) {
			pf_log( 'Update nom_count in user meta for user ' . $user_id );
			$nom_counter = get_user_meta( $user_id, 'nom_count', true );
			$nom_stats   = get_user_meta( $user_id, 'nom_stats', true );

			if ( empty( $nom_stats ) ) {
				$nom_stats = array();
			}

			$old_nom_stats   = $nom_stats;
			$old_nom_counter = $nom_counter;

			if ( $increase ) {
				++$nom_counter;
				$nom_stats[ $nomination_id ] = array(
					'nomination_id'       => $nomination_id,
					'nomination_datetime' => gmdate( 'Y-m-d H:i:s' ),
					'nomination_unixtime' => time(),
				);
			} else {
				--$nom_counter;
				unset( $nom_stats[ $nomination_id ] );
			}

			pf_log( 'Update nom_count in user meta for user ' . $user_id . ' with value of ' . $nom_counter );
			update_user_meta( $user_id, 'nom_count', $nom_counter, $old_nom_counter );
			update_user_meta( $user_id, 'nom_stats', $nom_stats, $old_nom_stats );

		} elseif ( $increase ) {
			$nom_stats                   = array();
			$nom_stats[ $nomination_id ] = array(
				'nomination_id'       => $nomination_id,
				'nomination_datetime' => gmdate( 'Y-m-d H:i:s' ),
				'nomination_unixtime' => time(),
			);
			pf_log( 'Create nom_count in user meta for user ' . $user_id );
			add_user_meta( $user_id, 'nom_count', 1, true );
			add_user_meta( $user_id, 'nom_stats', $nom_stats, true );

		} else {
			pf_log( 'Nothing to do with nom_count in user meta for user ' . $user_id );
			return false;
		}
	}

	/**
	 * Gets information about a user performing a nomination.
	 *
	 * @param int $nomination_id Nomination ID.
	 * @return array
	 */
	public function find_nominating_user( $nomination_id ) {
		$current_user = wp_get_current_user();
		pf_log( 'User: ' );
		pf_log( $current_user );
		if ( 0 === $current_user->ID ) {
			// Not logged in.
			$user_slug   = 'external';
			$user_string = __( 'External User', 'pressforward' );
			$user_id     = 0;
			pf_log( 'Can not find a user to add to the nominated count of.' );
		} else {
			// Logged in.
			$this->user_nomination_meta( $nomination_id );
			$user_id     = $current_user->ID;
			$user_string = $user_id;
		}

		return array(
			'user_string' => $user_string,
			'user_id'     => $user_id,
		);
	}

	/**
	 * Transitions a new item to a nomination.
	 *
	 * @param string $item_id      PF Item ID.
	 * @param int    $item_post_id Item post ID.
	 * @return int|\WP_Error|bool
	 */
	public function item_to_nomination( $item_id, $item_post_id ) {
		$nomination_and_post_check = $this->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );
		pf_log( 'Is this a PF Type?' );
		pf_log( $nomination_and_post_check );

		if ( ! $nomination_and_post_check ) {
			$nominators = $this->apply_nomination_data( $item_post_id );
			$this->metas->update_pf_meta( $item_post_id, 'nominator_array', $nominators );
			$this->metas->update_pf_meta( $item_post_id, 'date_nominated', current_time( 'mysql' ) );
			$this->metas->update_pf_meta( $item_post_id, 'item_id', $item_id );
			$this->metas->update_pf_meta( $item_post_id, 'pf_item_post_id', $item_post_id );

			if ( ! empty( $_POST['item_link'] ) ) {
				$this->metas->update_pf_meta( $item_post_id, 'item_link', sanitize_text_field( wp_unslash( $_POST['item_link'] ) ) );
			}

			if ( empty( $_POST['item_date'] ) ) {
				$new_date  = current_time( 'Y-m-d H:i:s' );
				$item_date = $new_date;
			} else {
				$item_date = sanitize_text_field( wp_unslash( $_POST['item_date'] ) );
			}

			$this->metas->update_pf_meta( $item_post_id, 'item_date', $item_date );
			$this->metas->update_pf_meta( $item_post_id, 'item_wp_date', $item_date );
			pf_log( 'Start Transition.' );

			$user_data   = $this->find_nominating_user( $item_post_id );
			$user_id     = $user_data['user_id'];
			$user_string = $user_data['user_string'];
			$this->metas->update_pf_meta( $item_post_id, 'submitted_by', $user_string );

			$this->transition_to_readable_text( $item_post_id, true );
			$nomination_id = $this->transition_to_nomination( $item_post_id, true );
			$this->metas->update_pf_meta( $item_post_id, 'nom_id', $nomination_id );
			$this->metas->update_pf_meta( $nomination_id, 'nom_id', $nomination_id );

			// Assign user status as well here.
			return $nomination_id;
		} else {
			// Uptick user if they are not in nominator array.
			// If they are in the nominator array and no one else is, un-nominate.
			// If they are in the nominator array and someone else is, un-relate
			// them to that nomination.
			$this->nomination_user_transition_check( $nomination_and_post_check, $item_id, true );
			return $nomination_and_post_check;
		}
	}

	/**
	 * Transitions a nomination to a post draft.
	 *
	 * @param string $item_id       PF ID of the item.
	 * @param int    $nomination_id Nomination ID.
	 * @param bool   $make_readable Whether to process content through Readability.
	 * @return int|bool
	 */
	public function nomination_to_last_step( $item_id = '', $nomination_id = 0, $make_readable = true ) {
		$post_check = $this->is_a_pf_type( $item_id, pressforward_draft_post_type() );

		// Assign user status as well here.
		if ( ! $post_check ) {
			$id = 0;
			if ( $nomination_id ) {
				if ( $make_readable ) {
					$this->transition_to_readable_text( $nomination_id, true );
				}
				$id = $this->transition_to_last_step( $nomination_id );
				pf_log( $id );
			}
			return $id;
		} else {
			// @TODO We should increment nominations for this item maybe?
			// Some sort of signal should occur here to indicate that the item was
			// already sent to last step.
			return $post_check;
		}
	}

	/**
	 * Transitions an item to a post draft.
	 *
	 * @param string $item_id      PF Item ID.
	 * @param int    $item_post_id Item post ID.
	 * @return int
	 */
	public function item_to_last_step( $item_id, $item_post_id ) {
		$nomination_id = $this->item_to_nomination( $item_id, $item_post_id );
		$post_id       = $this->nomination_to_last_step( $item_id, $nomination_id );
		return $post_id;
	}

	/**
	 * Transitions an item from the bookmarklet to a draft.
	 *
	 * @param string $item_id PF ID of the item.
	 * @param array  $post    Array of post data.
	 * @return int|bool
	 */
	public function bookmarklet_to_nomination( $item_id = '', $post = [] ) {
		$_POST = array_merge( $_POST, $post );

		$item_link = isset( $_POST['item_link'] ) ? sanitize_text_field( wp_unslash( $_POST['item_link'] ) ) : '';

		if ( ! $item_id ) {
			$item_id = pressforward_create_feed_item_id( $item_link, $post['post_title'] );
		}

		$nom_and_post_check = $this->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );
		if ( ! $nom_and_post_check ) {
			$item_check = $this->is_a_pf_type( $item_id, pressforward( 'schema.feed_item' )->post_type );
			if ( $item_check ) {
				$nomination_id = false;
				$nomination_id = $this->item_to_nomination( $item_id, $item_check );
				pressforward( 'utility.relate' )->basic_relate( 'nominate', $item_check, 'on' );
			} else {
				$nomination_id = false;
			}
			// PF NOTE: Switching post type to nomination.
			$post['post_type']     = pressforward( 'schema.nominations' )->post_type;
			$post['post_date']     = current_time( 'Y-m-d H:i:s' );
			$post['post_date_gmt'] = get_gmt_from_date( current_time( 'Y-m-d H:i:s' ) );

			if ( strlen( esc_url( $item_link ) ) <= 243 ) {
				$post['guid'] = esc_url( $item_link );
			} else {
				$post['guid'] = substr( esc_url( $item_link ), 0, 243 );
			}
			$post_array = $post;

			// PF NOTE: This is where the inital post is created.
			// PF NOTE: Put get_post_nomination_status here.
			if ( $nomination_id ) {
				$save_post_to_avoid_hooks = array();
				foreach ( $_POST as $key => $value ) {
					$save_post_to_avoid_hooks[ $key ] = $value;
					$_POST[ $key ]                    = false;
				}

				$post['ID'] = $nomination_id;
				$this->item_interface->update_post( $post );
				foreach ( $save_post_to_avoid_hooks as $key => $value ) {
					$_POST[ $key ] = $value;
				}
				$post = $nomination_id;
			} else {
				$post = $this->item_interface->insert_post( $post, true, $item_id );
				if ( ! is_wp_error( $post ) ) {
					do_action( 'pf_transition_to_nomination', $post );
				}
			}

			if ( is_wp_error( $post ) ) {
				wp_die( esc_html( $post->get_error_message() ) );
			}

			$post_ID = $post;

			$this->advance_interface->prep_bookmarklet( $post );
			if ( ! isset( $_POST['item_date'] ) ) {
				$new_date  = gmdate( 'Y-m-d H:i:s' );
				$item_date = $new_date;
			} else {
				$item_date = sanitize_text_field( wp_unslash( $_POST['item_date'] ) );
			}

			$source = '';
			if ( ! empty( $_POST['item_link'] ) ) {
				$url_parts = wp_parse_url( sanitize_text_field( wp_unslash( $_POST['item_link'] ) ) );
				if ( ! empty( $url_parts['host'] ) ) {
					$source = $url_parts['host'];
				}
			}

			$tags = [];
			if ( ! empty( $_POST['post_tags'] ) ) {
				$tags = map_deep( wp_unslash( $_POST['post_tags'] ), 'sanitize_text_field' );
			}

			if ( empty( $tags ) || is_wp_error( $tags ) ) {
				$tags[] = 'via bookmarklet';
				// @phpstan-ignore-next-line
				if ( is_wp_error( $tags ) ) {
					pf_log( $tags );
				}
			}

			$tags_array = $this->metas->handle_item_tags( $post_ID, $tags );

			if ( $nomination_id ) {
				$old_tags = $this->metas->get_post_pf_meta( $post_ID, 'item_tags' );
				if ( ! empty( $old_tags ) ) {
					if ( ! is_array( $old_tags ) ) {
						$old_tags_array     = explode( ',', $old_tags );
						$old_tags_array_two = explode( ';', $old_tags );
						$old_tags           = array_merge( $old_tags_array, $old_tags_array_two );
					}
					if ( ! empty( $tags_array ) ) {
						$tags = array_merge( $old_tags, $tags_array );
					} else {
						$tags = $old_tags;
					}
				}
				if ( is_array( $tags ) ) {
					$tags = implode( ',', $tags );
				}
				$this->metas->update_pf_meta( $post_ID, 'item_tags', $tags );
				return $nomination_id;
			}

			$user_data   = $this->find_nominating_user( $post_ID );
			$user_id     = $user_data['user_id'];
			$user_string = $user_data['user_string'];

			if ( empty( $_POST['item_author'] ) ) {
				$item_author = 'Author on Source';
			} else {
				$item_author = sanitize_text_field( wp_unslash( $_POST['item_author'] ) );
			}

			$item_link     = isset( $_POST['item_link'] ) ? sanitize_text_field( wp_unslash( $_POST['item_link'] ) ) : '';
			$item_feat_img = isset( $_POST['item_feat_img'] ) ? sanitize_text_field( wp_unslash( $_POST['item_feat_img'] ) ) : '';

			$pf_meta_args = array(
				$this->metas->meta_for_entry( 'item_id', $item_id ),
				$this->metas->meta_for_entry( 'item_link', $item_link ),
				$this->metas->meta_for_entry( 'source_title', 'Bookmarklet' ),
				$this->metas->meta_for_entry( 'item_date', $item_date ),
				$this->metas->meta_for_entry( 'date_nominated', current_time( 'mysql' ) ),
				$this->metas->meta_for_entry( 'item_author', $item_author ),
				$this->metas->meta_for_entry( 'pf_source_link', $source ),
				$this->metas->meta_for_entry( 'item_feat_img', $item_feat_img ),
				$this->metas->meta_for_entry( 'submitted_by', $user_string ),

				// The item_wp_date allows us to sort the items with a query.
				$this->metas->meta_for_entry( 'item_wp_date', $item_date ),

				// We can't just sort by the time the item came into the system (for when mult items come into the system at once).
				// So we need to create a machine sortable date for use in the later query.
				$this->metas->meta_for_entry( 'sortable_item_date', strtotime( $item_date ) ),
				$this->metas->meta_for_entry( 'item_tags', $tags ),
				$this->metas->meta_for_entry( 'source_repeat', 1 ),
				$this->metas->meta_for_entry( 'revertible_feed_text', $post_array['post_content'] ),

			);
			$this->metas->establish_post( $post_ID, $pf_meta_args );
			pf_log( $pf_meta_args );
			$this->metas->update_pf_meta( $post_ID, 'nom_id', $post_ID );
			$nominators = $this->apply_nomination_data( $post_ID );
			$this->metas->update_pf_meta( $post_ID, 'nominator_array', $nominators );

			// When sorting by Nomination Date, we are only interested in the date of the first nomination.
			$this->metas->update_pf_meta( $post_ID, 'sortable_nom_date', current_time( 'mysql' ) );

			if ( ! empty( $_POST['item_author'] ) ) {
				$item_author = sanitize_text_field( wp_unslash( $_POST['item_author'] ) );
				pressforward( 'controller.metas' )->update_pf_meta( $post_ID, 'item_author', \sanitize_text_field( $item_author ) );
			}

			return $post_ID;
		} else {
			// Do something with the returned ID.
			// Increment the nomination count if the nomination exists.
			$this->nomination_user_transition_check( $nom_and_post_check, $item_id );

			// Update the existing post with values from the bookmarklet, which is assumed more accurate.
			$post['ID'] = $nom_and_post_check;
			$post_check = $this->is_a_pf_type( $item_id, pressforward_draft_post_type() );

			// If this is a nomination but has not yet been published, assume bookmarklet has best version of content.
			if ( ! empty( $post_check ) ) {
				$this->item_interface->update_post( $post );
			}

			if ( ! empty( $_POST['item_author'] ) ) {
				$item_author = sanitize_text_field( wp_unslash( $_POST['item_author'] ) );
				pressforward( 'controller.metas' )->update_pf_meta( $nom_and_post_check, 'item_author', $item_author );
			}

			return $nom_and_post_check;
		}
	}

	/**
	 * Transitions an item from the bookmarklet to a draft.
	 *
	 * @param string $item_id PF ID of the item.
	 * @param array  $post    Array of post data.
	 * @return int
	 */
	public function bookmarklet_to_last_step( $item_id = '', $post = null ) {
		if ( ! $item_id ) {
			$item_link = isset( $_POST['item_link'] ) ? sanitize_text_field( wp_unslash( $_POST['item_link'] ) ) : '';

			$item_id = pressforward_create_feed_item_id( $item_link, $post['post_title'] );
		}
		$nomination_id = $this->bookmarklet_to_nomination( $item_id, $post );
		pf_log( $nomination_id );
		$post_id = $this->nomination_to_last_step( $item_id, $nomination_id, false );

		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
		$result       = pressforward( 'utility.relate' )->basic_relate( 'draft', $nomination_id, 'off', $user_id );

		if ( isset( $_POST['post_category'] ) && ! empty( $_POST['post_category'] ) && ! is_array( $_POST['post_category'] ) ) {
			$categories = array_map( 'intval', explode( ',', sanitize_text_field( wp_unslash( $_POST['post_category'] ) ) ) );
			wp_set_post_categories( $post_id, $categories, false );
			wp_set_post_categories( $nomination_id, $categories, false );
		}

		return $post_id;
	}

	/**
	 * Checks whether an item is of a PressForward type.
	 *
	 * @param string $item_id   ID of the item.
	 * @param string $post_type Optional. Post type of the item.
	 * @param bool   $update    Update.
	 * @return int ID of the WP item,
	 */
	public function is_a_pf_type( $item_id, $post_type = '', $update = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! $post_type ) {
			$post_type = array( pressforward_draft_post_type(), pressforward( 'schema.nominations' )->post_type );
		}

		$attempt = $this->advance_interface->get_pf_type_by_id( $item_id, $post_type );
		if ( ! empty( $attempt ) ) {
			$r = $attempt;
			pf_log( 'Existing post at ' . $r );
		} else {
			$r = 0;
		}

		return $r;
	}
}
