<?php
namespace PressForward\Core\API;

use PressForward\Controllers\Metas;

class APIWithMetaEndpoints {

	public function pf_route( $endpoint = '' ){
		return 'pf/v1'.$endpoint;
	}


	public function valid_metas(){
		$metas = $this->metas->structure();
		$post_metas = array();
		foreach ( $metas as $meta ){
			// Don't use the serialized array.
			if ( $meta['name'] === 'pf_meta' ){
				continue;
			}
			// Only use Post level data
			if ( !in_array( $this->level, $meta['level'] ) ){
				continue;
			}
			// Don't use metas that belong elsewhere
			if ( !empty($meta['move']) ){
				continue;
			}
			// Only use metas marked for use in the top level API.
			if ( !in_array( 'api', $meta['use'] ) ){
				continue;
			}
			// Don't use metas marked as depreciated.
			if ( in_array( 'dep', $meta['type'] ) ){
				continue;
			}
			$post_metas[] = $meta['name'];
		}
		return $post_metas;
	}

	/**
	 * Get valid metas for this post object type and register them as api fields
	 * @return [type] [description]
	 */
	public function register_rest_post_read_meta_fields(){
		foreach ( $this->valid_metas() as $key ){
			$this->register_rest_post_read_field( $key, true );
		}
	}


	public function register_rest_post_read_field($key, $action = false){
		//http://v2.wp-api.org/extending/modifying/
		if (!$action) { $action = array( $this, $key.'_response' ); }
		if ( true === $action ){ $action = array( $this, 'meta_response' ); }
		register_rest_field( $this->post_type,
	        $key,
	        array(
	            'get_callback'    => $action,
	            'update_callback' => null,
	            'schema'          => null,
	        )
	    );
	}


	public function meta_response($object, $field_name, $request ){
		$response = $this->metas->get_post_pf_meta( $object[ 'id' ], $field_name, true );
		if ( empty($response) || is_wp_error( $response ) ){
			return 'false';
		} else {
			return $response;
		}
	}
}
