<?php
namespace PressForward\Core\Utility;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Interfaces\Items as Items;
use PressForward\Interfaces\Advance_System as Advance_System;

use PressForward\Controllers\Metas;
use PressForward\Core\Schema\Relationships;

use WP_Query;
use WP_Ajax_Response;

/**
 * PressForward relationships tools
 */

class Relate implements HasActions {
	// var $post_type;
	// var $tag_taxonomy;
	// var $advance_interface;
	// var $post_interface;
	function __construct( Items $item_interface, Advance_System $advance_interface, Metas $meta_interface, Relationships $relationship_schema ) {
		// var_dump('a');
		$this->item_interface = $item_interface;
		$this->advance_interface = $advance_interface;
		$this->metas = $meta_interface;
		$this->relationships = $relationship_schema;
	}


	public function action_hooks() {
		return array(
			array(
				'hook' => 'wp_ajax_pf_archive_nominations',
				'method' => 'archive_nominations',
			),
			array(
				'hook' => 'wp_ajax_pf_ajax_relate',
				'method' => 'ajax_relate',
			),
			array(
				'hook' => 'wp_ajax_pf_ajax_star',
				'method' => 'ajax_star',
			),
		);
	}

	/**
	 * Translates a relationship type string into its int value
	 *
	 * @param string $relationship_type
	 * @return int $relationship_type_id
	 */
	function get_relationship_type_id( $relationship_type ) {
		// Might pay to abstract these out at some point
		$types = array(
			1 => 'read',
			2 => 'star',
			3 => 'archive',
			4 => 'nominate',
			5 => 'draft',
		);

		$types = apply_filters( 'pf_relationship_types', $types );

		$relationship_type_id = array_search( $relationship_type, $types );

		// We'll return false if no type is found
		return $relationship_type_id;
	}

	/**
	 * Generic function for setting relationships
	 *
	 * @param string       $relationship_type
	 * @param int          $item_id
	 * @param int          $user_id
	 * @param string value
	 * @return bool True on success
	 */
	function set_relationship( $relationship_type, $item_id, $user_id, $value ) {
		$existing = pf_get_relationship( $relationship_type, $item_id, $user_id );

		$relationship = $this->relationships;

		// Translate relationship type
		$relationship_type_id = pf_get_relationship_type_id( $relationship_type );

		$params = array(
			'relationship_type' => $relationship_type_id,
			'item_id' => $item_id,
			'user_id' => $user_id,
			'value' => $value,
		);

		if ( ! empty( $existing ) ) {
			$params['id'] = $existing->id;
			$retval = $relationship->update( $params );
		} else {
			$retval = $relationship->create( $params );
		}

		return $retval;
	}

	/**
	 * Generic function for deleting relationships
	 *
	 * @param string $relationship_type
	 * @param int    $item_id
	 * @param int    $user_id
	 * @return bool True when a relationship is deleted OR when one is not found in the first place
	 */
	function delete_relationship( $relationship_type, $item_id, $user_id ) {
		$deleted = false;
		$existing = pf_get_relationship( $relationship_type, $item_id, $user_id );

		if ( empty( $existing ) ) {
			$deleted = true;
		} else {
			$relationship = $this->relationships;
			$deleted = $relationship->delete( array( 'id' => $existing->id ) );
		}

		return $deleted;
	}

	/**
	 * Generic function for getting relationships
	 *
	 * Note that this returns the relationship object, not the value
	 *
	 * @param string|int $relationship_type Accepts either numeric key of the
	 *   relationship type, or a string ('star', 'read', etc) describing the
	 *   relationship type
	 * @param int        $item_id
	 * @param int        $user_id
	 * @return object The relationship object
	 */
	function get_relationship( $relationship_type, $item_id, $user_id ) {
		$relationship = $this->relationships;

		// Translate relationship type to its integer index, if necessary
		if ( is_string( $relationship_type ) ) {
			$relationship_type_id = pf_get_relationship_type_id( $relationship_type );
		} else {
			$relationship_type_id = (int) $relationship_type;
		}

		$existing = $relationship->get( array(
			'relationship_type' => $relationship_type_id,
			'item_id' => $item_id,
			'user_id' => $user_id,
		) );

		$retval = false;

		if ( ! empty( $existing ) ) {
			// Take the first result for now
			$retval = $existing[0];
		}

		return $retval;
	}

	/**
	 * Generic function for getting relationship values
	 *
	 * @param string $relationship_type
	 * @param int    $item_id
	 * @param int    $user_id
	 * @return string|bool The relationship value if it exists, false otherwise
	 */
	function get_relationship_value( $relationship_type, $item_id, $user_id ) {
		$r = pf_get_relationship( $relationship_type, $item_id, $user_id );

		if ( ! empty( $r ) ) {
			$retval = $r->value;
		} else {
			$retval = false;
		}

		return $retval;
	}

	/**
	 * Generic function for getting relationships of a given type for a given user
	 *
	 * @param string $relationship_type
	 * @param int    $user_id
	 */
	function get_relationships_for_user( $relationship_type, $user_id ) {
		$relationship = $this->relationships;
		$relationship_type_id = pf_get_relationship_type_id( $relationship_type );

		$rs = $relationship->get( array(
			'relationship_type' => $relationship_type_id,
			'user_id' => $user_id,
		) );

		return $rs;
	}

	//
	// "STAR"            //
	//
	function is_item_starred_for_user( $item_id, $user_id ) {
		$v = pf_get_relationship_value( 'star', $item_id, $user_id );
		return 1 == $v;
	}

	function star_item_for_user( $item_id, $user_id ) {
		return pf_set_relationship( 'star', $item_id, $user_id, '1' );
	}

	function unstar_item_for_user( $item_id, $user_id ) {
		return pf_delete_relationship( 'star', $item_id, $user_id );
	}

	/**
	 * Function for AJAX action to mark an item as starred or unstarred.
	 */
	function ajax_star() {
		$item_id = $_POST['post_id'];
		$userObj = wp_get_current_user();
		$user_id = $userObj->ID;
		$result = 'nada';
		if ( 1 != pf_is_item_starred_for_user( $item_id, $user_id ) ) {
			$result = pf_star_item_for_user( $item_id, $user_id );
		} else {
			$result = pf_unstar_item_for_user( $item_id, $user_id );
		}

		ob_start();
		$response = array(
				'what' => 'relationships',
				'action' => 'pf_ajax_star',
				'id' => $item_id,
				'data' => $result,
				'supplemental' => array(
						'user' => $user_id,
						'buffered' => ob_get_contents(),
					),
				);

		$xmlResponse = new WP_Ajax_Response( $response );
		$xmlResponse->send();
		ob_end_flush();
		die();

	}

	/**
	 * Get a list of starred items for a given user
	 *
	 * Use this function in conjunction with PF_Feed_Item:
	 *
	 *    $starred_item_ids = pf_get_starred_items_for_user( $user_id, 'simple' );
	 *
	 *    $feed_item = new PF_Feed_Item();
	 *    $items = $feed_item->get( array(
	 *        'post__in' => $starred_item_ids
	 *    ) );
	 *
	 * @param int    $user_id
	 * @param string $format 'simple' to get back just the item IDs. Otherwise raw relationship objects
	 */
	function get_starred_items_for_user( $user_id, $format = 'raw' ) {
		$rs = pf_get_relationships_for_user( 'star', $user_id );

		if ( 'simple' == $format ) {
			$rs = wp_list_pluck( $rs, 'item_id' );
		}

		return $rs;
	}

	/**
	 * A generalized function for setting/unsetting a relationship via ajax
	 */
	function ajax_relate() {
		pf_log( 'Invoked: pf_ajax_relate()' );
		$item_id = $_POST['post_id'];
		$relationship_type = $_POST['schema'];
		$switch = $_POST['isSwitch'];
		$userObj = wp_get_current_user();
		$user_id = $userObj->ID;
		$result = 'nada';
		pf_log( 'pf_ajax_relate - received: ID = ' . $item_id . ', Schema = ' . $relationship_type . ', isSwitch = ' . $switch . ', userID = ' . $user_id . '.' );
		if ( 1 != pf_get_relationship_value( $relationship_type, $item_id, $user_id ) ) {
			$result = pf_set_relationship( $relationship_type, $item_id, $user_id, '1' );
			pf_log( 'pf_ajax_relate - set: relationship on' );
		} else {
			if ( $switch == 'on' ) {
				$result = pf_delete_relationship( $relationship_type, $item_id, $user_id );
				pf_log( 'pf_ajax_relate - set: relationship off' );
			} else {
				$result = 'unswitchable';
				pf_log( 'pf_ajax_relate - set: relationship unswitchable' );
			}
		}

		ob_start();
		$response = array(
				'what' => 'relationships',
				'action' => 'pf_ajax_relate',
				'id' => $item_id,
				'data' => $result,
				'supplemental' => array(
						'user' => $user_id,
						'buffered' => ob_get_contents(),
					),
				);

		$xmlResponse = new WP_Ajax_Response( $response );
		$xmlResponse->send();
		ob_end_flush();
		die();

	}

	function archive_nominations( $limit = false ) {
			global $wpdb, $post;
			// $args = array(
			// 'post_type' => array('any')
			// );
			// $$args = 'post_type=' . 'nomination';
			$args = array(
				'post_type'		=> 'nomination',
				'posts_per_page' => -1,

			);

			// $archiveQuery = new WP_Query( $args );
			if ( isset( $_POST['date_limit'] ) ) {
				$date_limit = $_POST['date_limit'];

				switch ( $date_limit ) {
					case '1week':
						$before = '1 week ago';
						break;
					case '2weeks':
						$before = '2 weeks ago';
						break;
					case '1month':
						$before = array( 'month' => date( 'm' ) -1 );
						break;
					case '1year':
						$before = array( 'year' => date( 'Y' ) -1 );
						break;

				}
				$args['date_query']	= array(
										'before' => $before,
									);
			} elseif ( false != $limit ) {
					$date_limit = $limit;

				switch ( $date_limit ) {
					case '1week':
						$before = array( 'week' => date( 'W' ) -1 );
						break;
					case '2weeks':
						$before = array( 'week' => date( 'W' ) -2 );
						break;
					case '1month':
						$before = array( 'month' => date( 'm' ) -1 );
						break;
					case '1year':
						$before = array( 'year' => date( 'Y' ) -1 );
						break;

				}
				$args['date_query']	= array(
										'before' => $before,
									);
			}

			$q = new WP_Query( $args );
			// echo '<pre>';
			// var_dump($q);# die();
			/**		$dquerystr = $wpdb->prepare("
				SELECT $wpdb->posts.*, $wpdb->postmeta.*
				FROM $wpdb->posts, $wpdb->postmeta
				WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
				AND $wpdb->posts.post_type = %s
			 ", 'nomination' );
			# This is how we do a custom query, when WP_Query doesn't do what we want it to.
			$nominationsArchivalPosts = $wpdb->get_results($dquerystr, OBJECT);
	*/		// print_r(count($nominationsArchivalPosts)); die();
			// $nominationsArchivalPosts = $q;
			$feedObject = array();
			$c = 0;
			$id_list = '';
			if ( $q->have_posts() ) :

				while ( $q->have_posts() ) : $q->the_post();

					// This takes the $post objects and translates them into something I can do the standard WP functions on.
					// setup_postdata($post);
					$post_id = get_the_ID();
					// var_dump(get_the_ID());
					$id_list .= get_the_title() . ',';
					// Switch the delete on to wipe rss archive posts from the database for testing.
					$userObj = wp_get_current_user();
					$user_id = $userObj->ID;
					// $feed_post_id = pressforward('controller.metas')->get_post_pf_meta($post_id, 'pf_item_post_id', true);
					// pf_set_relationship( 'archive', $feed_post_id, $user_id, '1' );
					pf_set_relationship( 'archive', $post_id, $user_id, '1' );
					pressforward( 'controller.metas' )->update_pf_meta( $post_id, 'pf_archive', 1 );
				endwhile;

			endif;

			wp_reset_postdata();
			// var_dump('IDs: ');
			// var_dump($id_list); die();
			ob_start();
			//var_dump( $q );
			$response = array(
					'what' => 'relationships',
					'action' => 'pf_archive_all_nominations',
					'id' => $user_id,
					'data' => 'Archives deleted: ' . $id_list,
					'supplemental' => array(
							'user' => $user_id,
							'buffered' => ob_get_contents(),
							'query'	=> $date_limit,
						),
					);

			$xmlResponse = new WP_Ajax_Response( $response );
			$xmlResponse->send();
			ob_end_flush();
			die();
			// print_r(__('All archives deleted.', 'pf'));
	}

}
