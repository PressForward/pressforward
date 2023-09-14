<?php
/**
 * Classes and functions related to user/feed item relationships
 *
 * Eg, read/unread, favoriting
 *
 * @package PressForward
 */

/**
 * Translates a relationship type string into its int value
 *
 * @param string $relationship_type Relationship type.
 * @return int $relationship_type_id
 */
function pf_get_relationship_type_id( $relationship_type ) {
	// We'll return false if no type is found.
	return pressforward( 'utility.relate' )->get_relationship_type_id( $relationship_type );
}

/**
 * Generic function for setting relationships
 *
 * @param string $relationship_type Relationship type.
 * @param int    $item_id           Item ID.
 * @param int    $user_id           User ID.
 * @param string $value             Value.
 * @return bool True on success
 */
function pf_set_relationship( $relationship_type, $item_id, $user_id, $value ) {

	return pressforward( 'utility.relate' )->set_relationship( $relationship_type, $item_id, $user_id, $value );
}

/**
 * Generic function for deleting relationships
 *
 * @param string $relationship_type Relationship type.
 * @param int    $item_id           Item ID.
 * @param int    $user_id           User ID.
 * @return bool True when a relationship is deleted OR when one is not found in the first place
 */
function pf_delete_relationship( $relationship_type, $item_id, $user_id ) {
	return pressforward( 'utility.relate' )->delete_relationship( $relationship_type, $item_id, $user_id );
}

/**
 * Generic function for getting relationships
 *
 * Note that this returns the relationship object, not the value
 *
 * @param string|int $relationship_type Accepts either numeric key of the relationship type,
 *                                      or a string ('star', 'read', etc) describing the
 *                                      relationship type.
 * @param int        $item_id           Item ID.
 * @param int        $user_id           User ID.
 * @return object|bool The relationship object
 */
function pf_get_relationship( $relationship_type, $item_id, $user_id ) {
	return pressforward( 'utility.relate' )->get_relationship( $relationship_type, $item_id, $user_id );
}

/**
 * Generic function for getting relationship values
 *
 * @param string $relationship_type Relationship type.
 * @param int    $item_id           Item ID.
 * @param int    $user_id           User ID.
 * @return int|bool The relationship value if it exists, false otherwise
 */
function pf_get_relationship_value( $relationship_type, $item_id, $user_id ) {
	return pressforward( 'utility.relate' )->get_relationship_value( $relationship_type, $item_id, $user_id );
}

/**
 * Generic function for getting relationships of a given type for a given user
 *
 * @param string $relationship_type Relationship type.
 * @param int    $user_id           User ID.
 */
function pf_get_relationships_for_user( $relationship_type, $user_id ) {
	return pressforward( 'utility.relate' )->get_relationships_for_user( $relationship_type, $user_id );
}

/**
 * Checks whether an item is starred for a user.
 *
 * @param int $item_id ID of the item.
 * @param int $user_id ID of the user.
 * @return bool
 */
function pf_is_item_starred_for_user( $item_id, $user_id ) {
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
function pf_star_item_for_user( $item_id, $user_id ) {
	return pf_set_relationship( 'star', $item_id, $user_id, '1' );
}

/**
 * Unstars an item for a user.
 *
 * @param int $item_id ID of the item.
 * @param int $user_id ID of the user.
 * @return bool
 */
function pf_unstar_item_for_user( $item_id, $user_id ) {
	return pf_delete_relationship( 'star', $item_id, $user_id );
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
function pf_get_starred_items_for_user( $user_id, $format = 'raw' ) {
	$rs = pf_get_relationships_for_user( 'star', $user_id );

	if ( 'simple' === $format ) {
		$rs = wp_list_pluck( $rs, 'item_id' );
	}

	return $rs;
}

/**
 * Gets a list of read items for a given user.
 *
 * @param int    $user_id User ID.
 * @param string $format  'simple' to get back just the item IDs. Otherwise raw relationship objects.
 * @return array
 */
function pf_get_read_items_for_user( $user_id, $format = 'raw' ) {
	$rs = pf_get_relationships_for_user( 'read', $user_id );

	if ( 'simple' === $format ) {
		$rs = wp_list_pluck( $rs, 'item_id' );
	}

	return $rs;
}
