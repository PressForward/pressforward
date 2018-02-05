<?php
namespace Intraxia\Jaxion\Contract\Axolotl;

/**
 * Interface UsesWordPressPost
 *
 * @package    Intraxia\Jaxion
 * @subpackage Contract\Axolotl
 */
interface UsesWordPressPost {
	/**
	 * Returns the custom post type used by the Model.
	 *
	 * @return string
	 */
	public static function get_post_type();
}
