<?php
/**
 * Utilities related to post advancement.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

/**
 * PressForward to WP post object lifecycle tools.
 */
class PF_Advancement implements \PressForward\Interfaces\Advance_System, \Intraxia\Jaxion\Contract\Core\HasActions {
	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $metas;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\Metas $metas Metas object.
	 */
	public function __construct( Metas $metas ) {
		$this->metas = $metas;
	}

	/**
	 * Sets up action hooks for this class.
	 *
	 * @return array
	 */
	public function action_hooks() {
		$actions = array(
			array(
				'hook'   => 'pf_transition_to_nomination',
				'method' => 'inform_of_nomination',
			),
		);
		return $actions;
	}

	/**
	 * Gets the post status for the "last step" in the advancement process.
	 *
	 * @return string
	 */
	public function last_step_state() {
		return get_option( PF_SLUG . '_draft_post_status', 'draft' );
	}

	/**
	 * Gets the post type for the "last step" in the advancement process.
	 *
	 * @return string
	 */
	public function last_step_post_type() {
		return pressforward_draft_post_type();
	}

	/**
	 * Creates a term in a taxonomy.
	 *
	 * @param string $tag_name Name of the term.
	 * @param string $taxonomy Taxonomy. Defaults to 'post_tag'.
	 * @return array|\WP_Error
	 */
	public function create_terms( $tag_name, $taxonomy = 'post_tag' ) {
		$id = term_exists( $tag_name, $taxonomy );
		if ( $id ) {
			return $id;
		}

		return wp_insert_term( $tag_name, $taxonomy );
	}

	/**
	 * Sends an email notification of a new nomination.
	 */
	public function inform_of_nomination() {
		$admin_email = get_option( 'pf_nomination_send_email', array() );
		if ( $admin_email ) {
			$siteurl      = get_option( 'siteurl', '' );
			$blogname     = get_option( 'blogname', '' );
			$admin_emails = explode( ',', $admin_email );
			foreach ( $admin_emails as $email ) {
				wp_mail(
					trim( $email ),
					/* translators: Site name */
					sprintf( esc_html__( 'New nomination on %s', 'pressforward' ), esc_html( $blogname ) ),
					/* translators: URL of Nominations panel */
					sprintf( esc_html__( 'A new nomination has been created! Please check it online on %s.', 'pressforward' ), esc_html( $siteurl . '/wp-admin/admin.php?page=pf-review' ) )
				);
			}
		}
	}

	/**
	 * Transitions an old post to a new one.
	 *
	 * @param int $old_post Source post ID.
	 * @param int $new_post Destination post ID.
	 * @return void
	 */
	public function transition( $old_post, $new_post ) {
		$this->transition_post_image( $old_post, $new_post );
		$this->metas->transition_post_meta( $old_post, $new_post );
		$this->transition_taxonomy_info( $old_post, $new_post );
		do_action( 'transition_pf_post_meta', $old_post, $new_post );
	}

	/**
	 * Transitions taxonomy terms from an old post to a new one.
	 *
	 * @param int|\WP_Post $old_post Source post.
	 * @param int|\WP_Post $new_post Destination post.
	 * @return void
	 */
	public function transition_taxonomy_info( $old_post, $new_post ) {
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
			$tag_info = $this->create_terms( $tag );
			if ( ! is_wp_error( $tag_info ) ) {
				$tag_id = intval( $tag_info['term_id'] );
				wp_set_object_terms( $new_post, $tag_id, 'post_tag', true );
			} else {
				pf_log( $tag_info );
			}
		}
	}

	/**
	 * Migrates a featured image from one post to another.
	 *
	 * @param int|\WP_Post $old_post Source post.
	 * @param int|\WP_Post $new_post Destination post.
	 * @return void
	 */
	public function transition_post_image( $old_post, $new_post ) {
		$already_has_thumb = has_post_thumbnail( $old_post );
		if ( $already_has_thumb ) {
			$post_thumbnail_id = get_post_thumbnail_id( $old_post );
			set_post_thumbnail( $new_post, $post_thumbnail_id );
		}
	}

	/**
	 * Transitions a nomination to the last step, ie becoming a post draft.
	 *
	 * @param array $post Post args.
	 * @return int ID of the newly created post draft.
	 */
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

		$id = pressforward( 'controller.items' )->insert_post( $post, true, pressforward( 'controller.metas' )->get_post_pf_meta( $old_id, 'item_id' ) );

		do_action( 'pf_transition_to_last_step', $id );
		return $id;
	}

	/**
	 * Transitions a post to a nomination.
	 *
	 * @param array $post Post args.
	 * @return int ID of the new nomination post.
	 */
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

	/**
	 * Checks for the existence of posts in previous PF states.
	 *
	 * @param string $item_id   ID of the item.
	 * @param string $post_type Post type.
	 * @return int ID of the corresponding WP post, if found.
	 */
	public function get_pf_type_by_id( $item_id, $post_type ) {
		$q = $this->pf_get_posts_by_id_for_check( $post_type, $item_id, true );
		if ( 0 < $q->post_count ) {
			$nom = $q->posts;
			$r   = $nom[0];
		} else {
			$r = 0;
		}
		/* Restore original Post Data */
		wp_reset_postdata();
		return $r;
	}

	/**
	 * Prepares a post sent from the bookmarklet.
	 *
	 * @param int $post_id ID of the post.
	 * @return void
	 */
	public function prep_bookmarklet( $post_id ) {
		if ( isset( $_POST['post_format'] ) ) {
			$post_format = sanitize_text_field( wp_unslash( $_POST['post_format'] ) );
			if ( current_theme_supports( 'post-formats', $post_format ) ) {
				set_post_format( $post_id, $post_format );
			} elseif ( '0' === $post_format ) {
				set_post_format( $post_id, '' );
			}
		}

		if ( isset( $_POST['post_category'] ) && is_array( $_POST['post_category'] ) ) {
			$categories = array();
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			foreach ( wp_unslash( $_POST['post_category'] ) as $category_id ) {
				$categories[ $category_id ] = intval( $category_id );
			}
			wp_set_object_terms( $post_id, $categories, 'category', false );
		}

		do_action( 'establish_pf_metas', $post_id, $_POST );
	}

	/**
	 * Get all posts with 'item_id' set to a given item id.
	 *
	 * @since 1.7
	 *
	 * @param string|bool $post_type The post type to limit results to.
	 * @param string      $item_id   The origin item id.
	 * @param bool        $ids_only  Set to true if you want only an array of IDs returned in the query.
	 * @return \WP_Query
	 */
	public function pf_get_posts_by_id_for_check( $post_type = false, $item_id = null, $ids_only = false ) {
		global $wpdb;

		// If the item is less than 24 hours old on nomination, check the whole database.
		$r = array(
			// phpcs:disable WordPress.DB.SlowDBQuery
			'meta_key'   => $this->metas->get_key( 'item_id' ),
			'meta_value' => $item_id,
			// phpcs:enable WordPress.DB.SlowDBQuery
			'post_type'  => array( pressforward_draft_post_type(), pf_feed_item_post_type() ),
		);

		if ( $ids_only ) {
			$r['fields']        = 'ids';
			$r['no_found_rows'] = true;
			$r['cache_results'] = false;
		}

		$r['post_status'] = array( 'publish', 'alert_specimen', 'under_review', 'future', 'draft', 'pending', 'private' );

		if ( false !== $post_type ) {
			$r['post_type'] = $post_type;
		}

		$posts_after = new \WP_Query( $r );
		pf_log( ' Checking for posts with item ID ' . $item_id . ' returned query with ' . $posts_after->post_count . ' items.' );

		return $posts_after;
	}

	/**
	 * No longer used.
	 */
	public function pull_content_images_into_pf() {}
}
