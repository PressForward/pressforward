<?php
/**
 * AJAX handlers for Nominations actions.
 *
 * @package PressForward
 */

namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;

/**
 * AJAX handlers for Nominations actions.
 */
class NominationsAJAX implements HasActions {
	/**
	 * Basename.
	 *
	 * @access protected
	 * @var string
	 */
	protected $basename;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $metas;

	/**
	 * PF_to_WP_Posts object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PF_to_WP_Posts
	 */
	public $posts;

	/**
	 * Feed_Items object.
	 *
	 * @access public
	 * @var \PressForward\Core\Schema\Feed_Items
	 */
	public $items;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\Metas          $metas Metas object.
	 * @param \PressForward\Controllers\PF_to_WP_Posts $posts PF_to_WP_Posts object.
	 * @param \PressForward\Core\Schema\Feed_Items     $items Feed_Items object.
	 */
	public function __construct( Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items ) {
		$this->metas = $metas;
		$this->posts = $posts;
		$this->items = $items;
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'wp_ajax_build_a_nomination',
				'method' => 'build_a_nomination',
			),
			array(
				'hook'   => 'wp_ajax_build_a_nom_draft',
				'method' => 'build_a_nom_draft',
			),
			array(
				'hook'   => 'wp_ajax_simple_nom_to_draft',
				'method' => 'simple_nom_to_draft',
			),
			array(
				'hook'   => 'wp_ajax_archive_a_nom',
				'method' => 'archive_a_nom',
			),
		);
	}

	/**
	 * AJAX handler for 'wp_ajax_build_a_nomination'.
	 */
	public function build_a_nomination() {
		pressforward( 'admin.nominated' )->build_nomination();
		die();
	}

	/**
	 * AJAX handler for 'wp_ajax_build_a_nom_draft'.
	 */
	public function build_a_nom_draft() {
		pressforward( 'admin.nominated' )->build_nom_draft();
		die();
	}

	/**
	 * AJAX handler for 'wp_ajax_archive_a_nom'.
	 */
	public function archive_a_nom() {
		pressforward( 'admin.nominated' )->archive_a_nom();
		die();
	}

	/**
	 * AJAX handler for 'wp_ajax_simple_nom_to_draft'.
	 */
	public function simple_nom_to_draft() {
		pressforward( 'admin.nominated' )->simple_nom_to_draft();
		die();
	}
}
