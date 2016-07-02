<?php
namespace PressForward\Core\Modals;

use PressForward\Interfaces\Items as Items;

class Feed_Item {
	protected $data = array();

	public function __construct( $item = array(), $handlers = array(), $post_type = false ) {
		if (!$post_type) {
			$this->post_type = pf_feed_item_post_type();
		}
		$this->tag_taxonomy = pf_feed_item_tag_taxonomy();
		if ( is_array( $item ) ){
			$this->set_up_item( $item['item_url'], $item['item_title'] );
		} else {
			$setup = $this->build_item( $item, $handlers['processor'], $handlers['metas'] );
		}
	}
    /**
     * Magic methods are apparently not super perfomant.
     * Avoid using them if you don't have to. Devs should
     * prefer the custom getters and setters that follow.
     */
    public function __isset( $key ) {
        return isset( $this->data[$key] );
    }
    public function __get( $key ) {
        return $this->get( $key );
    }
    public function __set( $key, $value ) {
        $this->set($key, $value);
    }

    //Setters and getters

    /**
     * Set a property for the object.
     *
     * @param string $key   Key to access the property
     * @param any $value    Value to store in the property.
     *
     */
    public function set( $key, $value ) {
        $value = apply_filters('pf_feed_item_property_'.$key, $value, $this);
        if ( 0 === strpos($key, 'item_') ) {
        	$key = str_replace('item_', '', $key);
        }
		if ( 0 === strpos($key, 'post_') ) {
			$key = str_replace('post_', '', $key);
		}
        if ( method_exists($this,$f='set_'.$key) ){
            $value = call_user_func(array( $this, $f ), $value);
        }
        $this->data[$key] = $value;
    }
    /**
     * Get an untreated property of the object.
     *
     * This function will retrieve the exact stored value
     * of a property within the object. If you want properties
     * that have been treated in accordance with their accepted
     * use then use the specific getter for that property type.
     *
     * @param  string $key  The name of the property.
     * @return any          Property value.
     */
    public function get( $key, $sub_key = false ) {
        if ( 0 === strpos($key, 'item_') ) {
            $key = str_replace('item_', '', $key);
        }
		if ( 0 === strpos($key, 'post_') ) {
			$key = str_replace('post_', '', $key);
		}
        if ( method_exists($this,$f='get_'.$key) ){
            $value = call_user_func(array( $this, $f ));
			return $value;
        }
		if ( false !== $sub_key ){
			$array_property = $this->data[$key];
			return $array_property[$sub_key];
		}
        return isset( $this->data[$key] ) ? $this->data[$key] : null;
    }

	/**
	 * Private function to set up the feed item object
	 * @param [type] $item_url   [description]
	 * @param [type] $item_title [description]
	 */
    private function set_up_item( $item_url, $item_title ) {
    	$this->set( 'title', $item_title);
    	$this->set( 'link', $item_url );
    	$this->set( 'item_id', $this->create_hash_id( $item_url, $item_title ) );
		$metas = array();
		foreach ( pressforward('controller.metas')->structure as $meta_key=>$meta_data ){
			if ( in_array('item', $meta_data['level']) ){
				if ( !empty( $meta_data['defaults'] ) ){
					$metas[$meta_key] = $meta_data['default'];
				} else {
					$metas[$meta_key] = '';
				}
			}
		}
		$defaults = $metas;
        foreach ( $defaults as $key=>$default ) {
            $this->set($key, $default);
        }
    }

	public function build_item( $post, Items $processor, SystemMeta $metas ){
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
		$link = $metas->get_meta( $post->ID, 'link' );
		$this->set( 'link', $link );
		$this->set( 'item_id', $this->create_hash_id( $link, $post->post_title ) );
	}

    private function create_hash_id($url, $title){
		$hash = md5($url . $title);
		return $hash;
	}

    public function date_maker( $format, $raw ){
        return DateTime::createFromFormat( $format, $raw );
    }

	public function set_date( $date ){
		if ( is_array( $date ) ) {
			$date_obj = $this->date_maker( $date['format'], $date['raw'] );
			$this->set( 'date_obj', $date_obj );
			return $date_obj->format('Y-m-d');
		} else {
			return $date;
		}
	}

	public function set_tags( $tags ) {
		if ( is_array( $tags ) ){
			$this->set( 'tags_array', $tags );
			$tag_string = implode(',', $tags);
			$this->set( 'tags_string', $tag_string );
		} else {
			$tag_string = $tags;
			$this->set( 'tags_string', $tags );
		}
		return $tag_string;
	}

	public function get_tags() {
		$tags = $this->get('tags_array');
		if ( isset( $tags ) && is_array( $tags ) ){
			return implode(',', $tags);
		} else {
			return $this->get('tags_string');
		}
	}

	public function set_content( $content ) {
		$content_obj = pressforward('library.htmlchecker');
		$this->content = $content_obj->closetags($content);
		return $this->content;
	}

}
