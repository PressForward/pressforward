<?php
/**
 * Folder extensions for REST API.
 *
 * @package PressForward
 */

namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

/**
 * Folder extensions for REST API.
 */
class FolderExtension extends APIWithMetaEndpoints implements HasFilters {

	/**
	 * Basename.
	 *
	 * @access protected
	 * @var string
	 */
	protected $basename;

	/**
	 * Taxonomy.
	 *
	 * @access protected
	 * @var string
	 */
	protected $tax;

	/**
	 * Level.
	 *
	 * @access protected
	 * @var string
	 */
	public $level;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $pf_metas;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\Metas $metas Metas object.
	 */
	public function __construct( Metas $metas ) {
		$this->pf_metas = $metas;
		$this->tax      = pressforward( 'schema.folders' )->tag_taxonomy;
		$this->level    = 'feed';
	}

	/**
	 * {@inheritdoc}
	 */
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
