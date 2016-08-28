<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use WP_Ajax_Response;

class FeedEndpoint extends APIWithMetaEndpoints implements HasActions {

	protected $basename;

	function __construct( Metas $metas ){
		$this->metas = $metas;
		$this->post_type = pressforward('schema.feeds')->post_type;
		$this->level = 'feed';
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


}
