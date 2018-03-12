<?php
namespace Intraxia\Jaxion\Axolotl;

use Intraxia\Jaxion\Contract\Axolotl\Collection as CollectionContract;
use Intraxia\Jaxion\Contract\Axolotl\Serializes;
use InvalidArgumentException;
use OutOfBoundsException;
use OutOfRangeException;

/**
 * Class Collection
 *
 * @package Intraxia\Jaxion
 * @subpackage Axolotl
 */
class Collection implements CollectionContract {

	/**
	 * Collection elements.
	 *
	 * @var array
	 */
	protected $elements = array();

	/**
	 * Collection type to enforce.
	 *
	 * @var Type
	 */
	private $type;

	/**
	 * Where Collection is in loop.
	 *
	 * @var int
	 */
	protected $position = 0;

	/**
	 * Collection constructor.
	 *
	 * @param string $type
	 * @param array  $elements
	 */
	public function __construct( $type, array $elements = array() ) {
		$this->type = new Type( $type );

		if ( $this->type->is_model() ) {
			foreach ( $elements as $idx => $element ) {
				if ( is_array( $element ) ) {
					$elements[ $idx ] = $this->type->create_model( $element );
				}
			}
		}

		if ( $elements ) {
			$this->type->validate_elements( $elements );
		}

		$this->elements = $elements;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type->get_type();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $element
	 *
	 * @return Collection
	 *
	 * @throws InvalidArgumentException
	 */
	public function add( $element ) {
		if ( $this->type->is_model() && is_array( $element ) ) {
			$element = $this->type->create_model( $element );
		}

		$this->type->validate_element( $element );

		$elements   = $this->elements;
		$elements[] = $element;

		$collection = new static( $this->get_type() );
		$collection->set_from_trusted( $elements );

		return $collection;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return Collection
	 */
	public function clear() {
		return new static( $this->get_type() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  callable $condition Condition to satisfy.
	 *
	 * @return bool
	 */
	public function contains( $condition ) {
		return (bool) $this->find( $condition );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  callable $condition Condition to satisfy.
	 *
	 * @return mixed
	 */
	public function find( $condition ) {
		$index = $this->find_index( $condition );

		return -1 === $index ? null : $this->elements[ $index ];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  callable $condition Condition to satisfy.
	 *
	 * @return int
	 */
	public function find_index( $condition ) {
		$index = -1;

		for ( $i = 0, $count = count( $this->elements ); $i < $count; $i++ ) {
			if ( call_user_func( $condition, ($this->at( $i ) ) ) ) {
				$index = $i;
				break;
			}
		}

		return $index;
	}

	/**
	 * Fetches the element at the provided index.
	 *
	 * @param int $index Index to get element from.
	 *
	 * @return mixed
	 *
	 * @throws OutOfRangeException
	 */
	public function at( $index ) {
		$this->validate_index( $index );

		return $this->elements[ $index ];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  int $index Index to check for existence.
	 *
	 * @return bool
	 *
	 * @throws InvalidArgumentException
	 */
	public function index_exists( $index ) {
		if ( ! is_int( $index ) ) {
			throw new InvalidArgumentException( 'Index must be an integer' );
		}

		if ( $index < 0 ) {
			throw new InvalidArgumentException( 'Index must be a non-negative integer' );
		}

		return $index < $this->count();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  callable $condition Condition to satisfy.
	 *
	 * @return Collection
	 */
	public function filter( $condition ) {
		$elements = array();

		foreach ( $this->elements as $element ) {
			if ( call_user_func( $condition, $element ) ) {
				$elements[] = $element;
			}
		}

		return $this->new_from_trusted( $elements );
	}
	/**
	 * {@inheritdoc}
	 *
	 * @param  callable $condition Condition to satisfy.
	 *
	 * @return mixed
	 */
	public function find_last( $condition ) {
		$index = $this->find_last_index( $condition );

		return -1 === $index ? null : $this->elements[ $index ];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  callable $condition
	 * @return int
	 */
	public function find_last_index( $condition ) {
		$index = -1;

		for ( $i = count( $this->elements ) - 1; $i >= 0; $i-- ) {
			if ( call_user_func( $condition, $this->elements[ $i ] ) ) {
				$index = $i;
				break;
			}
		}

		return $index;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  int $start Begining index to slice from.
	 * @param  int $end   End index to slice to.
	 *
	 * @return Collection
	 *
	 * @throws InvalidArgumentException
	 */
	public function slice( $start, $end ) {
		if ( $start < 0 || ! is_int( $start ) ) {
			throw new InvalidArgumentException( 'Start must be a non-negative integer' );
		}

		if ( $end < 0 || ! is_int( $end ) ) {
			throw new InvalidArgumentException( 'End must be a positive integer' );
		}

		if ( $start > $end ) {
			throw new InvalidArgumentException( 'End must be greater than start' );
		}

		if ( $end > $this->count() + 1 ) {
			throw new InvalidArgumentException( 'End must be less than the count of the items in the Collection' );
		}

		$length = $end - $start + 1;

		return $this->new_from_trusted( array_slice( $this->elements, $start, $length ) );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param int   $index     Index to start at.
	 * @param mixed $element Element to insert.
	 *
	 * @return Collection
	 *
	 * @throws InvalidArgumentException
	 * @throws OutOfRangeException
	 */
	public function insert( $index, $element ) {
		$this->validate_index( $index );
		$this->type->validate_element( $element );

		$a = array_slice( $this->elements, 0, $index );
		$b = array_slice( $this->elements, $index, count( $this->elements ) );

		$a[] = $element;

		return $this->new_from_trusted( array_merge( $a, $b ) );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param int   $index    Index to start insertion at.
	 * @param array $elements Elements in insert.
	 *
	 * @return Collection
	 *
	 * @throws OutOfRangeException
	 */
	public function insert_range( $index, array $elements ) {
		$this->validate_index( $index );
		$this->type->validate_elements( $elements );

		if ( $index < 0 ) {
			$index = $this->count() + $index + 1;
		}

		return $this->new_from_trusted(
			array_merge(
				array_slice( $this->elements, 0, $index ),
				$elements,
				array_slice( $this->elements, $index, count( $this->elements ) )
			)
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  callable $condition Condition to satisfy.
	 *
	 * @return Collection
	 */
	public function reject( $condition ) {
		$inverse = function ( $element ) use ( $condition ) {
			return ! call_user_func( $condition, $element );
		};

		return $this->filter( $inverse );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  int $index Index to remove.
	 *
	 * @return Collection
	 *
	 * @throws OutOfRangeException
	 */
	public function remove_at( $index ) {
		$this->validate_index( $index );

		$elements = $this->elements;

		return $this->new_from_trusted(
			array_merge(
				array_slice( $elements, 0, $index ),
				array_slice( $elements, $index + 1, count( $elements ) )
			)
		);
	}
	/**
	 * {@inheritdoc}
	 *
	 * @return Collection
	 */
	public function reverse() {
		return $this->new_from_trusted(
			array_reverse( $this->elements )
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $callback Sort callback.
	 *
	 * @return Collection
	 */
	public function sort( $callback ) {
		$elements = $this->elements;
		usort( $elements, $callback );
		return $this->new_from_trusted( $elements );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->elements;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $callable Reducer function.
	 *
	 * @param null     $initial  Initial reducer value.
	 *
	 * @return mixed
	 */
	public function reduce( $callable, $initial = null ) {
		return array_reduce( $this->elements, $callable, $initial );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $condition Condition callback.
	 *
	 * @return bool
	 */
	public function every( $condition ) {
		$response = true;

		foreach ( $this->elements as $element ) {
			$result = call_user_func( $condition, $element );

			if ( false === $result ) {
				$response = false;
				break;
			}
		}

		return $response;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  int $num Number of elements to drop.
	 *
	 * @return Collection
	 *
	 * @throws InvalidArgumentException
	 */
	public function drop( $num ) {
		if ( $num > $this->count() ) {
			$num = $this->count();
		}

		return $this->slice( $num, $this->count() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param int $num Number of elements to drop.
	 *
	 * @return Collection
	 *
	 * @throws InvalidArgumentException
	 */
	public function drop_right( $num ) {
		return $num !== $this->count()
			? $this->slice( 0, $this->count() - $num - 1 )
			: $this->clear();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $condition Condition callback.
	 *
	 * @return Collection
	 */
	public function drop_while( $condition ) {
		$count = $this->count_while_true( $condition );
		return $count ? $this->drop( $count ) : $this;
	}
	/**
	 * {@inheritdoc}
	 *
	 * @return Collection
	 *
	 * @throws InvalidArgumentException
	 */
	public function tail() {
		return $this->slice( 1, $this->count() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  int $num Number of elements to take.
	 *
	 * @return Collection
	 *
	 * @throws InvalidArgumentException
	 */
	public function take( $num ) {
		return $this->slice( 0, $num - 1 );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param int $num Number of elements to take.
	 *
	 * @return Collection
	 *
	 * @throws InvalidArgumentException
	 */
	public function take_right( $num ) {
		return $this->slice( $this->count() - $num, $this->count() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $condition Callback function.
	 *
	 * @return Collection
	 */
	public function take_while( $condition ) {
		$count = $this->count_while_true( $condition );

		return $count ? $this->take( $count ) : $this->clear();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $callable Callback function.
	 */
	public function each( $callable ) {
		foreach ( $this->elements as $element ) {
			call_user_func( $callable, $element );
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $callable Callback function.
	 *
	 * @return Collection
	 */
	public function map( $callable ) {
		$elements = array();
		$type = null;
		foreach ( $this->elements as $element ) {
			$result = call_user_func( $callable, $element );

			if ( null === $type ) {
				$type = gettype( $result );

				if ( 'object' === $type ) {
					$type = get_class( $result );
				}
			}

			$elements[] = $result;
		}

		return $this->new_from_trusted( $elements, $type ? : $this->get_type() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param callable $callable Reducer function.
	 * @param null     $initial  Initial value.
	 *
	 * @return mixed
	 */
	public function reduce_right( $callable, $initial = null ) {
		return array_reduce(
			array_reverse( $this->elements ),
			$callable,
			$initial
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return Collection
	 */
	public function shuffle() {
		$elements = $this->elements;
		shuffle( $elements );

		return $this->new_from_trusted( $elements );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array|Collection $elements Array of elements to merge.
	 *
	 * @return Collection
	 *
	 * @throws InvalidArgumentException
	 */
	public function merge( $elements ) {
		if ( $elements instanceof static ) {
			$elements = $elements->to_array();
		}

		if ( ! is_array( $elements ) ) {
			throw new InvalidArgumentException( 'Merge must be given array or Collection' );
		}

		$this->type->validate_elements( $elements );

		return $this->new_from_trusted(
			array_merge( $this->elements, $elements )
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return mixed
	 *
	 * @throws OutOfBoundsException
	 */
	public function first() {
		if ( empty( $this->elements ) ) {
			throw new OutOfBoundsException( 'Cannot get first element of empty Collection' );
		}

		return reset( $this->elements );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return mixed
	 *
	 * @throws OutOfBoundsException
	 */
	public function last() {
		if ( empty( $this->elements ) ) {
			throw new OutOfBoundsException( 'Cannot get last element of empty Collection' );
		}

		return end( $this->elements );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->elements );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function serialize() {
		return $this->map(function( $element ) {
			if ( $element instanceof Serializes ) {
				return $element->serialize();
			}

			return $element;
		} )->to_array();
	}

	/**
	 * Return the current element.
	 *
	 * @return mixed
	 */
	public function current() {
		return $this->at( $this->position );
	}

	/**
	 * Move forward to next element.
	 */
	public function next() {
		$this->position ++;
	}

	/**
	 * Return the key of the current element.
	 *
	 * @return mixed
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return bool
	 */
	public function valid() {
		return isset( $this->elements[ $this->position ] );
	}

	/**
	 * Rewind the Iterator to the first element.
	 */
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * Creates a new instance of the Collection
	 * from a trusted set of elements.
	 *
	 * @param array      $elements Array of elements to pass into new collection.
	 * @param null|mixed $type
	 *
	 * @return static
	 */
	protected function new_from_trusted( array $elements, $type = null ) {
		$collection = new static( null !== $type ? $type : $this->get_type() );
		$collection->set_from_trusted( $elements );

		return $collection;
	}

	/**
	 * Sets the elements without validating them.
	 *
	 * @param array $elements Pre-validated elements to set.
	 */
	protected function set_from_trusted( array $elements ) {
		$this->elements = $elements;
	}

	/**
	 * Number of elements true for the condition.
	 *
	 * @param callable $condition Condition to check.
	 * @return int
	 */
	protected function count_while_true( $condition ) {
		$count = 0;

		foreach ( $this->elements as $element ) {
			if ( ! $condition($element) ) {
				break;
			}
			$count++;
		}

		return $count;
	}

	/**
	 * Validates a number to be used as an index.
	 *
	 * @param  integer $index The number to be validated as an index.
	 *
	 * @throws OutOfRangeException
	 */
	protected function validate_index( $index ) {
		$exists = $this->index_exists( $index );

		if ( ! $exists ) {
			throw new OutOfRangeException( 'Index out of bounds of collection' );
		}
	}
}
