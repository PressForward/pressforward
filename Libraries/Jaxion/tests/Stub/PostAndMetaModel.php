<?php
namespace Intraxia\Jaxion\Test\Stub;

use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost;

class PostAndMetaModel extends Model implements UsesWordPressPost {
	protected $fillable = array(
		'title',
		'text',
		'children',
	);

	protected $guarded = array(
		'ID',
	);

	protected $visible = array(
		'title',
		'text',
		'url',
	);

	public static function get_post_type() {
		return 'custom';
	}

	protected function map_ID() {
		return 'ID';
	}

	protected function map_title() {
		return 'post_title';
	}

	protected function compute_url() {
		return 'example.com/' . $this->get_attribute( 'title' );
	}
}
