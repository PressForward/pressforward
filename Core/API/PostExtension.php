<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Controllers\Metas;

use WP_Ajax_Response;

class PostExtension implements HasActions {

	protected $basename;

	function __construct( Metas $metas ){
		$this->metas = $metas;
	}


	public function action_hooks() {
		$actions = array(
			array(
				'hook' => 'rest_api_init',
				'method' => 'register_rest_post_read_meta_fields',
			)
		);
		return $actions;
	}

	public function valid_post_metas(){
		$metas = $this->metas->structure();
		$post_metas = array();
		foreach ( $metas as $meta ){
			// Don't use the serialized array.
			if ( $meta['name'] === 'pf_meta' ){
				continue;
			}
			// Only use Post level data
			if ( !in_array( 'post', $meta['level'] ) ){
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

	public function rest_api_init_extension_hook( $action ){
		return array(
				'hook' => 'rest_api_init',
				'method' => $action,
			);
	}

	public function rest_api_init_extension_hook_read_only( $action ){
		return array(
				'hook' => 'rest_api_init',
				'method' => function(){
						$this->register_rest_post_read_field($action, true);
					},
			);
	}

	public function register_rest_post_read_field($key, $action = false){
		//http://v2.wp-api.org/extending/modifying/
		if (!$action) { $action = array( $this, $key.'_response' ); }
		if ( true === $action ){ $action = array( $this, 'meta_response' ); }
		register_rest_field( 'post',
	        $key,
	        array(
	            'get_callback'    => $action,
	            'update_callback' => null,
	            'schema'          => null,
	        )
	    );
	}

	public function register_rest_post_read_meta_fields(){
		foreach ( $this->valid_post_metas() as $key ){
			$this->register_rest_post_read_field( $key, true );
		}
	}

	public function meta_response($object, $field_name, $request ){
		$response = $this->metas->get_post_pf_meta( $object[ 'id' ], $field_name, true );
		if ( empty($response) || is_wp_error( $response ) ){
			return 'false';
		} else {
			return $response;
		}
	}

	public function item_id(){
		$this->register_rest_post_read_field('item_id', true);
	}
}