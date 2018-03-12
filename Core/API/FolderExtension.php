<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use WP_Ajax_Response;

class FolderExtension extends APIWithMetaEndpoints implements HasFilters {

	protected $basename;

	function __construct( Metas $metas ) {
		$this->pf_metas = $metas;
		$this->tax      = pressforward( 'schema.folders' )->tag_taxonomy;
		$this->level    = 'feed';
	}

	public function filter_hooks() {
		$filter = array(
			array(
				'hook'     => 'rest_prepare_' . $this->tax,
				'method'   => 'filter_wp_to_pf_in_terms',
				'priority' => 10,
				'args'     => 3,
			),
		);
		return $filter;
	}
}
