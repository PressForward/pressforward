<?php

/**
 * PressForward object lifecycle tools
 *
 */

class PF_Forward_Tools {
	#var $post_type;
	#var $tag_taxonomy;

	var $advance_interface;
	var $post_interface;

	public static function init() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self;
		}

		return $instance;
	}

	private function __construct() {
		//var_dump('a');
		//$this->post_interface = pressforward()->pf_item_interface;
		//$this->advance_interface = pressforward()->pf_advance_interface;
	}

	// Transition to next step Tools
	public function transition_to_last_step($nomination_id){
		$post = pressforward()->pf_item_interface->get_post($nomination_id, ARRAY_A);
		$d_post = $post;
		$newPostID = pressforward()->pf_advance_interface->to_last_step($d_post);
		pf_log($newPostID);
		#pressforward()->metas->transition_post_meta($post_ID, $newPostID);
		if ( is_wp_error($newPostID) ){
			pf_log($newPostID);
			return false;
		} else {
			pressforward()->pf_advance_interface->transition( $nomination_id, $newPostID );
			return $newPostID;
		}
	}

	public function transition_to_nomination($item_post_id){
		// Create
		$post = pressforward()->pf_item_interface->get_post($item_post_id, ARRAY_A);
		$nomination_id = pressforward()->pf_advance_interface->to_nomination($post);
		if ( $this->post_interface->is_error($nomination_id) ){
			pf_log($nomination_id);
			return false;
		} else {
			pressforward()->pf_advance_interface->transition( $item_post_id, $nomination_id );
			return $nomination_id;
		}
	}

	// Previous step to new step Tools


	public function item_to_nomination($item_id, $item_post_id){
		$nomination_and_post_check = $this->is_a_pf_type( $item_id );
		//$post_check = $this->is_a_pf_type( $item_id, pressforward()->nominations->post_type );
		//pressforward()->metas->update_pf_meta($post_ID, 'nom_id', $post_ID);
		if ($nomination_and_post_check == false){
			$nomination_id = $this->transition_to_nomination($item_post_id);
			// Assign user status as well here.
			return $nomination_id;
		} else {
			return $nomination_and_post_check;
		}
	}

	public function nomination_to_last_step( $item_id, $nomination_id ){
		$post_check = $this->is_a_pf_type( $item_id, 'post' );
		//$post_check = $this->is_a_pf_type( $item_id, pressforward()->nominations->post_type );
		//pressforward()->metas->update_pf_meta($post_ID, 'nom_id', $post_ID);
		//
		// Assign user status as well here.
		if ($post_check == false){
			$id = $this->transition_to_last_step($nomination_id);
			pf_log($id);
			return $id;
		} else {
			//@TODO We should increment nominations for this item maybe?
			//Some sort of signal should occur here to indicate that the item was
			//already sent to last step.

			return $post_check;
		}

	}

	public function item_to_last_step($item_id, $item_post_id){
		$nomination_id = $this->item_to_nomination( $item_id, $item_post_id );
		$post_id = $this->nomination_to_last_step( $item_id, $nomination_id );
		return $post_id;
	}

	public function bookmarklet_to_nomination($item_id = false, $post){
		if (!$item_id){
			$item_id = create_feed_item_id( $_POST['item_link'], $post['post_title'] );
		}
		$nom_and_post_check = $this->is_a_pf_type( $item_id );

		pressforward()->pf_advance_interface->prep_bookmarklet( $post['ID'] );
		# PF NOTE: Switching post type to nomination.
		$post['post_type'] = pressforward()->nominations->post_type;
		$post['post_date_gmt'] = gmdate('Y-m-d H:i:s');
		# PF NOTE: This is where the inital post is created.
		# PF NOTE: Put get_post_nomination_status here.
		//$item_id = create_feed_item_id( $_POST['item_link'], $post['post_title'] );
		if (!isset($_POST['item_date'])){
			$newDate = gmdate('Y-m-d H:i:s');
			$item_date = $newDate;
		} else {
			$item_date = $_POST['item_date'];
		}
		// Does not exist in the system.
		if (!$nom_and_post_check){
			// Update post here because we're working with the blank post
			// inited by the Nominate This page, at the beginning.
			$post_ID = pressforward()->pf_item_interface->update_post($post, true);
			// Check if thumbnail already exists, if not, set it up.
			$already_has_thumb = has_post_thumbnail($post_ID);
			if ($already_has_thumb)  {
				$post_thumbnail_id = get_post_thumbnail_id( $post_ID );
				$post_thumbnail_url = wp_get_attachment_image_src( $attachment_id );
			} else {
				$post_thumbnail_url = false;
			}

			$url_parts = parse_url($_POST['item_link']);
			if (!empty($url_parts['host'])){
			  $source = $url_parts['host'];
			} else {
			  $source = '';
			}


			$pf_meta_args = array(
				pressforward()->metas->meta_for_entry('item_id', $item_id ),
				pressforward()->metas->meta_for_entry('item_link', $_POST['item_link']),
				pressforward()->metas->meta_for_entry('nomination_count', 1),
				pressforward()->metas->meta_for_entry('source_title', 'Bookmarklet'),
				pressforward()->metas->meta_for_entry('item_date', $item_date),
				pressforward()->metas->meta_for_entry('posted_date', $item_date),
				pressforward()->metas->meta_for_entry('date_nominated', $_POST['date_nominated']),
				pressforward()->metas->meta_for_entry('item_author', $_POST['authors']),
				pressforward()->metas->meta_for_entry('authors', $_POST['authors']),
				pressforward()->metas->meta_for_entry('pf_source_link', $source),
				pressforward()->metas->meta_for_entry('item_feat_img', $post_thumbnail_url),
				pressforward()->metas->meta_for_entry('nominator_array', array(get_current_user_id())),
				// The item_wp_date allows us to sort the items with a query.
				pressforward()->metas->meta_for_entry('item_wp_date', $item_date),
				//We can't just sort by the time the item came into the system (for when mult items come into the system at once)
				//So we need to create a machine sortable date for use in the later query.
				pressforward()->metas->meta_for_entry('sortable_item_date', strtotime($item_date)),
				pressforward()->metas->meta_for_entry('item_tags', 'via bookmarklet'),
				pressforward()->metas->meta_for_entry('source_repeat', 1),
				pressforward()->metas->meta_for_entry('revertible_feed_text', $post['post_content'])

			);
			pressforward()->metas->establish_post($post_ID, $pf_meta_args);
			pressforward()->metas->update_pf_meta($post_ID, 'nom_id', $post_ID);
			return $post_ID;
		} else {
			// Do something with the returned ID.

			return $nom_and_post_check;
		}

	}

	public function bookmarklet_to_last_step($item_id = false, $post){
		if (!$item_id){
			$item_id = create_feed_item_id( $_POST['item_link'], $post['post_title'] );
		}
		$nomination_id = $this->bookmarklet_to_nomination($item_id, $post);
		pf_log($nomination_id);
		return $this->nomination_to_last_step($item_id, $nomination_id);
	}

	public function is_a_pf_type($item_id, $post_type = false, $update = false){
		if (!$post_type) {
			$post_type = array('post', pressforward()->nominations->post_type);
		}
		$attempt = pressforward()->pf_advance_interface->get_pf_type_by_id($item_id, $post_type);
		if (!empty($attempt)){
			$r = $attempt;
			pf_log('Existing post at '.$r);
		} else {
			$r = false;
		}
		return $r;
	}
}
