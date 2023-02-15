<?php
/**
 * Nominations data.
 *
 * @package PressForward
 */

namespace PressForward\Core\Schema;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

/**
 * Functionality related to nominations
 */
class Nominations implements HasActions, HasFilters {
	/**
	 * Post type.
	 *
	 * @access public
	 * @var string
	 */
	public $post_type;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->post_type = 'nomination';
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'init',
				'method' => 'register_post_type',
			),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_hooks() {
		return array(
			array(
				'hook'   => 'manage_edit-nomination_columns',
				'method' => 'edit_nominations_columns',
			),
			array(
				'hook'   => 'manage_edit-nomination_sortable_columns',
				'method' => 'nomination_sortable_columns',
			),
		);
	}

	/**
	 * Registers the 'nomination' post type.
	 *
	 * @since 1.7
	 */
	public function register_post_type() {
		$args = array(
			'labels'               => array(
				'name'               => __( 'Nominations', 'pf' ),
				'singular_name'      => __( 'Nomination', 'pf' ),
				'add_new'            => __( 'Nominate', 'pf' ),
				'add_new_item'       => __( 'Add New Nomination', 'pf' ),
				'edit_item'          => __( 'Edit Nomination', 'pf' ),
				'new_item'           => __( 'New Nomination', 'pf' ),
				'view_item'          => __( 'View Nomination', 'pf' ),
				'search_items'       => __( 'Search Nominations', 'pf' ),
				'not_found'          => __( 'No nominations found', 'pf' ),
				'not_found_in_trash' => __( 'No nominations found in Trash', 'pf' ),
			),
			'description'          => __( 'Posts from around the internet nominated for consideration to public posting', 'pf' ),
			// Not available to non-users.
			'public'               => false,
			// I want a UI for users to use, so true.
			'show_ui'              => true,
			// But not the default UI, we want to attach it to the plugin menu.
			'show_in_menu'         => false,
			// Linking in the metabox building function.
			'register_meta_box_cb' => array( $this, 'nominations_meta_boxes' ),
			'capability_type'      => 'post',
			// The type of input (besides the metaboxes) that it supports.
			'supports'             => array( 'title', 'editor', 'thumbnail' ),
			// I think this is set to false by the public argument, but better safe.
			'has_archive'          => false,
			'taxonomies'           => array( 'category', 'post_tag' ),
		);

		register_post_type( $this->post_type, $args );
	}

	/**
	 * Callback for registering meta boxes on 'nomination' post type
	 *
	 * @since 1.7
	 */
	public function nominations_meta_boxes() {
		add_meta_box(
			'pf-nominations',
			__( 'Nomination Data', 'pf' ),
			array( $this, 'nominations_box_builder' ),
			'nomination',
			'side',
			'high'
		);
	}

	/**
	 * Adds custom columns to Nominations view.
	 *
	 * This and the next few functions are to modify the table that shows up when you click "Nominations".
	 *
	 * @param array $columns Column names and headers.
	 * @return array
	 */
	public function edit_nominations_columns( $columns ) {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'title'           => __( 'Title', 'pf' ),
			'date'            => __( 'Last Modified', 'pf' ),
			'nomcount'        => __( 'Nominations', 'pf' ),
			'nominatedby'     => __( 'Nominated By', 'pf' ),
			'original_author' => __( 'Original Author', 'pf' ),
			'date_nominated'  => __( 'Date Nominated', 'pf' ),
		);

		return $columns;
	}

	/**
	 * Make these Nominations columns sortable.
	 *
	 * @return array
	 */
	public function nomination_sortable_columns() {
		return array(
			'title'           => 'title',
			'date'            => 'date',
			'nomcount'        => 'nomcount',
			'nominatedby'     => 'nominatedby',
			'original_author' => 'original_author',
			'date_nominated'  => 'date_nominated',
		);
	}

	/**
	 * The builder for the box that shows us the nomination metadata.
	 */
	public function nominations_box_builder() {
		do_action( 'nominations_box' );
	}
}
