<?php
/**
 * Base class for data models.
 *
 * @package PressForward
 */

namespace PressForward\Core\Models;

/**
 * BasicModel class.
 */
class BasicModel {
	/**
	 * Type.
	 *
	 * @access public
	 * @var string
	 */
	public $type;

	/**
	 * Type key.
	 *
	 * @access public
	 * @var string
	 */
	public $type_key;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var object
	 */
	public $metas;

	/**
	 * Processor object.
	 *
	 * @access public
	 * @var object
	 */
	public $processor;

	/**
	 * Data array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $data = array();

	/**
	 * Constructor.
	 *
	 * @param array  $item      Data about item.
	 * @param array  $handlers  Controllers.
	 * @param string $post_type Post type name.
	 */
	public function __construct( $item = array(), $handlers = array(), $post_type = '' ) {
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
	 * Init method.
	 *
	 * Should be overridden in a child class.
	 *
	 * @param array  $item      Data about item.
	 * @param array  $handlers  Controllers.
	 * @param string $post_type Post type name.
	 */
	public function init( $item = array(), $handlers = array(), $post_type = '' ) {}

	/**
	 * Magic methods are apparently not super perfomant.
	 * Avoid using them if you don't have to. Devs should
	 * prefer the custom getters and setters that follow.
	 *
	 * @param string $key Key.
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->data[ $key ] );
	}

	/**
	 * Magic getter.
	 *
	 * @param string $key Key.
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Magic setter.
	 *
	 * @param string $key   Key.
	 * @param mixed  $value Value.
	 */
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}

	/**
	 * Set a property for the object.
	 *
	 * @param string $key   Key to access the property.
	 * @param mixed  $value Value to store in the property.
	 */
	public function set( $key, $value ) {
		$value = apply_filters( 'pf_' . $this->type . '_property_' . $key, $value, $this );

		if ( 0 === strpos( $key, $this->type_key . '_' ) ) {
			$key = str_replace( $this->type_key . '_', '', $key );
		}

		if ( 0 === strpos( $key, 'post_' ) ) {
			$key = str_replace( 'post_', '', $key );
		}

		$method_name = 'get_' . $key;
		if ( method_exists( $this, $method_name ) ) {
			$value = call_user_func( array( $this, $method_name ), $value );
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
	 * @param  string      $key     The name of the property.
	 * @param  string|bool $sub_key Optional. The name of the subproperty.
	 * @return mixed           Property value.
	 */
	public function get( $key, $sub_key = false ) {
		if ( 0 === strpos( $key, $this->type_key . '_' ) ) {
			$key = str_replace( $this->type_key . '_', '', $key );
		}

		if ( 0 === strpos( $key, 'post_' ) ) {
			$key = str_replace( 'post_', '', $key );
		}

		$method_name = 'get_' . $key;
		if ( method_exists( $this, $method_name ) ) {
			$value = call_user_func( array( $this, $method_name ) );
			return $value;
		}

		if ( false !== $sub_key ) {
			$array_property = $this->data[ $key ];
			return $array_property[ $sub_key ];
		}

		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}

	/**
	 * Sets up meta data.
	 *
	 * @param array  $set_metas   Metas to set.
	 * @param object $meta_system Metas controller.
	 */
	public function set_up_metas( $set_metas, $meta_system ) {
		if ( empty( $meta_system->structure ) ) {
			return;
		}

		foreach ( $meta_system->structure as $meta_key => $meta_data ) {
			if ( in_array( $this->type, $meta_data['level'], true ) ) {
				if ( ( array_key_exists( $meta_key, $set_metas ) || empty( $set_metas[ $meta_key ] ) ) && ! empty( $meta_data['defaults'] ) ) {
					$metas[ $meta_key ] = $meta_data['default'];
				} else {
					$metas[ $meta_key ] = '';
				}
			}

			if ( ! empty( $meta_data['defaults'] ) ) {
				foreach ( $meta_data['defaults'] as $key => $default ) {
					$this->set( $key, $default );
				}
			}
		}
	}
}
