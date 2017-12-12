<?php

function get_the_source_title( $id = false ) {
	// $st = pressforward('controller.metas')->retrieve_meta(get_the_ID(), 'source_title');
	if ( ! $id ) {
		$id = get_the_ID();
	}
	if ( empty( $id ) ) {
		throw new Exception( 'get_the_source_title could not find an ID.', 1 );
		return;
	}
	$parent_id = get_post_ancestors( $id );
	if ( empty( $parent_id[0] ) ) { return __( 'Bookmarklet', 'pf' ); }
	$parent = get_post( $parent_id[0] );
	if ( empty( $parent ) ) {
		pf_log( 'get_the_source_title could not find a post object checking with the ID of ' . $parent_id[0] );
		return __( 'Unknown Feed', 'pf' );
	}
	$st = $parent->post_title;
	return $st;
}

function the_source_title() {
	echo get_the_source_title();
}

function get_the_original_post_date() {
	$opd = pressforward( 'controller.metas' )->retrieve_meta( get_the_ID(), 'item_date' );
	return $opd;
}

function the_original_post_date() {
	echo get_the_original_post_date();
}

function get_the_item_author( $id = false ) {
	if ( ! $id ) {
		$id = get_the_ID();
	}
	$ia = pressforward( 'controller.metas' )->retrieve_meta( $id, 'item_author' );
	return $ia;
}

function the_item_author() {
	echo get_the_item_author();
}

function get_the_item_link( $id = false ) {
	if ( ! $id ) {
		$id = get_the_ID();
	}
	$m = pressforward( 'controller.metas' )->retrieve_meta( $id, 'item_link' );
	return $m;
}

function the_item_link() {
	echo get_the_item_link();
}

function get_the_item_feat_image() {
	$m = pressforward( 'controller.metas' )->retrieve_meta( get_the_ID(), 'item_feat_img' );
	return $m;
}

function the_item_feat_image() {
	echo get_the_item_feat_image();
}

function get_the_item_tags() {
	$m = pressforward( 'controller.metas' )->retrieve_meta( get_the_ID(), 'item_tags' );
	return $m;
}

function the_item_tags() {
	echo get_the_item_tags();
}

function get_the_repeats() {
	$m = pressforward( 'controller.metas' )->retrieve_meta( get_the_ID(), 'source_repeat' );
	return $m;
}

function the_item_repeats() {
	echo get_the_repeats();
}

function get_the_nomination_count() {
	$m = pressforward( 'controller.metas' )->get_post_pf_meta( get_the_ID(), 'nomination_count' );
	return $m;
}

function the_nomination_count() {
	echo get_the_item_tags();
}

function get_the_nominator_ids($id = false) {
	if (!$id){
		$id = get_the_ID();
	}
	$m = pressforward( 'controller.metas' )->get_post_pf_meta( $id, 'nominator_array' );
	return array_keys($m);
}

function get_the_nominators() {
	// var_dump(get_the_nominators());
	$nominators = get_the_nominator_ids();
	if ( ! empty( $nominators ) && ! is_array( $nominators ) && is_string( $nominators ) ) {
		$nomers = explode( ',', $nominators );
		// $nomers = implode(", " , get_the_nominators());
	} else {
		$nomers = $nominators;
	}

	$nominating_user_ids = $nomers;
	$nominating_users = array();
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

function get_the_nominating_users() {
	$nominating_users = get_the_nominators();
	$imp = implode( ', ', $nominating_users );
	return $imp;

}

function the_nominators() {
	echo get_the_nominating_users();
}

function get_the_word_count() {
	$m = pressforward( 'controller.metas' )->get_post_pf_meta( get_the_ID(), 'pf_word_count' );
	return $m;
}

function the_word_count() {
	echo get_the_word_count();
}

function the_pf_comments( $id_for_comments = 0 ) {

	if ( 0 == $id_for_comments ) {
		$id_for_comments = get_the_ID();
	}
		$item_post_id = pressforward( 'controller.metas' )->get_post_pf_meta( $id_for_comments, 'pf_item_post_id', true );
	if ( $item_post_id ) {
		$id_for_comments = $item_post_id;
	}
	?>
	<ul id="ef-comments">
		<?php
		$comments = new PF_Comments;
					$editorial_comments = $comments->ef_get_comments_plus(
						array(
							'post_id' => $id_for_comments,
							'comment_type' => 'pressforward-comment',
							'orderby' => 'comment_date',
							'order' => 'ASC',
							'status' => 'pressforward-comment',
						)
					);
					// We use this so we can take advantage of threading and such
					wp_list_comments(
						array(
						'type' => 'pressforward-comment',
						'callback' => array( $comments, 'the_comment' ),
						'end-callback' => '__return_false',
						),
						$editorial_comments
					);
		?>
	</ul>
	<?php
}

function get_pf_feed_list( $status = 'publish', $page = 1, $order = 'ASC' ) {

	$return_string = '<ul class="feedlist">';
	$query = new WP_Query(array(
		'post_type' => pressforward( 'schema.feeds' )->post_type,
		'post_status' =>
		$status, 'paged' => $page,
		'orderby' => 'title',
		'order' => $order,
	));

	if ( $query->have_posts() ) :
		while ( $query->have_posts() ) : $query->the_post();
			$return_string .= '<li class="feeditem"><a href="' . pressforward( 'controller.metas' )->get_post_pf_meta( get_the_ID(), 'feedUrl', true ) . '"target="_blank">' . get_the_title() . '</a>';
		  	$child_args = array(
				'post_parent'	=> get_the_ID(),
				'post_type'		=> pressforward( 'schema.feeds' )->post_type,
			);
			$child_query = get_children( $child_args, OBJECT );
			// var_dump($child_query); die();
		  	if ( ! empty( $child_query ) ) {
				$return_string .= '<ul class="sub-feedlist">';
				foreach ( $child_query as $post ) {
					$return_string .= '<li class="feeditem"><a href="' . pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'feedUrl', true ) . '"target="_blank">' . $post->post_title . '</a>';
				}
				$return_string .= '</ul>';
			}
			$return_string .= '</li>';
		endwhile;
	endif;
	$return_string .= '</ul>';
	wp_reset_postdata();
	wp_reset_query();
	return $return_string;

}
