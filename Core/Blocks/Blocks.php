<?php
/**
 * Loads Blocks libraries.
 *
 * @since 5.6.0
 * @package PressForward
 */

namespace PressForward\Core\Blocks;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

/**
 * Blocks class.
 *
 * @since 5.6.0
 */
class Blocks implements HasActions, HasFilters {
	/**
	 * {@inheritDoc}
	 */
	public function action_hooks() {
		return [
			[
				'hook'     => 'init',
				'method'   => 'register_blocks',
				'priority' => 5,
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function filter_hooks() {
		return [
			[
				'hook'   => 'block_categories_all',
				'method' => 'register_block_category',
			],
		];
	}

	/**
	 * Registers blocks.
	 *
	 * @since 5.6.0
	 *
	 * @return void
	 */
	public function register_blocks() {
		$block_files = glob( PF_ROOT . '/assets/src/blocks/**/index.php' );

		foreach ( $block_files as $block_file ) {
			require_once $block_file;
		}
	}

	/**
	 * Registers the PressForward block category.
	 *
	 * @since 5.6.0
	 *
	 * @param array $categories Array of block categories.
	 * @return array
	 */
	public function register_block_category( $categories ) {
		return array_merge(
			$categories,
			[
				[
					'slug'  => 'pressforward',
					'title' => __( 'PressForward', 'pressforward' ),
				],
			]
		);
	}
}
