<?php
namespace Intraxia\Jaxion\Model;

/**
 * Class Post
 *
 * Default implementation of the Base model class, mapping a set
 * of clearer naming conventions to a basic underlying WP_Post
 * object.
 *
 * @property int    ID
 * @property string author
 * @property string slug
 * @property string title
 * @property string publish_date
 * @property string excerpt
 * @package Intraxia\Jaxion
 * @subpackage Model
 */
class Post extends Base {
	/**
	 * Post type this model maps to.
	 *
	 * @var string
	 */
	protected $type = 'post';

	/**
	 * Fillable attributes for the model.
	 *
	 * @var array
	 */
	protected $fillable = array( 'ID', 'author', 'slug', 'title', 'publish_date', 'content', 'excerpt' );

	/**
	 * ID property maps to ID.
	 *
	 * @return string
	 */
	protected function map_ID() {
		return 'ID';
	}

	/**
	 * Author property maps to post_author.
	 *
	 * @return string
	 */
	protected function map_author() {
		return 'post_author';
	}

	/**
	 * Slug property maps to post_name.
	 *
	 * @return string
	 */
	protected function map_slug() {
		return 'post_name';
	}

	/**
	 * Title property maps to post_title.
	 *
	 * @return string
	 */
	protected function map_title() {
		return 'post_title';
	}

	/**
	 * Publish date property maps to post_date_gmt.
	 *
	 * @return string
	 */
	protected function map_publish_date() {
		return 'post_date_gmt';
	}

	/**
	 * Content property maps to post_content.
	 *
	 * @return string
	 */
	protected function map_content() {
		return 'post_content';
	}

	/**
	 * Excerpt property maps to post_excerpt.
	 *
	 * @return string
	 */
	protected function map_excerpt() {
		return 'post_excerpt';
	}
}
