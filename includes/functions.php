<?php
/**
 * Miscellaneous utility functions
 *
 * @since 1.7
 *
 * @package PressForward
 */

/**
 * Register a PressForward module.
 *
 * This function allows developers to register modules into the array
 * of PressForward modules. Developers can do this to take advantage
 * of a variaty of PressForward function and to make it appear in
 * the PressForward dashboard.
 *
 * @since 2.x.x
 *
 * @param  array $args {
 *     Required. An array of arguments describing the module.
 *     @var string $slug A non-capatalized safe string.
 *     @var string $class The name of the module's class. Must match the folder name.
 * }
 * @return void
 */
function pressforward_register_module( $args ) {
	$defaults = array(
		'slug'  => '',
		'class' => '',
	);

	$r = wp_parse_args( $args, $defaults );

	// We need the 'class' and 'slug' terms.
	if ( empty( $r['class'] ) || empty( $r['slug'] ) ) {
		return;
	}

	// Ensure the class exists before attempting to initialize it.
	// @todo Should probably have better error reporting.
	if ( ! class_exists( $r['class'] ) ) {
		return;
	}

	add_filter(
		'pressforward_register_modules',
		function ( $modules ) use ( $r ) {
			return array_merge(
				$modules,
				[
					[
						'slug'  => $r['slug'],
						'class' => $r['class'],
					],
				]
			);
		}
	);
}

/**
 * Echoes the URL of the admin page.
 *
 * @since 1.7
 */
function pf_admin_url() {
	echo esc_url( pf_get_admin_url() );
}
	/**
	 * Returns the URL of the admin page.
	 *
	 * @return string
	 */
function pf_get_admin_url() {
	return add_query_arg( 'page', PF_SLUG . '-options', admin_url( 'admin.php' ) );
}


/**
 * Echoes the Nominate This bookmarklet link.
 *
 * @since 1.7
 */
function pf_shortcut_link() {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo pf_get_shortcut_link();
}

/**
 * Catches pf-nominate-this requests.
 *
 * This function contains the logic for redirecting to the correct interface
 * (Classic or Block), depending on the current URL and the site settings.
 */
function start_pf_nom_this() {
	global $pagenow;

	if ( empty( $_GET['pf-nominate-this'] ) ) {
		return;
	}

	$use_block = pressforward_use_block_nominate_this();
	$redirect  = null;

	// If this is a request for the Classic Nominate This interface, load and exit.
	if ( ! $use_block && 'edit.php' === $pagenow ) {
		include __DIR__ . '/nomthis/nominate-this.php';
		die();
	}

	// Check if we need a redirect to the correct interface.
	$redirect_query_arg_keys = [ 'u', 't', 's', 'v' ];
	$redirect_query_args     = [];
	foreach ( $redirect_query_arg_keys as $redirect_query_arg_key ) {
		if ( isset( $_GET[ $redirect_query_arg_key ] ) ) {
			$redirect_query_args[ $redirect_query_arg_key ] = sanitize_text_field( wp_unslash( $_GET[ $redirect_query_arg_key ] ) );
		}
	}

	$redirect_query_args['pf-nominate-this'] = 2;

	if ( $use_block && 'edit.php' === $pagenow ) {
		$redirect_query_args['post_type'] = 'nomination';

		$redirect = add_query_arg(
			$redirect_query_args,
			admin_url( 'post-new.php' )
		);
	} elseif ( ! $use_block && 'post-new.php' === $pagenow ) {
		$redirect = add_query_arg(
			$redirect_query_args,
			admin_url( 'edit.php' )
		);
	}

	if ( ! $redirect ) {
		return;
	}

	wp_safe_redirect( $redirect );
	die;
}

/**
 * Retrieve the Nominate This bookmarklet link.
 *
 * Use this in 'a' element 'href' attribute.
 *
 * Based on the Press This bookmarklet.
 *
 * @since 1.7
 * @link See https://github.com/WordPress/press-this/blob/trunk/press-this-plugin.php
 *
 * @return string
 */
function pf_get_shortcut_link() {
	$url = wp_json_encode( admin_url( 'post-new.php?pf-nominate-this=2&post_type=nomination' ) );

	$version = 5;

	$link = sprintf(
		'javascript:' .
		'var pfd=document,' .
		'pfw=window,' .
		'pfe=pfw.getSelection,' .
		'pfk=pfd.getSelection,' .
		'pfx=pfd.selection,' .
		'pfs=pfe?pfe():pfk?pfk():pfx?pfx.createRange().text:0,' .
		'pff="%s",' .
		'pfl=pfd.location,' .
		'pfu=pff+"&u="+(pfe=encodeURIComponent)(pfl.href)+"&t="+pfe(pfd.title)+"&s="+pfe(pfs)+"&v=%s",' .
		'pfa=function(){pfw.open(pfu,"t","toolbar=0,resizable=1,scrollbars=1,status=1,width=720,height=620")||(pfl.href=pfu)};' .
		'pfa();',
		esc_url_raw( $url ),
		esc_js( (string) $version )
	);

	return apply_filters( 'shortcut_link', $link );
}

/**
 * Determines whether to use the Block version of the Nominate This interface.
 *
 * If false, the Classic interface will be used.
 *
 * @since 5.6.0
 *
 * @return bool True if the Block version should be used, false otherwise.
 */
function pressforward_use_block_nominate_this() {
	$use_classic_override = get_option( 'pf_force_classic_nominate_this', 'no' );

	if ( 'yes' === $use_classic_override ) {
		$use_block = false;
	} else {
		/*
		 * Mock a 'nomination' post so that we can use WP's core function.
		 * Plugins like Classic Editor use those filters.
		 */
		$nomination_post            = new stdClass();
		$nomination_post->post_type = 'nomination';
		$nomination_post_obj        = new WP_Post( $nomination_post );

		// Classic Editor plugin falls back on this value when UI is hidden.
		$use_block_for_nomination_post_type = apply_filters( 'use_block_editor_for_post_type', true, 'nomination' );

		// Classic Editor plugin uses this when UI is available to users.
		$use_block_for_nomination_post = apply_filters( 'use_block_editor_for_post', true, $nomination_post_obj );

		// Since both values default to true, we disable Block if either one returns false.
		$use_block = $use_block_for_nomination_post_type && $use_block_for_nomination_post;
	}

	/**
	 * Filters whether to use the Block version of the Nominate This interface.
	 *
	 * @since 5.6.0
	 *
	 * @param bool $use_block True if the Block version should be used, false otherwise.
	 */
	return apply_filters( 'pressforward_use_block_nominate_this', $use_block );
}

/**
 * Get the draft post type name.
 *
 * @since 5.5.0
 *
 * @return string The name of the draft post_type for PressForward.
 */
function pressforward_draft_post_type() {
	$post_type = get_option( PF_SLUG . '_draft_post_type', 'post' );

	/**
	 * Filters the 'draft' post type.
	 *
	 * @param string $post_type Defaults to 'post'.
	 */
	return apply_filters( 'pressforward_draft_post_type', $post_type );
}

/**
 * Gets the post type name for nominations.
 *
 * @since 5.6.0
 *
 * @return string The name of the nomination post_type for PressForward.
 */
function pressforward_nomination_post_type() {
	return pressforward( 'schema.nominations' )->post_type;
}

/**
 * Gets the formats used to build source statements.
 *
 * @since 5.7.0
 *
 * @return string[]
 */
function pressforward_source_statement_formats() {
	$defaults = [
		// translators: Do not translate items in curly brackets.
		'with_publication'    => __( 'Source: {{item}} via {{publication}}', 'pressforward' ),
		// translators: Do not translate items in curly brackets.
		'without_publication' => __( 'Source: {{item}}', 'pressforward' ),
	];

	$saved = get_option( 'pf_source_statement_formats', [] );

	return array_merge( $defaults, $saved );
}

/**
 * Get the feed item post type name.
 *
 * @since 1.7
 *
 * @return string The name of the feed item post_type for PressForward.
 */
function pf_feed_item_post_type() {
	return pressforward( 'schema.feed_item' )->post_type;
}

/**
 * Get the feed item tag taxonomy name.
 *
 * @since 1.7
 *
 * @return string The slug for the taxonomy used by feed items.
 */
function pf_feed_item_tag_taxonomy() {
	return pressforward( 'schema.feed_item' )->tag_taxonomy;
}

/**
 * Get a feed excerpt.
 *
 * @param string $text Text to excerpt.
 */
function pf_feed_excerpt( $text ) {
	$text = apply_filters( 'the_content', $text );
	$text = str_replace( '\]\]\>', ']]&gt;', $text );
	$text = preg_replace( '@<script[^>]*?>.*?</script>@si', '', $text );
	$text = wp_strip_all_tags( $text );
	$text = substr( $text, 0, 260 );

	$excerpt_length = 28;

	$words = explode( ' ', $text, $excerpt_length + 1 );
	array_pop( $words );
	array_push( $words, '...' );
	$text = implode( ' ', $words );

	$content_obj  = pressforward( 'library.htmlchecker' );
	$item_content = $content_obj->closetags( $text );

	return $text;
}

/**
 * Sanitize a string for use in URLs and filenames.
 *
 * @since 1.7
 * @link http://stackoverflow.com/questions/2668854/sanitizing-strings-to-make-them-url-and-filename-safe
 *
 * @param string $raw_string      The string to be sanitized.
 * @param bool   $force_lowercase True to force all characters to lowercase.
 * @param bool   $strict          True to scrub all non-alphanumeric characters.
 * @return string $clean The cleaned string
 */
function pf_sanitize( $raw_string, $force_lowercase = true, $strict = false ) {
	$strip = array(
		'~',
		'`',
		'!',
		'@',
		'#',
		'$',
		'%',
		'^',
		'&',
		'*',
		'(',
		')',
		'_',
		'=',
		'+',
		'[',
		'{',
		']',
		'}',
		'\\',
		'|',
		';',
		':',
		'"',
		"'",
		'&#8216;',
		'&#8217;',
		'&#8220;',
		'&#8221;',
		'&#8211;',
		'&#8212;',
		'',
		'',
		',',
		'<',
		'.',
		'>',
		'/',
		'?',
	);

	$clean = trim( str_replace( $strip, '', wp_strip_all_tags( $raw_string ) ) );
	$clean = preg_replace( '/\s+/', '-', $clean );

	if ( $strict ) {
		$clean = preg_replace( '/[^a-zA-Z0-9]/', '', $clean );
	}

	if ( $force_lowercase ) {
		$clean = function_exists( 'mb_strtolower' ) ? mb_strtolower( $clean, 'UTF-8' ) : strtolower( $clean );
	}

	return $clean;
}

/**
 * Create a slug from a string.
 *
 * @since 1.7
 * @uses pf_sanitize()
 *
 * @param string $raw_string      The string to convert.
 * @param bool   $force_lowercase True to force all characters to lowercase.
 * @param bool   $strict          True to scrub all non-alphanumeric characters.
 * @param bool   $spaces          False to strip spaces.
 * @return string $string_slug The sanitized slug.
 */
function pf_slugger( $raw_string, $force_lowercase = false, $strict = true, $spaces = false ) {
	if ( false === $spaces ) {
		$raw_string   = wp_strip_all_tags( $raw_string );
		$string_array = explode( ' ', $raw_string );
		$string_slug  = '';

		foreach ( $string_array as $string_part ) {
			$string_slug .= ucfirst( $string_part );
		}

		$string_slug = str_replace( '&amp;', '&', $string_slug );
		$string_slug = pf_sanitize( $string_slug, $force_lowercase, $strict );
	} else {
		$string_slug = str_replace( '&amp;', '&', $raw_string );
		$string_slug = pf_sanitize( $string_slug, $force_lowercase, $strict );
	}

	return $string_slug;
}

/**
 * Convert data to the standardized item format expected by PF.
 *
 * @since 1.7
 * @todo Take params as an array and use wp_parse_args().
 *
 * @param string $item_title      Item title.
 * @param string $source_title    Source title.
 * @param string $item_date       Item date.
 * @param string $item_author     Item author.
 * @param string $item_content    Item content.
 * @param string $item_link       Item link.
 * @param string $item_feat_img   Item featured image URL.
 * @param string $item_uid        Item UID.
 * @param string $item_wp_date    Item date for WP.
 * @param string $item_tags       Item tags.
 * @param string $added_date      Added date.
 * @param string $source_repeat   Source repeat.
 * @param string $postid          Post ID.
 * @param string $readable_status Readable status.
 * @param array  $obj             Data array.
 * @return array $item_array
 */
function pf_feed_object( $item_title = '', $source_title = '', $item_date = '', $item_author = '', $item_content = '', $item_link = '', $item_feat_img = '', $item_uid = '', $item_wp_date = '', $item_tags = '', $added_date = '', $source_repeat = '', $postid = '', $readable_status = '', $obj = array() ) {

	// Assemble all the needed variables into our fancy object!
	$item_array = array(
		'item_title'      => $item_title,
		'source_title'    => $source_title,
		'item_date'       => $item_date,
		'item_author'     => $item_author,
		'item_content'    => $item_content,
		'item_link'       => $item_link,
		'item_feat_img'   => $item_feat_img,
		'item_id'         => $item_uid,
		'item_wp_date'    => $item_wp_date,
		'item_tags'       => $item_tags,
		'item_added_date' => $added_date,
		'source_repeat'   => $source_repeat,
		'post_id'         => $postid,
		'readable_status' => $readable_status,
		'obj'             => $obj,
	);

	return $item_array;
}

/**
 * Creates ID for PF object.
 *
 * @param string $url   URL.
 * @param string $title Title. Not used.
 * @return string
 */
function pressforward_create_feed_item_id( $url, $title ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$url = sanitize_url( $url );
	$url = str_replace( 'http://', '', $url );
	$url = str_replace( 'https://', '', $url );

	$hash = md5( untrailingslashit( trim( $url ) ) );

	return $hash;
}

/**
 * Get all posts with 'item_id' set to a given item id.
 *
 * @since 1.7
 *
 * @param null|string $post_type The post type to limit results to.
 * @param string      $item_id   The origin item id.
 * @param bool        $ids_only  Set to true if you want only an array of IDs returned in the query.
 * @return object A standard WP_Query object.
 */
function pf_get_posts_by_id_for_check( $post_type = null, $item_id = null, $ids_only = false ) {
	global $wpdb;

	// If the item is less than 24 hours old on nomination, check the whole database.
	$r = array(
		'meta_key'   => pressforward( 'controller.metas' )->get_key( 'item_id' ),
		'meta_value' => $item_id,
		'post_type'  => array( pressforward_draft_post_type(), pf_feed_item_post_type() ),
	);

	if ( $ids_only ) {
		$r['fields']        = 'ids';
		$r['no_found_rows'] = true;
		$r['cache_results'] = false;

	}

	if ( null !== $post_type ) {
		$r['post_type'] = $post_type;
	}

	$posts_after = new WP_Query( $r );
	pf_log( ' Checking for posts with item ID ' . $item_id . ' returned query with ' . $posts_after->post_count . ' items.' );

	return $posts_after;
}

/**
 * Creates the hidden inputs used when nominating a post from All Content.
 *
 * @since 1.7
 *
 * @param array $item Item data.
 */
function pf_prep_item_for_submit( $item ) {
	$item['item_content'] = htmlspecialchars( $item['item_content'] );

	$itemid = $item['item_id'];

	foreach ( $item as $item_key => $item_part ) {
		switch ( $item_key ) {
			case 'item_content':
				$item_part = htmlspecialchars( $item_part );
				break;

			case 'nominators':
				$item_part = wp_list_pluck( $item_part, 'user_id' );
				break;
		}

		if ( is_array( $item_part ) ) {
			$the_item_part = implode( ',', $item_part );
		} else {
			$the_item_part = $item_part;
		}

		echo '<input type="hidden" name="' . esc_attr( $item_key ) . '" id="' . esc_attr( $item_key . '_' . $itemid ) . '" id="' . esc_attr( $item_key ) . '" value="' . esc_attr( $the_item_part ) . '" />';
	}
}

/**
 * Converts an https URL into http, to account for servers without SSL access.
 *
 * If a function is passed, pf_de_https will return the function result
 * instead of the string.
 *
 * @since 1.7
 *
 * @param string        $url      URL.
 * @param bool|callable $callback Function to call first to try and get the URL.
 * @return string|array $r Returns the string URL, converted, when no function is passed.
 *                         Otherwise returns the result of the function after being
 *                         checked for accessibility.
 */
function pf_de_https( $url, $callback = false ) {
	$url_orig = $url;

	$url = str_replace( '&amp;', '&', $url );

	if ( ! $callback ) {
		$r = set_url_scheme( $url, 'http' );
		return $r;
	} else {
		return pressforward( 'controller.http_tools' )->get_url_content( $url_orig, $callback );
	}
}

/**
 * Fetches a feed.
 *
 * Derived from WordPress's fetch feed function at: https://developer.wordpress.org/reference/functions/fetch_feed/.
 *
 * @param string $url URL.
 */
function pf_fetch_feed( $url ) {
	$the_feed = fetch_feed( $url );
	if ( is_wp_error( $the_feed ) ) {
		if ( ! class_exists( 'SimplePie', false ) ) {
			require_once ABSPATH . WPINC . '/class-simplepie.php';
		}

		require_once ABSPATH . WPINC . '/class-wp-feed-cache.php';
		require_once ABSPATH . WPINC . '/class-wp-feed-cache-transient.php';
		require_once ABSPATH . WPINC . '/class-wp-simplepie-file.php';
		require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';

		$feed = new SimplePie();

		$feed->set_sanitize_class( 'WP_SimplePie_Sanitize_KSES' );

		// We must manually overwrite $feed->sanitize because SimplePie's
		// constructor sets it before we have a chance to set the sanitization class.
		$feed->sanitize = new WP_SimplePie_Sanitize_KSES();

		$feed->set_cache_class( 'WP_Feed_Cache' );
		$feed->set_file_class( 'WP_SimplePie_File' );
		add_filter( 'pf_encoding_retrieval_control', '__return_false' );

		$feed_xml = pf_de_https( $url, 'wp_remote_get' );

		$feed->set_raw_data( $feed_xml['body'] );
		$feed->set_cache_duration( apply_filters( 'wp_feed_cache_transient_lifetime', 12 * HOUR_IN_SECONDS, $url ) );

		/**
		 * Fires just before processing the SimplePie feed object.
		 *
		 * @param object $feed SimplePie feed object (passed by reference).
		 * @param mixed  $url  URL of feed to retrieve. If an array of URLs, the feeds are merged.
		 */

		do_action_ref_array( 'wp_feed_options', array( &$feed, $url ) );

		$feed->init();
		$feed->handle_content_type();
		$feed->set_output_encoding( get_option( 'blog_charset' ) );

		if ( $feed->error() ) {
			return new WP_Error( 'simplepie-error', $feed->error() );
		}

		return $feed;
	} else {
		return $the_feed;
	}
}

/**
 * Converts and echos a list of terms to a set of slugs to be listed in the nomination CSS selector.
 *
 * @param array $the_array Data array.
 */
function pf_nom_class_tagger( $the_array = array() ) {

	foreach ( $the_array as $class_name ) {
		if ( empty( $class_name ) ) {
			continue;

		} elseif ( is_array( $class_name ) ) {
			foreach ( $class_name as $sub_class ) {
				echo ' ';
				echo esc_attr( pf_slugger( $sub_class, true, false, true ) );
			}
		} else {
			echo ' ';
			echo esc_attr( pf_slugger( $class_name, true, false, true ) );
		}
	}
}

/**
 * Converts and returns a list of terms as a set of slugs useful for nominations.
 *
 * @since 1.7
 *
 * @todo This function overwrites in the foreach() loop, which is likely a bug.
 *
 * @param array $the_array A set of terms.
 * @return string $tags A string containing a comma-seperated list of slugged tags.
 */
function get_pf_nom_class_tags( $the_array = array() ) {
	$tags = '';
	foreach ( $the_array as $class_name ) {
		if ( empty( $class_name ) ) {
			// Do nothing.
			$tags = '';
		} elseif ( is_array( $class_name ) ) {

			foreach ( $class_name as $sub_class ) {
				$tags = ' ';
				$tags = pf_slugger( $sub_class, true, false, true );
			}
		} else {
			$tags = ' ';
			$tags = pf_slugger( $class_name, true, false, true );
		}
	}

	return $tags;
}

/**
 * Build an excerpt for a nomination. For filtering.
 *
 * @param string $text Text to excerpt.
 * @return string
 */
function pf_noms_filter( $text ) {
	$text = get_the_content( '' );
	return pf_noms_excerpt( $text );
}

/**
 * Build an excerpt for nominations.
 *
 * @since 1.7
 *
 * @param string $text Text to excerpt.
 * @return string $r Returns the adjusted excerpt.
 */
function pf_noms_excerpt( $text ) {
	$text = apply_filters( 'the_content', $text );
	$text = str_replace( '\]\]\>', ']]&gt;', $text );
	$text = preg_replace( '@<script[^>]*?>.*?</script>@si', '', $text );

	$content_obj = pressforward( 'library.htmlchecker' );

	$text = $content_obj->closetags( $text );
	$text = strip_tags( $text, '<p>' );

	$excerpt_length = 310;
	$words          = explode( ' ', $text, $excerpt_length + 1 );
	if ( is_array( $words ) && ( count( $words ) > $excerpt_length ) ) {
		array_pop( $words );
		array_push( $words, '...' );
		$text = implode( ' ', $words );
	}

	return $text;
}

/**
 * Get an object with capabilities as keys pointing to roles that contain those capabilities.
 *
 * @since 3.x
 *
 * @param string $cap Optional. If given, the function will return a set of roles that have that capability.
 * @return array $role_reversal An array with capailities as keys pointing to what roles they match to.
 */
function pf_get_capabilities( $cap = '' ) {
	// Get the WP_Roles object.
	global $wp_roles;

	// Set up array for storage.
	$role_reversal = array();

	// Walk through the roles object by role and get capabilities.
	foreach ( $wp_roles->roles as $role_slug => $role_set ) {
		foreach ( $role_set['capabilities'] as $capability => $cap_bool ) {
			// Don't store a capability if it is false for the role (though none are).
			if ( $cap_bool ) {
				$role_reversal[ $capability ][] = $role_slug;
			}
		}
	}

	// Allow users to get specific capabilities.
	if ( ! $cap ) {
		return $role_reversal;
	} else {
		return $role_reversal[ $cap ];
	}
}

/**
 * Request a role string or object by asking for its capability.
 *
 * Function allows the user to find out a role by a capability that it holds.
 * The user may specify the higest role with that capability or the lowest.
 * The lowest is the default.
 *
 * @since 3.x
 *
 * @param string $cap    The slug for the capacity being checked against.
 * @param bool   $lowest Optional. If the function should return the lowest capable role. Default true.
 * @param bool   $obj    Optional. If the function should return a role object instead of a string. Default false.
 * @return string|object Returns either the string name of the role or the WP object created by get_role.
 */
function pf_get_role_by_capability( $cap, $lowest = true, $obj = false ) {
	// Get set of roles for capability.
	$roles = pf_get_capabilities( $cap );

	// We probobly want to get the lowest role with that capability.
	if ( $lowest ) {
		$roles = array_reverse( $roles );
	}

	$arrayvalues = array_values( $roles );
	$the_role    = array_shift( $arrayvalues );

	if ( ! $obj ) {
		return $the_role;
	} else {
		return get_role( $the_role );
	}
}

/**
 * Get the capability that uniquely matches a specific role.
 *
 * If we want to allow users to set access by role, we need to give users the names
 * of all roles. But WordPress takes capabilities. This function matches the role with
 * its first capability, so users can set by Role but WordPress takes capability.
 *
 * However, it will check against the system options and either attempt to return
 * this information based on WordPress defaults or by checking the current system.
 *
 * @since 3.x
 *
 * @param string $role_slug The slug for the role being checked against.
 *
 * @return string The slug for the defining capability of the given role.
 */
function pf_get_defining_capability_by_role( $role_slug ) {
	$pf_use_advanced_user_roles = get_option( 'pf_use_advanced_user_roles', 'no' );

	// For those who wish to ignore the super-cool auto-detection for fringe-y sites that
	// let their user capabilities go wild.
	if ( 'no' !== $pf_use_advanced_user_roles ) {
		$caps = pf_get_capabilities();
		foreach ( $caps as $slug => $cap ) {
			$low_role = pf_get_role_by_capability( $slug );

			// Return the first capability only applicable to that role.
			if ( $role_slug === $low_role ) {
				return $slug;
			}
		}
	}

	// Even if we use $pf_use_advanced_user_roles, if it doesn't find any actual lowest option (like it is the case with contributor currently), it should still go to the default ones below.
	$role_slug = strtolower( $role_slug );
	switch ( $role_slug ) {
		case 'administrator':
			return 'manage_options';

		case 'editor':
			return 'edit_others_posts';

		case 'author':
			return 'publish_posts';

		case 'contributor':
			return 'edit_posts';

		case 'subscriber':
			return 'read';
	}

	return '';
}

/**
 * Adds PF cap to WP role.
 *
 * @param string $cap       Capacity name.
 * @param string $role_slug Role slug.
 */
function pf_capability_mapper( $cap, $role_slug ) {
	$feed_caps      = pressforward( 'schema.feeds' )->map_feed_caps();
	$feed_item_caps = pressforward( 'schema.feed_item' )->map_feed_item_caps();

	if ( array_key_exists( $cap, $feed_caps ) ) {
		$role = get_role( $role_slug );
		$role->add_cap( $feed_caps[ $cap ] );
	}

	if ( array_key_exists( $cap, $feed_item_caps ) ) {
		$role = get_role( $role_slug );
		$role->add_cap( $feed_item_caps[ $cap ] );
	}
}

/**
 * Assigns PF's caps to WP's default roles.
 */
function assign_pf_to_standard_roles() {
	$roles = array(
		'administrator',
		'editor',
		'author',
		'contributor',
		'subscriber',
	);

	$caps = pf_get_capabilities();

	foreach ( $caps as $cap => $role ) {
		foreach ( $role as $a_role ) {
			pf_capability_mapper( $cap, $a_role );
		}
	}
}

/**
 * A function to filter authors and, if available, replace their display with the original item author.
 *
 * Based on http://seoserpent.com/wordpress/custom-author-byline.
 *
 * @since 3.x
 *
 * @param string $author The author string currently being displayed.
 * @return string Returns the author.
 */
function pf_replace_author_presentation( $author ) {
	global $post;

	if ( 'yes' !== get_option( 'pf_present_author_as_primary', 'yes' ) ) {
		return $author;
	}

	// If this doesn't look like a PF item, return the author.
	$item_link = get_post_meta( $post->ID, 'item_link', true );
	if ( ! $item_link ) {
		return $author;
	}

	$custom_author = pressforward( 'controller.metas' )->retrieve_meta( $post->ID, 'item_author' );
	if ( $custom_author ) {
		return $custom_author;
	}

	return $author;
}
add_filter( 'the_author', 'pf_replace_author_presentation' );

/**
 * A function to filter author urls and, if available, replace their display with the origonal item author urls.
 *
 * @since 3.x
 *
 * @param string $author_uri The author URI currently in use.
 *
 * @return string Returns the author URI.
 */
function pf_replace_author_uri_presentation( $author_uri ) {
	global $post, $authordata;

	if ( is_object( $post ) ) {
		$id = $post->ID;
	} elseif ( is_numeric( get_the_ID() ) ) {
		$id = get_the_ID();
	} else {
		return $author_uri;
	}

	if ( 'yes' !== get_option( 'pf_present_author_as_primary', 'yes' ) ) {
		return $author_uri;
	}

	// If this doesn't look like a PF item, return the WP URI.
	$item_link = get_post_meta( $id, 'item_link', true );
	if ( ! $item_link ) {
		return $author_uri;
	}

	$custom_author_uri = pressforward( 'controller.metas' )->retrieve_meta( $id, 'item_link' );
	if ( empty( $custom_author_uri ) ) {
		return $author_uri;
	} else {
		return $custom_author_uri;
	}
}
add_filter( 'author_link', 'pf_replace_author_uri_presentation' );

/**
 * Gets the canonical URL for the current PF item.
 *
 * @return bool|string
 */
function pf_canonical_url() {
	if ( ! is_single() ) {
		return false;
	}

	$link = pressforward( 'controller.metas' )->get_post_pf_meta( get_queried_object_id(), 'item_link', true );

	if ( empty( $link ) ) {
		return false;
	}

	return $link;
}

/**
 * Filter callback that calls pf_canonical_url().
 *
 * @param string $url URL.
 * @return string
 */
function pf_filter_canonical( $url ) {
	$link = pf_canonical_url();

	if ( $link ) {
		return $link;
	} else {
		return $url;
	}
}
add_filter( 'wpseo_canonical', 'pf_filter_canonical' );
add_filter( 'wpseo_opengraph_url', 'pf_filter_canonical' );
add_filter( 'wds_filter_canonical', 'pf_filter_canonical' );

/**
 * A function to set up the HEAD data to forward users to original articles.
 *
 * Echoes the approprite code to forward users.
 *
 * @since 3.x
 */
function pf_forward_unto_source() {
	if ( ! is_singular() ) {
		return false;
	}

	$link = pf_canonical_url();
	if ( ! $link ) {
		return false;
	}

	$obj     = get_queried_object();
	$post_id = $obj->ID;

	if ( ! has_action( 'wpseo_head' ) ) {
		echo '<link rel="canonical" href="' . esc_attr( $link ) . '" />';
		echo '<meta property="og:url" content="' . esc_attr( $link ) . '" />';
		add_filter( 'wds_process_canonical', '__return_false' );
	}

	if ( ! empty( $_GET['noforward'] ) ) {
		return;
	}

	$wait       = get_option( 'pf_link_to_source', 0 );
	$post_check = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'pf_forward_to_origin', true );

	if ( ( $wait > 0 ) && ( 'no-forward' !== $post_check ) ) {
		echo '<META HTTP-EQUIV="refresh" CONTENT="' . esc_attr( $wait ) . ';URL=' . esc_attr( $link ) . '">';
		?>
			<script type="text/javascript">console.log('You are being redirected to the source item.');</script>
		<?php

		echo '</head><body></body></html>';
		die();
	}
}
add_action( 'wp_head', 'pf_forward_unto_source', 1000 );

/**
 * Echoes the script link to use phonegap's debugging tools.
 *
 * @since 3.x
 */
function pf_debug_ipads() {
	// phpcs:ignore
	echo '<script src="http://debug.phonegap.com/target/target-script-min.js#pressforward"></script>';
}

/**
 * Checks whether an item has been pushed to draft.
 *
 * @param int $item_id PF item ID.
 * @return bool|int
 */
function pf_is_drafted( $item_id ) {
	$a = array(
		'no_found_rows' => true,
		'fields'        => 'ids',
		'meta_key'      => pressforward( 'controller.metas' )->get_key( 'item_id' ),
		'meta_value'    => $item_id,
		'post_type'     => pressforward_draft_post_type(),
	);
	$q = new WP_Query( $a );

	if ( 0 < $q->post_count ) {
		$draft = $q->posts;
		return $draft[0];
	} else {
		return false;
	}
}

/**
 * Get a list of all drafted items.
 *
 * @param string $post_type Post type. Defaults to 'pf_feed_item'.
 * @return array
 */
function pf_get_drafted_items( $post_type = 'pf_feed_item' ) {
	$draft_query_args = [
		'no_found_rows'          => true,
		'post_type'              => pressforward_draft_post_type(),
		'post_status'            => 'any',
		'meta_query'             => array(
			array(
				'key' => 'item_id',
			),
		),
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
	];

	if ( ! current_user_can( 'edit_others_posts' ) ) {
		$draft_query_args['author'] = get_current_user_id();
	}

	$drafts = new WP_Query( $draft_query_args );

	$item_hashes = array();
	foreach ( $drafts->posts as $p ) {
		$item_hashes[] = pressforward( 'controller.metas' )->get_post_pf_meta( $p->ID, 'item_id', true );
	}

	$drafted_query = new WP_Query(
		array(
			'no_found_rows' => true,
			'post_status'   => 'any',
			'post_type'     => $post_type,
			'fields'        => 'ids',
			'meta_query'    => array(
				array(
					'key'     => 'item_id',
					'value'   => $item_hashes,
					'compare' => 'IN',
				),
			),
		)
	);

	return array_map( 'intval', $drafted_query->posts );
}

/**
 * Not used.
 *
 * @param mixed $retval Return value.
 */
function filter_for_pf_archives_only( $retval ) {
	return $retval;
}

/**
 * Filter the Nominated query for the Drafted filter.
 *
 * @param WP_Query $query WP_Query object.
 */
function pf_filter_nominated_query_for_drafted( $query ) {
	global $pagenow;

	if ( 'admin.php' !== $pagenow
		|| empty( $_GET['page'] )
		|| 'pf-review' !== $_GET['page']
		|| empty( $_GET['pf-see'] )
		|| 'drafted-only' !== $_GET['pf-see']
	) {
		return;
	}

	if ( 'nomination' !== $query->get( 'post_type' ) ) {
		return;
	}

	remove_action( 'pre_get_posts', 'pf_filter_nominated_query_for_drafted' );
	$drafted = pf_get_drafted_items( 'nomination' );
	add_action( 'pre_get_posts', 'pf_filter_nominated_query_for_drafted' );

	if ( ! $drafted ) {
		$drafted = array( 0 );
	}
	$query->set( 'post__in', $drafted );
}
add_action( 'pre_get_posts', 'pf_filter_nominated_query_for_drafted' );

/**
 * Adds 'text/x-opml' mime type to WP.
 *
 * @param array $existing_mimes MIME type array.
 * @return array
 */
function pf_custom_upload_opml( $existing_mimes = array() ) {
	$existing_mimes['opml'] = 'text/x-opml';
	return $existing_mimes;
}
add_filter( 'upload_mimes', 'pf_custom_upload_opml' );

/**
 * Iterates cycle state.
 *
 * @param string $option_name  Option name.
 * @param string $option_limit 'day', 'week', 'month'.
 * @param bool   $do_echo      Whether to echo results. Default false.
 */
function pf_iterate_cycle_state( $option_name, $option_limit = '', $do_echo = false ) {
	$default = array(
		'day'        => 0,
		'week'       => 0,
		'month'      => 0,
		'next_day'   => strtotime( '+1 day' ),
		'next_week'  => strtotime( '+1 week' ),
		'next_month' => strtotime( '+1 month' ),
	);

	$retrieval_cycle = get_option( PF_SLUG . '_' . $option_name );
	if ( ! is_array( $retrieval_cycle ) ) {
		$retrieval_cycle = $default;
		update_option( PF_SLUG . '_' . $option_name, $retrieval_cycle );
	}

	if ( $do_echo ) {
		// translators: Day count.
		echo '<br />' . esc_html( sprintf( __( 'Day: %s', 'pressforward' ), $retrieval_cycle['day'] ) );
		// translators: Week count.
		echo '<br />' . esc_html( sprintf( __( 'Week: %s', 'pressforward' ), $retrieval_cycle['week'] ) );
		// translators: Month count.
		echo '<br />' . esc_html( sprintf( __( 'Month: %s', 'pressforward' ), $retrieval_cycle['month'] ) );
	} elseif ( ! $option_limit ) {
		return $retrieval_cycle;
	} elseif ( $option_limit ) {
		$states = array( 'day', 'week', 'month' );
		foreach ( $states as $state ) {
			if ( strtotime( 'now' ) >= $retrieval_cycle[ 'next_' . $state ] ) {
				$retrieval_cycle[ $state ]           = 1;
				$retrieval_cycle[ 'next_' . $state ] = strtotime( '+1 ' . $state );
			} else {
				$retrieval_cycle[ $state ] = $retrieval_cycle[ $state ] + 1;
			}
		}
		update_option( PF_SLUG . '_' . $option_name, $retrieval_cycle );
		return $retrieval_cycle;
	}
}

/**
 * Delete a PF item and its descendants.
 *
 * PF content (OPML feeds, RSS feeds, feed items) is often arranged hierarchically, and deleting one item should delete
 * all descendants as well. However, this process can take a long time. So this function assembles a descendant tree
 * for the item to be deleted, and places them in a queue to be deleted on subsequent pageloads.
 *
 * @since 3.6
 *
 * @param int|WP_Post $item        ID or WP_Post object.
 * @param bool        $fake_delete If true, does not delete, but moves to "removed" post_status.
 * @param bool        $msg         Whether to return a message.
 * @return bool|string False on failure, otherwise post ID deletion queue.
 */
function pf_delete_item_tree( $item, $fake_delete = false, $msg = false ) {
	$item = get_post( $item );
	pf_log( 'Starting item deletion' );
	pf_log( $item );

	if ( ! $item || ! ( $item instanceof WP_Post ) ) {
		if ( $msg ) {
			pf_log( 'Post Not Found.' );
			return __( 'Post Not Found.', 'pressforward' );
		} else {
			return false;
		}
	}

	$feed_item_post_type = pf_feed_item_post_type();
	$feed_post_type      = pressforward( 'schema.feeds' )->post_type;

	if ( ! in_array( $item->post_type, array( $feed_item_post_type, $feed_post_type, 'nomination' ), true ) ) {
		if ( $msg ) {
			pf_log( 'Post Type Not Matched' );
			return __( 'Post Type Not Matched', 'pressforward' );
		} else {
			return false;
		}
	}

	$queued = array_map( 'intval', get_option( 'pf_delete_queue', array() ) );
	if ( in_array( $item->ID, $queued, true ) ) {
		if ( $msg ) {
			pf_log( 'Post Type Already Queued' );
			return __( 'Post Type Already Queued', 'pressforward' );
		} else {
			return false;
		}
	}

	$queued[] = $item->ID;

	// Store immediately so that subsequent calls to this function are accurate.
	update_option( 'pf_delete_queue', $queued );

	switch ( $item->post_type ) {
		// Feed item: queue all attachments.
		case $feed_item_post_type:
		case 'nomination':
			$atts = get_posts(
				array(
					'post_parent' => $item->ID,
					'post_type'   => 'attachment',
					'post_status' => 'inherit',
					'fields'      => 'ids',
					'numberposts' => -1,
				)
			);

			// @todo This is surely a bug.
			foreach ( $atts as $att ) {
				if ( ! in_array( $att, $queued, true ) ) {
					$queued[] = $att;
				}
			}

			// Store the assembled queue.
			update_option( 'pf_delete_queue', $queued );

			if ( $fake_delete ) {
				$fake_status = 'removed_' . $item->post_type;

				$wp_args = array(
					'ID'           => $item->ID,
					'post_type'    => pf_feed_item_post_type(),
					'post_status'  => $fake_status,
					'post_title'   => $item->post_title,
					'post_content' => '',
					'guid'         => pressforward( 'controller.metas' )->get_post_pf_meta( $item->ID, 'item_link' ),
					'post_date'    => $item->post_date,
				);

				$id = wp_update_post( $wp_args );
				pressforward( 'controller.metas' )->update_pf_meta( $id, 'item_id', pressforward_create_feed_item_id( pressforward( 'controller.metas' )->get_post_pf_meta( $item->ID, 'item_link' ), $item->post_title ) );
			}

			break;

		// Feed: queue all children (OPML only) and all feed items.
		case $feed_post_type:
			// Child feeds (applies only to OPML subscriptions).
			$child_feeds = get_posts(
				array(
					'post_parent' => $item->ID,
					'post_type'   => $feed_post_type,
					'post_status' => 'any',
					'fields'      => 'ids',
					'numberposts' => -1,
				)
			);

			foreach ( $child_feeds as $child_feed ) {
				pf_delete_item_tree( $child_feed );
			}

			// Feed items.
			$feed_items = get_posts(
				array(
					'post_parent' => $item->ID,
					'post_type'   => $feed_item_post_type,
					'post_status' => 'any',
					'fields'      => 'ids',
					'numberposts' => -1,
				)
			);

			foreach ( $feed_items as $feed_item ) {
				pf_delete_item_tree( $feed_item );
			}

			break;
	}

	// Fetch an updated copy of the queue, which may have been updated recursively.
	$queued = get_option( 'pf_delete_queue', array() );

	return $queued;
}

/**
 * Filters post results instead of manipulating the query.
 *
 * @param array    $posts Post array.
 * @param WP_Query $query Query.
 * @return array
 */
function pf_exclude_queued_items_from_query_results( $posts, $query ) {
	$type = $query->get( 'post_type' );

	$post_types = array(
		pressforward( 'schema.feeds' )->post_type,
		pressforward( 'schema.feed_item' )->post_type,
		pressforward( 'schema.nominations' )->post_type,
	);

	if ( empty( $type ) || in_array( $type, $post_types, true ) ) {
		$queued = get_option( 'pf_delete_queue' );
		if ( ! $queued || ! is_array( $queued ) ) {
			return $posts;
		}

		$queued = array_map( 'intval', array_slice( $queued, 0, 100 ) );
		foreach ( $posts as $key => $post ) {
			if ( in_array( $post->ID, $queued, true ) ) {
				unset( $posts[ $key ] );
			}
		}
	}
	return $posts;
}
add_filter( 'posts_results', 'pf_exclude_queued_items_from_query_results', 999, 2 );

/**
 * Ensures that the 'pf_process_delete_queue' task is scheduled.
 *
 * @since 5.5.0
 */
function pressforward_schedule_process_delete_queue() {
	if ( ! wp_next_scheduled( 'pf_process_delete_queue' ) ) {
		wp_schedule_event( time(), 'hourly', 'pf_process_delete_queue' );
	}
}
add_action( 'init', 'pressforward_schedule_process_delete_queue' );

/**
 * Callback for 'pf_process_delete_queue' cron action.
 *
 * @since 3.6
 * @since 5.5.0 Instead of being triggered by a custom nonce and a self-request,
 *              deletion now has its own cron event.
 */
function pf_process_delete_queue() {
	$queued = get_option( 'pf_delete_queue', array() );

	pf_log( 'Delete queue ready' );

	/**
	 * Filters the max batch size for processing the delete queue.
	 *
	 * @since 5.5.0
	 *
	 * @param int $batch_size Default 25.
	 */
	$batch_size = apply_filters( 'pressforward_process_delete_queue_batch_size', 25 );

	for ( $i = 0; $i <= $batch_size; $i++ ) {
		$post_id = array_shift( $queued );
		if ( null !== $post_id ) {
			pf_log( 'Deleting ' . $post_id );
			wp_delete_post( $post_id, true );
		}
	}

	update_option( 'pf_delete_queue', $queued );

	// Clean up empty taxonomy terms, only when the queue is cleared.
	if ( ! $queued ) {
		delete_option( 'pf_delete_queue' );

		$terms = get_terms(
			[
				'taxonomy'   => pressforward( 'schema.feeds' )->tag_taxonomy,
				'hide_empty' => false,
			]
		);

		foreach ( $terms as $term ) {
			if ( 0 === $term->count ) {
				wp_delete_term( $term->term_id, pressforward( 'schema.feeds' )->tag_taxonomy );
			}
		}
	}
}
add_action( 'pf_process_delete_queue', 'pf_process_delete_queue' );

/**
 * Send takes an array dimension from a backtrace and puts it in log format.
 *
 * As part of the effort to create the most informative log we want to auto
 * include the information about what function is adding to the log.
 *
 * @since 3.4
 *
 * @param array $caller The sub-array from a step in a debug_backtrace.
 */
function pf_function_auto_logger( $caller ) {
	if ( isset( $caller['class'] ) ) {
		$func_statement = '[ ' . $caller['class'] . '->' . $caller['function'] . ' ] ';
	} else {
		$func_statement = '[ ' . $caller['function'] . ' ] ';
	}
	return $func_statement;
}

/**
 * Ensures that a message is loggable as a string.
 *
 * @param mixed $message Message content.
 * @return string.
 */
function assure_log_string( $message ) {
	// Make sure we've got a string to log.
	if ( is_wp_error( $message ) ) {
		$message = $message->get_error_message();
	}

	if ( is_array( $message ) || is_object( $message ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$message = print_r( $message, true );
	}

	if ( true === $message ) {
		$message = 'True';
	}

	if ( false === $message ) {
		$message = 'False';
	}

	return $message;
}

/**
 * Send status messages to a custom log.
 *
 * Importing data via cron (such as in PF's RSS Import module) can be difficult
 * to debug. This function is used to send status messages to a custom error
 * log.
 *
 * The error log is disabled by default. To enable, set PF_DEBUG to true in
 * wp-config.php. Set a custom error log location using PF_DEBUG_LOG.
 *
 * @todo Move log check into separate function for better unit tests.
 *
 * @since 1.7
 *
 * @param mixed $message    The message to log.
 * @param bool  $display    Whether to echo the message. Default fals.
 * @param bool  $reset      Whether to delete the contents of the log before
 *                           appending message. Default false.
 * @param bool  $do_return  Whether to return the message instead of logging it.
 *                           Default false.
 */
function pf_log( $message = '', $display = false, $reset = false, $do_return = false ) {
	static $debug;

	// phpcs:disable WordPress.PHP.DevelopmentFunctions

	$trace = debug_backtrace();

	if ( $do_return && ( 0 === $debug ) ) {
		return assure_log_string( $message );
	}

	if ( 0 === $debug ) {
		return;
	}

	if ( ! defined( 'PF_DEBUG' ) || ! PF_DEBUG ) {
		$debug = 0;
		return;
	}

	$display = apply_filters( 'force_pf_log_print', $display );
	if ( true === $display ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $message;
	}

	// Default log location is in the uploads directory.
	if ( ! defined( 'PF_DEBUG_LOG' ) ) {
		$upload_dir = wp_upload_dir();
		$log_path   = $upload_dir['basedir'] . '/pressforward.log';
	} else {
		$log_path = PF_DEBUG_LOG;
	}

	// @todo error_log() should create path.
	// phpcs:disable WordPress.WP.AlternativeFunctions
	if ( $reset ) {
		$fo = fopen( $log_path, 'w' );
		if ( ! $fo ) {
			print_r( 'Can\'t open log file.' );
		} else {
			fwrite( $fo, "Log file reset.\n\n\n" );
			fclose( $fo );
		}
	}

	if ( ! isset( $debug ) ) {
		if ( ! is_file( $log_path ) ) {
			touch( $log_path );
		}

		if ( ! is_writable( $log_path ) ) {
			$debug = true;
			return new WP_Error( "Can't write to the error log at $log_path." );
		} else {
			$debug = 1;
		}
	}
	// phpcs:enable WordPress.WP.AlternativeFunctions

	$message = assure_log_string( $message );

	foreach ( $trace as $key => $call ) {
		if ( in_array( $call['function'], array( 'call_user_func_array', 'do_action', 'apply_filter', 'call_user_func', 'do_action_ref_array', 'require_once' ), true ) ) {
			unset( $trace[ $key ] );
		}
	}

	reset( $trace );
	$first_call = next( $trace );
	if ( ! empty( $first_call ) ) {
		$func_statement = pf_function_auto_logger( $first_call );
	} else {
		$func_statement = '[ ? ] ';
	}

	$second_call = next( $trace );
	if ( ! empty( $second_call ) ) {
		if ( ( 'call_user_func_array' === $second_call['function'] ) ) {
			$third_call = next( $trace );
			if ( ! empty( $third_call ) ) {
				$upper_func_statement = pf_function_auto_logger( $third_call );
			} else {
				$upper_func_statement = '[ ? ] ';
			}
		} else {
			$upper_func_statement = pf_function_auto_logger( $second_call );
		}
		$func_statement = $upper_func_statement . $func_statement;
	}

	error_log( '[' . gmdate( 'd-M-Y H:i:s' ) . '] ' . $func_statement . $message . "\n", 3, $log_path );

	if ( $do_return ) {
		return $message;
	}

	// phpcs:enable WordPress.PHP.DevelopmentFunctions
}

/**
 * Logs and then returns a message.
 *
 * @param mixed $message Message content.
 * @param bool  $display Whether to echo the message when logging.
 * @param bool  $reset   Whether to reset the debug log when logging.
 * @return string
 */
function pf_message( $message = '', $display = false, $reset = false ) {
	$returned_message = pf_log( $message, false, $reset, true );
	return $returned_message;
}

/**
 * Migrates 5.3.0 source statements to be part of the post content.
 *
 * @since 5.4.0
 */
function pressforward_migrate_530_source_statements() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( get_option( 'pressforward_migrated_530_source_statements' ) ) {
		return;
	}

	global $wpdb;

	// phpcs:ignore WordPress.DB
	$post_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'pf_source_statement'" );

	foreach ( $post_ids as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			continue;
		}

		$source_statement = get_post_meta( $post_id, 'pf_source_statement', true );
		if ( ! $source_statement ) {
			continue;
		}

		// Sanity check.
		if ( false !== strpos( $post->post_content, $source_statement ) ) {
			continue;
		}

		$new_post_content = $post->post_content . "\n" . $source_statement;

		wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => $new_post_content,
			]
		);
	}

	update_option( 'pressforward_migrated_530_source_statements', 1 );
}
add_action( 'admin_init', 'pressforward_migrate_530_source_statements' );
