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
}
