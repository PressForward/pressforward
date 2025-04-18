<?php
/**
 * Immutable representation of a single feed item at the time of import.
 *
 * @package PressForward
 *
 * @psalm-immutable
 * @phpstan-immutable
 */

namespace PressForward\Core\DTO;

use JsonSerializable;
use InvalidArgumentException;

/**
 * Immutable representation of a single feed item at the time of import.
 */
final class FeedItem implements JsonSerializable {

	/**
	 * The title of the item.
	 *
	 * @var string
	 */
	private $item_title;

	/**
	 * The title of the source.
	 *
	 * @var string
	 */
	private $source_title;

	/**
	 * The date of the item.
	 *
	 * @var string
	 */
	private $item_date;

	/**
	 * The author of the item.
	 *
	 * @var string|string[]|null
	 */
	private $item_author;

	/**
	 * The content of the item.
	 *
	 * @var string
	 */
	private $item_content;

	/**
	 * The link to the item.
	 *
	 * @var string
	 */
	private $item_link;

	/**
	 * The ID of the item.
	 *
	 * @var string
	 */
	private $item_id;

	/**
	 * The date of the item in WordPress format.
	 *
	 * @var string
	 */
	private $item_wp_date;

	/**
	 * The tags associated with the item.
	 *
	 * @var string
	 */
	private $item_tags;

	/**
	 * The description of the item.
	 *
	 * @var string|null
	 */
	private $description;

	/**
	 * Parent feed ID.
	 *
	 * @var int
	 */
	private $parent_feed_id;

	/**
	 * Private to prevent `new FeedItem()`; use factory instead.
	 */
	private function __construct() {}

	/**
	 * FeedItem object factory.
	 *
	 * @param array $data The data to populate the FeedItem object.
	 *
	 * @phpstan-param array{
	 *     item_title:string,
	 *     source_title:string,
	 *     item_date:string,
	 *     item_author:string|array<int,string>|null,
	 *     item_content:string,
	 *     item_link:string,
	 *     item_id:string,
	 *     item_wp_date:string,
	 *     item_tags:string,
	 *     description:?string,
	 *     parent_feed_id:int
	 * } $data
	 *
	 * @throws InvalidArgumentException If required keys are missing.
	 */
	public static function from_array( array $data ): self {
		$required = [
			'item_title',
			'source_title',
			'item_date',
			'item_author',
			'item_content',
			'item_link',
			'item_id',
			'item_wp_date',
			'item_tags',
			'description',
			'parent_feed_id',
		];

		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				// phpcs:ignore WordPress.Security
				throw new InvalidArgumentException( "Missing key: $key" );
			}
		}

		$self = new self();

		// Assign once; never change again.
		foreach ( $data as $prop => $value ) {
			$self->$prop = $value;
		}

		return $self;
	}

	/**
	 * Get the title of the item.
	 *
	 * @return string The title of the item.
	 */
	public function item_title(): string {
		return $this->item_title;
	}

	/**
	 * Get the title of the source.
	 *
	 * @return string The title of the source.
	 */
	public function source_title(): string {
		return $this->source_title;
	}

	/**
	 * Get the date of the item.
	 *
	 * @return string
	 */
	public function item_date(): string {
		return $this->item_date;
	}

	/**
	 * Get the author of the item.
	 *
	 * @return string|string[]|null The author of the item.
	 */
	public function item_author() {
		return $this->item_author;
	}

	/**
	 * Get the content of the item.
	 *
	 * @return string The content of the item.
	 */
	public function item_content(): string {
		return $this->item_content;
	}

	/**
	 * Get the link to the item.
	 *
	 * @return string The link to the item.
	 */
	public function item_link(): string {
		return $this->item_link;
	}

	/**
	 * Get the ID of the item.
	 *
	 * @return string The ID of the item.
	 */
	public function item_id(): string {
		return $this->item_id;
	}

	/**
	 * Get the date of the item in WordPress format.
	 *
	 * @return string The date of the item in WordPress format.
	 */
	public function item_wp_date(): string {
		return $this->item_wp_date;
	}

	/**
	 * Get the tags associated with the item.
	 *
	 * @return string The tags associated with the item.
	 */
	public function item_tags(): string {
		return $this->item_tags;
	}

	/**
	 * Get the description of the item.
	 *
	 * @return string|null The description of the item.
	 */
	public function description(): ?string {
		return $this->description;
	}

	/**
	 * Get the parent feed ID.
	 *
	 * @return int The parent feed ID.
	 */
	public function parent_feed_id(): int {
		return $this->parent_feed_id;
	}

	/**
	 * Disallow dynamic property writes.
	 *
	 * @param string $name The name of the property.
	 * @param mixed  $value The value to set.
	 * @throws \LogicException Always throws an exception.
	 */
	public function __set( $name, $value ) {
		throw new \LogicException( 'FeedItem is immutable.' );
	}

	/**
	 * Disallow cloning (optional but prudent).
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * {@inheritDoc}
	 *
	 * @return mixed
	 *
	 * @phpstan-return array{
	 *     item_title:string,
	 *     source_title:string,
	 *     item_date:string,
	 *     item_author:string|array<int,string>|null,
	 *     item_content:string,
	 *     item_link:string,
	 *     item_id:string,
	 *     item_wp_date:string,
	 *     item_tags:string,
	 *     description:?string,
	 *     parent_feed_id:int
	 * }
	 */
	public function jsonSerialize() {
		return get_object_vars( $this );
	}
}
