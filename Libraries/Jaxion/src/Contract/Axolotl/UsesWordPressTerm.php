<?php
namespace Intraxia\Jaxion\Contract\Axolotl;

/**
 * Interface UseWordPressTerm
 *
 * @package Intraxia\Jaxion
 * @subpackage Contract\Axolotl
 */
interface UsesWordPressTerm {
	/**
	 * Returns the taxonomy used by the Model.
	 *
	 * @return string
	 */
	public static function get_taxonomy();
}
