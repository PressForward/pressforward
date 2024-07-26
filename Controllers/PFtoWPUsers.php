<?php
/**
 * User utilities.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

/**
 * User utilities.
 */
class PFtoWPUsers implements \PressForward\Interfaces\SystemUsers {
	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas $metas
	 */
	public $metas;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\Metas $metas Metas object.
	 */
	public function __construct( Metas $metas ) {
		$this->metas = $metas;
	}

	/**
	 * Checks whether a user nominated a given post.
	 *
	 * @param int $post_id ID of the nominated item.
	 * @param int $user_id ID of the user. Optional. Defaults to current user.
	 * @return bool
	 */
	public function did_user_nominate( $post_id, $user_id = 0 ) {
		$nominators = $this->metas->get_post_pf_meta( $post_id, 'nominator_array' );
		if ( ! $user_id ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		}

		// @todo The $nominators array may need to be cast to int before doing a strict check.
		// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
		if ( ! empty( $nominators ) && in_array( $user_id, $nominators ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Wrapper for get_user_option().
	 *
	 * @param string $option Option name.
	 * @return mixed
	 */
	public function get_user_option( $option ) {
		return get_user_option( $option );
	}

	/**
	 * Wrapper for current_user_can().
	 *
	 * @param string $capability Capability to check.
	 * @return bool
	 */
	public function current_user_can( $capability ) {
		return current_user_can( $capability );
	}

	/**
	 * Wrapper for get_current_user_id().
	 *
	 * @return int
	 */
	public function get_current_user_id() {
		return get_current_user_id();
	}

	/**
	 * Get an object with capabilities as keys pointing to roles that contain those capabilities.
	 *
	 * @since 3.x
	 *
	 * @param string|bool $cap Optional. If given, the function will return a set of roles that have that capability.
	 *
	 * @return array $role_reversal An array with capailities as keys pointing to what roles they match to.
	 */
	public function pf_get_capabilities( $cap = false ) {
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
	public function pf_get_role_by_capability( $cap, $lowest = true, $obj = false ) {
		// Get set of roles for capability.
		$roles = $this->pf_get_capabilities( $cap );

		// We probably want to get the lowest role with that capability.
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
	 * @param string|array $role_slug The slug for the role being checked against.
	 * @return string The slug for the defining capability of the given role.
	 */
	public function pf_get_defining_capability_by_role( $role_slug ) {
		$pf_use_advanced_user_roles = get_option( 'pf_use_advanced_user_roles', 'no' );
		// For those who wish to ignore the super-cool auto-detection for fringe-y sites that
		// let their user capabilities go wild.
		if ( 'no' !== $pf_use_advanced_user_roles ) {
			$caps = $this->pf_get_capabilities();
			foreach ( $caps as $slug => $cap ) {
				$low_role = $this->pf_get_role_by_capability( $slug );
				// Return the first capability only applicable to that role.
				if ( $role_slug === $low_role ) {
					return $slug;
				}
			}
		}

		// If we get multiple roles back, grab the first one.
		if ( is_array( $role_slug ) ) {
			$role_slug = $role_slug[0];
		}

		/*
		 * Even if we use $pf_use_advanced_user_roles, if it doesn't find
		 * any actual lowest option (like it is the case with contributor
		 * currently), it should still go to the default ones below.
		 */
		$role_slug = strtolower( $role_slug );
		switch ( $role_slug ) {
			case 'administrator':
				return 'manage_options';

			case 'editor':
				return 'edit_others_posts';

			case 'author':
				return 'publish_posts';

			case 'contributor':
				return 'edit_posts';

			case 'subscriber':
				return 'read';
		}

		return '';
	}

	/**
	 * Maps PF capabilities to a role.
	 *
	 * @param string $cap       Capability.
	 * @param string $role_slug Role.
	 */
	public function pf_capability_mapper( $cap, $role_slug ) {
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

	/**
	 * Gets a user setting.
	 *
	 * @param int    $user_id User ID.
	 * @param string $setting Setting name.
	 * @return mixed
	 */
	public function get_user_setting( $user_id, $setting ) {
		switch ( $setting ) {
			case 'nomination-success-email-toggle':
				$saved = get_user_option( 'pf_nomination_success_email_toggle', $user_id );
				if ( false === $saved ) {
					$saved = get_option( 'pf_user_nomination_success_email_default', 'off' );
				}

				return 'on' === $saved;

			case 'nomination-promoted-email-toggle':
				$saved = get_user_option( 'pf_nomination_promoted_email_toggle', $user_id );
				if ( false === $saved ) {
					$saved = get_option( 'pf_user_nomination_promoted_email_default', 'off' );
				}

				return 'on' === $saved;

			case 'item-comment-email-toggle':
				$saved = get_user_option( 'pf_item_comment_email_toggle', $user_id );
				if ( false === $saved ) {
					$saved = get_option( 'pf_user_item_comment_email_default', 'off' );
				}

				return 'on' === $saved;
		}
	}

	/**
	 * Assigns PressForward capabilities to the default WP roles.
	 *
	 * @todo This method is not called anywhere in PF and should be removed.
	 *
	 * @return void
	 */
	public function assign_pf_to_standard_roles() {
		$roles = array(
			'administrator',
			'editor',
			'author',
			'contributor',
			'subscriber',
		);

		$caps = $this->pf_get_capabilities();

		foreach ( $caps as $cap => $role ) {
			foreach ( $role as $a_role ) {
				$this->pf_capability_mapper( $cap, $a_role );
			}
		}
	}

	/**
	 * Gets the user capability for a given PF action type.
	 *
	 * @param string $option_name Option name.
	 * @param string $role        Role name.
	 * @return string
	 */
	public function user_level( $option_name, $role ) {
		return get_option( $option_name, $this->pf_get_defining_capability_by_role( $role ) );
	}

	/**
	 * Wrapper for wp_get_current_user().
	 *
	 * @return \WP_User
	 */
	public function get_current_user() {
		return wp_get_current_user();
	}

	/**
	 * Wrapper for get_user_meta().
	 *
	 * @param int    $user_id  ID of the user.
	 * @param string $meta_key Meta key.
	 * @param bool   $single   Whether to return a single row.
	 */
	public function get_user_meta( $user_id, $meta_key, $single = true ) {
		return get_user_meta( $user_id, $meta_key, $single );
	}

	/**
	 * Wrapper for update_user_meta().
	 *
	 * @param int    $user_id    ID of the user.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @param mixed  $prev_value Optional. Previous value.
	 */
	public function update_user_meta( $user_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_user_meta( $user_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Gets the text for the footer of an email notification.
	 *
	 * @since 5.4.0
	 *
	 * @return string
	 */
	public function get_email_notification_footer() {
		$footer = sprintf(
			// translators: 1. Site name. 2. Settings URL.
			__(
				'---

You are subscribed to receive emails from %1$s. To change your email preferences, visit %2$s.',
				'pressforward'
			),
			get_bloginfo( 'blogname' ),
			admin_url( 'admin.php?page=pf-options#user' )
		);

		return $footer;
	}
}
