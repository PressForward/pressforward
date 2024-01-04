<?php
/**
 * Feed item model.
 *
 * @package PressForward
 */

namespace PressForward\Core\Models;

use PressForward\Core\Models\BasicModel;
use PressForward\Interfaces\Items;
use PressForward\Controllers\Metas;

/**
 * Feed_Item class.
 */
class Feed_Item extends BasicModel {
	/**
	 * Post type.
	 *
	 * @access public
	 * @var string
	 */
	public $post_type;

	/**
	 * Tag taxonomy.
	 *
	 * @access public
	 * @var string
	 */
	public $tag_taxonomy;

	/**
	 * ID of the feed item.
	 *
	 * @access public
	 * @var int
	 */
	public $id;

	/**
	 * Content string.
	 *
	 * @access public
	 * @var string
	 */
	public $content;

	/**
	 * Data array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $data = array();

	/**
	 * Initalizes model.
	 *
	 * @param array|object $item      Data about item.
	 * @param array        $handlers  Controllers.
	 * @param string       $post_type Post type name.
	 */
	public function init( $item = array(), $handlers = array(), $post_type = '' ) {
		$this->type     = 'feed_item';
		$this->type_key = 'item';
		if ( ! $post_type ) {
			$this->post_type = pf_feed_item_post_type();
		}
		$this->tag_taxonomy = pf_feed_item_tag_taxonomy();
		if ( is_array( $item ) ) {
			$this->set_up_item( $item['item_url'], $item['item_title'] );
		} else {
			$setup = $this->build_item( $item, $handlers['processor'], $handlers['metas'] );
			$this->build_metas();
		}
	}

	/**
	 * Private function to set up the feed item object
	 *
	 * @param string $item_url   Item URL.
	 * @param string $item_title Item title.
	 */
	private function set_up_item( $item_url, $item_title ) {
		$this->set( 'title', $item_title );
		$this->set( 'link', $item_url );
		$this->set( 'item_id', $this->create_hash_id( $item_url, $item_title ) );
	}

	/**
	 * Build an item out of a post object.
	 *
	 * @param \WP_Post                        $post      Post object.
	 * @param \PressForward\Interfaces\Items  $processor Items object.
	 * @param \PressForward\Controllers\Metas $metas     Metas object.
	 */
	public function build_item( $post, Items $processor, Metas $metas ) {
		$post = $processor->get_post( $post );
		$this->set( 'id', $post->ID );
		$this->set( 'user', $post->post_author );
		$this->set( 'slug', $post->post_name );
		$this->set( 'title', $post->post_title );
		$this->set( 'date', $post->post_date );
		$this->set( 'date_gmt', $post->post_date_gmt );
		$this->set( 'content', $post->post_content );
		$this->set( 'excerpt', $post->post_excerpt );
		$this->set( 'status', $post->post_status );
		$this->set( 'parent', $post->post_parent );
		$this->set( 'modified', $post->post_modified );
		$this->set( 'modified_gmt', $post->post_modified_gmt );
		$this->set( 'post', $post );
		$link = $metas->get_post_pf_meta( $post->ID, 'link' );
		$this->set( 'link', $link );
		$this->set( 'item_id', $this->create_hash_id( $link, $post->post_title ) );
	}

	/**
	 * Set up item metas from postmeta.
	 */
	public function build_metas() {
		foreach ( $this->metas->structure as $meta_key => $meta_data ) {
			if ( in_array( 'item', $meta_data['level'], true ) ) {
				$this->set( $meta_key, $this->metas->get_post_pf_meta( $this->id, $meta_key ) );
			}
		}
	}

	/**
	 * Create hash ID from URL and title.
	 *
	 * @param string $url   URL.
	 * @param string $title Title.
	 * @return string
	 */
	private function create_hash_id( $url, $title ) {
		$hash = pressforward_create_feed_item_id( $url, $title );
		return $hash;
	}

	/**
	 * Creates a DateTime object.
	 *
	 * @param string $format Date format.
	 * @param string $raw    Date string.
	 * @return \DateTime
	 */
	public function date_maker( $format, $raw ) {
		return \DateTime::createFromFormat( $format, $raw );
	}

	/**
	 * Get a formatted date string.
	 *
	 * @param array|string $date Date format and raw.
	 * @return string
	 */
	public function set_date( $date ) {
		if ( is_array( $date ) ) {
			$date_obj = $this->date_maker( $date['format'], $date['raw'] );
			$this->set( 'date_obj', $date_obj );
			return $date_obj->format( 'Y-m-d' );
		} else {
			return $date;
		}
	}

	/**
	 * Set tags.
	 *
	 * @param array|string $tags Tags to set.
	 * @return string Comma-separated list of tags.
	 */
	public function set_tags( $tags ) {
		if ( is_array( $tags ) ) {
			$this->set( 'tags_array', $tags );
			$tag_string = implode( ',', $tags );
			$this->set( 'tags_string', $tag_string );
		} else {
			$tag_string = $tags;
			$this->set( 'tags_string', $tags );
		}
		return $tag_string;
	}

	/**
	 * Get tags.
	 *
	 * @return string Comma-separated list of tags.
	 */
	public function get_tags() {
		$tags = $this->get( 'tags_array' );
		if ( isset( $tags ) && is_array( $tags ) ) {
			return implode( ',', $tags );
		} else {
			return $this->get( 'tags_string' );
		}
	}

	/**
	 * Set content.
	 *
	 * @param string $content Tags to set.
	 * @return string
	 */
	public function set_content( $content ) {
		$content_obj   = pressforward( 'library.htmlchecker' );
		$this->content = $content_obj->closetags( $content );
		return $this->content;
	}
}
