<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use WP_Ajax_Response;

class FeedEndpoint extends APIWithMetaEndpoints implements HasActions, HasFilters {

	protected $basename;

	function __construct( Metas $metas ) {
		$this->metas     = $metas;
		$this->post_type = pressforward( 'schema.feeds' )->post_type;
		$this->level     = 'feed';
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
				'method'   => 'filter_wp_to_pf_in_terms',
				'priority' => 10,
				'args'     => 3,
			),
		);
		return $filter;
	}

	public function filter_in_nomination_count( $data, $post, $request ) {
		var_dump( $data );
		return $data;
	}


}
