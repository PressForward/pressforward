<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use WP_Ajax_Response;

class PFEndpoint extends APIWithMetaEndpoints implements HasActions {

	protected $basename;

	function __construct( Metas $metas ) {
		$this->metas = $metas;
		$this->post_type = pressforward( 'schema.feed_item' )->post_type;
		$this->level = 'item';
	}

	public function action_hooks() {
		$actions = array(
			array(
				'hook' => 'rest_api_init',
				'method' => 'callback_init',
			),
		);
		return $actions;
	}

	public function callback_init() {
		require_once( 'PF_REST_Controller.php' );
		require_once( 'PF_Stats_Controller.php' );
		require_once( 'PF_REST_Taxonomies_Controller.php' );
		require_once( 'PF_REST_Post_Types_Controller.php' );
		require_once( 'PF_REST_Posts_Controller.php' );
		require_once( 'PF_REST_Terms_Controller.php' );

	}

}
