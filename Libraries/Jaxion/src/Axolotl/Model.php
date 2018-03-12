<?php
namespace Intraxia\Jaxion\Axolotl;

use Exception;
use Intraxia\Jaxion\Contract\Axolotl\Serializes;
use Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressTerm;
use LogicException;
use WP_Post;
use WP_Term;

/**
 * Class Model
 *
 * Shared model methods and properties, allowing models
 * to transparently map some attributes to an underlying WP_Post
 * object and others to postmeta or a custom table.
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl
 * @since      0.1.0
 */
abstract class Model implements Serializes {
	/**
	 * Table attribute key.
	 */
	const TABLE_KEY = '@@table';

	/**
	 * Object attribute key.
	 */
	const OBJECT_KEY = '@@object';

	/**
	 * Memoized values for class methods.
	 *
	 * @var array
	 */
	private static $memo = array();

	/**
	 * Model attributes.
	 *
	 * @var array
	 */
	private $attributes = array(
		self::TABLE_KEY  => array(),
		self::OBJECT_KEY => null,
	);

	/**
	 * Model's original attributes.
	 *
	 * @var array
	 */
	private $original = array(
		self::TABLE_KEY  => array(),
		self::OBJECT_KEY => null,
	);

	/**
	 * Default attribute values.
	 *
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * Properties which are allowed to be set on the model.
	 *
	 * If this array is empty, any attributes can be set on the model.
	 *
	 * @var string[]
	 */
	protected $fillable = array();

	/**
	 * Properties which cannot be automatically filled on the model.
	 *
	 * If the model is unguarded, these properties can be filled.
	 *
	 * @var array
	 */
	protected $guarded = array();

	/**
	 * Properties which should not be serialized.
	 *
	 * @var array
	 */
	protected $hidden = array();

	/**
	 * Properties which should be serialized.
	 *
	 * @var array
	 */
	protected $visible = array();

	/**
	 * Whether the model's properties are guarded.
	 *
	 * When false, allows guarded properties to be filled.
	 *
	 * @var bool
	 */
	protected $is_guarded = true;

	/**
	 * Constructs a new model with provided attributes.
	 *
	 * If self::OBJECT_KEY is passed as one of the attributes, the underlying post
	 * will be overwritten.
	 *
	 * @param array <string, mixed> $attributes
	 */
	public function __construct( array $attributes = array() ) {
		$this->maybe_boot();
		$this->sync_original();

		if ( $this->uses_wp_object() ) {
			$this->create_wp_object();
		}

		$this->unguard();
		$this->refresh( $attributes );
		$this->reguard();
	}

	/**
	 * Refreshes the model's current attributes with the provided array.
	 *
	 * The model's attributes will match what was provided in the array,
	 * and any attributes not passed
	 *
	 * @param array $attributes
	 *
	 * @return $this
	 */
	public function refresh( array $attributes ) {
		$this->clear();

		return $this->merge( $attributes );
	}

	/**
	 * Merges the provided attributes with the provided array.
	 *
	 * @param array $attributes
	 *
	 * @return $this
	 */
	public function merge( array $attributes ) {
		foreach ( $attributes as $name => $value ) {
			$this->set_attribute( $name, $value );
		}

		return $this;
	}

	/**
	 * Get the model's table attributes.
	 *
	 * Returns the array of for the model that will either need to be
	 * saved in postmeta or a separate table.
	 *
	 * @return array
	 */
	public function get_table_attributes() {
		return $this->attributes[ self::TABLE_KEY ];
	}

	/**
	 * Get the model's original attributes.
	 *
	 * @return array
	 */
	public function get_original_table_attributes() {
		return $this->original[ self::TABLE_KEY ];
	}

	/**
	 * Retrieve an array of the attributes on the model
	 * that have changed compared to the model's
	 * original data.
	 *
	 * @return array
	 */
	public function get_changed_table_attributes() {
		$changed = array();

		foreach ( $this->get_table_attributes() as $key => $value ) {
			if ( $value !==
				 $this->get_original_attribute( $key )
			) {
				$changed[ $key ] = $value;
			}
		}

		return $changed;
	}

	/**
	 * Get the model's underlying post.
	 *
	 * Returns the underlying WP_Post object for the model, representing
	 * the data that will be save in the wp_posts table.
	 *
	 * @return false|WP_Post|WP_Term
	 */
	public function get_underlying_wp_object() {
		if ( isset( $this->attributes[ self::OBJECT_KEY ] ) ) {
			return $this->attributes[ self::OBJECT_KEY ];
		}

		return false;
	}

	/**
	 * Get the model's original underlying post.
	 *
	 * @return WP_Post
	 */
	public function get_original_underlying_wp_object() {
		return $this->original[ self::OBJECT_KEY ];
	}

	/**
	 * Get the model attributes on the WordPress object
	 * that have changed compared to the model's
	 * original attributes.
	 *
	 * @return array
	 */
	public function get_changed_wp_object_attributes() {
		$changed = array();

		foreach ( $this->get_wp_object_keys() as $key ) {
			if ( $this->get_attribute( $key ) !==
				 $this->get_original_attribute( $key )
			) {
				$changed[ $key ] = $this->get_attribute( $key );
			}
		}

		return $changed;
	}

	/**
	 * Magic __set method.
	 *
	 * Passes the name and value to set_attribute, which is where the magic happens.
	 *
	 * @param string $name
	 * @param mixed  $value
	 */
	public function __set( $name, $value ) {
		$this->set_attribute( $name, $value );
	}

	/**
	 * Sets the model attributes.
	 *
	 * Checks whether the model attribute can be set, check if it
	 * maps to the WP_Post property, otherwise, assigns it to the
	 * table attribute array.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return $this
	 *
	 * @throws GuardedPropertyException
	 */
	public function set_attribute( $name, $value ) {
		if ( self::OBJECT_KEY === $name ) {
			return $this->override_wp_object( $value );
		}

		if ( self::TABLE_KEY === $name ) {
			return $this->override_table( $value );
		}

		if ( ! $this->is_fillable( $name ) ) {
			throw new GuardedPropertyException;
		}

		if ( $method = $this->has_map_method( $name ) ) {
			$this->attributes[ self::OBJECT_KEY ]->{$this->{$method}()} = $value;
		} else {
			$this->attributes[ self::TABLE_KEY ][ $name ] = $value;
		}

		return $this;
	}

	/**
	 * Retrieves all the attribute keys for the model.
	 *
	 * @return array
	 */
	public function get_attribute_keys() {
		if ( isset( self::$memo[ get_called_class() ][ __METHOD__ ] ) ) {
			return self::$memo[ get_called_class() ][ __METHOD__ ];
		}

		return self::$memo[ get_called_class() ][ __METHOD__ ]
			= array_merge(
				$this->fillable,
				$this->guarded,
				$this->get_compute_methods()
			);
	}

	/**
	 * Retrieves the attribute keys that aren't mapped to a post.
	 *
	 * @return array
	 */
	public function get_table_keys() {
		if ( isset( self::$memo[ get_called_class() ][ __METHOD__ ] ) ) {
			return self::$memo[ get_called_class() ][ __METHOD__ ];
		}

		$keys = array();

		foreach ( $this->get_attribute_keys() as $key ) {
			if ( ! $this->has_map_method( $key ) &&
				 ! $this->has_compute_method( $key )
			) {
				$keys[] = $key;
			}
		}

		return self::$memo[ get_called_class() ][ __METHOD__ ] = $keys;
	}

	/**
	 * Retrieves the attribute keys that are mapped to a post.
	 *
	 * @return array
	 */
	public function get_wp_object_keys() {
		if ( isset( self::$memo[ get_called_class() ][ __METHOD__ ] ) ) {
			return self::$memo[ get_called_class() ][ __METHOD__ ];
		}

		$keys = array();

		foreach ( $this->get_attribute_keys() as $key ) {
			if ( $this->has_map_method( $key ) ) {
				$keys[] = $key;
			}
		}

		return self::$memo[ get_called_class() ][ __METHOD__ ] = $keys;
	}

	/**
	 * Returns the model's keys that are computed at call time.
	 *
	 * @return array
	 */
	public function get_computed_keys() {
		if ( isset( self::$memo[ get_called_class() ][ __METHOD__ ] ) ) {
			return self::$memo[ get_called_class() ][ __METHOD__ ];
		}

		$keys = array();

		foreach ( $this->get_attribute_keys() as $key ) {
			if ( $this->has_compute_method( $key ) ) {
				$keys[] = $key;
			}
		}

		return self::$memo[ get_called_class() ][ __METHOD__ ] = $keys;
	}

	/**
	 * Serializes the model's public data into an array.
	 *
	 * @return array
	 */
	public function serialize() {
		$attributes = array();

		if ( $this->visible ) {
			// If visible attributes are set, we'll only reveal those.
			foreach ( $this->visible as $key ) {
				$attributes[ $key ] = $this->get_attribute( $key );
			}
		} elseif ( $this->hidden ) {
			// If hidden attributes are set, we'll grab everything and hide those.
			foreach ( $this->get_attribute_keys() as $key ) {
				if ( ! in_array( $key, $this->hidden ) ) {
					$attributes[ $key ] = $this->get_attribute( $key );
				}
			}
		} else {
			// If nothing is hidden/visible, we'll grab and reveal everything.
			foreach ( $this->get_attribute_keys() as $key ) {
				$attributes[ $key ] = $this->get_attribute( $key );
			}
		}

		return array_map( function ( $attribute ) {
			if ( $attribute instanceof Serializes ) {
				return $attribute->serialize();
			}

			return $attribute;
		}, $attributes );
	}

	/**
	 * Syncs the current attributes to the model's original.
	 *
	 * @return $this
	 */
	public function sync_original() {
		$this->original = $this->attributes;

		if ( $this->attributes[ self::OBJECT_KEY ] ) {
			$this->original[ self::OBJECT_KEY ] = clone $this->attributes[ self::OBJECT_KEY ];
		}

		foreach ( $this->original[ self::TABLE_KEY ] as $key => $item ) {
			if ( is_object( $item ) ) {
				$this->original[ $key ] = clone $item;
			}
		}

		return $this;
	}

	/**
	 * Checks if a given attribute is mass-fillable.
	 *
	 * Returns true if the attribute can be filled, false if it can't.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	private function is_fillable( $name ) {
		// If this model isn't guarded, everything is fillable.
		if ( ! $this->is_guarded ) {
			return true;
		}

		// If it's in the fillable array, then it's fillable.
		if ( in_array( $name, $this->fillable ) ) {
			return true;
		}

		// If it's explicitly guarded, then it's not fillable.
		if ( in_array( $name, $this->guarded ) ) {
			return false;
		}

		// If fillable hasn't been defined, then everything else fillable.
		return ! $this->fillable;
	}

	/**
	 * Overrides the current WordPress object with a provided one.
	 *
	 * Resets the post's default values and stores it in the attributes.
	 *
	 * @param WP_Post|WP_Term|null $value
	 *
	 * @return $this
	 */
	private function override_wp_object( $value ) {
		if ( is_object( $value ) ) {
			$this->attributes[ self::OBJECT_KEY ] = $this->set_wp_object_constants( $value );
		} else {
			$this->attributes[ self::OBJECT_KEY ] = null;

			if ( $this->uses_wp_object() ) {
				$this->create_wp_object();
			}
		}

		return $this;
	}

	/**
	 * Overrides the current table attributes array with a provided one.
	 *
	 * @param array $value
	 *
	 * @return $this
	 */
	private function override_table( array $value ) {
		$this->attributes[ self::TABLE_KEY ] = $value;

		return $this;
	}

	/**
	 * Create and set with a new blank post.
	 *
	 * Creates a new WP_Post object, assigns it the default attributes,
	 * and stores it in the attributes.
	 *
	 * @throws LogicException
	 */
	private function create_wp_object() {
		switch ( true ) {
			case $this instanceof UsesWordPressPost:
				$object = new WP_Post( (object) array() );
				break;
			case $this instanceof UsesWordPressTerm:
				$object = new WP_Term( (object) array() );
				break;
			default:
				throw new LogicException;
				break;
		}

		$this->attributes[ self::OBJECT_KEY ] = $this->set_wp_object_constants( $object );
	}

	/**
	 * Enforces values on the post that can't change.
	 *
	 * Primarily, this is used to make sure the post_type always maps
	 * to the model's "$type" property, but this can all be overridden
	 * by the developer to enforce other values in the model.
	 *
	 * @param object $object
	 *
	 * @return object
	 */
	protected function set_wp_object_constants( $object ) {
		if ( $this instanceof UsesWordPressPost ) {
			$object->post_type = static::get_post_type();
		}

		if ( $this instanceof UsesWordPressTerm ) {
			$object->taxonomy = static::get_taxonomy();
		}

		return $object;
	}

	/**
	 * Magic __get method.
	 *
	 * Passes the name and value to get_attribute, which is where the magic happens.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		return $this->get_attribute( $name );
	}

	/**
	 * Retrieves the model attribute.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 *
	 * @throws PropertyDoesNotExistException If property isn't found.
	 */
	public function get_attribute( $name ) {
		if ( $method = $this->has_map_method( $name ) ) {
			return $this->attributes[ self::OBJECT_KEY ]->{$this->{$method}()};
		}

		if ( $method = $this->has_compute_method( $name ) ) {
			return $this->{$method}();
		}

		if ( isset( $this->attributes[ self::TABLE_KEY ][ $name ] ) ) {
			return $this->attributes[ self::TABLE_KEY ][ $name ];
		}

		if ( isset( $this->defaults[ $name ] ) ) {
			return $this->defaults[ $name ];
		}

		return null;
	}

	/**
	 * Retrieve the model's original attribute value.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 *
	 * @throws PropertyDoesNotExistException If property isn't found.
	 */
	public function get_original_attribute( $name ) {
		$original_attributes = $this->original;

		if ( ! is_object( $original_attributes[ static::OBJECT_KEY ] ) ) {
			unset( $original_attributes[ static::OBJECT_KEY ] );
		}

		$original = new static( $original_attributes );

		return $original->get_attribute( $name );
	}

	/**
	 * Fetches the Model's primary ID, depending on the model
	 * implementation.
	 *
	 * @return int
	 *
	 * @throws LogicException
	 */
	public function get_primary_id() {
		if ( $this instanceof UsesWordPressPost ) {
			return $this->get_underlying_wp_object()->ID;
		}

		if ( $this instanceof UsesWordPressTerm ) {
			return $this->get_underlying_wp_object()->term_id;
		}

		if ( $this instanceof UsesCustomTable ) {
			return $this->get_attribute( $this->get_primary_key() );
		}

		// Model w/o wp_object not yet supported.
		throw new LogicException;
	}

	/**
	 * Checks whether the attribute has a map method.
	 *
	 * This is used to determine whether the attribute maps to a
	 * property on the underlying WP_Post object. Returns the
	 * method if one exists, returns false if it doesn't.
	 *
	 * @param string $name
	 *
	 * @return false|string
	 */
	protected function has_map_method( $name ) {
		if ( method_exists( $this, $method = "map_{$name}" ) ) {
			return $method;
		}

		return false;
	}

	/**
	 * Checks whether the attribute has a compute method.
	 *
	 * This is used to determine if the attribute should be computed
	 * from other attributes.
	 *
	 * @param string $name
	 *
	 * @return false|string
	 */
	protected function has_compute_method( $name ) {
		if ( method_exists( $this, $method = "compute_{$name}" ) ) {
			return $method;
		}

		return false;
	}

	/**
	 * Clears all the current attributes from the model.
	 *
	 * This does not touch the model's original attributes, and will
	 * only clear fillable attributes, unless the model is unguarded.
	 *
	 * @return $this
	 */
	public function clear() {
		$keys = array_merge(
			$this->get_table_keys(),
			$this->get_wp_object_keys()
		);

		foreach ( $keys as $key ) {
			try {
				$this->set_attribute( $key, null );
			} catch ( GuardedPropertyException $e ) {
				// We won't clear out guarded attributes.
			}
		}

		return $this;
	}

	/**
	 * Unguards the model.
	 *
	 * Sets the model to be unguarded, allowing the filling of
	 * guarded attributes.
	 */
	public function unguard() {
		$this->is_guarded = false;
	}

	/**
	 * Reguards the model.
	 *
	 * Sets the model to be guarded, preventing filling of
	 * guarded attributes.
	 */
	public function reguard() {
		$this->is_guarded = true;
	}

	/**
	 * Retrieves all the compute methods on the model.
	 *
	 * @return array
	 */
	protected function get_compute_methods() {
		$methods = get_class_methods( get_called_class() );
		$methods = array_filter( $methods, function ( $method ) {
			return strrpos( $method, 'compute_', - strlen( $method ) ) !== false;
		} );
		$methods = array_map( function ( $method ) {
			return substr( $method, strlen( 'compute_' ) );
		}, $methods );

		return $methods;
	}

	/**
	 * Sets up the memo array for the creating model.
	 */
	private function maybe_boot() {
		if ( ! isset( self::$memo[ get_called_class() ] ) ) {
			self::$memo[ get_called_class() ] = array();
		}
	}

	/**
	 * Whether this Model uses an underlying WordPress object.
	 *
	 * @return bool
	 */
	protected function uses_wp_object() {
		return $this instanceof UsesWordPressPost ||
			$this instanceof UsesWordPressTerm;
	}
}
