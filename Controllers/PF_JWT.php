<?php
/**
 * JWT tools.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

use PressForward\Interfaces\System;
use PressForward\Interfaces\SystemUsers;

/**
 * JWT functionality.
 */
class PF_JWT {
	/**
	 * SystemUsers object.
	 *
	 * @access public
	 * @var \PressForward\Interfaces\SystemUsers
	 */
	public $system_users;

	/**
	 * System object.
	 *
	 * @access public
	 * @var \PressForward\Interfaces\System
	 */
	public $system;

	/**
	 * JWT object.
	 *
	 * @access public
	 * @var \Firebase\JWT\JWT
	 */
	public $jwt;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Interfaces\SystemUsers $users  SystemUsers object.
	 * @param \PressForward\Interfaces\System      $system Systemobject.
	 */
	public function __construct( SystemUsers $users, System $system ) {
		$this->system_users = $users;
		$this->system       = $system;
		$this->jwt          = new \Firebase\JWT\JWT();
	}

	/**
	 * Returns a random string based on an input.
	 *
	 * @param int $num Number.
	 * @throws \TypeError|\Error|\Exception On error.
	 * @return string
	 */
	public function random_bytes( $num ) {
		if ( function_exists( 'random_bytes' ) ) {
			try {
				$value = random_bytes( $num );
			} catch ( \Exception $e ) {
				// If you get this message, the CSPRNG failed hard.
				die( esc_html__( 'Could not generate a random string. Is our OS secure?', 'pressforward' ) );
			}
			return bin2hex( $value );
		} else {
			throw new \Exception( esc_html__( 'PressForward cannot provide a cryptographically secure API key.', 'pressforward' ), 1 );
		}
	}

	/**
	 * Gets the system key.
	 *
	 * Creates one if it doesn't exist.
	 *
	 * @return int
	 */
	public function system_key() {
		$system_key = $this->system->get_option( 'pf_system_jwt_key', false );
		if ( ! $system_key ) {
			$system_key = $this->random_bytes( 64 );
			$this->system->update_option( 'pf_system_jwt_key', $system_key );
		}
		return $system_key;
	}

	/**
	 * Encodes a token.
	 *
	 * @param string $token Token.
	 * @param string $key   Key.
	 * @param string $alg   Algorithm.
	 * @return string
	 */
	public function encode_with_jwt( $token, $key, $alg = 'HS256' ) {
		return $this->jwt->encode( $token, $key, $alg );
	}

	/**
	 * Decodes a token.
	 *
	 * @param string $token Token.
	 * @param string $key   Key.
	 * @return bool|object
	 */
	public function decode_with_jwt( $token, $key ) {
		try {
			$decode = $this->jwt->decode( $token, $key );
		} catch ( \Exception $e ) {
			return false;
		}
		return $decode;
	}

	/**
	 * Make a public key.
	 *
	 * @param bool $is_new Whether we're in make-new mode.
	 * @return string $key
	 */
	public function make_a_public_key( $is_new = false ) {
		$key_seed = 'pf';

		$user                    = $this->system_users->get_current_user();
		$unique_strings_to_users = $this->system->get_option( 'pf_jwt_users', array() );
		if ( $is_new || ! array_key_exists( 'by_id', $unique_strings_to_users ) || ! array_key_exists( $user->ID, $unique_strings_to_users['by_id'] ) ) {
			$user_key = sanitize_key( $this->random_bytes( wp_rand( 6, 12 ) ) );
			if ( ! empty( $unique_strings_to_users['by_id'] ) && array_key_exists( $user->ID, $unique_strings_to_users['by_id'] ) ) {
				// We need to unset the old version because we are in make-new mode.
				$old_user_key = $unique_strings_to_users['by_id'][ $user->ID ];
				unset( $unique_strings_to_users['by_key'][ $old_user_key ] );
				unset( $unique_strings_to_users['by_id'][ $user->ID ] );
			}
			$unique_strings_to_users['by_key'][ $user_key ] = $user->ID;
			$unique_strings_to_users['by_id'][ $user->ID ]  = $user_key;
			$this->system->update_option( 'pf_jwt_users', $unique_strings_to_users );
		} else {
			$user_key = $unique_strings_to_users['by_id'][ $user->ID ];
		}
		$user_seed = $user_key;

		$key_set = array(
			'key_seed'  => $key_seed,
			'user_seed' => $user_seed,
		);
		$key     = $this->encode_with_jwt( $key_set, $this->system_key() );
		return $key;
	}

	/**
	 * Gets a user's public key.
	 *
	 * @param int  $user_id ID of the user.
	 * @param bool $is_new     Whether this is new.
	 * @return string
	 */
	public function get_a_user_public_key( $user_id = 0, $is_new = false ) {
		if ( ! $user_id ) {
			$user    = $this->system_users->get_current_user();
			$user_id = $user->ID;
		}
		$existing_key = $this->system_users->get_user_meta( $user_id, 'pf_public_key', true );
		if ( $is_new || ! $existing_key ) {
			$key = $this->make_a_public_key( true );
			$this->system_users->update_user_meta( $user_id, 'pf_public_key', $key );
			return $key;
		} else {
			return $existing_key;
		}
	}

	/**
	 * Makes a private key.
	 *
	 * @return string
	 */
	public function make_a_jwt_private_key() {
		$extra       = ord( $this->system->get_site_info( 'url' ) );
		$key_seed    = sanitize_key( (string) $extra );
		$key_private = sanitize_key( $this->random_bytes( 64 ) );
		$key         = $key_seed . $key_private;

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( $key );
	}

	/**
	 * Maps a private key to a user.
	 *
	 * @param int $user_id ID of the user.
	 * @return string
	 */
	public function map_private_key_to_user( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user    = $this->system_users->get_current_user();
			$user_id = $user->ID;
		}
		$key = $this->make_a_jwt_private_key();
		$this->system_users->update_user_meta( $user_id, 'pf_jwt_private_key', $key );
		return $key;
	}

	/**
	 * Get a user's private key.
	 *
	 * @param int  $user_id ID of the user.
	 * @param bool $is_new  Whether this is a new user.
	 * @return string
	 */
	public function get_a_user_private_key( $user_id = 0, $is_new = false ) {
		if ( ! $user_id ) {
			$user    = $this->system_users->get_current_user();
			$user_id = $user->ID;
		}
		$existing_key = $this->system_users->get_user_meta( $user_id, 'pf_jwt_private_key', true );
		if ( $is_new || ! $existing_key ) {
			$key = $this->map_private_key_to_user( $user_id );
			return $key;
		} else {
			return $existing_key;
		}
	}

	/**
	 * Gets a user by a key array.
	 *
	 * @param array $key_array Key array.
	 * @return int|false
	 */
	public function get_user_by_key( $key_array ) {
		if ( ! array_key_exists( 'key_seed', $key_array ) || ! array_key_exists( 'user_seed', $key_array ) ) {
			return false;
		}
		$unique_strings_to_users = $this->system->get_option( 'pf_jwt_users', array() );
		if ( ! array_key_exists( $key_array['user_seed'], $unique_strings_to_users['by_key'] ) ) {
			return false;
		}
		$user_id = $unique_strings_to_users['by_key'][ $key_array['user_seed'] ];
		return $user_id;
	}

	/**
	 * Gets a private key based on a public key.
	 *
	 * @param string $public_pf_key Key.
	 * @return string|false
	 */
	public function get_a_user_private_key_for_decrypt( $public_pf_key ) {
		$key_parts = array();
		$key_obj   = $this->decode_with_jwt( $public_pf_key, $this->system_key() );
		$key_array = (array) $key_obj;
		$user_id   = $this->get_user_by_key( $key_array );

		if ( false === $user_id ) {
			return false;
		}

		$key = $this->system_users->get_user_meta( $user_id, 'pf_jwt_private_key', true );
		return $key;
	}

	/**
	 * Gets a user from a public key.
	 *
	 * @param string $public_pf_key Key.
	 * @return bool|\WP_User
	 */
	public function get_a_user_from_public_key( $public_pf_key ) {
		$key_parts = array();
		$key_obj   = $this->decode_with_jwt( $public_pf_key, $this->system_key() );
		$key_array = (array) $key_obj;
		if ( ! array_key_exists( 'key_seed', $key_array ) || ! array_key_exists( 'user_seed', $key_array ) ) {
			return false;
		}
		$unique_strings_to_users = $this->system->get_option( 'pf_jwt_users', array() );
		if ( ! array_key_exists( $key_array['user_seed'], $unique_strings_to_users['by_key'] ) ) {
			return false;
		}
		$user_id = $unique_strings_to_users['by_key'][ $key_array['user_seed'] ];
		$user    = get_user_by( 'id', $user_id );
		return $user;
	}
}
