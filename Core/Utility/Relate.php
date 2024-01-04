<?php
/**
 * Relationship utilities.
 *
 * @package PressForward
 */

namespace PressForward\Core\Utility;

use PressForward\Interfaces\Items;
use PressForward\Interfaces\Advance_System;
use PressForward\Controllers\Metas;
use PressForward\Core\Schema\Relationships;
use WP_Query;
use WP_Ajax_Response;

/**
 * PressForward relationships tools.
 */
class Relate implements \Intraxia\Jaxion\Contract\Core\HasActions {
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
	 * Relationships object.
	 *
	 * @access public
	 * @var \PressForward\Core\Schema\Relationships
	 */
	public $relationships;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Interfaces\Items          $item_interface      Items object.
	 * @param \PressForward\Interfaces\Advance_System $advance_interface   Advance_System object.
	 * @param \PressForward\Controllers\Metas         $meta_interface      Metas object.
	 * @param \PressForward\Core\Schema\Relationships $relationship_schema Relationships object.
	 */
	public function __construct( Items $item_interface, Advance_System $advance_interface, Metas $meta_interface, Relationships $relationship_schema ) {
		$this->item_interface    = $item_interface;
		$this->advance_interface = $advance_interface;
		$this->metas             = $meta_interface;
		$this->relationships     = $relationship_schema;
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'wp_ajax_pf_archive_nominations',
				'method' => 'archive_nominations',
			),
			array(
				'hook'   => 'wp_ajax_pf_ajax_relate',
				'method' => 'ajax_relate',
			),
			array(
				'hook'   => 'wp_ajax_pf_ajax_star',
				'method' => 'ajax_star',
			),
		);
	}

	/**
	 * Translates a relationship type string into its int value.
	 *
	 * @param string $relationship_type Relationship type.
	 * @return int $relationship_type_id
	 */
	public function get_relationship_type_id( $relationship_type ) {
		// Might pay to abstract these out at some point.
		$types = array(
			1 => 'read',
			2 => 'star',
			3 => 'archive',
			4 => 'nominate',
			5 => 'draft',
		);

		$types = apply_filters( 'pf_relationship_types', $types );

		$relationship_type_id = array_search( $relationship_type, $types, true );

		// We'll return false if no type is found.
		return $relationship_type_id;
	}

	/**
	 * Generic function for setting relationships.
	 *
	 * @param string $relationship_type Relationship type.
	 * @param int    $item_id           Item ID.
	 * @param int    $user_id           User ID.
	 * @param string $value             Value.
	 *
	 * @return bool True on success
	 */
	public function set_relationship( $relationship_type, $item_id, $user_id, $value ) {
		$existing = pf_get_relationship( $relationship_type, $item_id, $user_id );

		$relationship = $this->relationships;

		// Translate relationship type.
		$relationship_type_id = pf_get_relationship_type_id( $relationship_type );

		$params = array(
			'relationship_type' => $relationship_type_id,
			'item_id'           => $item_id,
			'user_id'           => $user_id,
			'value'             => $value,
		);

		if ( ! is_bool( $existing ) ) {
			$params['id'] = $existing->id;
			$retval       = $relationship->update( $params );
		} else {
			$retval = $relationship->create( $params );
		}

		return $retval;
	}

	/**
	 * Generic function for deleting relationships.
	 *
	 * @param string $relationship_type Relationship type.
	 * @param int    $item_id           Item ID.
	 * @param int    $user_id           User ID.
	 * @return bool True when a relationship is deleted OR when one is not found in the first place
	 */
	public function delete_relationship( $relationship_type, $item_id, $user_id ) {
		$deleted  = false;
		$existing = pf_get_relationship( $relationship_type, $item_id, $user_id );

		if ( empty( $existing ) ) {
			$deleted = true;
		} elseif ( ! is_bool( $existing ) ) {
			$relationship = $this->relationships;
			$deleted      = $relationship->delete( array( 'id' => $existing->id ) );
		}

		return $deleted;
	}

	/**
	 * Generic function for getting relationships.
	 *
	 * Note that this returns the relationship object, not the value.
	 *
	 * @param string|int $relationship_type Accepts either numeric key of the
	 *                                      relationship type, or a string ('star', 'read', etc) describing the
	 *                                      relationship type.
	 * @param int        $item_id           Item ID.
	 * @param int        $user_id           User ID.
	 * @return object|bool The relationship object
	 */
	public function get_relationship( $relationship_type, $item_id, $user_id ) {
		$relationship = $this->relationships;

		// Translate relationship type to its integer index, if necessary.
		if ( is_string( $relationship_type ) ) {
			$relationship_type_id = pf_get_relationship_type_id( $relationship_type );
		} else {
			$relationship_type_id = (int) $relationship_type;
		}

		$existing = $relationship->get(
			array(
				'relationship_type' => $relationship_type_id,
				'item_id'           => $item_id,
				'user_id'           => $user_id,
			)
		);

		$retval = false;

		if ( ! empty( $existing ) ) {
			// Take the first result for now.
			$retval = $existing[0];
		}

		return $retval;
	}

	/**
	 * Generic function for getting relationship values.
	 *
	 * @param string $relationship_type Relationship type.
	 * @param int    $item_id           Item ID.
	 * @param int    $user_id           User ID.
	 * @return int|bool The relationship value if it exists, false otherwise.
	 */
	public function get_relationship_value( $relationship_type, $item_id, $user_id ) {
		$r = pf_get_relationship( $relationship_type, $item_id, $user_id );

		if ( ! is_bool( $r ) ) {
			$retval = (int) $r->value;
		} else {
			$retval = false;
		}

		return $retval;
	}

	/**
	 * Generic function for getting relationships of a given type for a given user.
	 *
	 * @param string $relationship_type Relationship type.
	 * @param int    $user_id           User ID.
	 */
	public function get_relationships_for_user( $relationship_type, $user_id ) {
		$relationship         = $this->relationships;
		$relationship_type_id = pf_get_relationship_type_id( $relationship_type );

		$rs = $relationship->get(
			array(
				'relationship_type' => $relationship_type_id,
				'user_id'           => $user_id,
			)
		);

		return $rs;
	}

	/**
	 * Checks whether an item is starred for a user.
	 *
	 * @param int $item_id ID of the item.
	 * @param int $user_id ID of the user.
	 * @return bool
	 */
	public function is_item_starred_for_user( $item_id, $user_id ) {
		$v = (int) pf_get_relationship_value( 'star', $item_id, $user_id );
		return 1 === $v;
	}

	/**
	 * Stars an item for a user.
	 *
	 * @param int $item_id ID of the item.
	 * @param int $user_id ID of the user.
	 * @return bool
	 */
	public function star_item_for_user( $item_id, $user_id ) {
		return pf_set_relationship( 'star', $item_id, $user_id, '1' );
	}

	/**
	 * Unstars an item for a user.
	 *
	 * @param int $item_id ID of the item.
	 * @param int $user_id ID of the user.
	 * @return bool
	 */
	public function unstar_item_for_user( $item_id, $user_id ) {
		return pf_delete_relationship( 'star', $item_id, $user_id );
	}

	/**
	 * Function for AJAX action to mark an item as starred or unstarred.
	 */
	public function ajax_star() {
		$item_id  = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$user_obj = wp_get_current_user();
		$user_id  = $user_obj->ID;
		$result   = 'nada';
		if ( 1 !== (int) pf_is_item_starred_for_user( $item_id, $user_id ) ) {
			$result = pf_star_item_for_user( $item_id, $user_id );
		} else {
			$result = pf_unstar_item_for_user( $item_id, $user_id );
		}

		ob_start();
		$response = array(
			'what'         => 'relationships',
			'action'       => 'pf_ajax_star',
			'id'           => $item_id,
			'data'         => $result,
			'supplemental' => array(
				'user'     => $user_id,
				'buffered' => ob_get_contents(),
			),
		);

		$xml_response = new WP_Ajax_Response( $response );
		$xml_response->send();
	}

	/**
	 * Get a list of starred items for a given user.
	 *
	 * Use this function in conjunction with PF_Feed_Item:
	 *
	 *    $starred_item_ids = pf_get_starred_items_for_user( $user_id, 'simple' );
	 *
	 *    $feed_item = new PF_Feed_Item();
	 *    $items = $feed_item->get( array(
	 *        'post__in' => $starred_item_ids
	 *    ) );
	 *
	 * @param int    $user_id User ID.
	 * @param string $format  'simple' to get back just the item IDs. Otherwise raw relationship objects.
	 */
	public function get_starred_items_for_user( $user_id, $format = 'raw' ) {
		$rs = pf_get_relationships_for_user( 'star', $user_id );

		if ( 'simple' === $format ) {
			$rs = wp_list_pluck( $rs, 'item_id' );
		}

		return $rs;
	}

	/**
	 * Performs a transition of relationship status.
	 *
	 * @param string $relationship_type Relationship type.
	 * @param int    $item_post_id      ID of the item.
	 * @param string $switch_value      'on' to turn on.
	 * @param int    $user_id           Defaults to currently logged-in user.
	 */
	public function basic_relate( $relationship_type, $item_post_id, $switch_value, $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_obj = wp_get_current_user();
			$user_id  = $user_obj->ID;
		}

		if ( 1 !== (int) pf_get_relationship_value( $relationship_type, $item_post_id, $user_id ) ) {
			$result = pf_set_relationship( $relationship_type, $item_post_id, $user_id, '1' );
			pf_log( 'pf_ajax_relate - set: relationship on' );
		} elseif ( 'on' === $switch_value ) {
			$result = pf_delete_relationship( $relationship_type, $item_post_id, $user_id );
			pf_log( 'pf_ajax_relate - set: relationship off' );
		} else {
			$result = 'unswitchable';
			pf_log( 'pf_ajax_relate - set: relationship unswitchable' );
		}

		return $result;
	}

	/**
	 * A generalized function for setting/unsetting a relationship via ajax.
	 */
	public function ajax_relate() {
		pf_log( 'Invoked: pf_ajax_relate()' );
		$item_id           = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$relationship_type = isset( $_POST['schema'] ) ? sanitize_text_field( wp_unslash( $_POST['schema'] ) ) : '';
		$switch            = isset( $_POST['isSwitch'] ) ? sanitize_text_field( wp_unslash( $_POST['isSwitch'] ) ) : '';
		$user_obj          = wp_get_current_user();
		$user_id           = $user_obj->ID;
		$result            = 'nada';

		pf_log( 'pf_ajax_relate - received: ID = ' . $item_id . ', Schema = ' . $relationship_type . ', isSwitch = ' . $switch . ', userID = ' . $user_id . '.' );

		$result = $this->basic_relate( $relationship_type, $item_id, $switch, $user_id );

		ob_start();
		$response = array(
			'what'         => 'relationships',
			'action'       => 'pf_ajax_relate',
			'id'           => $item_id,
			'data'         => $result,
			'supplemental' => array(
				'user'     => $user_id,
				'buffered' => ob_get_contents(),
			),
		);

		$xml_response = new WP_Ajax_Response( $response );
		$xml_response->send();
	}

	/**
	 * AJAX handler for 'wp_ajax_pf_archive_nominations' action.
	 *
	 * @param bool $limit Limit.
	 */
	public function archive_nominations( $limit = false ) {
		global $wpdb, $post;

		$args = array(
			'post_type'      => 'nomination',
			'posts_per_page' => -1,
		);

		$date_limit = null;
		if ( isset( $_POST['date_limit'] ) ) {
			$date_limit = sanitize_text_field( wp_unslash( $_POST['date_limit'] ) );

			switch ( $date_limit ) {
				case '1week':
					$before = '1 week ago';
					break;
				case '2weeks':
					$before = '2 weeks ago';
					break;
				case '1month':
					$before = array( 'month' => (int) gmdate( 'm' ) - 1 );
					break;
				case '1year':
					$before = array( 'year' => (int) gmdate( 'Y' ) - 1 );
					break;
			}

			if ( isset( $before ) ) {
				$args['date_query'] = array(
					'before' => $before,
				);
			}
		} elseif ( false !== $limit ) {
			$date_limit = $limit;

			switch ( $date_limit ) {
				case '1week':
					$before = array( 'week' => (int) gmdate( 'W' ) - 1 );
					break;
				case '2weeks':
					$before = array( 'week' => (int) gmdate( 'W' ) - 2 );
					break;
				case '1month':
					$before = array( 'month' => (int) gmdate( 'm' ) - 1 );
					break;
				case '1year':
					$before = array( 'year' => (int) gmdate( 'Y' ) - 1 );
					break;
			}

			if ( isset( $before ) ) {
				$args['date_query'] = array(
					'before' => $before,
				);
			}
		}

		$q = new WP_Query( $args );

		$id_list = '';

		$user_id = get_current_user_id();

		if ( $q->have_posts() ) {
			while ( $q->have_posts() ) {
				$q->the_post();

				// This takes the $post objects and translates them into something I can do the standard WP functions on.
				$post_id  = get_the_ID();
				$id_list .= get_the_title() . ',';

				// Switch the delete on to wipe rss archive posts from the database for testing.
				pf_set_relationship( 'archive', $post_id, $user_id, '1' );
				pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_archive', 1 );
			}
		}

		wp_reset_postdata();
		ob_start();
		$response = array(
			'what'         => 'relationships',
			'action'       => 'pf_archive_all_nominations',
			'id'           => $user_id,
			'data'         => 'Archives deleted: ' . $id_list,
			'supplemental' => array(
				'user'     => $user_id,
				'buffered' => ob_get_contents(),
				'query'    => $date_limit,
			),
		);

		$xml_response = new WP_Ajax_Response( $response );
		$xml_response->send();
	}
}
