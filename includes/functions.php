<?php

/**
 * Miscellaneous utility functions
 *
 * @since 1.7
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
 *
 *     @var string $slug A non-capatalized safe string.
 *     @var string $class The name of the module's class.
 * 												Must match the folder name.
 * }
 * @return null
 */
function pressforward_register_module( $args ) {
	$defaults = array(
		'slug' => '',
		'class' => '',
	);
	$r = wp_parse_args( $args, $defaults );

	// We need the 'class' and 'slug' terms
	if ( empty( $r['class'] ) || empty( $r['slug'] ) ) {
		return;
	}

	// Ensure the class exists before attempting to initialize it
	// @todo Should probably have better error reporting
	if ( ! class_exists( $r['class'] ) ) {
		return;
	}

	add_filter( 'pressforward_register_modules', create_function( '$modules', '
		return array_merge( $modules, array( array(
			"slug"  => "' . $r['slug'] . '",
			"class" => "' . $r['class'] . '",
		) ) );
	' ) );
}

/**
 * Echoes the URL of the admin page
 *
 * @since 1.7
 */
function pf_admin_url() {
	echo pf_get_admin_url();
}
	/**
	 * Returns the URL of the admin page
	 *
	 * @return string
	 */
function pf_get_admin_url() {
	return add_query_arg( 'page', PF_SLUG . '-options', admin_url( 'admin.php' ) );
}


/**
 * Echoes the Nominate This bookmarklet link
 *
 * @since 1.7
 */
function pf_shortcut_link() {
	echo pf_get_shortcut_link();
}

function start_pf_nom_this(){
	global $pagenow;
	//var_dump('2test2<pre>',$pagenow); die();
	if( 'edit.php' == $pagenow && array_key_exists( 'pf-nominate-this', $_GET ) && 2 == $_GET['pf-nominate-this']) {
		//var_dump(dirname(__FILE__),$wp_query->get('pf-nominate-this'),file_exists(dirname(__FILE__).'/nomthis/nominate-this.php'),(dirname(__FILE__).'/nomthis/nominate-this.php')); die();
		//$someVar = $wp_query->get('some-var');
		include(dirname(__FILE__).'/nomthis/nominate-this.php');
		die();
	}

	return '';
}
	/**
	 * Retrieve the Nominate This bookmarklet link.
	 *
	 * Use this in 'a' element 'href' attribute.
	 *
	 * @since 1.7
	 * @see get_shortcut_link()
	 *
	 * @return string
	 */
function pf_get_shortcut_link() {
	$url = trailingslashit(get_bloginfo('wpurl')).'wp-admin/edit.php?pf-nominate-this=2';
	// In case of breaking changes, version this. #WP20071
	$link = "javascript:
				var d=document,
				w=window,
				e=w.getSelection,
				k=d.getSelection,
				x=d.selection,
				s=(e?e():(k)?k():(x?x.createRange().text:0)),
				f='" . $url . "',
				l=d.location,
				e=encodeURIComponent,
				u=f+'&u='+e(l.href)+'&t='+e(d.title)+'&s='+e(s)+'&v=4';
				a=function(){if(!w.open(u,'t','toolbar=0,resizable=1,scrollbars=1,status=1,width=720px,height=620px'))l.href=u;};
				if (/Firefox/.test(navigator.userAgent)) setTimeout(a, 0); else a();
				void(0)";

	$link = str_replace( array( "\r", "\n", "\t" ),  '', $link );

	return apply_filters( 'shortcut_link', $link );


}

/**
 * Retrieve the Nominate This bookmarklet link.
 *
 * Use this in 'a' element 'href' attribute.
 *
 * @since 1.7
 * @see get_shortcut_link()
 *
 * @return string
 */
function pf_nomthis_bookmarklet() {
	//get_site_url(null, 'wp-json/'
	$user = wp_get_current_user();
	$user_id = $user->ID;
	$link = "javascript:
				var d=document,
				w=window,
				e=w.getSelection,
				k=d.getSelection,
				x=d.selection,
				s=(e?e():(k)?k():(x?x.createRange().text:0)),
				l=d.location,
				e=encodeURIComponent,
				ku='".bin2hex(pressforward('controller.jwt')->get_a_user_public_key())."',
				ki='".get_user_meta($user_id, 'pf_jwt_private_key', true)."',
				p='" . rest_url().pressforward('api.nominatethis')->endpoint_for_nominate_this_script . "?k='+ku,
				pe=document.createElement('script'),
				a=function(){pe.src=p;document.getElementsByTagName('head')[0].appendChild(pe);};
				if (/Firefox/.test(navigator.userAgent)) setTimeout(a, 0); else a();
				void(0)";

	$link = str_replace( array( "\r", "\n", "\t" ),  '', $link );

	return apply_filters( 'pf_nomthis_bookmarklet', $link );

}

/**
 * Get the feed item post type name
 *
 * @since 1.7
 *
 * @return string The name of the feed item post_type for PressForward.
 */
function pf_feed_item_post_type() {
	return pressforward( 'schema.feed_item' )->post_type;
}

/**
 * Get the feed item tag taxonomy name
 *
 * @since 1.7
 *
 * @return string The slug for the taxonomy used by feed items.
 */
function pf_feed_item_tag_taxonomy() {
	return pressforward( 'schema.feed_item' )->tag_taxonomy;
}

/**
 * Get a feed excerpt
 */
function pf_feed_excerpt( $text ) {

	$text = apply_filters( 'the_content', $text );
	$text = str_replace( '\]\]\>', ']]&gt;', $text );
	$text = preg_replace( '@<script[^>]*?>.*?</script>@si', '', $text );
	$text = strip_tags( $text );
	$text = substr( $text, 0, 260 );
	$excerpt_length = 28;
	$words = explode( ' ', $text, $excerpt_length + 1 );
	array_pop( $words );
	array_push( $words, '...' );
	$text = implode( ' ', $words );

	$contentObj = pressforward( 'library.htmlchecker' );
	$item_content = $contentObj->closetags( $text );

	return $text;
}

/**
 * Sanitize a string for use in URLs and filenames
 *
 * @since 1.7
 * @link http://stackoverflow.com/questions/2668854/sanitizing-strings-to-make-them-url-and-filename-safe
 *
 * @param string $string The string to be sanitized
 * @param bool   $force_lowercase True to force all characters to lowercase
 * @param bool   $anal True to scrub all non-alphanumeric characters
 * @return string $clean The cleaned string
 */
function pf_sanitize( $string, $force_lowercase = true, $anal = false ) {
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
	if ( is_array( $string ) ) {
		$string = implode( ' ', $string );
	}
	$clean = trim( str_replace( $strip, '', strip_tags( $string ) ) );
	$clean = preg_replace( '/\s+/', '-', $clean );
	$clean = ($anal) ? preg_replace( '/[^a-zA-Z0-9]/', '', $clean ) : $clean ;

	return ($force_lowercase) ?
		(function_exists( 'mb_strtolower' )) ?
			mb_strtolower( $clean, 'UTF-8' ) :
			strtolower( $clean ) :
		$clean;
}

/**
 * Create a slug from a string
 *
 * @since 1.7
 * @uses pf_sanitize()
 *
 * @param string $string The string to convert
 * @param bool   $case True to force all characters to lowercase
 * @param bool   $string True to scrub all non-alphanumeric characters
 * @param bool   $spaces False to strip spaces
 * @return string $stringSlug The sanitized slug
 */
function pf_slugger( $string, $case = false, $strict = true, $spaces = false ) {

	if ( $spaces == false ) {
		$string = strip_tags( $string );
		$stringArray = explode( ' ', $string );
		$stringSlug = '';
		foreach ( $stringArray as $stringPart ) {
			$stringSlug .= ucfirst( $stringPart );
		}
		$stringSlug = str_replace( '&amp;','&', $stringSlug );
		// $charsToElim = array('?','/','\\');
		$stringSlug = pf_sanitize( $stringSlug, $case, $strict );
	} else {
		// $string = strip_tags($string);
		// $stringArray = explode(' ', $string);
		// $stringSlug = '';
		// foreach ($stringArray as $stringPart){
		// $stringSlug .= ucfirst($stringPart);
		// }
		$stringSlug = str_replace( '&amp;','&', $string );
		// $charsToElim = array('?','/','\\');
		$stringSlug = pf_sanitize( $stringSlug, $case, $strict );
	}

	return $stringSlug;

}

/**
 * Convert data to the standardized item format expected by PF
 *
 * @since 1.7
 * @todo Take params as an array and use wp_parse_args()
 *
 * @return array $itemArray
 */
function pf_feed_object( $itemTitle = '', $sourceTitle = '', $itemDate = '', $itemAuthor = '', $itemContent = '', $itemLink = '', $itemFeatImg = '', $itemUID = '', $itemWPDate = '', $itemTags = '', $addedDate = '', $sourceRepeat = '', $postid = '', $readable_status = '', $obj = array() ) {

	// Assemble all the needed variables into our fancy object!
	$itemArray = array(
		'item_title'      => $itemTitle,
		'source_title'    => $sourceTitle,
		'item_date'       => $itemDate,
		'item_author'     => $itemAuthor,
		'item_content'    => $itemContent,
		'item_link'       => $itemLink,
		'item_feat_img'   => $itemFeatImg,
		'item_id'         => $itemUID,
		'item_wp_date'    => $itemWPDate,
		'item_tags'       => $itemTags,
		'item_added_date' => $addedDate,
		'source_repeat'   => $sourceRepeat,
		'post_id'		  => $postid,
		'readable_status' => $readable_status,
		'obj'				=> $obj,
	);

	return $itemArray;
}

function create_feed_item_id( $url, $title ) {
	$url = str_replace('http://', '', $url);
	$url = str_replace('https://', '', $url);
	$hash = md5( untrailingslashit(trim($url)) );
	return $hash;
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
function pf_get_posts_by_id_for_check( $post_type = false, $item_id, $ids_only = false ) {
	global $wpdb;
	// If the item is less than 24 hours old on nomination, check the whole database.
	// $theDate = getdate();
	// $w = date('W');
	$r = array(
							'meta_key' => pressforward('controller.metas')->get_key('item_id'),
							'meta_value' => $item_id,
							'post_type'	=> array( 'post', pf_feed_item_post_type() ),
						);

	if ( $ids_only ) {
		$r['fields'] = 'ids';
		$r['no_found_rows'] = true;
		$r['cache_results'] = false;

	}

	if ( false != $post_type ) {
		$r['post_type'] = $post_type;
	}

	$postsAfter = new WP_Query( $r );
	pf_log( ' Checking for posts with item ID ' . $item_id . ' returned query with ' . $postsAfter->post_count . ' items.' );
	// pf_log($postsAfter);
	return $postsAfter;
}

/**
 * Create the hidden inputs used when nominating a post from All Content
 *
 * @since 1.7
 */
function pf_prep_item_for_submit( $item ) {
	$item['item_content'] = htmlspecialchars( $item['item_content'] );
	$itemid = $item['item_id'];

	foreach ( $item as $itemKey => $itemPart ) {

		if ( $itemKey == 'item_content' ) {
			$itemPart = htmlspecialchars( $itemPart );
		}

		if ( is_array( $itemPart ) ) {
			$itemPart = implode( ',',$itemPart );
		}

		echo '<input type="hidden" name="' . $itemKey . '" id="' . $itemKey . '_' . $itemid . '" id="' . $itemKey . '" value="' . $itemPart . '" />';

	}

}

function pf_get_user_level( $option, $default_level ) {

}

/**
 * Converts an https URL into http, to account for servers without SSL access.
 * If a function is passed, pf_de_https will return the function result
 * instead of the string.
 *
 * @since 1.7
 *
 * @param string       $url
 * @param string|array $function Function to call first to try and get the URL.
 * @return string|object $r Returns the string URL, converted, when no function is passed.
 *
 * otherwise returns the result of the function after being checked for accessability.
 */
function pf_de_https( $url, $function = false ) {
	$args = func_get_args();
	$url_orig = $url;
	$url = str_replace( '&amp;','&', $url );
	$url_first = $url;
	if ( ! $function ) {
		$r = set_url_scheme( $url, 'http' );
		return $r;
	} else {
		return pressforward( 'controller.http_tools' )->get_url_content( $url_orig, $function );
	}
}
/**
 * Derived from WordPress's fetch feed function at: https://developer.wordpress.org/reference/functions/fetch_feed/
 */
function pf_fetch_feed( $url ){
	$theFeed = fetch_feed( $url );
	if ( is_wp_error( $theFeed ) ) {

		if ( ! class_exists( 'SimplePie', false ) ) {
			require_once( ABSPATH . WPINC . '/class-simplepie.php' );
		}

		require_once( ABSPATH . WPINC . '/class-wp-feed-cache.php' );
		require_once( ABSPATH . WPINC . '/class-wp-feed-cache-transient.php' );
		require_once( ABSPATH . WPINC . '/class-wp-simplepie-file.php' );
		require_once( ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php' );

		$feed = new SimplePie();

		$feed->set_sanitize_class( 'WP_SimplePie_Sanitize_KSES' );
		// We must manually overwrite $feed->sanitize because SimplePie's
		// constructor sets it before we have a chance to set the sanitization class
		$feed->sanitize = new WP_SimplePie_Sanitize_KSES();

		$feed->set_cache_class( 'WP_Feed_Cache' );
		$feed->set_file_class( 'WP_SimplePie_File' );
		add_filter( 'pf_encoding_retrieval_control', '__return_false' );
		$feedXml = pf_de_https( $url, 'wp_remote_get' );
		//$feedXml = mb_convert_encoding($feedXml['body'], 'UTF-8');
		$feed->set_raw_data($feedXml['body']);
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

		if ( $feed->error() ){
			return new WP_Error( 'simplepie-error', $feed->error() );
		}
		return $feed;
	} else {
		return $theFeed;
	}
}

/**
 * Converts and echos a list of terms to a set of slugs to be listed in the nomination CSS selector
 */
function pf_nom_class_tagger( $array = array() ) {

	foreach ( $array as $class ) {
		if ( ($class == '') || (empty( $class )) || ( ! isset( $class )) ) {
			// Do nothing.
		} elseif ( is_array( $class ) ) {

			foreach ( $class as $subclass ) {
				echo ' ';
				echo pf_slugger( $class, true, false, true );
			}
		} else {
			echo ' ';
			echo pf_slugger( $class, true, false, true );
		}
	}

}

/**
 * Converts and returns a list of terms as a set of slugs useful for nominations
 *
 * @since 1.7
 *
 * @param array $array A set of terms.
 *
 * @return string|object $tags A string containing a comma-seperated list of slugged tags.
 */
function get_pf_nom_class_tags( $array = array() ) {

	foreach ( $array as $class ) {
		if ( ($class == '') || (empty( $class )) || ( ! isset( $class )) ) {
			// Do nothing.
			$tags = '';
		} elseif ( is_array( $class ) ) {

			foreach ( $class as $subclass ) {
				$tags = ' ';
				$tags = pf_slugger( $class, true, false, true );
			}
		} else {
			$tags = ' ';
			$tags = pf_slugger( $class, true, false, true );
		}
	}
	return $tags;

}

/**
 * Build an excerpt for a nomination. For filtering.
 *
 * @param string $text
 */
function pf_noms_filter( $text ) {
	global $post;
	$text = get_the_content( '' );
	$text = apply_filters( 'the_content', $text );
	$text = str_replace( '\]\]\>', ']]&gt;', $text );
	$text = preg_replace( '@<script[^>]*?>.*?</script>@si', '', $text );
	$contentObj = pressforward( 'library.htmlchecker' );
	$text = $contentObj->closetags( $text );
	$text = strip_tags( $text, '<p>' );

	$excerpt_length = 310;
	$words = explode( ' ', $text, $excerpt_length + 1 );
	if ( is_array( $words ) && ( count( $words ) > $excerpt_length ) ) {
		array_pop( $words );
		array_push( $words, '...' );
		$text = implode( ' ', $words );
	}

	return $text;
}


/**
 * Build an excerpt for nominations.
 *
 * @since 1.7
 *
 * @param string $text
 *
 * @return string $r Returns the adjusted excerpt.
 */
function pf_noms_excerpt( $text ) {

	$text = apply_filters( 'the_content', $text );
	$text = str_replace( '\]\]\>', ']]&gt;', $text );
	$text = preg_replace( '@<script[^>]*?>.*?</script>@si', '', $text );
	$contentObj = pressforward( 'library.htmlchecker' );
	$text = $contentObj->closetags( $text );
	$text = strip_tags( $text, '<p>' );

	$excerpt_length = 310;
	$words = explode( ' ', $text, $excerpt_length + 1 );
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
 *
 * @return array $role_reversal An array with capailities as keys pointing to what roles they match to.
 */

function pf_get_capabilities( $cap = false ) {
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
 * @param string $cap The slug for the capacity being checked against.
 * @param bool   $lowest Optional. If the function should return the lowest capable role. Default true.
 * @param bool   $obj Optional. If the function should return a role object instead of a string. Default false.
 *
 * @return string|object Returns either the string name of the role or the WP object created by get_role.
 */

function pf_get_role_by_capability( $cap, $lowest = true, $obj = false ) {
	// Get set of roles for capability.
	$roles = pf_get_capabilities( $cap );
	// We probobly want to get the lowest role with that capability
	if ( $lowest ) {
		$roles = array_reverse( $roles );
	}
	$arrayvalues = array_values( $roles );
	$the_role = array_shift( $arrayvalues );
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
 * of all roles. But Wordpress takes capabilities. This function matches the role with
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
	if ( 'no' != $pf_use_advanced_user_roles ) {
		$caps = pf_get_capabilities();
		foreach ( $caps as $slug => $cap ) {
			$low_role = pf_get_role_by_capability( $slug );
			// Return the first capability only applicable to that role.
			if ( $role_slug == ($low_role) ) {
				return $slug;
			}
		}
	}
    // Even if we use $pf_use_advanced_user_roles, if it doesn't find any actual lowest option (like it is the case with contributor currently), it should still go to the default ones below
    $role_slug = strtolower( $role_slug );
    switch ( $role_slug ) {
        case 'administrator':
            return 'manage_options';
            break;
        case 'editor':
            return 'edit_others_posts';
            break;
        case 'author':
            return 'publish_posts';
            break;
        case 'contributor':
            return 'edit_posts';
            break;
        case 'subscriber':
            return 'read';
            break;
    }
}

function pf_capability_mapper( $cap, $role_slug ) {
	$feed_caps = pressforward( 'schema.feeds' )->map_feed_caps();
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

function assign_pf_to_standard_roles() {
	$roles = array(
		'administrator',
		'editor',
		'author',
		'contributor',
		'subscriber',
	);
	$caps = pf_get_capabilities();
	// $feed_caps = pressforward('schema.feeds')->map_feed_caps();
	// $feed_item_caps = pressforward()->schema->map_feed_item_caps();
	foreach ( $caps as $cap => $role ) {
		foreach ( $role as $a_role ) {
			pf_capability_mapper( $cap, $a_role );
		}
	}
}

/**
 * A function to filter authors and, if available, replace their display with the origonal item author.
 *
 * Based on http://seoserpent.com/wordpress/custom-author-byline
 *
 * @since 3.x
 *
 * @param string $author The author string currently being displayed.
 *
 * @return string Returns the author.
 */
function pf_replace_author_presentation( $author ) {
	global $post;
	if ( 'yes' == get_option( 'pf_present_author_as_primary', 'yes' ) ) {
		$custom_author = pressforward( 'controller.metas' )->retrieve_meta( $post->ID, 'item_author' );
		if ( $custom_author ) {
			return $custom_author; }
		return $author;
	} else {
		return $author;
	}
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
	if ( 'yes' == get_option( 'pf_present_author_as_primary', 'yes' ) ) {
		$custom_author_uri = pressforward( 'controller.metas' )->retrieve_meta( $id, 'item_link' );
		if ( ! $custom_author_uri || 0 == $custom_author_uri || empty( $custom_author_uri ) ) {
			return $author_uri;
		} else {
			return $custom_author_uri;
		}
	} else {
		return $author_uri;
	}
}

add_filter( 'author_link', 'pf_replace_author_uri_presentation' );

function pf_canonical_url() {
	if ( is_single() ) {
		$obj = get_queried_object();
		$post_ID = $obj->ID;
		$link = pressforward( 'controller.metas' )->get_post_pf_meta( $post_ID, 'item_link', true );
		if (empty($link)){
			return false;
		}
		return $link;
	} else {
		return false;
	}
}

function pf_filter_canonical( $url ) {
	if ( $link = pf_canonical_url() ) {
		return $link;
	} else {
		return $url;
	}
}

add_filter( 'wpseo_canonical', 'pf_filter_canonical' );
add_filter( 'wpseo_opengraph_url', 'pf_filter_canonical' );
add_filter("wds_filter_canonical", 'pf_filter_canonical');

/**
 * A function to set up the HEAD data to forward users to origonal articles.
 *
 * Echos the approprite code to forward users.
 *
 * @since 3.x
 */
function pf_forward_unto_source() {
	if ( ! is_singular() ) {
		return false;
	}
	$link = pf_canonical_url();
	if ( ! empty( $link ) && false !== $link ) {

		$obj = get_queried_object();
		$post_id = $obj->ID;

		if ( has_action( 'wpseo_head' ) ) {

		} else {
			echo '<link rel="canonical" href="' . $link . '" />';
			echo '<meta property="og:url" content="' . $link . '" />';
			add_filter( 'wds_process_canonical', '__return_false');
		}
		$wait = get_option( 'pf_link_to_source', 0 );
		$post_check = pressforward( 'controller.metas' )->get_post_pf_meta( $post_id, 'pf_forward_to_origin', true );
		// var_dump($post_check); die();
		if ( isset( $_GET['noforward'] ) && true == $_GET['noforward'] ) {

		} else {
			if ( ( $wait > 0 ) && ( 'no-forward' !== $post_check ) ) {
				echo '<META HTTP-EQUIV="refresh" CONTENT="' . $wait . ';URL=' . $link . '">';
				?>
					<script type="text/javascript">console.log('You are being redirected to the source item.');</script>
				<?php

				echo '</head><body></body></html>';
				die();
			}
		}
	}
}

add_action( 'wp_head', 'pf_forward_unto_source', 1000 );

/**
 * Echos the script link to use phonegap's debugging tools.
 *
 * @since 3.x
 */
function pf_debug_ipads() {
	echo '<script src="http://debug.phonegap.com/target/target-script-min.js#pressforward"></script>';
}
// add_action ('wp_head', 'pf_debug_ipads');
// add_action ('admin_head', 'pf_debug_ipads');
function pf_is_drafted( $item_id ) {
	$a = array(
			'no_found_rows' => true,
			'fields' => 'ids',
			'meta_key' => pressforward('controller.metas')->get_key('item_id'),
			'meta_value' => $item_id,
			'post_type'	=> get_option( PF_SLUG . '_draft_post_type', 'post' ),
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
 * @return array
 */
function pf_get_drafted_items( $post_type = 'pf_feed_item' ) {
	$drafts = new WP_Query( array(
		'no_found_rows' => true,
		'post_type' => get_option( PF_SLUG . '_draft_post_type', 'post' ),
		'post_status' => 'any',
		'meta_query' => array(
		array(
				'key' => 'item_id',
			),
		),
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
	) );

	$item_hashes = array();
	foreach ( $drafts->posts as $p ) {
		$item_hashes[] = pressforward( 'controller.metas' )->get_post_pf_meta( $p->ID, 'item_id', true );
	}

	$drafted_query = new WP_Query( array(
		'no_found_rows' => true,
		'post_status' => 'any',
		'post_type' => $post_type,
		'fields' => 'ids',
		'meta_query' => array(
		array(
				'key' => 'item_id',
				'value' => $item_hashes,
				'compare' => 'IN',
			),
		),
	) );

	return array_map( 'intval', $drafted_query->posts );
}

function filter_for_pf_archives_only( $sql ) {
	global $wpdb;
	// if (isset($_GET['pf-see']) && ('archive-only' == $_GET['pf-see'])){
		$relate = pressforward( 'schema.relationships' );
		$rt = $relate->table_name;
		$user_id = get_current_user_id();
		$read_id = pf_get_relationship_type_id( 'archive' );

	/**		$sql .= " AND {$wpdb->posts}.ID
				IN (
					SELECT item_id
					FROM {$rt}
					WHERE {$rt}.user_id = {$user_id}
					AND {$rt}.relationship_type = {$read_id}
					AND {$rt}.value = 1
				) ";
	}
*/	// var_dump($sql);
	return $sql;

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

function prep_archives_query( $q ) {
		global $wpdb;

	if ( isset( $_GET['pc'] ) ) {
		$offset = $_GET['pc'] -1;
		$offset = $offset * 20;
	} else {
		$offset = 0;
	}
		// var_dump('see'); die();
		$relate = pressforward( 'schema.relationships' );
		$rt = $relate->table_name;

	if ( isset( $_GET['pf-see'] ) && ('archive-only' == $_GET['pf-see']) ) {
		$pagefull = 20;
		$user_id = get_current_user_id();
		$read_id = pf_get_relationship_type_id( 'archive' );
		//It is bad to use SQL_CALC_FOUND_ROWS, but we need it to replicate the same behaviour as non-archived items (including pagination).
		$q = $wpdb->prepare("
				SELECT SQL_CALC_FOUND_ROWS {$wpdb->posts}.*, {$wpdb->postmeta}.*
				FROM {$wpdb->posts}, {$wpdb->postmeta}
				WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
				AND {$wpdb->posts}.post_type = %s
				AND {$wpdb->postmeta}.meta_key = 'pf_archive'
				AND {$wpdb->postmeta}.meta_value > 0
				AND {$wpdb->posts}.ID
				GROUP BY {$wpdb->posts}.ID
				ORDER BY {$wpdb->postmeta}.meta_value DESC, {$wpdb->posts}.post_date DESC
				LIMIT {$pagefull} OFFSET {$offset}
			 ", 'nomination');
	} elseif ( isset( $_GET['pf-see'] ) && ('unread-only' == $_GET['pf-see']) ) {
		$pagefull = 20;
		$user_id = get_current_user_id();
		$read_id = pf_get_relationship_type_id( 'read' );
		// var_dump($user_id); die();
		$q = $wpdb->prepare("
				SELECT {$wpdb->posts}.*, {$wpdb->postmeta}.*
				FROM {$wpdb->posts}, {$wpdb->postmeta}
				WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
				AND {$wpdb->posts}.post_type = %s
				AND {$wpdb->posts}.post_status = 'draft'
				AND {$wpdb->postmeta}.meta_key = 'sortable_item_date'
				AND {$wpdb->postmeta}.meta_value > 0
				AND {$wpdb->posts}.ID
				NOT IN (
					SELECT item_id
					FROM {$rt}
					WHERE {$rt}.user_id = {$user_id}
					AND {$rt}.relationship_type = {$read_id}
					AND {$rt}.value = 1
				)
				GROUP BY {$wpdb->posts}.ID
				ORDER BY {$wpdb->postmeta}.meta_value DESC
				LIMIT {$pagefull} OFFSET {$offset}
			 ", 'nomination');
	} elseif ( isset( $_GET['action'] ) && (isset( $_POST['search-terms'] )) ) {
		$pagefull = 20;
		$user_id = get_current_user_id();
		$read_id = pf_get_relationship_type_id( 'archive' );
		$search = $_POST['search-terms'];
		$q = $wpdb->prepare("
				SELECT {$wpdb->posts}.*, {$wpdb->postmeta}.*
				FROM {$wpdb->posts}, {$wpdb->postmeta}
				WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
				AND {$wpdb->postmeta}.meta_key = 'sortable_item_date'
				AND {$wpdb->postmeta}.meta_value > 0
				AND {$wpdb->posts}.post_type = %s
				AND {$wpdb->posts}.post_status = 'draft'
				AND ((({$wpdb->posts}.post_title LIKE '%s') OR ({$wpdb->posts}.post_content LIKE '%s')))
				GROUP BY {$wpdb->posts}.ID
				ORDER BY {$wpdb->postmeta}.meta_value DESC
				LIMIT {$pagefull} OFFSET {$offset}
			 ", 'nomination', '%' . $search . '%', '%' . $search . '%');
	} elseif ( isset( $_GET['pf-see'] ) && ('starred-only' == $_GET['pf-see']) ) {
		$pagefull = 20;
		$user_id = get_current_user_id();
		$read_id = pf_get_relationship_type_id( 'star' );
		$q = $wpdb->prepare("
				SELECT DISTINCT wposts.*
				FROM {$wpdb->posts} wposts
				LEFT JOIN {$wpdb->postmeta} wpm1 ON (wposts.ID = wpm1.post_id
					AND wpm1.meta_key = 'sortable_item_date' AND wpm1.meta_value > 0 AND wposts.post_type = %s
				)
				LEFT JOIN {$wpdb->postmeta} wpm2 ON  (wposts.ID = wpm2.post_id
                       AND wpm2.meta_key = 'pf_item_post_id' AND wposts.post_type = %s )
				WHERE wposts.post_status = 'draft'
				AND wpm1.meta_value > 0
				AND wposts.ID
				IN (
					SELECT item_id
					FROM {$rt}
					WHERE {$rt}.user_id = {$user_id}
					AND {$rt}.relationship_type = {$read_id}
					AND {$rt}.value = 1
				)
				OR wpm2.meta_value
				IN (
					SELECT item_id
					FROM {$rt}
					WHERE {$rt}.user_id = {$user_id}
					AND {$rt}.relationship_type = {$read_id}
					AND {$rt}.value = 1
				)
				GROUP BY wpm2.post_id
				ORDER BY wpm1.meta_value DESC
				LIMIT {$pagefull} OFFSET {$offset}
			 ", 'nomination', 'nomination');
		 // var_dump($q);
	}// End if().
	// $archivalposts = $wpdb->get_results($dquerystr, OBJECT);
	// return $archivalposts;
	// var_dump('<pre>'); var_dump($q); die();
	return $q;
}

add_filter( 'upload_mimes', 'pf_custom_upload_opml' );

function pf_custom_upload_opml( $existing_mimes = array() ) {

	// add your ext => mime to the array
	$existing_mimes['opml'] = 'text/x-opml';

	// and return the new full result
	return $existing_mimes;

}

function pf_iterate_cycle_state( $option_name, $option_limit = false, $echo = false ) {
	$default = array(
		'day' 			=> 0,
		'week'			=> 0,
		'month' 		=> 0,
		'next_day'		=> strtotime( '+1 day' ),
		'next_week'		=> strtotime( '+1 week' ),
		'next_month'	=> strtotime( '+1 month' ),
	);
	$retrieval_cycle = get_option( PF_SLUG . '_' . $option_name,$default );
	if ( ! is_array( $retrieval_cycle ) ) {
		$retrieval_cycle = $default;
		update_option( PF_SLUG . '_' . $option_name, $retrieval_cycle );
	}
	if ( $echo ) {
		echo '<br />Day: ' . $retrieval_cycle['day'];
		echo '<br />Week: ' . $retrieval_cycle['week'];
		echo '<br />Month: ' . $retrieval_cycle['month'];
	} elseif ( ! $option_limit ) {
		return $retrieval_cycle;
	} elseif ( $option_limit ) {
		$states = array( 'day','week','month' );
		foreach ( $states as $state ) {
			if ( strtotime( 'now' ) >= $retrieval_cycle[ 'next_' . $state ] ) {
				$retrieval_cycle[ $state ] = 1;
				$retrieval_cycle[ 'next_' . $state ] = strtotime( '+1 ' . $state );
			} else {
				$retrieval_cycle[ $state ] = $retrieval_cycle[ $state ] + 1;
			}
		}
		update_option( PF_SLUG . '_' . $option_name, $retrieval_cycle );
		return $retrieval_cycle;
	} else {
		if ( strtotime( 'now' ) >= $retrieval_cycle[ 'next_' . $option_limit ] ) {
			$retrieval_cycle[ $option_limit ] = 1;
			$retrieval_cycle[ 'next_' . $option_limit ] = strtotime( '+1 ' . $option_limit );
		} else {
			$retrieval_cycle[ $option_limit ] = $retrieval_cycle[ $option_limit ] + 1;
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
 * @param int|WP_Post ID or WP_Post object.
 * @return bool|array False on failure, otherwise post ID deletion queue.
 */
function pf_delete_item_tree( $item, $fake_delete = false, $msg = false ) {
	$item = get_post( $item );
	pf_log( 'Starting item deletion' );
	pf_log( $item );

	if ( ! $item || ! ( $item instanceof WP_Post ) ) {
		if ( $msg ) {
			pf_log( 'Post Not Found.' );
			return 'Post Not Found.';
		} else {
			return false;
		}
	}

	$feed_item_post_type = pf_feed_item_post_type();
	$feed_post_type      = pressforward( 'schema.feeds' )->post_type;

	if ( ! in_array( $item->post_type, array( $feed_item_post_type, $feed_post_type, 'nomination' ) ) ) {
		if ( $msg ) {
			pf_log( 'Post Type Not Matched' );
			return 'Post Type Not Matched';
		} else {
			return false;
		}
	}

	$queued = get_option( 'pf_delete_queue', array() );
	if ( in_array( $item->ID, $queued ) ) {
		if ( $msg ) {
			pf_log( 'Post Type Already Queued' );
			return 'Post Type Already Queued';
		} else {
			return false;
		}
	}

	$queued[] = $item->ID;

	// Store immediately so that subsequent calls to this function are accurate.
	update_option( 'pf_delete_queue', $queued );

	switch ( $item->post_type ) {
		// Feed item: queue all attachments.
		case $feed_item_post_type :
		case 'nomination' :
			$atts = get_posts( array(
				'post_parent' => $item->ID,
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'fields'      => 'ids',
				'numberposts' => -1,
			) );

			foreach ( $atts as $att ) {
				if ( ! in_array( $att, $queued ) ) {
					$queued[] = $att;
				}
			}

			// Store the assembled queue.
			update_option( 'pf_delete_queue', $queued );

			if ( $fake_delete ) {
				$fake_status = 'removed_' . $item->post_type;

				$wp_args = array(
					'ID'		=> $item->ID,
					'post_type'    => pf_feed_item_post_type(),
					'post_status'  => $fake_status,
					'post_title'   => $item->post_title,
					'post_content' => '',
					'guid'         => pressforward( 'controller.metas' )->get_post_pf_meta( $item->ID, 'item_link' ),
					'post_date'    => $item->post_date,
				);

				$id = wp_update_post( $wp_args );
				pressforward( 'controller.metas' )->update_pf_meta( $id, 'item_id', create_feed_item_id( pressforward( 'controller.metas' )->get_post_pf_meta( $item->ID, 'item_link' ), $item->post_title ) );
			}

		break; // $feed_item_post_type

		// Feed: queue all children (OPML only) and all feed items.
		case $feed_post_type :
			// Child feeds (applies only to OPML subscriptions).
			$child_feeds = get_posts( array(
				'post_parent' => $item->ID,
				'post_type'   => $feed_post_type,
				'post_status' => 'any',
				'fields'      => 'ids',
				'numberposts' => -1,
			) );

			foreach ( $child_feeds as $child_feed ) {
				pf_delete_item_tree( $child_feed );
			}

			// Feed items.
			$feed_items = get_posts( array(
				'post_parent' => $item->ID,
				'post_type'   => $feed_item_post_type,
				'post_status' => 'any',
				'fields'      => 'ids',
				'numberposts' => -1,
			) );

			foreach ( $feed_items as $feed_item ) {
				pf_delete_item_tree( $feed_item );
			}

		break; // $feed_post_type
	}// End switch().

	// Fetch an updated copy of the queue, which may have been updated recursively.
	$queued = get_option( 'pf_delete_queue', array() );

	return $queued;
}

/**
 * Prevent items waiting to be queued from appearing in any query results.
 *
 * This is primarily meant to hide from the Trash screen, where the deletion of a queued item could result in
 * various weirdnesses.
 *
 * @since 3.6
 *
 * @param WP_Query $query
 */
function pf_exclude_queued_items_from_queries( $query ) {
	$queued = get_option( 'pf_delete_queue' );
	// var_dump($queued); die();
	if ( ! $queued || ! is_array( $queued ) ) {
		return $query;
	}

	$type = $query->get( 'post_type' );
	if ( ( empty( $type ) ) || ( 'post' != $type ) ) {
		if ( 300 <= count( $queued ) ) {
			$queued_chunk = array_chunk( $queued, 100 );
			$queued = $queued_chunk[0];
		}
		$post__not_in = $query->get( 'post__not_in' );
		$post__not_in = array_merge( $post__not_in, $queued );
		$query->set( 'post__not_in', $post__not_in );
	}

	// pf_log($query);// die();
}
// add_action( 'pre_get_posts', 'pf_exclude_queued_items_from_queries', 999 );
// Filter post results instead of manipulating the query.
function pf_exclude_queued_items_from_query_results( $posts, $query ) {
	// var_dump($posts[0]); die();
	$type = $query->get( 'post_type' );
	$post_types = array(
		pressforward('schema.feeds')->post_type,
		pressforward('schema.feed_item')->post_type,
		pressforward('schema.nominations')->post_type,
	);
	if ( ( empty( $type ) ) || ( in_array( $type, $post_types ) ) ) {
		$queued = get_option( 'pf_delete_queue' );
		// var_dump($queued); die();
		if ( ! $queued || ! is_array( $queued ) ) {
			return $posts;
		}
		$queued = array_slice( $queued, 0, 100 );
		foreach ( $posts as $key => $post ) {
			if ( in_array( $post->ID, $queued ) ) {
				unset( $posts[ $key ] );
			}
		}
	}
	return $posts;
}

add_filter( 'posts_results', 'pf_exclude_queued_items_from_query_results', 999, 2 );

/**
 * Detect and process a delete queue request.
 *
 * Request URLs are of the form example.com?pf_process_delete_queue=123, where '123' is a single-use nonce stored in
 * the 'pf_delete_queue_nonce' option.
 *
 * @since 3.6
 */
function pf_process_delete_queue() {
	// pf_log('pf_process_delete_queue');
	if ( ! isset( $_GET['pf_process_delete_queue'] ) ) {
		// pf_log( 'Not set to go on ' );
		// pf_log($_GET);
		return;
	}

	$nonce = $_GET['pf_process_delete_queue'];
	$saved_nonce = get_option( 'pf_delete_queue_nonce' );
	if ( $saved_nonce !== $nonce ) {
		pf_log( 'nonce indicates not ready.' );
		return;
	}

	$queued = get_option( 'pf_delete_queue', array() );
	pf_log( ' Delete queue ready' );
	for ( $i = 0; $i <= 1; $i++ ) {
		$post_id = array_shift( $queued );
		if ( null !== $post_id ) {
			pf_log( 'Deleting ' . $post_id );
			wp_delete_post( $post_id, true );
		}
	}
	update_option( 'pf_delete_queue', $queued );
	delete_option( 'pf_delete_queue_nonce' );

	if ( ! $queued ) {
		delete_option( 'pf_delete_queue' );

		// Clean up empty taxonomy terms.
		$terms = get_terms( pressforward( 'schema.feeds' )->tag_taxonomy, array(
			'hide_empty' => false,
		) );

		foreach ( $terms as $term ) {
			if ( 0 == $term->count ) {
				wp_delete_term( $term->term_id, pressforward( 'schema.feeds' )->tag_taxonomy );
			}
		}
	} else {
		pf_launch_batch_delete();
	}
}
add_action( 'wp_loaded', 'pf_process_delete_queue' );

/**
 * Launch the processing of the delete queue.
 *
 * @since 3.6
 */
function pf_launch_batch_delete() {
	// Nothing to do.
	$queued = get_option( 'pf_delete_queue' );
	if ( ! $queued ) {
		delete_option( 'pf_delete_queue_nonce' );
		return;
	}

	// If a nonce is saved, then a deletion is pending, and we should do nothing.
	$saved_nonce = get_option( 'pf_delete_queue_nonce' );
	if ( $saved_nonce ) {
		return;
	}

	$nonce = rand( 10000000, 99999999 );
	add_option( 'pf_delete_queue_nonce', $nonce );
	wp_remote_get( add_query_arg( 'pf_process_delete_queue', $nonce, home_url() ) );
}

/**
 * Send takes an array dimension from a backtrace and puts it in log format
 *
 * As part of the effort to create the most informative log we want to auto
 * include the information about what function is adding to the log.
 *
 * @since 3.4
 *
 * @param array $caller The sub-array from a step in a debug_backtrace
 */

function pf_function_auto_logger( $caller ) {
	if ( isset( $caller['class'] ) ) {
		$func_statement = '[ ' . $caller['class'] . '->' . $caller['function'] . ' ] ';
	} else {
		$func_statement = '[ ' . $caller['function'] . ' ] ';
	}
	return $func_statement;
}

function assure_log_string( $message ) {
	if ( is_array( $message ) || is_object( $message ) ) {
		$message = print_r( $message, true );
	}

	// Make sure we've got a string to log
	if ( is_wp_error( $message ) ) {
		$message = $message->get_error_message();
	}

	if ( $message === true ) {
		$message = 'True';
	}

	if ( $message === false ) {
		$message = 'False';
	}

	return $message;
}


/**
 * Send status messages to a custom log
 *
 * Importing data via cron (such as in PF's RSS Import module) can be difficult
 * to debug. This function is used to send status messages to a custom error
 * log.
 *
 * The error log is disabled by default. To enable, set PF_DEBUG to true in
 * wp-config.php. Set a custom error log location using PF_DEBUG_LOG.
 *
 * @todo Move log check into separate function for better unit tests
 *
 * @since 1.7
 *
 * @param string $message The message to log
 */
function pf_log( $message = '', $display = false, $reset = false, $return = false ) {
	static $debug;

	if ( $return && ( 0 === $debug ) ) {
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
	if ( ( ( true === $display ) ) ) {
		print_r( $message );
	}

	// Default log location is in the uploads directory
	if ( ! defined( 'PF_DEBUG_LOG' ) ) {
		$upload_dir = wp_upload_dir();
		$log_path = $upload_dir['basedir'] . '/pressforward.log';
	} else {
		$log_path = PF_DEBUG_LOG;
	}

	if ( $reset ) {
		$fo = fopen( $log_path, 'w' ) or print_r( 'Can\'t open log file.' );
		fwrite( $fo, "Log file reset.\n\n\n" );
		fclose( $fo );

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

	$message = assure_log_string( $message );

	$trace = debug_backtrace();
	foreach ( $trace as $key => $call ) {

		if ( in_array( $call['function'], array( 'call_user_func_array', 'do_action', 'apply_filter', 'call_user_func', 'do_action_ref_array', 'require_once' ) ) ) {
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
		if ( ('call_user_func_array' == $second_call['function']) ) {
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

	if ( $return ) {
		return $message;
	}
}

function pf_message( $message = '', $display = false, $reset = false ) {
	$returned_message = pf_log( $message, false, $reset, true );
	return $returned_message;
}
