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
		$this->item_interface    = $item_interface;
		$this->advance_interface = $advance_interface;
		$this->metas             = $meta_interface;
	}

	public function assure_user_id( $user_id = false ) {
		if ( ! $user_id ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		}
		pf_log( 'by ' . $user_id );
		return $user_id;
	}

	public function apply_nomination_array( $id, $user_id = false ) {
		pf_log( 'Processing Nominator Array on post ' );
		pf_log( $id );
		$nominators = $this->metas->get_post_pf_meta( $id, 'nominator_array' );

		$value_array = array(
			'user_id'             => $user_id,
			'nomination_datetime' => date( 'Y-m-d H:i:s' ),
			'nomination_unixtime' => time(),
		);
		if ( empty( $nominators ) ) {
			$nominators = array();
		} elseif ( ! is_array( $nominators ) ) {
			$nominators = array( $nominators );
		}
		// We are doing a removal.
		if ( array_key_exists( $user_id, $nominators ) ) {
			unset( $nominators[ $user_id ] );
			$applied = false;
		} else {
			$nominators[ $user_id ] = $value_array;
			$applied                = true;

		}
		return array(
			'nominators' => $nominators,
			'applied'    => $applied,
		);
	}

	public function apply_nomination_count( $id, $user_id = false, $is_post = false ) {
		$nomCount = $this->metas->get_post_pf_meta( $id, 'nomination_count', true );
		if ( empty( $nomCount ) ) {
			$nomCount = 0;
		}
		$parent_id = wp_get_post_parent_id( $id );
		if ( false != $parent_id && ! $is_post ) {
			$feedNomCount = $this->metas->get_post_pf_meta( $parent_id, 'pf_nominations_in_feed', true );
			if ( empty( $feedNomCount ) ) {
				$feedNomCount = 0;
			}
			$check_meta = $this->metas->update_pf_meta( $parent_id, 'pf_nominations_in_feed', ++$feedNomCount );
		}

		$check_meta = $this->metas->update_pf_meta( $id, 'nomination_count', ++$nomCount );
		pf_log( 'Attempt to update the meta for nomination_count resulted in: ' );
		pf_log( $check_meta );
		return $nomCount;
	}

	public function revoke_nomination_count( $id, $user_id = false, $is_post = false ) {
		$nomCount = $this->metas->get_post_pf_meta( $id, 'nomination_count', true );
		if ( empty( $nomCount ) ) {
			$nomCount = 0;
		}

		$parent_id = wp_get_post_parent_id( $id );
		if ( false != $parent_id && ! $is_post ) {
			$feedNomCount = $this->metas->get_post_pf_meta( $parent_id, 'pf_nominations_in_feed', true );
			if ( empty( $feedNomCount ) ) {
				$feedNomCount = 0;
			}
			$check_meta = $this->metas->update_pf_meta( $parent_id, 'pf_nominations_in_feed', max( 0, --$feedNomCount ) );
		}

		$check_meta = $this->metas->update_pf_meta( $id, 'nomination_count', max( 0, --$nomCount ) );
		pf_log( 'Attempt to update the meta for nomination_count resulted in: ' );
		pf_log( $check_meta );
		return $nomCount;
	}

	public function apply_nomination_user_data( $id, $userID = false ) {
		$nom_stats       = get_user_meta( $userID, 'nom_stats', true );
		$noms_counted    = get_user_meta( $userID, 'nom_count', true );
		$function_to_use = 'update_user_meta';
		if ( ! is_array( $nom_stats ) ) {
			$nom_stats       = array();
			$function_to_use = 'add_user_meta';
		}
		if ( empty( $noms_counted ) || $noms_counted <= 0 ) {
			$noms_counted = 0;
		}
		// var_dump($id);
		if ( ! array_key_exists( $id, $nom_stats ) ) {
			$nom_stats[ $id ] = array(
				'nomination_id'       => $id,
				'nomination_datetime' => date( 'Y-m-d H:i:s' ),
				'nomination_unixtime' => time(),
			);
		}
		pf_log( 'Create nom_count in user meta for user ' . $userID );
		$function_to_use( $userID, 'nom_count', ++$noms_counted, true );
		$function_to_use( $userID, 'nom_stats', $nom_stats, true );
		return $noms_counted;
	}

	public function revoke_nomination_user_data( $id, $userID = false ) {
		$nom_stats    = get_user_meta( $userID, 'nom_stats', true );
		$noms_counted = get_user_meta( $userID, 'nom_count', true );
		if ( ! is_array( $nom_stats ) ) {
			$nom_stats = array();
			return false;
		}
		if ( empty( $noms_counted ) || $noms_counted < 0 ) {
			$noms_counted = 0;
		}
		if ( array_key_exists( $id, $nom_stats ) ) {
			unset( $nom_stats[ $id ] );
		}
		pf_log( 'Create nom_count in user meta for user ' . $userID );
		update_user_meta( $userID, 'nom_count', max( 0, --$noms_counted ) );
		update_user_meta( $userID, 'nom_stats', $nom_stats );
		return $noms_counted;
	}

	public function apply_nomination_data( $id, $user_id = false, $is_post = false ) {
		$user_id    = $this->assure_user_id( $user_id );
		$nominators = $this->apply_nomination_array( $id, $user_id );
		// var_dump($id, $nominators);
		if ( $nominators['applied'] && ! $is_post ) {
			$this->apply_nomination_user_data( $id, $user_id );
			$this->apply_nomination_count( $id, $user_id );
		} elseif ( ! $is_post ) {
			$this->revoke_nomination_user_data( $id, $user_id );
			$this->revoke_nomination_count( $id, $user_id );
		} elseif ( $nominators['applied'] && $is_post ) {
			$this->apply_nomination_count( $id, $user_id, true );
		} elseif ( $is_post ) {
			//$this->revoke_nomination_count( $id, $user_id, true );
		}
		return $nominators['nominators'];
	}

	// Transition to next step Tools
	public function transition_to_last_step( $nomination_id ) {
		$post         = $this->item_interface->get_post( $nomination_id, ARRAY_A );
		$d_post       = $post;
		$current_user = wp_get_current_user();
		pf_log( 'User: ' );
		pf_log( $current_user );
		if ( $current_user->ID > 0 ) {
			$d_post['post_author'] = $current_user->ID;
		}
		$newPostID = $this->advance_interface->to_last_step( $d_post );
		pf_log( $newPostID );
		$this->metas->update_pf_meta( $nomination_id, 'pf_final_step_id', $newPostID );
		// $this->metas->transition_post_meta($post_ID, $newPostID);
		if ( $this->item_interface->is_error( $newPostID ) ) {
			pf_log( $newPostID );
			return false;
		} else {
			$this->advance_interface->transition( $nomination_id, $newPostID );
			return $newPostID;
		}
	}

	public function transition_to_nomination( $item_post_id, $from_meta_added_item = false ) {
		// Create
		$post          = $this->item_interface->get_post( $item_post_id, ARRAY_A );
		$nomination_id = $this->advance_interface->to_nomination( $post );

		if ( $this->item_interface->is_error( $nomination_id ) ) {
			pf_log( $nomination_id );
			return false;
		} else {
			$this->advance_interface->transition( $item_post_id, $nomination_id );
			if ( ! $from_meta_added_item ) {
				$nominators = pressforward( 'utility.forward_tools' )->apply_nomination_data( $nomination_id );
				pressforward( 'controller.metas' )->update_pf_meta( $nomination_id, 'nominator_array', $nominators );
			}
			return $nomination_id;
		}
	}

	public function append_source_statement( $post_id, $item_content, $source ) {
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
		$post         = $this->item_interface->get_post( $post_id, ARRAY_A );
		$item_content = $post['post_content'];
		// $readable_state = $this->metas->get_post_pf_meta($item_post_id, 'readable_status', true);
		$readable_status = $this->metas->get_post_pf_meta( $post_id, 'readable_status', true );
		if ( $readable_status != 1 ) {
			$readArgs         = array(
				'force'      => false,
				'descrip'    => htmlspecialchars_decode( $item_content ),
				'url'        => $this->metas->get_post_pf_meta( $post_id, 'item_link', true ),
				'authorship' => 'auto',
			);
			$item_content_obj = pressforward( 'controller.readability' )->get_readable_text( $readArgs );
			$item_content     = htmlspecialchars_decode( $item_content_obj['readable'] );
			$word_count       = str_word_count( $item_content );
			// $item_content = $this->append_source_statement($post_id, $item_content, $source);
			$saved__POST = $_POST;
			$_POST       = array();
			$post_id     = $this->item_interface->update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $item_content,
				), true
			);
			$_POST       = $saved__POST;
			if ( is_numeric( $post_id ) ) {
				if ( ( ! empty( $item_content_obj['status'] ) ) && ( 'secured' != $item_content_obj['status'] ) ) {
					$this->metas->update_pf_meta( $post_id, 'readable_status', 1 );
					$this->metas->update_pf_meta( $post_id, 'pf_word_count', $word_count );
				} elseif ( ( 1 != $readable_status ) ) {
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

	function nomination_user_transition_check( $id, $item_id, $can_delete = false ) {
		$nominators = $this->apply_nomination_data( $id );
		$this->metas->update_pf_meta( $id, 'nominator_array', $nominators );
		$final_step_parent = $this->metas->get_post_pf_meta( $id, 'pf_final_step_id' );
		if ( 0 !== $final_step_parent && false !== $final_step_parent ) {
			// The nomination has already been pushed to final step.
			// Increment it as well
			// var_dump($final_step_parent); die();
			$nominators = $this->apply_nomination_data( $final_step_parent, false, true );
			$this->metas->update_pf_meta( $final_step_parent, 'nominator_array', $nominators );
		}
		return $nominators;
	}

	public function user_nomination_meta( $nomination_id, $increase = true ) {
		$current_user   = wp_get_current_user();
		$userID         = $current_user->ID;
		$user_nom_count = get_user_meta( $userID, 'nom_count', true );
		if ( ! empty( $user_nom_count ) ) {
				pf_log( 'Update nom_count in user meta for user ' . $userID );
						$nom_counter = get_user_meta( $userID, 'nom_count', true );
						$nom_stats   = get_user_meta( $userID, 'nom_stats', true );
			if ( empty( $nom_stats ) ) {
				$nom_stats = array();
			}
						$old_nom_stats   = $nom_stats;
						$old_nom_counter = $nom_counter;
			if ( $increase ) {
				$nom_counter                 = $nom_counter + 1;
				$nom_stats[ $nomination_id ] = array(
					'nomination_id'       => $nomination_id,
					'nomination_datetime' => date( 'Y-m-d H:i:s' ),
					'nomination_unixtime' => time(),
				);
			} else {
				$nom_counter = $nom_counter - 1;
				unset( $nom_stats[ $nomination_id ] );
			}
						pf_log( 'Update nom_count in user meta for user ' . $userID . ' with value of ' . $nom_counter );
						update_user_meta( $userID, 'nom_count', $nom_counter, $old_nom_counter );
						update_user_meta( $userID, 'nom_stats', $nom_stats, $old_nom_stats );

		} elseif ( $increase ) {
			$nom_stats                   = array();
			$nom_stats[ $nomination_id ] = array(
				'nomination_id'       => $nomination_id,
				'nomination_datetime' => date( 'Y-m-d H:i:s' ),
				'nomination_unixtime' => time(),
			);
			pf_log( 'Create nom_count in user meta for user ' . $userID );
			add_user_meta( $userID, 'nom_count', 1, true );
			add_user_meta( $userID, 'nom_stats', $nom_stats, true );

		} else {
			pf_log( 'Nothing to do with nom_count in user meta for user ' . $userID );
			return false;
		}
	}

	public function find_nominating_user( $nomination_id ) {
		$current_user = wp_get_current_user();
		pf_log( 'User: ' );
		pf_log( $current_user );
		if ( 0 == $current_user->ID ) {
			// Not logged in.
			$userSlug = 'external';
			$userName = __( 'External User', 'pf' );
			$userID   = 0;
			pf_log( 'Can not find a user to add to the nominated count of.' );
		} else {
			// Logged in.
			$this->user_nomination_meta( $nomination_id );
			$userID     = $current_user->ID;
			$userString = $userID;
		}

		return array(
			'user_string' => $userString,
			'user_id'     => $userID,
		);
	}

	// Previous step to new step Tools
	public function item_to_nomination( $item_id, $item_post_id ) {
		$nomination_and_post_check = $this->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );
		pf_log( 'Is this a PF Type?' );
		pf_log( $nomination_and_post_check );
		// var_dump($nomination_and_post_check); die();
		// $post_check = $this->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );
		if ( $nomination_and_post_check == false ) {

			// $this->metas->update_pf_meta( $item_post_id, 'nomination_count', 1 );
			$nominators = $this->apply_nomination_data( $item_post_id );
			$this->metas->update_pf_meta( $item_post_id, 'nominator_array', $nominators );
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
			pf_log( 'Start Transition.' );

			$user_data  = $this->find_nominating_user( $item_post_id );
			$userID     = $user_data['user_id'];
			$userString = $user_data['user_string'];
			$this->metas->update_pf_meta( $item_post_id, 'submitted_by', $userString );

			$this->transition_to_readable_text( $item_post_id, true );
			$nomination_id = $this->transition_to_nomination( $item_post_id, true );
			$this->metas->update_pf_meta( $item_post_id, 'nom_id', $nomination_id );
			$this->metas->update_pf_meta( $nomination_id, 'nom_id', $nomination_id );
			// Assign user status as well here.
			return $nomination_id;
		} else {
			// Uptick user if they are not in nominator array
			// If they are in the nominator array and no one else is, un-nominate.
			// If they are in the nominator array and someone else is, un-relate
			// them to that nomination.
			$this->nomination_user_transition_check( $nomination_and_post_check, $item_id, true );
			return $nomination_and_post_check;
		}
	}

	public function nomination_to_last_step( $item_id = false, $nomination_id, $make_readable = true ) {
		$post_check = $this->is_a_pf_type( $item_id, 'post' );

		// Assign user status as well here.
		if ( $post_check == false ) {

			if ( false != $nomination_id ) {
				if ( $make_readable ) {
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
		$post_id       = $this->nomination_to_last_step( $item_id, $nomination_id );
		return $post_id;
	}

	public function bookmarklet_to_nomination( $item_id = false, $post ) {
		$_POST = array_merge( $_POST, $post );
		if ( ! $item_id ) {
			$item_id = create_feed_item_id( $_POST['item_link'], $post['post_title'] );
			// $post['item_id'] = $item_id;
		}

		$nom_and_post_check = $this->is_a_pf_type( $item_id, pressforward( 'schema.nominations' )->post_type );
		if ( $nom_and_post_check == false ) {
			$item_check = $this->is_a_pf_type( $item_id, pressforward( 'schema.feed_item' )->post_type );
			if ( $item_check != false ) {
				$nomination_id = false;
				$nomination_id = $this->item_to_nomination( $item_id, $item_check );
				pressforward( 'utility.relate' )->basic_relate( 'nominate', $item_check, 'on' );
				// var_dump($nomination_id); die();
			} else {
				$nomination_id = false;
			}
			// PF NOTE: Switching post type to nomination.
			$post['post_type']     = pressforward( 'schema.nominations' )->post_type;
			$post['post_date']     = current_time( 'Y-m-d H:i:s' );
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
			if ( $nomination_id != false ) {
				$save_post_to_avoid_hooks = array();
				foreach ( $_POST as $key => $value ) {
					$save_post_to_avoid_hooks[ $key ] = $value;
					$_POST[ $key ]                    = false;
				}
				$post['ID'] = $nomination_id;
				$this->item_interface->update_post( $post );
				foreach ( $save_post_to_avoid_hooks as $key => $value ) {
					$_POST[ $key ] = $value;
				}
				// $_POST = $save_post_to_avoid_hooks;
				$post = $nomination_id;
			} else {
				$post = $this->item_interface->insert_post( $post, true, $item_id );
				if ( !is_wp_error( $post ) ) {
					do_action( 'pf_transition_to_nomination', $post );
				}
			}
			if ( is_wp_error( $post ) ) {
				wp_die( $post->get_error_message() );
			}
			$post_ID = $post;

			// var_dump('<pre>'); var_dump($post); var_dump($post_array); die();
			$this->advance_interface->prep_bookmarklet( $post );
			if ( ! isset( $_POST['item_date'] ) ) {
				$newDate   = date( 'Y-m-d H:i:s' );
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
			$tags_array = $this->metas->handle_item_tags( $post_ID, $tags );

			if ( $nomination_id != false ) {
				$old_tags = $this->metas->get_post_pf_meta( $post_ID, 'item_tags' );
				if ( ! empty( $old_tags ) ) {
					if ( ! is_array( $old_tags ) ) {
						$old_tags_array     = explode( ',', $old_tags );
						$old_tags_array_two = explode( ';', $old_tags );
						$old_tags           = array_merge( $old_tags_array, $old_tags_array_two );
					}
					if ( ! empty( $tags_array ) ) {
						$tags = array_merge( $old_tags, $tags_array );
					} else {
						$tags = $old_tags;
					}
				}
				if ( is_array( $tags ) ) {
					$tags = implode( ',', $tags );
				}
				$this->metas->update_pf_meta( $post_ID, 'item_tags', $tags );
				return $nomination_id;
			}

			$user_data  = $this->find_nominating_user( $post_ID );
			$userID     = $user_data['user_id'];
			$userString = $user_data['user_string'];
			// $tags[] = 'via bookmarklet';
			// pf_log($_POST);
			if ( empty( $_POST['item_author'] ) ) {
				$item_author = 'Author on Source';
			} else {
				$item_author = $_POST['item_author'];
			}
			$pf_meta_args = array(
				$this->metas->meta_for_entry( 'item_id', $item_id ),
				$this->metas->meta_for_entry( 'item_link', $_POST['item_link'] ),
				// $this->metas->meta_for_entry( 'nomination_count', 1 ),
				$this->metas->meta_for_entry( 'source_title', 'Bookmarklet' ),
				$this->metas->meta_for_entry( 'item_date', $item_date ),
				// $this->metas->meta_for_entry('item_date', $item_date),
				$this->metas->meta_for_entry( 'date_nominated', current_time( 'mysql' ) ),
				$this->metas->meta_for_entry( 'item_author', $item_author ),
				// $this->metas->meta_for_entry('authors', $_POST['authors']),
				$this->metas->meta_for_entry( 'pf_source_link', $source ),
				$this->metas->meta_for_entry( 'item_feat_img', $_POST['item_feat_img'] ),
				$this->metas->meta_for_entry( 'submitted_by', $userString ),
				// $this->metas->meta_for_entry( 'nominator_array', array( $userID ) ),
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
			pf_log( $pf_meta_args );
			$this->metas->update_pf_meta( $post_ID, 'nom_id', $post_ID );
			// $this->metas->handle_item_tags( $post_ID, $tags );
			$nominators = $this->apply_nomination_data( $post_ID );
			$this->metas->update_pf_meta( $post_ID, 'nominator_array', $nominators );
			return $post_ID;
		} else {
			// Do something with the returned ID.
			// Increment the nomination count if the nomination exists.
			$this->nomination_user_transition_check( $nom_and_post_check, $item_id );
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
		// var_dump($post_type); die();
		if ( ! empty( $attempt ) ) {
			$r = $attempt;
			pf_log( 'Existing post at ' . $r );
		} else {
			$r = false;
		}
		return $r;
	}
}
