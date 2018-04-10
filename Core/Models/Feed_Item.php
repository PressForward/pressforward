<?php
namespace PressForward\Core\Models;

use PressForward\Models\BasicModel;
use PressForward\Interfaces\Items as Items;
use PressForward\Interfaces\SystemMeta as SystemMeta;

class Feed_Item extends BasicModel {
	protected $data = array();

	function init( $item = array(), $handlers = array(), $post_type = false ) {
		$this->type     = 'feed_item';
		$this->type_key = 'item';
		if ( ! $post_type ) {
			$this->post_type = pf_feed_item_post_type();
		}
		$this->tag_taxonomy = pf_feed_item_tag_taxonomy();
		if ( is_array( $item ) ) {
			$this->set_up_item( $item['item_url'], $item['item_title'] );
			// $this->set_up_metas( $item, $handlers['metas'] );
		} else {
			$setup = $this->build_item( $item, $handlers['processor'], $handlers['metas'] );
			$this->build_metas();
		}
	}

	/**
	 * Private function to set up the feed item object
	 *
	 * @param [type] $item_url   [description]
	 * @param [type] $item_title [description]
	 */
	private function set_up_item( $item_url, $item_title ) {
		$this->set( 'title', $item_title );
		$this->set( 'link', $item_url );
		$this->set( 'item_id', $this->create_hash_id( $item_url, $item_title ) );

	}

	public function build_item( $post, Items $processor, SystemMeta $metas ) {
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

	public function build_metas() {
		foreach ( $this->metas->structure as $meta_key => $meta_data ) {
			if ( in_array( 'item', $meta_data['level'] ) ) {
				$this->set( $meta_key, $this->metas->get_post_pf_meta( $this->id, $meta_key ) );
			}
		}
	}

	private function create_hash_id( $url, $title ) {
		$hash = create_feed_item_id( $url, $title );
		return $hash;
	}

	public function date_maker( $format, $raw ) {
		return DateTime::createFromFormat( $format, $raw );
	}

	public function set_date( $date ) {
		if ( is_array( $date ) ) {
			$date_obj = $this->date_maker( $date['format'], $date['raw'] );
			$this->set( 'date_obj', $date_obj );
			return $date_obj->format( 'Y-m-d' );
		} else {
			return $date;
		}
	}

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

	public function get_tags() {
		$tags = $this->get( 'tags_array' );
		if ( isset( $tags ) && is_array( $tags ) ) {
			return implode( ',', $tags );
		} else {
			return $this->get( 'tags_string' );
		}
	}

	public function set_content( $content ) {
		$content_obj   = pressforward( 'library.htmlchecker' );
		$this->content = $content_obj->closetags( $content );
		return $this->content;
	}

}
