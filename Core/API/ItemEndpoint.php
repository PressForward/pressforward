<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use WP_Ajax_Response;

class ItemEndpoint extends APIWithMetaEndpoints implements HasActions, HasFilters {

	protected $basename;

	function __construct( Metas $metas ) {
		$this->metas     = $metas;
		$this->post_type = pressforward( 'schema.feed_item' )->post_type;
		$this->level     = 'item';
	}


	public function action_hooks() {
		$actions = array(
			array(
				'hook'   => 'rest_api_init',
				'method' => 'register_rest_post_read_meta_fields',
			),
		);
		return $actions;
	}

	public function filter_hooks() {
		$filter = array(
			array(
				'hook'     => 'rest_prepare_' . $this->post_type,
				'method'   => 'add_rest_post_links',
				'priority' => 10,
				'args'     => 3,
			),
			array(
				'hook'     => 'rest_prepare_' . $this->post_type,
				'method'   => 'filter_wp_to_pf_in_terms',
				'priority' => 10,
				'args'     => 3,
			),
		);
		return $filter;
	}

	public function add_rest_post_links( $data, $post, $request ) {
		// http://v2.wp-api.org/extending/linking/
		// https://1fix.io/blog/2015/06/26/adding-fields-wp-rest-api/
		$feed_id = 'false';
		if ( ! empty( $post->post_parent ) ) {
			$feed_id = $post->post_parent;
		}
		$data->add_links(
			array(
				'feed' => array(
					'href'       => rest_url( '/pf/v1/feeds/' . $feed_id ),
					'embeddable' => true,
				),
			)
		);
		return $data;
	}


}
