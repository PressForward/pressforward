<?php

/**
 * Classes and functions related to user/feed item relationships
 *
 * Eg, read/unread, favoriting
 */

/**
 * Database class for interacting with feed item relationships
 */
class PF_RSS_Import_Relationship {
	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'pf_relationships';
	}

	public function create( $args = array() ) {
		global $wpdb;

		$r = wp_parse_args( $args, array(
			'user_id' => 0,
			'item_id' => 0,
			'relationship_type' => 0,
			'value' => '',
			'unique' => true, // Generally you want one entry per user_id+item_id+relationship_type combo
		) );

		if ( $r['unique'] ) {
			$existing = $this->get( $r );
			if ( ! empty( $existing ) ) {
				return false;
			}
		}

		$wpdb->insert(
			$this->table_name,
			array(
				'user_id' => $r['user_id'],
				'item_id' => $r['item_id'],
				'relationship_type' => $r['relationship_type'],
				'value' => $r['value'],
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
			)
		);

		return $wpdb->insert_id;
	}

	/**
	 * We assume that only the value ever needs to change.
	 *
	 * Any other params are interpreted as WHERE conditions
	 */
	public function update( $args = array() ) {
		global $wpdb;

		$r = wp_parse_args( $args, array(
			'id' => 0,
			'user_id' => false,
			'item_id' => false,
			'relationship_type' => false,
			'value' => false,
		) );

		// If an 'id' is passed, use it. Otherwise build a WHERE
		$where = array();
		$where_format = array();
		if ( $r['id'] ) {
			$where['id']    = (int) $r['id'];
			$where_format[] = '%d';
		} else {
			foreach ( $r as $rk => $rv ) {
				if ( in_array( $rk, array( 'id', 'value' ) ) ) {
					continue;
				}

				if ( false !== $rv ) {
					$where[ $rk ]   = $rv;
					$where_format[] = '%d';
				}
			}
		}

		$updated = false;

		// Sanity: Don't allow for empty $where
		if ( ! empty( $where ) ) {
			$updated = $wpdb->update(
				$this->table_name,
				array( 'value' => $r['value'] ),
				$where,
				array( '%s' ),
				$where_format
			);
		}

		return (bool) $updated;
	}

	public function get( $args = array() ) {
		global $wpdb;

		$r = wp_parse_args( $args, array(
			'id' => 0,
			'user_id' => false,
			'item_id' => false,
			'relationship_type' => false,
		) );

		$sql[] = "SELECT * FROM {$this->table_name}";

		// If an ID is passed, use it. Otherwise build WHERE from params
		$where = array();
		if ( $r['id'] ) {
			$where[] = $wpdb->prepare( "id = %d", $r['id'] );
		} else {
			foreach ( $r as $rk => $rv ) {
				if ( ! in_array( $rk, array( 'id', 'unique', 'value' ) ) && false !== $rv ) {
					$where[] = $wpdb->prepare( "{$rk} = %d", $rv );
				}
			}
		}

		if ( ! empty( $where ) ) {
			$sql[] = "WHERE " . implode( " AND ", $where );
		}

		$sql = implode( ' ', $sql );

		return $wpdb->get_results( $sql );

	}

	function delete( $args = array() ) {
		global $wpdb;

		if ( ! empty( $args['id'] ) ) {
			$id = $args['id'];
		} else {
			$relationships = $this->get( $args );
			// Assume it's the first one!
			if ( ! empty( $relationships ) ) {
				$id = $relationships[0]->id;
			}
		}

		$deleted = false;
		if ( $id ) {
			$d = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE id = %d", $id ) );
			$deleted = false !== $d;
		}

		return $deleted;
	}
}

/**
 * Translates a relationship type string into its int value
 *
 * @param string $relationship_type
 * @return int $relationship_type_id
 */
function pf_get_relationship_type_id( $relationship_type ) {
	// Might pay to abstract these out at some point
	$types = array(
		1 => 'read',
		2 => 'star',
		3 => 'archive',
		4 => 'nominate',
		5 => 'draft'
	);

	$types = apply_filters('pf_relationship_types', $types);

	$relationship_type_id = array_search( $relationship_type, $types );

	// We'll return false if no type is found
	return $relationship_type_id;
}

/**
 * Generic function for setting relationships
 *
 * @param string $relationship_type
 * @param int $item_id
 * @param int $user_id
 * @param string value
 * @return bool True on success
 */
function pf_set_relationship( $relationship_type, $item_id, $user_id, $value ) {
	$existing = pf_get_relationship( $relationship_type, $item_id, $user_id );

	$relationship = new PF_RSS_Import_Relationship();

	// Translate relationship type
	$relationship_type_id = pf_get_relationship_type_id( $relationship_type );

	$params = array(
		'relationship_type' => $relationship_type_id,
		'item_id' => $item_id,
		'user_id' => $user_id,
		'value' => $value,
	);

	if ( ! empty( $existing ) ) {
		$params['id'] = $existing->id;
		$retval = $relationship->update( $params );
	} else {
		$retval = $relationship->create( $params );
	}

	return $retval;
}

/**
 * Generic function for deleting relationships
 *
 * @param string $relationship_type
 * @param int $item_id
 * @param int $user_id
 * @return bool True when a relationship is deleted OR when one is not found in the first place
 */
function pf_delete_relationship( $relationship_type, $item_id, $user_id ) {
	$deleted = false;
	$existing = pf_get_relationship( $relationship_type, $item_id, $user_id );

	if ( empty( $existing ) ) {
		$deleted = true;
	} else {
		$relationship = new PF_RSS_Import_Relationship();
		$deleted = $relationship->delete( array( 'id' => $existing->id ) );
	}

	return $deleted;
}

/**
 * Generic function for getting relationships
 *
 * Note that this returns the relationship object, not the value
 *
 * @param string|int $relationship_type Accepts either numeric key of the
 *   relationship type, or a string ('star', 'read', etc) describing the
 *   relationship type
 * @param int $item_id
 * @param int $user_id
 * @return object The relationship object
 */
function pf_get_relationship( $relationship_type, $item_id, $user_id ) {
	$relationship = new PF_RSS_Import_Relationship();

	// Translate relationship type to its integer index, if necessary
	if ( is_string( $relationship_type ) ) {
		$relationship_type_id = pf_get_relationship_type_id( $relationship_type );
	} else {
		$relationship_type_id = (int) $relationship_type;
	}

	$existing = $relationship->get( array(
		'relationship_type' => $relationship_type_id,
		'item_id' => $item_id,
		'user_id' => $user_id,
	) );

	$retval = false;

	if ( ! empty( $existing ) ) {
		// Take the first result for now
		$retval = $existing[0];
	}

	return $retval;
}

/**
 * Generic function for getting relationship values
 *
 * @param string $relationship_type
 * @param int $item_id
 * @param int $user_id
 * @return string|bool The relationship value if it exists, false otherwise
 */
function pf_get_relationship_value( $relationship_type, $item_id, $user_id ) {
	$r = pf_get_relationship( $relationship_type, $item_id, $user_id );

	if ( ! empty( $r ) ) {
		$retval = $r->value;
	} else {
		$retval = false;
	}

	return $retval;
}

/**
 * Generic function for getting relationships of a given type for a given user
 *
 * @param string $relationship_type
 * @param int $user_id
 */
function pf_get_relationships_for_user( $relationship_type, $user_id ) {
	$relationship = new PF_RSS_Import_Relationship();
	$relationship_type_id = pf_get_relationship_type_id( $relationship_type );

	$rs = $relationship->get( array(
		'relationship_type' => $relationship_type_id,
		'user_id' => $user_id,
	) );

	return $rs;
}

////////////////////////////////
//          "STAR"            //
////////////////////////////////

function pf_is_item_starred_for_user( $item_id, $user_id ) {
	$v = pf_get_relationship_value( 'star', $item_id, $user_id );
	return 1 == $v;
}

function pf_star_item_for_user( $item_id, $user_id ) {
	return pf_set_relationship( 'star', $item_id, $user_id, '1' );
}

function pf_unstar_item_for_user( $item_id, $user_id ) {
	return pf_delete_relationship( 'star', $item_id, $user_id );
}

/**
 * Function for AJAX action to mark an item as starred or unstarred.
 *
 */
add_action( 'wp_ajax_pf_ajax_star', 'pf_ajax_star');
function pf_ajax_star(){
	$item_id = $_POST['post_id'];
	$userObj = wp_get_current_user();
	$user_id = $userObj->ID;
	$result = 'nada';
	if ( 1 != pf_is_item_starred_for_user( $item_id, $user_id )){
		$result = pf_star_item_for_user( $item_id, $user_id );
	} else {
		$result = pf_unstar_item_for_user( $item_id, $user_id );
	}

	ob_start();
	$response = array(
			'what' => 'relationships',
			'action' => 'pf_ajax_star',
			'id' => $item_id,
			'data' => $result,
			'supplemental' => array(
					'user' => $user_id,
					'buffered' => ob_get_contents()
				)
			);

	$xmlResponse = new WP_Ajax_Response($response);
	$xmlResponse->send();
	ob_end_flush();
	die();

}

/**
 * Get a list of starred items for a given user
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
 * @param int $user_id
 * @param string $format 'simple' to get back just the item IDs. Otherwise raw relationship objects
 */
function pf_get_starred_items_for_user( $user_id, $format = 'raw' ) {
	$rs = pf_get_relationships_for_user( 'star', $user_id );

	if ( 'simple' == $format ) {
		$rs = wp_list_pluck( $rs, 'item_id' );
	}

	return $rs;
}

/**
 * A generalized function for setting/unsetting a relationship via ajax
 *
 */
add_action( 'wp_ajax_pf_ajax_relate', 'pf_ajax_relate');
function pf_ajax_relate(){
	pf_log( 'Invoked: pf_ajax_relate()' );
	$item_id = $_POST['post_id'];
	$relationship_type = $_POST['schema'];
	$switch = $_POST['isSwitch'];
	$userObj = wp_get_current_user();
	$user_id = $userObj->ID;
	$result = 'nada';
	pf_log( 'pf_ajax_relate - received: ID = '.$item_id.', Schema = '.$relationship_type.', isSwitch = '.$switch.', userID = '.$user_id.'.' );
	if ( 1 != pf_get_relationship_value( $relationship_type, $item_id, $user_id )){
		$result = pf_set_relationship( $relationship_type, $item_id, $user_id, '1' );
		pf_log('pf_ajax_relate - set: relationship on');
	} else {
		if($switch == 'on'){
			$result = pf_delete_relationship( $relationship_type, $item_id, $user_id );
			pf_log('pf_ajax_relate - set: relationship off');
		} else {
			$result = 'unswitchable';
			pf_log('pf_ajax_relate - set: relationship unswitchable');
		}
	}

	ob_start();
	$response = array(
			'what' => 'relationships',
			'action' => 'pf_ajax_relate',
			'id' => $item_id,
			'data' => $result,
			'supplemental' => array(
					'user' => $user_id,
					'buffered' => ob_get_contents()
				)
			);

	$xmlResponse = new WP_Ajax_Response($response);
	$xmlResponse->send();
	ob_end_flush();
	die();



}
