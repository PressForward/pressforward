<?php

/**
 * Folders data schema
 *
 * Folders are used to track and organize feeds. Here is where they are declared.
 */

class PF_Forward_Tools {
	#var $post_type;
	#var $tag_taxonomy;

	public static function init() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self;
		}

		return $instance;
	}

	private function __construct() {
		$this->last_step_state = get_option(PF_SLUG.'_draft_post_status', 'draft');
		$this->last_step_post_type = get_option(PF_SLUG.'_draft_post_type', 'post');
	}

	public function nomination_to_last_step( $item_id, $nomination_id ){
		$post_check = $this->is_a_pf_type( $item_id, 'post' );
		//$post_check = $this->is_a_pf_type( $item_id, pressforward()->nominations->post_type );
		//pressforward()->metas->update_pf_meta($post_ID, 'nom_id', $post_ID);
		//
		// Assign user status as well here.
		if ($post_check != false){
			$post = get_post($nomination_id, ARRAY_A);
			$d_post = $post;
			unset($d_post['ID']);
			$d_post['post_type'] = $this->last_step_post_type;
			$d_post['post_status'] = $this->last_step_state;
			$newPostID = wp_insert_post( $d_post, true );
			#var_dump($newPostID); die();
			#pressforward()->metas->transition_post_meta($post_ID, $newPostID);
			$already_has_thumb = has_post_thumbnail($nomination_id);
			if ($already_has_thumb)  {
				$post_thumbnail_id = get_post_thumbnail_id( $nomination_id );
				set_post_thumbnail($newPostID, $post_thumbnail_id);
			}
			pressforward()->metas->transition_post_meta($nomination_id, $newPostID);
			return $newPostID;
		} else {
			return $post_check;
		}

	}

	public function item_to_nomination($item_id, $item_post_id){
		$nomination_check = $this->is_a_pf_type( $item_id );
		//$post_check = $this->is_a_pf_type( $item_id, pressforward()->nominations->post_type );
		//pressforward()->metas->update_pf_meta($post_ID, 'nom_id', $post_ID);
		if ($nomination_check != false){
			// Create
			// Assign user status as well here.
			return $nomination_id;
		} else {
			return $nomination_check;
		}
	}

	public function item_to_last_step($item_id, $item_post_id){
		$nomination_id = $this->item_to_nomination( $item_id, $item_post_id );
		$post_id = $this->nomination_to_last_step( $item_id, $nomination_id );
		return $post_id;
	}

	public function bookmarklet_to_nomination($item_id, $post){
		$item_id = create_feed_item_id( $_POST['item_link'], $post['post_title'] );
		$nom_and_post_check = $this->is_a_pf_type( $item_id );

		if ( isset( $_POST['post_format'] ) ) {
			if ( current_theme_supports( 'post-formats', $_POST['post_format'] ) )
				set_post_format( $post_ID, $_POST['post_format'] );
			elseif ( '0' == $_POST['post_format'] )
				set_post_format( $post_ID, false );
		}
		# PF NOTE: Switching post type to nomination.
		$post['post_type'] = 'nomination';
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
			$post_ID = wp_update_post($post);
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

	public function bookmarklet_to_last_step($item_id, $post){
		$nomination_id = $this->bookmarklet_to_nomination($item_id);
		//Is this already a post?
		$post_check = $this->is_a_pf_type($item_id, 'post', false);
		if ($post_check != true) {
			$d_post = $post;
			$d_post['post_type'] = get_option(PF_SLUG.'_draft_post_type', 'post');
			$d_post['post_status'] = get_option(PF_SLUG.'_draft_post_status', 'draft');
			$newPostID = wp_insert_post( $d_post, true );
			#var_dump($newPostID); die();
			#pressforward()->metas->transition_post_meta($post_ID, $newPostID);
			$already_has_thumb = has_post_thumbnail($nomination_id);
			if ($already_has_thumb)  {
				$post_thumbnail_id = get_post_thumbnail_id( $nomination_id );
				set_post_thumbnail($newPostID, $post_thumbnail_id);
			}
			pressforward()->metas->transition_post_meta($nomination_id, $newPostID);
			return $newPostID;
		} else {
		  //@TODO We should increment nominations for this item maybe?
		  //Some sort of signal should occur here to indicate that the item was
		  //already sent to last step.
		  return $post_check;
		}
	}

	public function get_pf_type_id($item_id, $post_type){
		$q = $this->pf_get_posts_by_id_for_check($post_type, $item_id, true);
		if ( 0 < $q->post_count ){
			$nom = $q->posts;
			$r = $nom[0];
			return $r;
		} else {
			return false;
		}
	}

	public function is_a_pf_type($item_id, $post_type = false, $update = false){
		if (!$post_type) {
			$post_type = array('post', pressforward()->nominations->post_type);
		}
		$attempt = $this->get_pf_type_id($item_id, $post_type);
		if (!empty($attempt)){
			$r = $attempt;
			pf_log('Existing post at '.$r);
		} else {
			$r = false;
		}
		/* Restore original Post Data */
		wp_reset_postdata();
		return $r;
	}

	/**
	 * Get all posts with 'item_id' set to a given item id
	 *
	 * @since 1.7
	 *
	 * @param string $post_type The post type to limit results to.
	 * @param string $item_id The origin item id.
	 * @param bool $ids_only Set to true if you want only an array of IDs returned in the query.
	 *
	 * @return object A standard WP_Query object.
	 */
	function pf_get_posts_by_id_for_check( $post_type = false, $item_id, $ids_only = false ) {
		global $wpdb;
		# If the item is less than 24 hours old on nomination, check the whole database.
	#	$theDate = getdate();
		#$w = date('W');
		$r = array(
								'meta_key' => 'item_id',
								'meta_value' => $item_id,
								'post_type'	=> array('post', pf_feed_item_post_type())
							);

		if ($ids_only){
			$r['fields'] = 'ids';
			$r['no_found_rows'] = true;
			$r['cache_results'] = false;

		}

		if (false != $post_type){
			$r['post_type'] = $post_type;
		}

		$postsAfter =  new WP_Query( $r );
		pf_log(' Checking for posts with item ID '. $item_id .' returned query with ' . $postsAfter->post_count . ' items.');
		#pf_log($postsAfter);
		return $postsAfter;
	}

	function pull_content_images_into_pf($post_ID, $item_content, $photo_src, $photo_description){
		$content = isset($item_content) ? $item_content] : '';

		$upload = false;
		if ( !empty($photo_src) && current_user_can('upload_files') ) {
			foreach( (array) $photo_src as $key => $image) {
				// see if files exist in content - we don't want to upload non-used selected files.
				if ( strpos($content, htmlspecialchars($image)) !== false ) {
					$desc = isset($photo_description[$key]) ? $photo_description[$key] : '';
					$upload = media_sideload_image($image, $post_ID, $desc);

					// Replace the POSTED content <img> with correct uploaded ones. Regex contains fix for Magic Quotes
					if ( !is_wp_error($upload) )
						$content = preg_replace('/<img ([^>]*)src=\\\?(\"|\')'.preg_quote(htmlspecialchars($image), '/').'\\\?(\2)([^>\/]*)\/*>/is', $upload, $content);
				}
			}
		}

		return $content;
	}

}
