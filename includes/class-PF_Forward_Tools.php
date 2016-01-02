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
		if ( pressforward()->pf_item_interface->is_error($newPostID) ){
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

		if ( pressforward()->pf_item_interface->is_error($nomination_id) ){
			pf_log($nomination_id);
			return false;
		} else {
			pressforward()->pf_advance_interface->transition( $item_post_id, $nomination_id );
			$this->nomination_user_transition_check( $nomination_id );
			return $nomination_id;
		}
	}

	// Call me before transitioning the post.
	public function transition_to_readable_text($post_id, $source = false){
		$post = pressforward()->pf_item_interface->get_post($post_id, ARRAY_A);
		$item_content = $post['post_content'];
		//$readable_state = pressforward()->metas->get_post_pf_meta($item_post_id, 'readable_status', true);
		$readable_status = pressforward()->metas->get_post_pf_meta($post_id, 'readable_status', true);
		if ($readable_status != 1){
			$readArgs = array(
				'force' => false,
				'descrip' => htmlspecialchars_decode($item_content),
				'url' => pressforward()->metas->get_post_pf_meta($post_id, 'item_link', true),
				'authorship' => 'auto'
			);
			$item_content_obj = pressforward()->readability->get_readable_text($readArgs);
			$item_content = htmlspecialchars_decode($item_content_obj['readable']);
			$source_position = get_option('pf_source_statement_position', 'bottom');
			if ( ( 'bottom' == $source_position ) && $source ){
				$item_content = $item_content . pressforward()->nominations->get_the_source_statement( $post_id );
			} else {
				$item_content = pressforward()->nominations->get_the_source_statement( $post_id ) . $item_content;
			}
			$post_id = pressforward()->pf_item_interface->update_post( array(
				'ID'	=>	$post_id,
				'post_content'	=>	$item_content
			), true );
			if ( is_numeric($post_id) ){
				if ((!empty($item_content_obj['status'])) && ('secured' != $item_content_obj['status'])){
					pressforward()->metas->update_pf_meta($post_id, 'readable_status', 1);
				} elseif ((1 != $readable_status)) {
					pressforward()->metas->update_pf_meta($post_id, 'readable_status', 0);
				}
				return $item_content;
			} else {
				return false;
			}

		} else {
			return false;
		}
	}

	function user_meta_nomination_counter_change($userID, $increase = true){
		if (get_user_meta( $userID, 'nom_count', true )){

						$nom_counter = get_user_meta( $userID, 'nom_count', true );
						if ($increase) {
							$nom_counter++;
						}	else {
							$nom_counter--;
						}
						update_user_meta( $userID, 'nom_count', $nom_counter, true );

		} elseif ($increase) {
						add_user_meta( $userID, 'nom_count', 1, true );

		}
	}

	function nomination_user_transition_check($id, $can_delete = false){
		$current_user = wp_get_current_user();
		$nominators_orig = pressforward()->metas->retrieve_meta($id, 'nominator_array');
		if ( is_array( $nominators_orig ) && !in_array($current_user->ID, $nominators_orig) ){
			$nominators = $nominators_orig;
			$nominator = $current_user->ID;
											$nominators[] = $current_user->ID;
			pressforward()->metas->update_pf_meta($id, 'nominator_array', $nominator);
			$nomCount = pressforward()->metas->get_post_pf_meta($id, 'nomination_count', true);
			if ( empty($nomCount) ){
				$nomCount = 0;
			}
			$this->user_meta_nomination_counter_change($current_user->ID);
			pf_log('So far we have a nominating count of '.$nomCount);
											$nomCount++;
											pf_log('Now we have a nominating count of '.	$nomCount);
			$check_meta = pressforward()->metas->update_pf_meta($id, 'nomination_count', $nomCount);
											pf_log('Attempt to update the meta for nomination_count resulted in: ');
											pf_log($check_meta);
											$check = true;
		} else if ( $can_delete ) {
			pf_log('user_nominated_already');
				$check = true;
				$this->user_meta_nomination_counter_change($current_user->ID, false);
				$nomCount = pressforward()->metas->retrieve_meta($id, 'nomination_count');
				$nomCount--;
				pressforward()->metas->update_pf_meta($id, 'nomination_count', $nomCount);
				if ( 0 != $current_user->ID ) {
					if ( !is_array($nominators_orig) ){
						$nominators_orig = array();
					}
					if (true == in_array($current_user->ID, $nominators_orig)){
						$nominators_new = array_diff($nominators_orig, array($current_user->ID));
						if (empty($nominators_new)){
							pressforward()->pf_item_interface->delete_post( $id );
						} else {
							pressforward()->metas->update_pf_meta( $id, 'nominator_array', $nominators_new );
						}
					}
				}
		}
	}

	// Previous step to new step Tools


	public function item_to_nomination($item_id, $item_post_id){
		$nomination_and_post_check = $this->is_a_pf_type( $item_id );
		//$post_check = $this->is_a_pf_type( $item_id, pressforward()->nominations->post_type );
		//pressforward()->metas->update_pf_meta($post_ID, 'nom_id', $post_ID);
		if ($nomination_and_post_check == false){
			$this->transition_to_readable_text($item_post_id, false);

			$current_user = wp_get_current_user();
			if ( 0 == $current_user->ID ) {
				//Not logged in.
				$userSlug = "external";
				$userName = __('External User', 'pf');
				$userID = 0;
			} else {
				// Logged in.
				//self::user_nomination_meta();
				$userID = $current_user->ID;
				$userString = $userID;
			}


			pressforward()->metas->update_pf_meta($item_post_id, 'nomination_count', 1);
			pressforward()->metas->update_pf_meta($item_post_id, 'submitted_by', $userString);
			pressforward()->metas->update_pf_meta($item_post_id, 'nominator_array', array($userID));
			pressforward()->metas->update_pf_meta($item_post_id, 'date_nominated', date('c'));
			pressforward()->metas->update_pf_meta($item_post_id, 'origin_item_ID', $item_id);
			pressforward()->metas->update_pf_meta($item_post_id, 'item_feed_post_id', $item_post_id);
			if ( !empty($_POST['item_link']) ){
				pressforward()->metas->update_pf_meta($item_post_id, 'item_link', $_POST['item_link']);
			}

			if (empty($_POST['item_date'])){
				$newDate = gmdate('Y-m-d H:i:s');
				$item_date = $newDate;
			} else {
				$item_date = $_POST['item_date'];
			}
			pressforward()->metas->update_pf_meta($item_post_id, 'posted_date', $item_date);

			$nomination_id = $this->transition_to_nomination($item_post_id);
			//$this->nomination_user_transition_check( $nomination_id );
			// Assign user status as well here.
			return $nomination_id;
		} else {
			// Uptick user if they are not in nominator array
			// If they are in the nominator array and no one else is, un-nominate.
			// If they are in the nominator array and someone else is, un-relate
			// them to that nomination.
			$this->nomination_user_transition_check( $nomination_and_post_check, true );
			return $nomination_and_post_check;
		}
	}

	public function nomination_to_last_step( $item_id = false, $nomination_id ){
		$post_check = $this->is_a_pf_type( $item_id, 'post' );


		// Assign user status as well here.
		if ($post_check == false){

			if ( false != $nomination_id ){
				$this->transition_to_readable_text($nomination_id, true);
				$id = $this->transition_to_last_step($nomination_id);
				pf_log($id);
			}
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
			// Increment the nomination count if the nomination exists.
			$this->nomination_user_transition_check( $nom_and_post_check );
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
