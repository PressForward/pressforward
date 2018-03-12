<?php
namespace PressForward\Core\Models;

class BasicModel {
	protected $data = array();

	public function __construct( $item = array(), $handlers = array(), $post_type = false ) {
		if ( empty( $handlers ) ) {
			$handlers = array(
				'processor' => pressforward( 'controller.items' ),
				'metas'     => pressforward( 'controller.metas' ),
			);
		}
		$this->metas     = $handlers['metas'];
		$this->processor = $handlers['processor'];
		$this->set_up_metas( $item, $handlers['metas'] );
		$this->init( $item, $handlers, $post_type );
	}
	/**
	 * Magic methods are apparently not super perfomant.
	 * Avoid using them if you don't have to. Devs should
	 * prefer the custom getters and setters that follow.
	 */
	public function __isset( $key ) {
		return isset( $this->data[ $key ] );
	}
	public function __get( $key ) {
		return $this->get( $key );
	}
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}

	// Setters and getters
	/**
	 * Set a property for the object.
	 *
	 * @param string $key   Key to access the property
	 * @param any    $value    Value to store in the property.
	 */
	public function set( $key, $value ) {
		$value = apply_filters( 'pf_' . $this->type . '_property_' . $key, $value, $this );
		if ( 0 === strpos( $key, $this->type_key . '_' ) ) {
			$key = str_replace( $this->type_key . '_', '', $key );
		}
		if ( 0 === strpos( $key, 'post_' ) ) {
			$key = str_replace( 'post_', '', $key );
		}
		if ( method_exists( $this, $f = 'set_' . $key ) ) {
			$value = call_user_func( array( $this, $f ), $value );
		}
		$this->data[ $key ] = $value;
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
		if ( 0 === strpos( $key, $this->type_key . '_' ) ) {
			$key = str_replace( $this->type_key . '_', '', $key );
		}
		if ( 0 === strpos( $key, 'post_' ) ) {
			$key = str_replace( 'post_', '', $key );
		}
		if ( method_exists( $this, $f = 'get_' . $key ) ) {
			$value = call_user_func( array( $this, $f ) );
			return $value;
		}
		if ( false !== $sub_key ) {
			$array_property = $this->data[ $key ];
			return $array_property[ $sub_key ];
		}
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}

	public function set_up_metas( $set_metas = array(), $meta_system ) {
		foreach ( $meta_system->structure as $meta_key => $meta_data ) {
			if ( in_array( $this->type, $meta_data['level'] ) ) {
				if ( ( array_key_exists( $meta_key, $set_metas ) || empty( $set_metas[ $meta_key ] ) ) && ! empty( $meta_data['defaults'] ) ) {
					$metas[ $meta_key ] = $meta_data['default'];
				} else {
					$metas[ $meta_key ] = '';
				}
			}
		}
		foreach ( $defaults as $key => $default ) {
			$this->set( $key, $default );
		}
	}


}
