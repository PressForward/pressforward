<?php
namespace PressForward\Controllers;

// use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Interfaces\System;
use PressForward\Interfaces\SystemUsers;
use Firebase\JWT\JWT as JWT;
/**
 * Readability stuff
 */

class PF_JWT {

	function __construct( SystemUsers $users, System $system ) {
		$this->system_users = $users;
		$this->system = $system;
		$this->JWT = new JWT;
	}

	public function random_bytes( $num ){
		if ( function_exists('random_bytes') ){
			try {
			    $value = random_bytes($num);
			} catch (\TypeError $e) {
			    // Well, it's an integer, so this IS unexpected.
			    die("An unexpected error has occurred when generating a cryptographic API key.");
			} catch (\Error $e) {
			    // This is also unexpected because 32 is a reasonable integer.
			    die("An unexpected error has occurred when generating a cryptographic API key.");
			} catch (\Exception $e) {
			    // If you get this message, the CSPRNG failed hard.
			    die("Could not generate a random string. Is our OS secure?");
			}
			return bin2hex($value);
		} else {
			throw new Exception("PressForward cannot provide a cryptographically secure API key.", 1);
		}
	}

	public function system_key(){
		$system_key = $this->system->get_option('pf_system_jwt_key', false);
		if (!$system_key){
			$system_key = $this->random_bytes(64);
			$this->system->update_option( 'pf_system_jwt_key', $system_key );
		}
		return $system_key;
	}

	public function encode_with_jwt($token, $key,  $alg = 'HS256' ){
		return $this->JWT->encode($token, $key, $alg);
	}

	public function decode_with_jwt($token, $key, $alg = array('HS256')){
		//var_dump($token, $key); die();
		try {
			$decode = $this->JWT->decode($token, $key, $alg);
		} catch ( \Exception $e ) {
			// echo 'Caught exception: ',  $e->getMessage(), "\n";
			return false;
		}
		return $decode;
	}

	public function make_a_public_key( $new = false ){
		$key_seed = 'pf';
		//$key_public = sanitize_key($this->random_bytes(4));
		$user = $this->system_users->get_current_user();
		$unique_strings_to_users = $this->system->get_option('pf_jwt_users', array());
		if ( $new || !array_key_exists('by_id', $unique_strings_to_users) || !array_key_exists($user->ID, $unique_strings_to_users['by_id']) ){
			$user_key = sanitize_key($this->random_bytes(rand(6,12)));
			if ( array_key_exists($user->ID, $unique_strings_to_users['by_id']) ){
				// We need to unset the old version because we are in make-new mode.
				$old_user_key = $unique_strings_to_users['by_id'][$user->ID];
				unset($unique_strings_to_users['by_key'][$old_user_key]);
				unset($unique_strings_to_users['by_id'][$user->ID]);
			}
			$unique_strings_to_users['by_key'][$user_key] = $user->ID;
			$unique_strings_to_users['by_id'][$user->ID] = $user_key;
			$this->system->update_option( 'pf_jwt_users', $unique_strings_to_users );
		} else {
			$user_key = $unique_strings_to_users['by_id'][$user->ID];
		}
		$user_seed = $user_key;
		//$key = 'pf'.'|'.$key_public.'|'.$key_seed.'|'.$user_seed;
		$key_set = array(
			'key_seed'		=>	$key_seed,
			'user_seed'		=>	$user_seed,
		);
		$key = $this->encode_with_jwt($key_set, $this->system_key());
		return $key;
	}

	public function get_a_user_public_key( $user_id = false, $new = false ){
		if (!$user_id){
			$user = $this->system_users->get_current_user();
			$user_id = $user->ID;
		}
		$existing_key = $this->system_users->get_user_meta($user_id, 'pf_public_key', true);
		if ( $new || !$existing_key ){
			$key = $this->make_a_public_key( true );
			$this->system_users->update_user_meta($user_id, 'pf_public_key', $key);
			return $key;
		} else {
			return $existing_key;
		}
	}

	public function make_a_jwt_private_key(){
		$extra = ord($this->system->get_site_info('url'));
		$key_seed = sanitize_key($extra);
		$key_private = sanitize_key($this->random_bytes(64));
		$key = $key_seed.$key_private;
		return base64_encode($key);
	}

	public function map_private_key_to_user( $user_id = false ){
		if (!$user_id){
			$user = $this->system_users->get_current_user();
			$user_id = $user->ID;
		}
		$key = $this->make_a_jwt_private_key();
		$this->system_users->update_user_meta($user_id, 'pf_jwt_private_key', $key);
		return $key;
	}

	public function get_a_user_private_key( $user_id = false, $new = false ){
		if (!$user_id){
			$user = $this->system_users->get_current_user();
			$user_id = $user->ID;
		}
		$existing_key = $this->system_users->get_user_meta($user_id, 'pf_jwt_private_key', true);
		if ( $new || !$existing_key ){
			$key = $this->map_private_key_to_user( $user_id );
			// $this->system_users->update_user_meta($user_id, 'pf_jwt_private_key', $key);
			return $key;
		} else {
			return $existing_key;
		}
	}

	public function get_user_by_key($key_array){
		if (!array_key_exists('key_seed', $key_array) || !array_key_exists('user_seed', $key_array)){
			return false;
		}
		$unique_strings_to_users = $this->system->get_option('pf_jwt_users', array());
		if (!array_key_exists($key_array['user_seed'], $unique_strings_to_users['by_key'])){
			return false;
		}
		$user_id = $unique_strings_to_users['by_key'][$key_array['user_seed']];
		return $user_id;
	}

	public function get_a_user_private_key_for_decrypt( $public_pf_key ){
		$key_parts = array();
		//$decoded_key = base64_decode($public_pf_key);
		//$key_parts = explode('|', $decoded_key);
		//$user_key = array_pop($key_parts);
		//$site_url = array_pop($key_parts);
		$key_obj = $this->decode_with_jwt($public_pf_key, $this->system_key());
		$key_array = (array) $key_obj;
		$user_id = $this->get_user_by_key($key_array);
		if ( false === $user_id ){
			return false;
		}
		//$user = get_user_by( 'id', $user_id );
		$key = $this->system_users->get_user_meta($user_id, 'pf_jwt_private_key', true);
		return $key;
	}

	public function get_a_user_from_public_key( $public_pf_key ){
		$key_parts = array();
		//$decoded_key = base64_decode($public_pf_key);
		//$key_parts = explode('|', $decoded_key);
		//$user_key = array_pop($key_parts);
		//$site_url = array_pop($key_parts);
		$key_obj = $this->decode_with_jwt($public_pf_key, $this->system_key());
		$key_array = (array) $key_obj;
		if (!array_key_exists('key_seed', $key_array) || !array_key_exists('user_seed', $key_array)){
			return false;
		}
		$unique_strings_to_users = $this->system->get_option('pf_jwt_users', array());
		if (!array_key_exists($key_array['user_seed'], $unique_strings_to_users['by_key'])){
			return false;
		}
		$user_id = $unique_strings_to_users['by_key'][$key_array['user_seed']];
		$user = get_user_by( 'id', $user_id );
		return $user;
	}

}
