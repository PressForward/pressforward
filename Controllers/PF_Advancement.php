<?php
namespace PressForward\Controllers;

use PressForward\Interfaces\Advance_System as Advance_System;

use Intraxia\Jaxion\Contract\Core\HasActions as HasActions;

/**
 * PressForward to WP post object lifecycle tools
 */
class PF_Advancement implements Advance_System, HasActions {
	// var $last_step_state;
	// var $last_step_post_type;
	public function __construct( Metas $metas ) {
		$this->metas = $metas;
		// $this->last_step_state = $this->last_step_state();
		// $this->last_step_post_type = $this->last_step_post_type();
	}

	public function action_hooks() {
		$actions = array(
			array(
				'hook'   => 'pf_transition_to_nomination',
				'method' => 'inform_of_nomination',
			),
		);
		return $actions;
	}

	public function last_step_state() {
		return get_option( PF_SLUG . '_draft_post_status', 'draft' );
	}

	public function last_step_post_type() {
		return get_option( PF_SLUG . '_draft_post_type', 'post' );
	}

	public function inform_of_nomination(){
		$admin_email = get_option( 'pf_nomination_send_email', array() );
		if($admin_email) {
			$siteurl = get_option( 'siteurl', '' );
			$blogname = get_option( 'blogname', '' );
			$admin_emails = explode(",", $admin_email);
			foreach ($admin_emails as $email){
				wp_mail(trim($email), "New nomination on '".$blogname."'", "A new nomination has been created! Please check it online on " . $siteurl . "/wp-admin/admin.php?page=pf-review");
			}
		}
	}

	// Transition Tools
	public function transition( $old_post, $new_post ) {
		$this->transition_post_image( $old_post, $new_post );
		$this->metas->transition_post_meta( $old_post, $new_post );
		$this->transition_taxonomy_info( $old_post, $new_post );
		do_action( 'transition_pf_post_meta', $old_post, $new_post );
	}

	public function transition_taxonomy_info( $old_post, $new_post ) {
		// $old_terms = array();
		$taxonomies = apply_filters( 'pf_valid_post_taxonomies', array( 'category', 'post_tag' ) );
		foreach ( $taxonomies as $taxonomy ) {
			$old_tax_terms = get_the_terms( $old_post, $taxonomy );

			if ( ( false !== $old_tax_terms ) && ( ! is_wp_error( $old_tax_terms ) ) && ( is_array( $old_tax_terms ) ) ) {
				$old_term_ids = array();
				foreach ( $old_tax_terms as $term ) {
					$old_term_ids[] = intval( $term->term_id );
				}
				wp_set_object_terms( $new_post, $old_term_ids, $taxonomy, true );
			}
		}
		$item_tags = $this->metas->get_post_pf_meta( $old_post, 'item_tags' );
		if ( ! is_array( $item_tags ) ) {
			$item_tags = explode( ',', $item_tags );
		}
		foreach ( $item_tags as $key => $tag ) {
			$tag      = trim( $tag );
			$tag_info = wp_create_term( $tag );
			if ( ! is_wp_error( $tag_info ) ) {
				$tag_id = intval( $tag_info['term_id'] );
				wp_set_object_terms( $new_post, $tag_id, 'post_tag', true );
			} else {
				pf_log( $tag_info );
			}
		}
		// $old_category_terms = get_the_terms($old_post, 'category');
		// var_dump($old_terms); die();
		// foreach ($old_terms as $old_term){
		// wp_set_object_terms($new_post, $old_term->term_id, $old_term->taxonomy, true);
		// }
	}

	public function transition_post_image( $old_post, $new_post ) {
		$already_has_thumb = has_post_thumbnail( $old_post );
		if ( $already_has_thumb ) {
			$post_thumbnail_id = get_post_thumbnail_id( $old_post );
			set_post_thumbnail( $new_post, $post_thumbnail_id );
		}
	}

	// Step Tools
	// NOTE: The old ID should always be a nomination.
	public function to_last_step( $post = array() ) {
		$old_id = $post['ID'];
		unset( $post['ID'] );
		$post['post_type']     = $this->last_step_post_type();
		$post['post_status']   = $this->last_step_state();
		$post['post_date']     = current_time( 'Y-m-d H:i:s' );
		$post['post_date_gmt'] = get_gmt_from_date( current_time( 'Y-m-d H:i:s' ) );
		pf_log( $post );
		$post['post_content'] = pressforward( 'controller.readability' )->process_in_oembeds( pressforward( 'controller.metas' )->get_post_pf_meta( $old_id, 'item_link' ), $post['post_content'] );
		$post['post_content'] = pressforward( 'utility.forward_tools' )->append_source_statement( $old_id, $post['post_content'], true );
		$id                   = pressforward( 'controller.items' )->insert_post( $post, true, pressforward( 'controller.metas' )->get_post_pf_meta( $old_id, 'item_id' ) );

		do_action( 'pf_transition_to_last_step', $id );
		return $id;
	}

	public function to_nomination( $post = array() ) {
		$post['post_status']   = 'draft';
		$post['post_type']     = pressforward( 'schema.nominations' )->post_type;
		$post['post_date']     = current_time( 'Y-m-d H:i:s' );
		$post['post_date_gmt'] = get_gmt_from_date( current_time( 'Y-m-d H:i:s' ) );
		$orig_post_id          = $post['ID'];
		unset( $post['ID'] );
		$id = pressforward( 'controller.items' )->insert_post( $post, false, pressforward( 'controller.metas' )->get_post_pf_meta( $orig_post_id, 'item_id' ) );
		pf_log( $id );
		do_action( 'pf_transition_to_nomination', $id );

		return $id;
	}

	// Checking for the existence of posts in previous PF states.
	public function get_pf_type_by_id( $item_id, $post_type ) {
		$q = $this->pf_get_posts_by_id_for_check( $post_type, $item_id, true );
		if ( 0 < $q->post_count ) {
			$nom = $q->posts;
			$r   = $nom[0];
		} else {
			$r = false;
		}
		/* Restore original Post Data */
		wp_reset_postdata();
		return $r;
	}

	public function prep_bookmarklet( $post_id ) {
		if ( isset( $_POST['post_format'] ) ) {
			if ( current_theme_supports( 'post-formats', $_POST['post_format'] ) ) {
				set_post_format( $post_id, $_POST['post_format'] ); } elseif ( '0' == $_POST['post_format'] ) {
				set_post_format( $post_id, false ); }
		}

		if ( isset( $_POST['post_category'] ) && is_array( $_POST['post_category'] ) ) {
			// var_dump($_POST['post_category']); die();
			$categories = array();
			foreach ( $_POST['post_category'] as $category_id ) {
				$categories[ $category_id ] = intval( $category_id );
			}
			wp_set_object_terms( $post_id, $categories, 'category', false );
		}
	}

	/**
	 * Get all posts with 'item_id' set to a given item id
	 *
	 * @since 1.7
	 *
	 * @param string $post_type The post type to limit results to.
	 * @param string $item_id The origin item id.
	 * @param bool   $ids_only Set to true if you want only an array of IDs returned in the query.
	 *
	 * @return object A standard WP_Query object.
	 */
	public function pf_get_posts_by_id_for_check( $post_type = false, $item_id, $ids_only = false ) {
		global $wpdb;
		// If the item is less than 24 hours old on nomination, check the whole database.
		// $theDate = getdate();
		// $w = date('W');
		$r = array(
			'meta_key'   => $this->metas->get_key( 'item_id' ),
			'meta_value' => $item_id,
			'post_type'  => array( 'post', pf_feed_item_post_type() ),
		);

		if ( $ids_only ) {
			$r['fields']        = 'ids';
			$r['no_found_rows'] = true;
			$r['cache_results'] = false;

		}

		$r['post_status'] = array( 'publish', 'alert_specimen', 'under_review', 'future', 'draft', 'pending', 'private' );

		if ( false != $post_type ) {
			$r['post_type'] = $post_type;
		}

		$postsAfter = new \WP_Query( $r );
		// var_dump($postsAfter, $r); die();
		pf_log( ' Checking for posts with item ID ' . $item_id . ' returned query with ' . $postsAfter->post_count . ' items.' );
		// pf_log($postsAfter);
		return $postsAfter;
	}

	// Utility Functions
	function pull_content_images_into_pf( $post_ID, $item_content, $photo_src, $photo_description ) {
		$content = isset( $item_content ) ? $item_content : '';

		$upload = false;
		if ( ! empty( $photo_src ) && current_user_can( 'upload_files' ) ) {
			foreach ( (array) $photo_src as $key => $image ) {
				// see if files exist in content - we don't want to upload non-used selected files.
				if ( strpos( $content, htmlspecialchars( $image ) ) !== false ) {
					$desc   = isset( $photo_description[ $key ] ) ? $photo_description[ $key ] : '';
					$upload = media_sideload_image( $image, $post_ID, $desc );

					// Replace the POSTED content <img> with correct uploaded ones. Regex contains fix for Magic Quotes
					if ( ! is_wp_error( $upload ) ) {
						$content = preg_replace( '/<img ([^>]*)src=\\\?(\"|\')' . preg_quote( htmlspecialchars( $image ), '/' ) . '\\\?(\2)([^>\/]*)\/*>/is', $upload, $content ); }
				}
			}
		}

		return $content;
	}

}
