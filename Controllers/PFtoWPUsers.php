<?php
namespace PressForward\Controllers;

use PressForward\Interfaces\SystemUsers as SystemUsers;

class PFtoWPUsers implements SystemUsers {

	function __construct( Metas $metas ) {
		// var_dump('a');
		$this->metas = $metas;
	}

	public function did_user_nominate( $post_id, $user_id = false ) {
		$nominators = $this->metas->get_post_pf_meta( $id, 'nominator_array' );
		if ( ! $user_id ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		}
		if ( ! empty( $nominators ) && in_array( $user_id, $nominators ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function get_user_option( $option ) {
		return get_user_option( $option );
	}

	public function current_user_can( $capacity ) {
		return current_user_can( $capacity );
	}

	public function get_current_user_id() {
		return get_current_user_id();
	}

	/**
	 * Get an object with capabilities as keys pointing to roles that contain those capabilities.
	 *
	 * @since 3.x
	 *
	 * @param string $cap Optional. If given, the function will return a set of roles that have that capability.
	 *
	 * @return array $role_reversal An array with capailities as keys pointing to what roles they match to.
	 */

	function pf_get_capabilities( $cap = false ) {
		// Get the WP_Roles object.
		global $wp_roles;
		// Set up array for storage.
		$role_reversal = array();
		// Walk through the roles object by role and get capabilities.
		foreach ( $wp_roles->roles as $role_slug => $role_set ) {

			foreach ( $role_set['capabilities'] as $capability => $cap_bool ) {
				// Don't store a capability if it is false for the role (though none are).
				if ( $cap_bool ) {
					$role_reversal[ $capability ][] = $role_slug;
				}
			}
		}
		// Allow users to get specific capabilities.
		if ( ! $cap ) {
			return $role_reversal;
		} else {
			return $role_reversal[ $cap ];
		}
	}

	/**
	 * Request a role string or object by asking for its capability.
	 *
	 * Function allows the user to find out a role by a capability that it holds.
	 * The user may specify the higest role with that capability or the lowest.
	 * The lowest is the default.
	 *
	 * @since 3.x
	 *
	 * @param string $cap The slug for the capacity being checked against.
	 * @param bool   $lowest Optional. If the function should return the lowest capable role. Default true.
	 * @param bool   $obj Optional. If the function should return a role object instead of a string. Default false.
	 *
	 * @return string|object Returns either the string name of the role or the WP object created by get_role.
	 */

	function pf_get_role_by_capability( $cap, $lowest = true, $obj = false ) {
		// Get set of roles for capability.
		$roles = $this->pf_get_capabilities( $cap );
		// We probobly want to get the lowest role with that capability
		if ( $lowest ) {
			$roles = array_reverse( $roles );
		}
		$arrayvalues = array_values( $roles );
		$the_role    = array_shift( $arrayvalues );
		if ( ! $obj ) {
			return $the_role;
		} else {
			return get_role( $the_role );
		}

	}


	/**
	 * Get the capability that uniquely matches a specific role.
	 *
	 * If we want to allow users to set access by role, we need to give users the names
	 * of all roles. But WordPress takes capabilities. This function matches the role with
	 * its first capability, so users can set by Role but WordPress takes capability.
	 *
	 * However, it will check against the system options and either attempt to return
	 * this information based on WordPress defaults or by checking the current system.
	 *
	 * @since 3.x
	 *
	 * @param string $role_slug The slug for the role being checked against.
	 *
	 * @return string The slug for the defining capability of the given role.
	 */
	function pf_get_defining_capability_by_role( $role_slug ) {
		$pf_use_advanced_user_roles = get_option( 'pf_use_advanced_user_roles', 'no' );
		// For those who wish to ignore the super-cool auto-detection for fringe-y sites that
		// let their user capabilities go wild.
		if ( 'no' != $pf_use_advanced_user_roles ) {
			$caps = $this->pf_get_capabilities();
			foreach ( $caps as $slug => $cap ) {
				$low_role = $this->pf_get_role_by_capability( $slug );
				// Return the first capability only applicable to that role.
				if ( $role_slug == ( $low_role ) ) {
					return $slug;
				}
			}
		}
		// Even if we use $pf_use_advanced_user_roles, if it doesn't find any actual lowest option (like it is the case with contributor currently), it should still go to the default ones below
		$role_slug = strtolower( $role_slug );
		switch ( $role_slug ) {
			case 'administrator':
				return 'manage_options';
				break;
			case 'editor':
				return 'edit_others_posts';
				break;
			case 'author':
				return 'publish_posts';
				break;
			case 'contributor':
				return 'edit_posts';
				break;
			case 'subscriber':
				return 'read';
				break;
		}
	}

	function pf_capability_mapper( $cap, $role_slug ) {
		$feed_caps      = pressforward( 'schema.feeds' )->map_feed_caps();
		$feed_item_caps = pressforward( 'schema.feed_item' )->map_feed_item_caps();
		if ( array_key_exists( $cap, $feed_caps ) ) {
			$role = get_role( $role_slug );
			$role->add_cap( $feed_caps[ $cap ] );
		}
		if ( array_key_exists( $cap, $feed_item_caps ) ) {
			$role = get_role( $role_slug );
			$role->add_cap( $feed_item_caps[ $cap ] );
		}
	}

	function assign_pf_to_standard_roles() {
		$roles = array(
			'administrator',
			'editor',
			'author',
			'contributor',
			'subscriber',
		);
		$caps  = $this->pf_get_capabilities();
		// $feed_caps = pressforward('schema.feeds')->map_feed_caps();
		// $feed_item_caps = pressforward()->schema->map_feed_item_caps();
		foreach ( $caps as $cap => $role ) {
			foreach ( $role as $a_role ) {
				$this->pf_capability_mapper( $cap, $a_role );
			}
		}
	}

	public function user_level( $option_name, $role ) {
		return get_option( $option_name, $this->pf_get_defining_capability_by_role( $role ) );
	}

}
