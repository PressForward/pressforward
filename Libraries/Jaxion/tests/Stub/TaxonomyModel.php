<?php
namespace Intraxia\Jaxion\Test\Stub;

use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressTerm;

class TaxonomyModel extends Model implements UsesWordPressTerm {
	protected $fillable = array(
		'slug',
		'meta'
	);

	/**
	 * @inheritDoc
	 */
	public static function get_taxonomy() {
		return 'category';
	}

	public function map_slug() {
		return 'slug';
	}
}
