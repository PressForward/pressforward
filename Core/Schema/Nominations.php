<?php
namespace PressForward\Core\Schema;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;
/**
 * Functionality related to nominations
 */
class Nominations implements HasActions, HasFilters {
	function __construct() {
		$this->post_type = 'nomination';
		// var_dump($this->post_type); die();
		// add_action('edit_post', array( $this, 'send_nomination_for_publishing'));
		// add_action( 'manage_nomination_posts_custom_column',  array($this, 'nomination_custom_columns') );
		// add_filter( "manage_edit-nomination_sortable_columns", array($this, "nomination_sortable_columns") );
		// add_action( 'feeder_menu', array($this, "nominate_this_tile"), 11 );
	}

	public function action_hooks() {
		return array(
			array(
				'hook'   => 'init',
				'method' => 'register_post_type',
			),
		);
	}

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
	 * Register the 'nomination' post type
	 *
	 * @since 1.7
	 */
	function register_post_type() {
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

		// register_taxonomy_for_object_type( pressforward()->get_feed_folder_taxonomy(), $this->post_type );
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

	// This and the next few functions are to modify the table that shows up when you click "Nominations".
	function edit_nominations_columns( $columns ) {

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

	// Make these columns sortable
	function nomination_sortable_columns() {
		return array(
			'title'           => 'title',
			'date'            => 'date',
			'nomcount'        => 'nomcount',
			'nominatedby'     => 'nominatedby',
			'original_author' => 'original_author',
			'date_nominated'  => 'date_nominated',
		);
	}

	// The builder for the box that shows us the nomination metadata.
	public function nominations_box_builder() {
		global $post;

		do_action( 'nominations_box' );

	}




}
