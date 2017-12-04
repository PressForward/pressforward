<?php
namespace PressForward\Core\Utility;

use PressForward\Interfaces\Items as Items;
use PressForward\Interfaces\Advance_System as Advance_System;

use PressForward\Controllers\Metas;

/**
 * PressForward object lifecycle tools
 * was once form_of
 */

class Forward_Tools {
	// var $post_type;
	// var $tag_taxonomy;
	// var $advance_interface;
	// var $post_interface;
	function __construct( Items $item_interface, Advance_System $advance_interface, Metas $meta_interface ) {
		// var_dump('a');
		$this->item_interface = $item_interface;
		$this->advance_interface = $advance_interface;
		$this->metas = $meta_interface;
	}

	// Transition to next step Tools
	public function transition_to_last_step( $nomination_id ) {
		$post = $this->item_interface->get_post( $nomination_id, ARRAY_A );
		$d_post = $post;
		$current_user = wp_get_current_user();
		pf_log( 'User: ' );
		pf_log( $current_user );
		if ( $current_user->ID > 0 ) {
			$d_post['post_author'] = $current_user->ID;
		}
		$newPostID = $this->advance_interface->to_last_step( $d_post );
		pf_log( $newPostID );
		// $this->metas->transition_post_meta($post_ID, $newPostID);
		if ( $this->item_interface->is_error( $newPostID ) ) {
			pf_log( $newPostID );
			return false;
		} else {
			$this->advance_interface->transition( $nomination_id, $newPostID );
			return $newPostID;
		}
	}

	public function transition_to_nomination( $item_post_id ) {
		// Create
		$post = $this->item_interface->get_post( $item_post_id, ARRAY_A );
		$nomination_id = $this->advance_interface->to_nomination( $post );

		if ( $this->item_interface->is_error( $nomination_id ) ) {
			pf_log( $nomination_id );
			return false;
		} else {
			$this->advance_interface->transition( $item_post_id, $nomination_id );
			$this->nomination_user_transition_check( $nomination_id );
			return $nomination_id;
		}
	}

	public function append_source_statement($post_id, $item_content, $source){
		$source_position = get_option( 'pf_source_statement_position', 'bottom' );
		if ( ( 'bottom' == $source_position ) && $source ) {
			$item_content = $item_content . pressforward( 'admin.nominated' )->get_the_source_statement( $post_id );
		} elseif ( $source ) {
			$item_content = pressforward( 'admin.nominated' )->get_the_source_statement( $post_id ) . $item_content;
		}
		return $item_content;
	}

	// Call me before transitioning the post.
	public function transition_to_readable_text( $post_id, $source = false ) {
		$post = $this->item_interface->get_post( $post_id, ARRAY_A );
		$item_content = $post['post_content'];
		// $readable_state = $this->metas->get_post_pf_meta($item_post_id, 'readable_status', true);
		$readable_status = $this->metas->get_post_pf_meta( $post_id, 'readable_status', true );
		if ( $readable_status != 1 ) {
			$readArgs = array(
				'force' => false,
				'descrip' => htmlspecialchars_decode( $item_content ),
				'url' => $this->metas->get_post_pf_meta( $post_id, 'item_link', true ),
				'authorship' => 'auto',
			);
			$item_content_obj = pressforward( 'controller.readability' )->get_readable_text( $readArgs );
			$item_content = htmlspecialchars_decode( $item_content_obj['readable'] );
			$word_count = str_word_count( $item_content );
			//$item_content = $this->append_source_statement($post_id, $item_content, $source);
			$post_id = $this->item_interface->update_post( array(
				'ID'	=> $post_id,
				'post_content'	=> $item_content,
			), true );
			if ( is_numeric( $post_id ) ) {
				if ( ( ! empty( $item_content_obj['status'] )) && ('secured' != $item_content_obj['status']) ) {
					$this->metas->update_pf_meta( $post_id, 'readable_status', 1 );
					$this->metas->update_pf_meta( $post_id, 'pf_word_count', $word_count );
				} elseif ( (1 != $readable_status) ) {
					$this->metas->update_pf_meta( $post_id, 'readable_status', 0 );
				}
				return $item_content;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function user_meta_nomination_counter_change( $userID, $increase = true ) {
		if ( get_user_meta( $userID, 'nom_count', true ) ) {

						$nom_counter = get_user_meta( $userID, 'nom_count', true );
			if ( $increase ) {
				$nom_counter++;
			} else {
				$nom_counter--;
			}
						update_user_meta( $userID, 'nom_count', $nom_counter, true );

		} elseif ( $increase ) {
						add_user_meta( $userID, 'nom_count', 1, true );

		}
	}

	function nomination_user_transition_check( $id, $can_delete = false ) {
		$current_user = wp_get_current_user();
		$nominators_orig = $this->metas->retrieve_meta( $id, 'nominator_array' );
		if ( is_array( $nominators_orig ) && ! in_array( $current_user->ID, $nominators_orig ) ) {
			$nominators = $nominators_orig;
			$nominator = $current_user->ID;
											$nominators[] = $current_user->ID;
			$this->metas->update_pf_meta( $id, 'nominator_array', $nominator );
			$nomCount = $this->metas->get_post_pf_meta( $id, 'nomination_count', true );
			if ( empty( $nomCount ) ) {
				$nomCount = 0;
			}
			$this->user_meta_nomination_counter_change( $current_user->ID );
			pf_log( 'So far we have a nominating count of ' . $nomCount );
											$nomCount++;
											pf_log( 'Now we have a nominating count of ' . $nomCount );
			$check_meta = $this->metas->update_pf_meta( $id, 'nomination_count', $nomCount );
											pf_log( 'Attempt to update the meta for nomination_count resulted in: ' );
											pf_log( $check_meta );
											$check = true;
		} elseif ( $can_delete ) {
			pf_log( 'user_nominated_already' );
				$check = true;
				$this->user_meta_nomination_counter_change( $current_user->ID, false );
				$nomCount = $this->metas->retrieve_meta( $id, 'nomination_count' );
				$nomCount--;
				$this->metas->update_pf_meta( $id, 'nomination_count', $nomCount );
			if ( 0 != $current_user->ID ) {
				if ( ! is_array( $nominators_orig ) ) {
					$nominators_orig = array();
				}
				if ( true == in_array( $current_user->ID, $nominators_orig ) ) {
					$nominators_new = array_diff( $nominators_orig, array( $current_user->ID ) );
					if ( empty( $nominators_new ) ) {
						$this->item_interface->delete_post( $id );
					} else {
						$this->metas->update_pf_meta( $id, 'nominator_array', $nominators_new );
					}
				}
			}
		} else {
			pf_log( 'User nominated already but cannot delete a post.' );
			$this->user_meta_nomination_counter_change( $current_user->ID, false );
		}
	}

	public function find_nominating_user() {
		$current_user = wp_get_current_user();
		pf_log( 'User: ' );
		pf_log( $current_user );
		if ( 0 == $current_user->ID ) {
			// Not logged in.
			$userSlug = 'external';
			$userName = __( 'External User', 'pf' );
			$userID = 0;
			pf_log( 'Can not find a user to add to the nominated count of.' );
		} else {
			// Logged in.
			pressforward( 'admin.nominated' )->user_nomination_meta();
			$userID = $current_user->ID;
			$userString = $userID;
		}

		return array( 'user_string' => $userString, 'user_id' => $userID );
	}

	public function meta_for_item_pre_nomination( $item_post_id, $item_id ){
			$user_data = $this->find_nominating_user();
			$userID = $user_data['user_id'];
			$userString = $user_data['user_string'];

			$this->metas->update_pf_meta( $item_post_id, 'nomination_count', 1 );
			$this->metas->update_pf_meta( $item_post_id, 'submitted_by', $userString );
			$this->metas->update_pf_meta( $item_post_id, 'nominator_array', array( $userID ) );
			$this->metas->update_pf_meta( $item_post_id, 'date_nominated', current_time( 'mysql' ) );
			$this->metas->update_pf_meta( $item_post_id, 'item_id', $item_id );
			$this->metas->update_pf_meta( $item_post_id, 'pf_item_post_id', $item_post_id );
			if ( ! empty( $_POST['item_link'] ) ) {
				$this->metas->update_pf_meta( $item_post_id, 'item_link', $_POST['item_link'] );
			}

			if ( empty( $_POST['item_date'] ) ) {
				$newDate = current_time( 'Y-m-d H:i:s' );
				// $_POST['item_date'] = $newDate;
				$item_date = $newDate;
			} else {
				$item_date = $_POST['item_date'];
			}
			$this->metas->update_pf_meta( $item_post_id, 'item_date', $item_date );
			$this->metas->update_pf_meta( $item_post_id, 'item_wp_date', $item_date );
	}

	// Previous step to new step Tools
	public function item_to_nomination( $item_id, $item_post_id ) {
		$nomination_and_post_check = $this->is_a_pf_type( $item_id );
		pf_log( 'Is this a PF Type?' );
		pf_log( $nomination_and_post_check );
		$post_check = $this->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );
		$this->metas->update_pf_meta( $item_post_id, 'nom_id', $item_id );
		if ( $nomination_and_post_check == false ) {
			pf_log( 'Start Transition.' );
			$this->transition_to_readable_text( $item_post_id, true );

			$this->meta_for_item_pre_nomination( $item_post_id, $item_id );

			$nomination_id = $this->transition_to_nomination( $item_post_id );
			// $this->nomination_user_transition_check( $nomination_id );
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

	public function nomination_to_last_step( $item_id = false, $nomination_id, $make_readable = true ) {
		$post_check = $this->is_a_pf_type( $item_id, 'post' );

		// Assign user status as well here.
		if ( $post_check == false ) {

			if ( false != $nomination_id ) {
				if ($make_readable){
					$this->transition_to_readable_text( $nomination_id, true );
				}
				$id = $this->transition_to_last_step( $nomination_id );
				pf_log( $id );
			}
			return $id;
		} else {
			// @TODO We should increment nominations for this item maybe?
			// Some sort of signal should occur here to indicate that the item was
			// already sent to last step.
			return $post_check;
		}

	}

	public function item_to_last_step( $item_id, $item_post_id ) {
		$nomination_id = $this->item_to_nomination( $item_id, $item_post_id );
		$post_id = $this->nomination_to_last_step( $item_id, $nomination_id );
		return $post_id;
	}

	public function bookmarklet_to_nomination( $item_id = false, $post ) {
		if ( ! $item_id ) {
			$item_id = create_feed_item_id( $_POST['item_link'], $_POST['item_title'] );
		}

		$nom_and_post_check = $this->is_a_pf_type( $item_id );
		if ( $nom_and_post_check == false ) {
			$item_check = pressforward('schema.feed_item')->get_by_item_id($item_id);
			var_dump($item_check, $item_id, $_POST['item_title']); die();
			if ( !empty( $item_check ) && ( $item_check->ID > 0 ) ){
				$this->meta_for_item_pre_nomination( $item_check->ID, $item_id );
				$item_post = $post;
				$item_post['ID'] = $item_check->ID;
				$item_post = $this->item_interface->update_post( $item_post );
			}
			// PF NOTE: Switching post type to nomination.
			$post['post_type'] = pressforward( 'schema.nominations' )->post_type;
			$post['post_date'] = current_time( 'Y-m-d H:i:s' );
			$post['post_date_gmt'] = get_gmt_from_date( current_time( 'Y-m-d H:i:s' ) );
			if ( strlen( esc_url( $_POST['item_link'] ) ) <= 243 ) {
				$post['guid'] = esc_url( $_POST['item_link'] );
			} else {
				$post['guid'] = substr( esc_url( $_POST['item_link'] ), 0, 243 );
			}
			$post_array = $post;
			// var_dump('<pre>'); var_dump($post); die();
			// $post['post_type'] = 'post';
			// PF NOTE: This is where the inital post is created.
			// PF NOTE: Put get_post_nomination_status here.
			$post = $this->item_interface->insert_post( $post, true, $item_id );
			if ( is_wp_error( $post ) ) {
				wp_die( $post->get_error_message() );
			}
			$post_ID = $post;
			// var_dump('<pre>'); var_dump($post); var_dump($post_array); die();
			$this->advance_interface->prep_bookmarklet( $post );
			if ( ! isset( $_POST['item_date'] ) ) {
				$newDate = date( 'Y-m-d H:i:s' );
				$item_date = $newDate;
				// $_POST['item_date'] = $newDate;
			} else {
				$item_date = $_POST['item_date'];
			}

			$url_parts = parse_url( $_POST['item_link'] );
			if ( ! empty( $url_parts['host'] ) ) {
				$source = $url_parts['host'];
			} else {
				$source = '';
			}
			$tags = $_POST['post_tags'];
			if ( empty( $tags ) || is_wp_error( $tags ) ) {
				$tags[] = 'via bookmarklet';
				if ( is_wp_error( $tags ) ) {
					pf_log( $tags );
				}
			}
			$user_data = $this->find_nominating_user();
			$userID = $user_data['user_id'];
			$userString = $user_data['user_string'];
			// $tags[] = 'via bookmarklet';
			// pf_log($_POST);
			if ( empty($_POST['item_author']) ){
				$item_author = 'Author on Source';
			} else {
				$item_author = $_POST['item_author'];
			}
			$pf_meta_args = array(
				$this->metas->meta_for_entry( 'item_id', $item_id ),
				$this->metas->meta_for_entry( 'item_link', $_POST['item_link'] ),
				$this->metas->meta_for_entry( 'nomination_count', 1 ),
				$this->metas->meta_for_entry( 'source_title', 'Bookmarklet' ),
				$this->metas->meta_for_entry( 'item_date', $item_date ),
				// $this->metas->meta_for_entry('item_date', $item_date),
				$this->metas->meta_for_entry( 'date_nominated', $_POST['date_nominated'] ),
				$this->metas->meta_for_entry( 'item_author', $item_author ),
				// $this->metas->meta_for_entry('authors', $_POST['authors']),
				$this->metas->meta_for_entry( 'pf_source_link', $source ),
				$this->metas->meta_for_entry( 'item_feat_img', $_POST['item_feat_img'] ),
				$this->metas->meta_for_entry( 'submitted_by', $userString ),
				$this->metas->meta_for_entry( 'nominator_array', array( $userID ) ),
				// The item_wp_date allows us to sort the items with a query.
				$this->metas->meta_for_entry( 'item_wp_date', $item_date ),
				// We can't just sort by the time the item came into the system (for when mult items come into the system at once)
				// So we need to create a machine sortable date for use in the later query.
				$this->metas->meta_for_entry( 'sortable_item_date', strtotime( $item_date ) ),
				$this->metas->meta_for_entry( 'item_tags', $tags ),
				$this->metas->meta_for_entry( 'source_repeat', 1 ),
				$this->metas->meta_for_entry( 'revertible_feed_text', $post['post_content'] ),

			);
			$this->metas->establish_post( $post_ID, $pf_meta_args );
			$this->metas->update_pf_meta( $post_ID, 'nom_id', $post_ID );
			$this->metas->handle_item_tags( $post_ID, $tags );
			return $post_ID;
		} else {
			// Do something with the returned ID.
			// Increment the nomination count if the nomination exists.
			$this->nomination_user_transition_check( $nom_and_post_check );
			return $nom_and_post_check;
		}

	}

	public function bookmarklet_to_last_step( $item_id = false, $post ) {
		if ( ! $item_id ) {
			$item_id = create_feed_item_id( $_POST['item_link'], $post['post_title'] );
		}
		$nomination_id = $this->bookmarklet_to_nomination( $item_id, $post );
		pf_log( $nomination_id );
		return $this->nomination_to_last_step( $item_id, $nomination_id, false );
	}

	public function is_a_pf_type( $item_id, $post_type = false, $update = false ) {
		if ( ! $post_type ) {
			$post_type = array( 'post', pressforward( 'schema.nominations' )->post_type );
		}
		$attempt = $this->advance_interface->get_pf_type_by_id( $item_id, $post_type );
		if ( ! empty( $attempt ) ) {
			$r = $attempt;
			pf_log( 'Existing post at ' . $r );
		} else {
			$r = false;
		}
		return $r;
	}
}
