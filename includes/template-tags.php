<?php
/**
 * Template functions.
 *
 * @package PressForward
 */

/**
 * Gets the source title for an item.
 *
 * @throws Exception When an ID could not be found.
 *
 * @param int $id ID of the item.
 * @return string
 */
function get_the_source_title( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
	}

	if ( empty( $id ) ) {
		throw new Exception( 'get_the_source_title could not find an ID.', 1 );
	}

	$parent_id = get_post_ancestors( $id );

	if ( empty( $parent_id[0] ) ) {
		return __( 'Bookmarklet', 'pressforward' );
	}

	$parent = get_post( $parent_id[0] );
	if ( empty( $parent ) ) {
		pf_log( 'get_the_source_title could not find a post object checking with the ID of ' . $parent_id[0] );
		return __( 'Unknown Feed', 'pressforward' );
	}

	$st = $parent->post_title;
	return $st;
}

/**
 * Echoes the source title of an item.
 */
function the_source_title() {
	echo esc_html( get_the_source_title() );
}

/**
 * Gets the original post date of the current item.
 *
 * @return string
 */
function get_the_original_post_date() {
	$opd = pressforward( 'controller.metas' )->retrieve_meta( get_the_ID(), 'item_date' );
	return $opd;
}

/**
 * Echoes the original post date of the current item.
 */
function the_original_post_date() {
	echo esc_html( get_the_original_post_date() );
}

/**
 * Gets the item author of an item.
 *
 * @param int $id ID.
 * @return string
 */
function get_the_item_author( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
	}
	$ia = pressforward( 'controller.metas' )->retrieve_meta( $id, 'item_author' );
	return $ia;
}

/**
 * Echoes the item author of the current item.
 */
function the_item_author() {
	echo esc_html( get_the_item_author() );
}

/**
 * Gets the item link of an item.
 *
 * @param int $id ID.
 * @return string
 */
function get_the_item_link( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
	}
	$m = pressforward( 'controller.metas' )->retrieve_meta( $id, 'item_link' );
	return $m;
}

/**
 * Echoes the item link of an item.
 */
function the_item_link() {
	echo esc_url( get_the_item_link() );
}

/**
 * Gets the featured image of the current item.
 *
 * @return string
 */
function get_the_item_feat_image() {
	$m = pressforward( 'controller.metas' )->retrieve_meta( get_the_ID(), 'item_feat_img' );
	return $m;
}

/**
 * Echoes the featured image of the current item.
 */
function the_item_feat_image() {
	echo esc_html( get_the_item_feat_image() );
}

/**
 * Gets the tags of the current item.
 *
 * @return string
 */
function get_the_item_tags() {
	$m = pressforward( 'controller.metas' )->retrieve_meta( get_the_ID(), 'item_tags' );
	return $m;
}

/**
 * Echoes the tags of the current item.
 */
function the_item_tags() {
	echo esc_html( get_the_item_tags() );
}

/**
 * Gets the repeats of the current item.
 *
 * @return string
 */
function get_the_repeats() {
	$m = pressforward( 'controller.metas' )->retrieve_meta( get_the_ID(), 'source_repeat' );
	return $m;
}

/**
 * Echoes the repeats of the current item.
 */
function the_item_repeats() {
	echo esc_html( get_the_repeats() );
}

/**
 * Gets the nomination count of the current item.
 *
 * @return string
 */
function get_the_nomination_count() {
	$m = pressforward( 'controller.metas' )->get_post_pf_meta( get_the_ID(), 'nomination_count' );
	return $m;
}

/**
 * Echoes the nomination count of the current item.
 */
function the_nomination_count() {
	echo esc_html( get_the_item_tags() );
}

/**
 * Gets the nominator IDs of an item.
 *
 * @param int $id ID.
 * @return array
 */
function get_the_nominator_ids( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
	}
	$m = pressforward( 'controller.metas' )->get_post_pf_meta( $id, 'nominator_array' );

	return is_array( $m ) ? array_keys( $m ) : [];
}

/**
 * Gets information about nominators for the current item.
 *
 * @return array
 */
function get_the_nominators() {
	$nomers = get_the_nominator_ids();

	$nominating_user_ids = $nomers;
	$nominating_users    = array();
	if ( empty( $nominating_user_ids ) ) {
		return array();
	}

	foreach ( $nominating_user_ids as $user_id ) {
		if ( empty( $user_id ) ) {
			continue;
		}

		$user_obj = get_user_by( 'id', $user_id );
		if ( ! empty( $user_obj ) ) {
			$nominating_users[] = $user_obj->display_name;
		}
	}

	return $nominating_users;
}

/**
 * Gets a list of nominating users for the current item.
 *
 * @return string
 */
function get_the_nominating_users() {
	$nominating_users = get_the_nominators();
	return implode( ', ', $nominating_users );
}

/**
 * Echoes a list of nominating users for the current item.
 */
function the_nominators() {
	echo esc_html( get_the_nominating_users() );
}

/**
 * Gets the word count for the current item.
 *
 * @return string
 */
function get_the_word_count() {
	$m = pressforward( 'controller.metas' )->get_post_pf_meta( get_the_ID(), 'pf_word_count' );
	return $m;
}

/**
 * Echoes the word count for the current item.
 */
function the_word_count() {
	echo esc_html( get_the_word_count() );
}

/**
 * Generates a list of comments.
 *
 * @param int $id_for_comments Item ID.
 */
function the_pf_comments( $id_for_comments = 0 ) {
	if ( ! $id_for_comments ) {
		$id_for_comments = get_the_ID();
	}

	$item_post_id = pressforward( 'controller.metas' )->get_post_pf_meta( $id_for_comments, 'pf_item_post_id', true );

	if ( $item_post_id ) {
		$id_for_comments = $item_post_id;
	}

	?>
	<ul id="ef-comments">
		<?php
		$comments = new PF_Comments();

		$editorial_comments = $comments->ef_get_comments_plus(
			array(
				'post_id'      => $id_for_comments,
				'comment_type' => 'pressforward-comment',
				'orderby'      => 'comment_date',
				'order'        => 'ASC',
				'status'       => 'pressforward-comment',
			)
		);

		// We use this so we can take advantage of threading and such.
		wp_list_comments(
			array(
				'type'         => 'pressforward-comment',
				'callback'     => array( $comments, 'the_comment' ),
				'end-callback' => '__return_false',
			),
			$editorial_comments
		);
		?>
	</ul>
	<?php
}

/**
 * Gets the feed list.
 *
 * @param string $status Default 'publish'.
 * @param int    $page   Default 1.
 * @param string $order  Default 'ASC'.
 * @return string
 */
function get_pf_feed_list( $status = 'publish', $page = 1, $order = 'ASC' ) {
	$return_string = '<ul class="feedlist">';

	$query = new WP_Query(
		array(
			'post_type'   => pressforward( 'schema.feeds' )->post_type,
			'post_status' => $status,
			'paged'       => $page,
			'orderby'     => 'title',
			'order'       => $order,
		)
	);

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();

			$return_string .= '<li class="feeditem"><a href="' . esc_url( pressforward( 'controller.metas' )->get_post_pf_meta( get_the_ID(), 'feedUrl', true ) ) . '" target="_blank">' . esc_html( get_the_title() ) . '</a>';

			$child_args = array(
				'post_parent' => get_the_ID(),
				'post_type'   => pressforward( 'schema.feeds' )->post_type,
			);

			$child_query = get_children( $child_args, OBJECT );
			if ( ! empty( $child_query ) ) {
				$return_string .= '<ul class="sub-feedlist">';

				foreach ( $child_query as $post ) {
					$return_string .= '<li class="feeditem"><a href="' . esc_url( pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'feedUrl', true ) ) . '" target="_blank">' . esc_html( $post->post_title ) . '</a>';
				}

				$return_string .= '</ul>';
			}

			$return_string .= '</li>';
		}
	}

	$return_string .= '</ul>';
	wp_reset_postdata();
	return $return_string;
}
