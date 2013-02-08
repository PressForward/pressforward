<?php

/**
 * Functionality related to nominations
 */
class PF_Nominations {
	function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action('edit_post', array( $this, 'send_nomination_for_publishing'));
		add_filter( 'manage_edit-nomination_columns', array ($this, 'edit_nominations_columns') );
		add_action( 'manage_nomination_posts_custom_column',  array ($this, 'nomination_custom_columns') );
		add_filter( "manage_edit-nomination_sortable_columns", array ($this, "nomination_sortable_columns") );

	}

	/**
	 * Register the 'nomination' post type
	 *
	 * @since 1.7
	 */
	function register_post_type() {
		$args = array(
			'labels' => array(
				'name' => __( 'Nominations', 'pf' ),
				'singular_name' => __( 'Nomination', 'pf' ),
				'add_new' => __('Nominate', 'pf'),
				'add_new_item' => __('Add New Nomination', 'pf'),
				'edit_item' => __('Edit Nomination', 'pf'),
				'new_item' => __('New Nomination', 'pf'),
				'view_item' => __('View Nomination', 'pf'),
				'search_items' => __('Search Nominations', 'pf'),
				'not_found' => __('No nominations found', 'pf'),
				'not_found_in_trash' => __('No nominations found in Trash', 'pf')
			),
			'description' => __('Posts from around the internet nominated for consideration to public posting', 'pf'),
			//Not available to non-users.
			'public' => false,
			//I want a UI for users to use, so true.
			'show_ui' => true,
			//But not the default UI, we want to attach it to the plugin menu.
			'show_in_menu' => false,
			//Linking in the metabox building function.
			'register_meta_box_cb' => array($this, 'nominations_meta_boxes'),
			'capability_type' => 'post',
			//The type of input (besides the metaboxes) that it supports.
			'supports' => array('title', 'editor', 'thumbnail', 'revisions'),
			//I think this is set to false by the public argument, but better safe.
			'has_archive' => false
		);

		register_post_type('nomination', $args);

	}

	/**
	 * Callback for registering meta boxes on 'nomination' post type
	 *
	 * @since 1.7
	 */
	public function nominations_meta_boxes() {
		add_meta_box(
			'pf-nominations',
			__('Nomination Data', 'pf'),
			array($this, 'nominations_box_builder'),
			'nomination',
			'side',
			'high'
		);
	}

	# The builder for the box that shows us the nomination metadata.
	public function nominations_box_builder() {
		global $post;
		//wp_nonce_field( 'nominate_meta', 'nominate_meta_nonce' );
		$origin_item_ID = get_post_meta($post->ID, 'origin_item_ID', true);
		$nomination_count = get_post_meta($post->ID, 'nomination_count', true);
		$submitted_by = get_post_meta($post->ID, 'submitted_by', true);
		$source_title = get_post_meta($post->ID, 'source_title', true);
		$posted_date = get_post_meta($post->ID, 'posted_date', true);
		$nom_authors = get_post_meta($post->ID, 'authors', true);
		$nomination_permalink = get_post_meta($post->ID, 'nomination_permalink', true);
		$date_nominated = get_post_meta($post->ID, 'date_nominated', true);
		$user = get_user_by('id', $submitted_by);
		$item_tags = get_post_meta($post->ID, 'item_tags', true);
		$source_repeat = get_post_meta($post->ID, 'source_repeat', true);
		$this->meta_box_printer(__('Item ID', 'pf'), $origin_item_ID);
		$this->meta_box_printer(__('Nomination Count', 'pf'), $nomination_count);
		$this->meta_box_printer(__('Submitted By', 'pf'), $user->display_name);
		$this->meta_box_printer(__('Feed Title', 'pf'), $source_title);
		$this->meta_box_printer(__('Source Posted', 'pf'), $posted_date);
		$this->meta_box_printer(__('Source Authors', 'pf'), $nom_authors);
		$this->meta_box_printer(__('Source Link', 'pf'), $nomination_permalink, true, __('Original Post', 'pf'));
		$this->meta_box_printer(__('Item Tags', 'pf'), $item_tags);
		$this->meta_box_printer(__('Date Nominated', 'pf'), $date_nominated);
		$this->meta_box_printer(__('Repeated in Feed', 'pf'), $source_repeat);

	}

	public function send_nomination_for_publishing() {
		global $post;
		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		//if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		if ( isset( $_POST['post_status'] ) && isset( $_POST['post_type'] ) && ($_POST['post_status'] == 'publish') && ($_POST['post_type'] == 'nomination')){
		//print_r($_POST); die();
			$item_title = $_POST['post_title'];
			$item_content = $_POST['post_content'];
			$data = array(
				'post_status' => 'draft',
				'post_type' => 'post',
				'post_title' => $item_title,
				'post_content' => $item_content,
			);
			//Will need to use a meta field to pass the content's md5 id around to check if it has already been posted.

			//We assume that it is already in nominations, so no need to check there. This might be why we can't use post_exists here.
			//No need to origonate the check at the time of the feed item either. It can't become a post with the proper meta if it wasn't a nomination first.
			$item_id = get_post_meta($_POST['ID'], 'origin_item_ID', true);
			$nom_date = $_POST['aa'] . '-' . $_POST['mm'] . '-' . $_POST['jj'];

			//Now function will not update nomination count when it pushes nomination to publication.
			$post_check = $this->get_post_nomination_status($nom_date, $item_id, 'post', false);

			//Alternative check with post_exists? or use same as above?
			if ($post_check != true) {
				$newPostID = wp_insert_post( $data );
				add_post_meta($newPostID, 'origin_item_ID', $item_id, true);
				$nomCount = get_post_meta($_POST['ID'], 'nomination_count', true);
				add_post_meta($newPostID, 'nomination_count', $nomCount, true);
				$userID = get_post_meta($_POST['ID'], 'submitted_by', true);
				add_post_meta($newPostID, 'submitted_by', $userID, true);
				$item_permalink = get_post_meta($_POST['ID'], 'nomination_permalink', true);
				add_post_meta($newPostID, 'nomination_permalink', $item_permalink, true);
				$item_authorship = get_post_meta($_POST['ID'], 'authors', true);
				add_post_meta($newPostID, 'authors', $item_authorship, true);
				$date_nom = get_post_meta($_POST['ID'], 'date_nominated', true);
				add_post_meta($newPostID, 'date_nominated', $date_nom, true);
				$item_tags = get_post_meta($_POST['ID'], 'item_tags', true);
				add_post_meta($newPostID, 'item_tags', $item_tags, true);
				//If user wants to use tags, we'll create an option to use it.
				$nominators = get_post_meta($_POST['ID'], 'nominator_array', true);
				add_post_meta($newPostID, 'nominator_array', $nominators, true);
				$source_repeat = get_post_meta($_POST['ID'], 'source_repeat', true);
				add_post_meta($newPostID, 'source_repeat', $source_repeat, true);

				$already_has_thumb = has_post_thumbnail($_POST['ID']);
				if ($already_has_thumb)  {
					$post_thumbnail_id = get_post_thumbnail_id( $_POST['ID'] );
					set_post_thumbnail($newPostID, $post_thumbnail_id);
				}

			}
		}

	}

	public function get_post_nomination_status($date, $item_id, $post_type, $updateCount = true){
		//Get the query object, limiting by date, type and metavalue ID.
		$postsAfter = $this->get_posts_by_id_for_check( $date, $post_type, $item_id );
		//Assume that it will not find anything.
		$check = false;
		if ($postsAfter):

			global $post;
			foreach ($postsAfter as $post):
				setup_postdata($post);
				$id = get_the_ID();
				$origin_item_id = get_post_meta($id, 'origin_item_ID', true);
				if ($origin_item_id == $item_id) {
					$check = true;
					//Only update the nomination count on request.
					if ($updateCount){
						$nomCount = get_post_meta($id, 'nomination_count', true);
						$nomCount++;
						update_post_meta($id, 'nomination_count', $nomCount);
						$current_user = wp_get_current_user();
						if ( 0 == $current_user->ID ) {
							//Not logged in.
							//If we ever reveal this to non users and want to count nominations by all, here is where it will go.
						} else {
							$nominators = get_post_meta($id, 'nominator_array', true);
							$nominators .= ',' . $current_user->ID;
							update_post_meta($id, 'nominator_array', $nominators);
						}

					return $check;
					break;
					}
				}
			endforeach;
		endif;
		return $check;

	}

	public function meta_box_printer($title, $variable, $link = false, $anchor_text = 'Link'){
		echo '<strong>' . $title . '</strong>: ';
		if ($link === true){
			if ($anchor_text === 'Link'){
				$anchor_text = $this->__('Link', 'pf');
			}
			echo '<a href=';
			echo $variable;
			echo '" target="_blank">';
			echo $anchor_text;
			echo '</a>';
		} else {
			echo $variable;
		}

		echo '<br />';
	}

	# This and the next few functions are to modify the table that shows up when you click "Nominations".
	function edit_nominations_columns ( $columns ){

		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Title', 'pf'),
			'date' => __('Last Modified', 'pf'),
			'nomcount' => __('Nominations', 'pf'),
			'nominatedby' => __('Nominated By', 'pf'),
			'original_author' => __('Original Author', 'pf'),
			'date_nominated' => __('Date Nominated', 'pf')
		);

		return $columns;

	}

	//Via http://slides.helenhousandi.com/wcnyc2012.html#15 and http://svn.automattic.com/wordpress/tags/3.4/wp-admin/includes/class-wp-posts-list-table.php
	function nomination_custom_columns ( $column ) {

		global $post;
		switch ($column) {
			case 'nomcount':
				echo get_post_meta($post->ID, 'nomination_count', true);
				break;
			case 'nominatedby':
				$nominatorID = get_post_meta($post->ID, 'submitted_by', true);
				$user = get_user_by('id', $nominatorID);
				if ( is_a( $user, 'WP_User' ) ) {
					echo $user->display_name;
				}
				break;
			case 'original_author':
				$orig_auth = get_post_meta($post->ID, 'authors', true);
				echo $orig_auth;
				break;
			case 'date_nominated':
				$dateNomed = get_post_meta($post->ID, 'date_nominated', true);
				echo $dateNomed;
				break;


		}
	}

	// Make these columns sortable
	function nomination_sortable_columns() {
	  return array(
		'title' => 'title',
		'date' => 'date',
		'nomcount' => 'nomcount',
		'nominatedby' => 'nominatedby',
		'original_author' => 'original_author',
		'date_nominated' => 'date_nominated'
	  );
	}
}
