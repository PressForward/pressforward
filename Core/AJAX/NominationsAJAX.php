<?php
namespace PressForward\Core\AJAX;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Controllers\Metas;
use PressForward\Controllers\PF_to_WP_Posts;
use PressForward\Core\Schema\Feed_Items;

class NominationsAJAX implements HasActions {

	protected $basename;

	function __construct( Metas $metas, PF_to_WP_Posts $posts, Feed_Items $items ) {
		$this->metas = $metas;
		$this->posts = $posts;
		$this->items = $items;

	}


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

	public function build_a_nomination() {
		pressforward( 'admin.nominated' )->build_nomination();
		die();
	}

	public function build_a_nom_draft() {
		pressforward( 'admin.nominated' )->build_nom_draft();
		die();
	}

	public function archive_a_nom() {
		pressforward( 'admin.nominated' )->archive_a_nom();
		die();
	}

	public function simple_nom_to_draft() {
		pressforward( 'admin.nominated' )->simple_nom_to_draft();
		die();
	}

}
