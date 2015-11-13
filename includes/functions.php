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
 * of a variaty of PressForward function and to makeit appear in
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
			"slug"  => "' . $r['slug']  . '",
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

		// In case of breaking changes, version this. #WP20071
		$link = "javascript:
				var d=document,
				w=window,
				e=w.getSelection,
				k=d.getSelection,
				x=d.selection,
				s=(e?e():(k)?k():(x?x.createRange().text:0)),
				f='" . PF_URL . "includes/nomthis/nominate-this.php" . "',
				l=d.location,
				e=encodeURIComponent,
				u=f+'?u='+e(l.href)+'&t='+e(d.title)+'&s='+e(s)+'&v=4';
				a=function(){if(!w.open(u,'t','toolbar=0,resizable=1,scrollbars=1,status=1,width=720,height=620'))l.href=u;};
				if (/Firefox/.test(navigator.userAgent)) setTimeout(a, 0); else a();
				void(0)";

		$link = str_replace(array("\r", "\n", "\t"),  '', $link);

		return apply_filters('shortcut_link', $link);

	}

/**
 * Get the feed item post type name
 *
 * @since 1.7
 *
 * @return string The name of the feed item post_type for PressForward.
 */
function pf_feed_item_post_type() {
	return pressforward()->get_feed_item_post_type();
}

/**
 * Get the feed item tag taxonomy name
 *
 * @since 1.7
 *
 * @return string The slug for the taxonomy used by feed items.
 */
function pf_feed_item_tag_taxonomy() {
	return pressforward()->get_feed_item_tag_taxonomy();
}

/**
 * Get a feed excerpt
 */
function pf_feed_excerpt( $text ) {

	$text = apply_filters('the_content', $text);
	$text = str_replace('\]\]\>', ']]&gt;', $text);
	$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
	$text = strip_tags($text);
	$text = substr($text, 0, 260);
	$excerpt_length = 28;
	$words = explode(' ', $text, $excerpt_length + 1);
	array_pop($words);
	array_push($words, '...');
	$text = implode(' ', $words);

	$contentObj = new pf_htmlchecker($text);
	$item_content = $contentObj->closetags($text);

	return $text;
}

/**
 * Sanitize a string for use in URLs and filenames
 *
 * @since 1.7
 * @link http://stackoverflow.com/questions/2668854/sanitizing-strings-to-make-them-url-and-filename-safe
 *
 * @param string $string The string to be sanitized
 * @param bool $force_lowercase True to force all characters to lowercase
 * @param bool $anal True to scrub all non-alphanumeric characters
 * @return string $clean The cleaned string
 */
function pf_sanitize($string, $force_lowercase = true, $anal = false) {
	$strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
				   "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
				   "", "", ",", "<", ".", ">", "/", "?");
	if (is_array($string)){
		$string = implode(' ', $string);
	}
	$clean = trim(str_replace($strip, "", strip_tags($string)));
	$clean = preg_replace('/\s+/', "-", $clean);
	$clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;

	return ($force_lowercase) ?
		(function_exists('mb_strtolower')) ?
			mb_strtolower($clean, 'UTF-8') :
			strtolower($clean) :
		$clean;
}

/**
 * Create a slug from a string
 *
 * @since 1.7
 * @uses pf_sanitize()
 *
 * @param string $string The string to convert
 * @param bool $case True to force all characters to lowercase
 * @param bool $string True to scrub all non-alphanumeric characters
 * @param bool $spaces False to strip spaces
 * @return string $stringSlug The sanitized slug
 */
function pf_slugger($string, $case = false, $strict = true, $spaces = false){

	if ($spaces == false){
		$string = strip_tags($string);
		$stringArray = explode(' ', $string);
		$stringSlug = '';
		foreach ($stringArray as $stringPart){
			$stringSlug .= ucfirst($stringPart);
		}
		$stringSlug = str_replace('&amp;','&', $stringSlug);
		//$charsToElim = array('?','/','\\');
		$stringSlug = pf_sanitize($stringSlug, $case, $strict);
	} else {
		//$string = strip_tags($string);
		//$stringArray = explode(' ', $string);
		//$stringSlug = '';
		//foreach ($stringArray as $stringPart){
		//	$stringSlug .= ucfirst($stringPart);
		//}
		$stringSlug = str_replace('&amp;','&', $string);
		//$charsToElim = array('?','/','\\');
		$stringSlug = pf_sanitize($stringSlug, $case, $strict);
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
function pf_feed_object( $itemTitle='', $sourceTitle='', $itemDate='', $itemAuthor='', $itemContent='', $itemLink='', $itemFeatImg='', $itemUID='', $itemWPDate='', $itemTags='', $addedDate='', $sourceRepeat='', $postid='', $readable_status = '', $obj = array() ) {

	# Assemble all the needed variables into our fancy object!
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
		'obj'				=> $obj
	);

	return $itemArray;
}

function create_feed_item_id($url, $title){
	$hash = md5($url . $title);
	return $hash;
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

/**
 * Create the hidden inputs used when nominating a post from All Content
 *
 * @since 1.7
 */
function pf_prep_item_for_submit($item) {
	$item['item_content'] = htmlspecialchars($item['item_content']);
	$itemid = $item['item_id'];

	foreach ($item as $itemKey => $itemPart) {

		if ($itemKey == 'item_content'){
			$itemPart = htmlspecialchars($itemPart);
		}

		if (is_array($itemPart)){
			$itemPart = implode(",",$itemPart);
		}

		echo '<input type="hidden" name="' . $itemKey . '" id="' . $itemKey . '_' . $itemid . '" id="' . $itemKey . '" value="' . $itemPart . '" />';

	}

}

function pf_get_user_level($option, $default_level) {

}

/**
 * Converts an https URL into http, to account for servers without SSL access.
 * If a function is passed, pf_de_https will return the function result
 * instead of the string.
 *
 * @since 1.7
 *
 * @param string $url
 * @param string|array $function Function to call first to try and get the URL.
 * @return string|object $r Returns the string URL, converted, when no function is passed.
 *
 * otherwise returns the result of the function after being checked for accessability.
 */
function pf_de_https($url, $function = false) {
	$args = func_get_args();
	$url = str_replace('&amp;','&', $url);
	$url_first = $url;
	if (!$function){
		$r = set_url_scheme($url, 'http');
	} else {
		$args[0] = $url;
		#unset($args[1]);
		#var_dump($args);
		$r = call_user_func_array( $function, $args );
		# "A variable is considered empty if it does not exist or if its value equals FALSE"
		if ( is_wp_error( $r ) || empty($r) ) {
		    $non_ssl_url = pf_de_https( $url );
		    if ( $non_ssl_url != $url ) {
						$args[0] = $non_ssl_url;
		        $r = call_user_func_array( $function, $args );
		    }

		    if ( !$r || is_wp_error( $r ) ) {
		        # Last Chance!
						if ('file_get_contents' != $function){
							$r = file_get_contents($url_first);
							#var_dump($r); die();
						} else {
								// bail
								return false;
						}
		    }
		}
	}
	return $r;
}

/**
 * Converts and echos a list of terms to a set of slugs to be listed in the nomination CSS selector
 */
function pf_nom_class_tagger($array = array()){

	foreach ($array as $class){
		if (($class == '') || (empty($class)) || (!isset($class))){
			//Do nothing.
		}
		elseif (is_array($class)){

			foreach ($class as $subclass){
				echo ' ';
				echo pf_slugger($class, true, false, true);
			}

		} else {
			echo ' ';
			echo pf_slugger($class, true, false, true);
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
*
*/
function get_pf_nom_class_tags($array = array()){

	foreach ($array as $class){
		if (($class == '') || (empty($class)) || (!isset($class))){
			//Do nothing.
			$tags = '';
		}
		elseif (is_array($class)){

			foreach ($class as $subclass){
				$tags = ' ';
				$tags = pf_slugger($class, true, false, true);
			}

		} else {
			$tags = ' ';
			$tags = pf_slugger($class, true, false, true);
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
	$text = get_the_content('');
	$text = apply_filters('the_content', $text);
	$text = str_replace('\]\]\>', ']]&gt;', $text);
	$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
	$contentObj = new pf_htmlchecker($text);
	$text = $contentObj->closetags($text);
	$text = strip_tags($text, '<p>');

	$excerpt_length = 310;
	$words = explode(' ', $text, $excerpt_length + 1);
	if (count($words)> $excerpt_length) {
		array_pop($words);
		array_push($words, '...');
		$text = implode(' ', $words);
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

	$text = apply_filters('the_content', $text);
	$text = str_replace('\]\]\>', ']]&gt;', $text);
	$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
	$contentObj = new pf_htmlchecker($text);
	$text = $contentObj->closetags($text);
	$text = strip_tags($text, '<p>');

	$excerpt_length = 310;
	$words = explode(' ', $text, $excerpt_length + 1);
	if (count($words) > $excerpt_length) {
		array_pop($words);
		array_push($words, '...');
		$text = implode(' ', $words);
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

function pf_get_capabilities($cap = false){
  # Get the WP_Roles object.
  global $wp_roles;
  # Set up array for storage.
  $role_reversal = array();
  # Walk through the roles object by role and get capabilities.
  foreach ($wp_roles->roles as $role_slug=>$role_set){

    foreach ($role_set['capabilities'] as $capability=>$cap_bool){
    	# Don't store a capability if it is false for the role (though none are).
		if ($cap_bool){
  			$role_reversal[$capability][] = $role_slug;
  		}
  	}
  }
  # Allow users to get specific capabilities.
  if (!$cap){
    return $role_reversal;
  } else {
    return $role_reversal[$cap];
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
 * @param bool $lowest Optional. If the function should return the lowest capable role. Default true.
 * @param bool $obj Optional. If the function should return a role object instead of a string. Default false.
 *
 * @return string|object Returns either the string name of the role or the WP object created by get_role.
 */

function pf_get_role_by_capability($cap, $lowest = true, $obj = false){
	# Get set of roles for capability.
	$roles = pf_get_capabilities($cap);
	# We probobly want to get the lowest role with that capability
	if ($lowest){
		$roles = array_reverse($roles);
	}
  $arrayvalues = array_values($roles);
  $the_role = array_shift($arrayvalues);
  if (!$obj){
	return $the_role;
  } else {
    	return get_role($the_role);
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
function pf_get_defining_capability_by_role($role_slug){
	$pf_use_advanced_user_roles = get_option('pf_use_advanced_user_roles', 'no');
    # For those who wish to ignore the super-cool auto-detection for fringe-y sites that
    # let their user capabilities go wild.
    if ('no' == $pf_use_advanced_user_roles){
        $role_slug = strtolower($role_slug);
        switch ($role_slug){
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
    } else {
        $caps = pf_get_capabilities();
        foreach ($caps as $slug=>$cap){
            $low_role = pf_get_role_by_capability($slug);
            # Return the first capability only applicable to that role.
            if ($role_slug == ($low_role))
                return $slug;
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
	if ('yes' == get_option('pf_present_author_as_primary', 'yes')){
		$custom_author = pf_retrieve_meta($post->ID, 'item_author');
		if($custom_author)
			return $custom_author;
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
	if(is_object($post)){
		$id = $post->ID;
	} elseif (is_numeric(get_the_ID())){
		$id = get_the_ID();
	} else {
		return $author_uri;
	}
	if ('yes' == get_option('pf_present_author_as_primary', 'yes')) {
		$custom_author_uri = pf_retrieve_meta($id, 'item_link');
		if(!$custom_author_uri || 0 == $custom_author_uri || empty($custom_author_uri)){
			return $author_uri;
		} else {
			return $custom_author_uri;
		}
	} else {
		return $author_uri;
	}
}

add_filter( 'author_link', 'pf_replace_author_uri_presentation' );

function pf_canonical_url(){
	if(is_single()){
		$obj = get_queried_object();
		$post_ID = $obj->ID;
		$link = get_post_meta($post_ID, 'item_link', TRUE);
		return $link;
	} else {
		return false;
	}
}

function pf_filter_canonical($url){
	if ($link = pf_canonical_url()){
		return $link;
	} else {
		return $url;
	}
}

add_filter('wpseo_canonical', 'pf_filter_canonical');
add_filter('wpseo_opengraph_url', 'pf_filter_canonical');

/**
 * A function to set up the HEAD data to forward users to origonal articles.
 *
 * Echos the approprite code to forward users.
 *
 * @since 3.x
 */
function pf_forward_unto_source(){
	$link = pf_canonical_url();
	if(!empty($link)){

		$obj = get_queried_object();
		$post_id = $obj->ID;

		if (has_action('wpseo_head')){

		} else {
			echo '<link rel="canonical" href="'.$link.'" />';
			echo '<meta property="og:url" content="'.$link.'" />';
		}
		$wait = get_option('pf_link_to_source', 0);
		$post_check = pf_get_post_meta($post_id, 'pf_forward_to_origin', true);
		//var_dump($post_check); die();
		if ( ( $wait > 0 ) && ( "no-forward" !== $post_check ) ){
			echo '<META HTTP-EQUIV="refresh" CONTENT="'.$wait.';URL='.$link.'">';
		}
	}
}

add_action ('wp_head', 'pf_forward_unto_source');

/**
* Echos the script link to use phonegap's debugging tools.
*
* @since 3.x
*
*/
function pf_debug_ipads(){
	echo '<script src="http://debug.phonegap.com/target/target-script-min.js#pressforward"></script>';
}
#add_action ('wp_head', 'pf_debug_ipads');
#add_action ('admin_head', 'pf_debug_ipads');

/**
 * Take an array of objects describing post_metas and set them to the id of a post.
 *
 * @since 3.x
 *
 * @param int $id A post object ID number.
 * @param array $args {
 * 			An array of objects containing post_meta data.
 *
 * 			@var array {
 *						@var string $name The post_meta slug.
 * 						@var string $value The post_meta's value.
 *			}
 * }
 *
 */
function pf_meta_establish_post($id, $args){
	foreach ($args as $arg){
		pf_add_meta($id, $arg['name'], $arg['value'], true);
	}
}

/**
 * Takes a post_meta name and a post_meta value and turns it into an for use.
 *
 * @return array An array useful in thevarious parts of the post_meta setting process.
 *
 */
function pf_meta_for_entry($key, $value){
	return array(
		'name'	=>	$key,
		'value'	=>	$value
	);
}

/**
 * With two post IDs copy all the standard PressForward meta from one post to another.
 *
 * @param int $idA The ID of the post that has all the meta info already set.
 * @param int $idB The ID of the post that needs to have the meta info attached to it.
 *
 */
function pf_meta_transition_post($idA, $idB, $term_transition = false){
	pf_log('Transition post '.$idA.' to '.$idB);
	foreach(pf_meta_structure() as $meta){
		pf_meta_transition(get_pf_meta_name($meta), $idA, $idB);
	}
	if ( $term_transition ){
		pf_log('Transitioning Terms.');
		pf_transition_terms($idA, $idB);
	}
}

function pf_transition_terms($idA, $idB){
	$parent = wp_get_post_parent_id($idA);
	$ids = array($idA);
	if ( !empty($parent) && !is_wp_error( $parent ) ){
		$ids[] = $parent;
	}
	$item_id = pf_get_post_meta($idA, 'pf_item_post_id');
	if ( !empty($item_id) && !is_wp_error( $item_id ) ){
		$ids[] = $item_id;
	}
	/**$parent_parent = wp_get_post_parent_id( $parent );
	if ( !empty($parent_parent) && !is_wp_error( $parent_parent ) ){
		$ids[] = $parent_parent;
	}**/
	$term_objects = wp_get_object_terms( $ids, array( pressforward()->pf_feeds->tag_taxonomy, 'post_tag', 'category' ) );
	$item_tags = pf_get_post_meta($idA, 'item_tags');
	if ( !empty($term_objects) ){
		foreach ( $term_objects as $term ){
			wp_set_object_terms($idB, $term->term_id, $term->taxonomy, true);
			if ( pressforward()->pf_feeds->tag_taxonomy == $term->taxonomy ){
				$check = pf_cascade_tagging($idB, $term->slug, 'slug');
				if (!$check){
					pf_build_and_assign_new_tag($idB, $term->name);
				}
			}
		}
	}
	if ( !empty($item_tags) ){
		pf_log('Attempting to attach item_tags.');
		if ( !is_array( $item_tags ) ){
			pf_log($item_tags);
			$item_tags = explode(',',$item_tags);
		}
		foreach ($item_tags as $tag){
			$check = pf_cascade_tagging($idB, $tag, 'name');
			if (!$check){
				pf_build_and_assign_new_tag($idB, $tag);
			}
		}
	}
}

function pf_cascade_tagging($idB, $term_id, $term_id_type = 'slug'){
	pf_log('Trying to assign taxonomy for '.$idB);
	$term_object = get_term_by($term_id_type, $term_id, 'category');
	if ( empty( $term_object ) ){
		pf_log('No category match.');
		$term_object = get_term_by($term_id_type, $term_id, 'post_tag');
		if ( empty( $term_object ) ){
			pf_log('No post_tag match.');
			return false;
		} else {
			wp_set_object_terms( $idB, $term_object->term_id, 'post_tag', true );
		}
	} else {
		wp_set_object_terms( $idB, $term_object->term_id, 'category', true );
	}
	return true;
}

function pf_build_and_assign_new_tag($idB, $full_tag_name){
	pf_log('Attaching new tag to '.$idB.' with a name of '.$full_tag_name);
	$term_args = array(
						'description'	=>	'Added by PressForward',
						'parent'		=>	0,
						'slug'			=>	pf_slugger($full_tag_name)
					);
	$r = wp_insert_term($full_tag_name, 'post_tag', $term_args);
	if ( !is_wp_error( $r ) && !empty($r['term_id']) ){
		pf_log('Making a new post_tag, ID:'.$r['term_id']);
		wp_set_object_terms( $idB, $r['term_id'], 'post_tag', true );
	} else {
		pf_log('Failed making a new post_tag');
		pf_log($r);
	}
}

/**
 * With a post_meta slug and two post IDs copy a post_meta from one post to another.
 *
 * @param string $name The post_meta slug.
 * @param int $idA The post which already has the post_meta data.
 * @param int $idB The post which needs the post_meta copied to it.
 *
 * @return int The result of the update_post_meta function.
 *
 */
function pf_meta_transition($name, $idA, $idB){
	$meta_value = get_post_meta($idA, $name, true);
	#$result = pf_prep_for_depreciation($name, $meta_value, $idA, $idB);
	#if (!$result){
		$result = update_post_meta($idB, $name, $meta_value);
	#}

	return $result;
}

/**
 * Check a post_meta slug and insure that the correct post_meta is being set.
 *
 * Considers a post_meta slug and checkes it against a list for depreciation.
 * If the post_meta slug has been depreciated update the new slug and the old one.
 *
 * Based on http://seoserpent.com/wordpress/custom-author-byline
 *
 * @since 3.x
 *
 * @param string $name The post_meta slug.
 * @param string $value The post_meta value.
 * @param int $idA The id of the post that already has the post_meta set.
 * @param int $idB The id of the post that needs the post_meta set.
 *
 * @return bool True if the post_meta is supported by PressForward.
 */
function pf_prep_for_depreciation($name, $value, $idA, $idB){
	foreach (pf_meta_structure() as $meta){
		if ($meta['name'] == $name){
			if (in_array('dep', $meta['type'])){
				#if ((!isset($value)) || (false == $value) || ('' == $value) || (0 == $value) || (empty($value))){
					$value = get_post_meta($idA, $meta['move'], true);
				#}
				#update_post_meta($idA, $name, $value);
				update_post_meta($idB, $meta['move'], $value);
				update_post_meta($idB, $name, $value);
				return true;
			}
		}
	}
	return false;
}

/**
 * Get the meta by its name, if it is supported by PressForward.
 *
 * @since 3.x
 *
 * @param string $author The author string currently being displayed.
 *
 * @return string Returns the author.
 */
function pf_meta_by_name($name){
	foreach (pf_meta_structure() as $meta){
		if($name == $meta['name']){
			return $meta;
		}
	}
}

function pf_assure_meta_key($name){
	$meta = pf_meta_by_name($name);
	if ( !empty( $meta['move'] ) ){
		return pf_meta_by_name( $meta['move'] );
	} else{
		return $meta;
	}
}

function pf_get_meta_key( $name ){
	$meta = pf_assure_meta_key( $name );
	return get_pf_meta_name( $meta );
}

/**
 * Get the name out of the meta object.
 */
function get_pf_meta_name($meta){
	return $meta['name'];
}

/**
 * Get an array representing all the approved post_meta objects for PressForward.
 *
 * @since 3.x
 *
 * @return array An object describing all the post_metas used by PressForward.
 */
function pf_meta_structure(){
	#Inspired by http://www.loc.gov/standards/metable.html
	#Adm=Administrative, Struc=Structural, Desc=Descriptive, Req=Required, Rep=Repeatable, Set=Set, Aggr=Aggregate, Dep = Depreciated

	$metas = array(
		'item_id' => array(
			'name' => 'item_id',
			'definition' => __('Unique PressForward ID', 'pf'),
			'function'	=> __('Stores hashed ID based on title and URL of retrieved item', 'pf'),
			'type'	=> array('struc'),
			'use'	=> array('req'),
			'level'	=> array('item', 'nomination', 'post')
		),
		'origin_item_ID' => array(
			'name' => 'origin_item_ID',
			'definition' => __('DUPE Soon to be depreciated version of item_id', 'pf'),
			'function'	=> __('Stores hashed ID based on title and URL of retrieved item', 'pf'),
			'type'	=> array('struc', 'dep'),
			'use'	=> array('req'),
			'move'	=> 'item_id',
			'level'	=> array('item', 'nomination', 'post')
		),
		'pf_item_post_id' => array(
			'name' => 'pf_item_post_id',
			'definition' => __('The WordPress postID associated with the original item', 'pf'),
			'function'	=> __('Stores hashed WP post_ID associated with the original item', 'pf'),
			'type'	=> array('struc'),
			'use'	=> array('req'),
			'level'	=> array('item', 'nomination', 'post')
		),
		'pf_nomination_post_id' => array(
			'name' => 'pf_nomination_post_id',
			'definition' => __('The WordPress postID associated with the nomination', 'pf'),
			'function'	=> __('Stores postID associated with the nominated item', 'pf'),
			'type'	=> array('struc'),
			'use'	=> array(),
			'level'	=> array('item', 'nomination', 'post')
		),
		'item_feed_post_id' => array(
			'name' => 'item_feed_post_id',
			'definition' => __('DUPE Soon to be depreciated version of pf_item_post_id', 'pf'),
			'function'	=> __('Stores hashed ID based on title and URL of retrieved item', 'pf'),
			'type'	=> array('struc', 'dep'),
			'use'	=> array('req'),
			'move'	=> 'pf_item_post_id',
			'level'	=> array('item', 'nomination', 'post')
		),
		'source_title' => array(
			'name' => 'source_title',
			'definition' => __('Title of the item\'s source', 'pf'),
			'function'	=> __('Stores the title retrieved from the feed.', 'pf'),
			'type'	=> array('adm'),
			'use'	=> array(),
			'level'	=> array('item', 'nomination', 'post')
		),
		'pf_source_link' => array(
			'name' => 'pf_source_link',
			'definition' => __('URL of the item\'s source', 'pf'),
			'function'	=> __('Stores the url of feed source.', 'pf'),
			'type'	=> array('adm'),
			'use'	=> array(),
			'level'	=> array('item', 'nomination', 'post')
		),
		'pf_feed_item_source' => array(
			'name' => 'pf_feed_item_source',
			'definition' => __('DUPE Soon to be depreciate version of source_title.', 'pf'),
			'function'	=> __('Stores the title retrieved from the feed.', 'pf'),
			'type'	=> array('desc','dep'),
			'use'	=> array('req'),
			'move'	=> 'source_title',
			'level'	=> array('item', 'nomination', 'post')
		),
		'item_date' => array(
			'name' => 'item_date',
			'definition' => __('Date posted on the original site', 'pf'),
			'function'	=> __('Stores the date the item was posted on the original site', 'pf'),
			'type'	=> array('desc'),
			'use'	=> array('req'),
			'level'	=> array('item', 'nomination', 'post')
		),
		'posted_date' => array(
			'name' => 'posted_date',
			'definition' => __('DUPE The soon to be depreciated version of item_date', 'pf'),
			'function'	=> __('Stores the date given by the source.', 'pf'),
			'type'	=> array('struc', 'dep'),
			'use'	=> array('req'),
			'move'	=> 'item_date',
			'level'	=> array('nomination', 'post')
		),
		'item_author' => array(
			'name' => 'item_author',
			'definition' => __('Author(s) listed on the original site', 'pf'),
			'function'	=> __('Stores array value containing authors listed in the source feed.', 'pf'),
			'type'	=> array('struc'),
			'use'	=> array(),
			'level'	=> array('item', 'nomination', 'post')
		),
		'authors' => array(
			'name' => 'authors',
			'definition' => __('DUPE The soon to be depreciated version of item_author', 'pf'),
			'function'	=> __('Stores a comma-separated set of authors as listed in the source feed', 'pf'),
			'type'	=> array('struc','dep'),
			'use'	=> array(),
			'move'	=> 'item_author',
			'level'	=> array('nomination', 'post')
		),
		'item_link' => array(
			'name' => 'item_link',
			'definition' => __('Source link', 'pf'),
			'function'	=> __('Stores link to the origonal post.', 'pf'),
			'type'	=> array('struc'),
			'use'	=> array('req'),
			'level'	=> array('item', 'nomination', 'post')
		),
		'nomination_permalink' => array(
			'name' => 'item_link',
			'definition' => __('Source link', 'pf'),
			'function'	=> __('DUPE Soon to be depreciated version of item_link', 'pf'),
			'type'	=> array('struc','dep'),
			'use'	=> array('req'),
			'move'	=> 'item_link',
			'level'	=> array('nomination', 'post')
		),
		'item_feat_img' => array(
			'name' => 'item_feat_img',
			'definition' => __('Featured image from source', 'pf'),
			'function'	=> __('A featured image associated with the item, when it is available', 'pf'),
			'type'	=> array('struc'),
			'use'	=> array(),
			'level'	=> array('item', 'nomination', 'post')
		),
		'item_wp_date' => array(
			'name' => 'item_wp_date',
			'definition' => __('Time item was retrieved', 'pf'),
			'function'	=> __('The datetime an item was added to WordPress via PressForward', 'pf'),
			'type'	=> array('desc'),
			'use'	=> array('req'),
			'level'	=> array('item', 'nomination', 'post')
		),
		'date_nominated' => array(
			'name' => 'date_nominated',
			'definition' => __('Time nominated', 'pf'),
			'function'	=> __('The datetime the item was made a nomination', 'pf'),
			'type'	=> array('desc'),
			'use'	=> array('req'),
			'level'	=> array('nomination', 'post')
		),
		'item_tags' => array(
			'name' => 'item_tags',
			'definition' => __('Tags associated with the item by source', 'pf'),
			'function'	=> __('An array of tags associated with the item, as created in the feed', 'pf'),
			'type'	=> array('desc'),
			'use'	=> array(),
			'level'	=> array('item', 'nomination', 'post')
		),
		'source_repeat' => array(
			'name' => 'source_repeat',
			'definition' => __('Times retrieved', 'pf'),
			'function'	=> __('Counts number of times the item has been collected from the multiple feeds (Ex: from origin feed and Twitter)', 'pf'),
			'type'	=> array('adm'),
			'use'	=> array(),
			'level'	=> array('item', 'nomination', 'post')
		),
		'nomination_count' => array(
			'name' => 'nomination_count',
			'definition' => __('Nominations', 'pf'),
			'function'	=> __('Counts number of times users have nominated an item', 'pf'),
			'type'	=> array('adm'),
			'use'	=> array('req'),
			'level'	=> array('item', 'nomination', 'post')
		),
		'submitted_by' => array(
			'name' => 'submitted_by',
			'definition' => __('The user who submitted the nomination', 'pf'),
			'function'	=> __('The first user who submitted the nomination (if it has been nominated). User ID number', 'pf'),
			'type'	=> array('adm'),
			'use'	=> array('req'),
			'level'	=> array('item', 'nomination', 'post')
		),
		'nominator_array' => array(
			'name' => 'nominator_array',
			'definition' => __('Users who nominated this item', 'pf'),
			'function'	=> __('Stores and array of all userIDs that nominated the item in an array', 'pf'),
			'type'	=> array('adm'),
			'use'	=> array('req'),
			'level'	=> array('item', 'nomination', 'post')
		),
		'sortable_item_date' => array(
			'name' => 'sortable_item_date',
			'definition' => __('Timestamp for the item', 'pf'),
			'function'	=> __('A version of the item_date meta that\'s ready for sorting. Should be a Unix timestamp', 'pf'),
			'type'	=> array('adm'),
			'use'	=> array('req'),
			'level'	=> array('item', 'nomination', 'post')
		),
		'readable_status' => array(
			'name' => 'readable_status',
			'definition' => __('If the content is readable', 'pf'),
			'function'	=> __('A check to determine if the content of the item has been made readable', 'pf'),
			'type'	=> array('desc'),
			'use'	=> array('req'),
			'level'	=> array('item', 'nomination', 'post')
		),
		'revertible_feed_text' => array(
			'name' => 'revertible_feed_text',
			'definition' => __('The originally retrieved description', 'pf'),
			'function'	=> __('The original description, excerpt or content text given by the feed', 'pf'),
			'type'	=> array('adm'),
			'use'	=> array(),
			'level'	=> array('item', 'nomination', 'post')
		),
		'pf_feed_item_word_count' => array(
			'name' => 'pf_feed_item_word_count',
			'definition' => __('Word count of original item text', 'pf'),
			'function'	=> __('Stores the count of the original words retrieved with the feed item', 'pf'),
			'type'	=> array('desc'),
			'use'	=> array(),
			'level'	=> array('item', 'nomination', 'post')
		),
		'pf_feed_error_count' => array(
			'name' => 'pf_feed_error_count',
			'definition' => __('Count of feed errors', 'pf'),
			'function'	=> __('Stores a count of the number of errors a feed has experianced', 'pf'),
			'type'	=> array('adm'),
			'use'	=> array(),
			'level'	=> array('feed', 'post')
		)
	);

	$metas = apply_filters('pf_meta_terms',$metas);
	return $metas;
}

/*
 * A function to check and retrieve the right meta field for a post.
 */
function pf_pass_meta($field, $id = false, $value = '', $single = true){
    $metas = pf_meta_structure();
    # Check if it exists.
    if (empty($metas[$field])){
        pf_log('The field ' . $field . ' is not supported.');
				return $field;
    }
	# Check if it has been depreciated (dep). If so retrieve
    if (in_array('dep',$metas[$field]['type'])){
		$new_field = $metas[$field]['move'];
		pf_log('You tried to use depreciated field '.$field.' it was moved to '.$new_field);
		pf_transition_deped_meta($field, $id, $value, $single, $new_field);
        $field = $new_field;
    }
    return $field;

}

/**
 * Transitions meta values from old depreciated meta_slugs to new ones.
 *
 */
function pf_transition_deped_meta($field, $id, $value, $single, $new_field){
	$result = false;
	# Note - empty checks for FALSE
	$old = get_post_meta($id, $field, $single);
	$new = get_post_meta($id, $new_field, $single);
	if ((false != $id) && !empty($old) && empty($new)){
		if (empty($value)){
			$result = update_post_meta($id, $new_field, $old);
		} else {
			$result = update_post_meta($id, $new_field, $value);
		}
	}
	return $result;
}

/**
 * Retrieve post_meta data in a way that insures the correct value is pulled.
 *
 * Function allows users to retrieve the post_meta in a safe way standerdizing against
 * the list of accepted PressForward meta_slugs. It deals with depreciated post_meta.
 *
 * @since 3.x
 *
 * @param int $id Post ID.
 * @param string $field The post_meta field to retrieve.
 * @param bool $obj If the user wants to return a PressForward post_meta description object. Default false.
 * @param bool $single If the user wants to use the WordPress post_meta Single decleration. Default true.
 *
 * @return string|array Returns the result of retrieving the post_meta or the self-descriptive meta-object with value.
 */
function pf_retrieve_meta($id, $field, $obj = false, $single = true){
    $field = pf_pass_meta($field, $id);
    $meta = get_post_meta($id, $field, $single);
    if ($obj){
        $metas = pf_meta_structure();
        $meta_obj = $metas[$field];
        $meta_obj['value'] = $meta;
        return $meta_obj;
    }
    return $meta;

}

/**
 * An alias for pf_retrieve_meta that allows you to use the standard argument set from get_post_meta.
 *
 */
function pf_get_post_meta($id, $field, $single = true, $obj = false){

		return pf_retrieve_meta($id, $field, $obj, $single);

}

/**
 * Update post_meta on a post using PressForward post_meta standardization.
 *
 * @param int|string $id The post ID.
 * @param string $field The post_meta field slug.
 * @param string $value The post_meta value.
 * @param string $prev_value The previous value to insure proper replacement.
 *
 * @return int The check value from update_post_meta.
 */
function pf_update_meta($id, $field, $value = '', $prev_value = NULL){
    $field = pf_pass_meta($field, $id, $value);
    $check = pf_apply_meta($id, $field, $value, $prev_value);
    return $check;

}

function pf_get_author_from_url($url){
	$response = pf_file_get_html( $url );
	$possibles = array();
	if (empty($response)){
		return false;
	}
	$possibles[] = $response->find('meta[name=author]', 0);
	$possibles[] = $response->find('meta[name=Author]', 0);
	$possibles[] = $response->find('meta[property=author]', 0);
	$possibles[] = $response->find('meta[property=Author]', 0);
	$possibles[] = $response->find('meta[name=parsely-author]', 0);
	$possibles[] = $response->find('meta[name=sailthru.author]', 0);

	foreach ($possibles as $possible){
		if ( false != $possible ){
			$author_meta = $possible;
			break;
		}
	}

	if ( empty($author_meta) ){
		return false;
	}

	$author = $author_meta->content;
	$author = trim(str_replace("by","",$author));
	$author = trim(str_replace("By","",$author));
	return $author;
}

/**
 * Add post_meta on a post using PressForward post_meta standardization.
 *
 * @param int|string $id The post ID.
 * @param string $field The post_meta field slug.
 * @param string $value The post_meta value.
 * @param string $unique If the post_meta is unique.
 *
 * @return int The check value from add_post_meta.
 */
function pf_add_meta($id, $field, $value = '', $unique = false){
    $field = pf_pass_meta($field, $id, $value, $unique);
    $check = pf_apply_meta($id, $field, $value, $unique);
    return $check;

}

function pf_apply_meta($id, $field, $value = '', $state = null, $apply_type = 'update'){
	switch ($field) {
		case 'nominator_array':
			$nominators = pf_get_post_meta($id, $field);
			if ( !is_array( $value ) ){
				$value = array( $value );
			}
			if ( !is_array( $nominators ) ){
				$nominators = array( $nominators );
			}
			//We are doing a removal.
			if ( 1 == count(array_diff($value, $nominators) ) ){
				$nominators = array_unique( $value );
				continue;
			}
			if ( !is_array($value) ){
				$value = array($value);
			}
			$nominators = array_merge( $nominators, $value );
			$nominators = array_unique( $nominators );
			$value = $nominators;
			break;
		default:
			# code...
			break;
	}
	if ( 'update' == $apply_type ){
		$check = update_post_meta($id, $field, $value, $state);
	} elseif ( 'add' == $apply_type ) {
		$check = add_post_meta($id, $field, $value, $state);
	}
	return $check;
}

function pf_is_drafted($item_id){
	$a = array(
			'no_found_rows' => true,
			'fields' => 'ids',
			'meta_key' => 'item_id',
			'meta_value' => $item_id,
			'post_type'	=> get_option(PF_SLUG.'_draft_post_type', 'post')
		);
	$q = new WP_Query($a);
	if ( 0 < $q->post_count ){
		$draft = $q->posts;
		return $draft[0];
	}
	else {
		return false;
	}
}

/**
 * Get a list of all drafted items.
 *
 * @return array
 */
function pf_get_drafted_items( $post_type = 'pf_feed_item' ) {
	$drafts = get_posts( array(
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
	foreach ( $drafts as $p ) {
		$item_hashes[] = get_post_meta( $p->ID, 'item_id', true );
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

function filter_for_pf_archives_only($sql){
	global $wpdb;
#	if (isset($_GET['pf-see']) && ('archive-only' == $_GET['pf-see'])){
		$relate = new PF_RSS_Import_Relationship();
		$rt = $relate->table_name;
		$user_id = get_current_user_id();
		$read_id = pf_get_relationship_type_id('archive');

/**		$sql .= " AND {$wpdb->posts}.ID
				IN (
					SELECT item_id
					FROM {$rt}
					WHERE {$rt}.user_id = {$user_id}
					AND {$rt}.relationship_type = {$read_id}
					AND {$rt}.value = 1
				) ";
	}
**/	#var_dump($sql);
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

function prep_archives_query($q){
		global $wpdb;

		if (isset($_GET["pc"])){
			$offset = $_GET["pc"]-1;
			$offset = $offset*10;
		} else {
			$offset = 0;
		}

		if (isset($_GET['pf-see']) && ('archive-only' == $_GET['pf-see'])){
			$pagefull = 20;
			$relate = new PF_RSS_Import_Relationship();
			$rt = $relate->table_name;
			$user_id = get_current_user_id();
			$read_id = pf_get_relationship_type_id('archive');
			$q = $wpdb->prepare("
				SELECT {$wpdb->posts}.*, {$wpdb->postmeta}.*
				FROM {$wpdb->posts}, {$wpdb->postmeta}
				WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
				AND {$wpdb->posts}.post_type = %s
				AND {$wpdb->posts}.post_status = 'draft'
				AND {$wpdb->postmeta}.meta_key = 'pf_archive'
				AND {$wpdb->postmeta}.meta_value > 0
				AND {$wpdb->posts}.ID
				GROUP BY {$wpdb->posts}.ID
				ORDER BY {$wpdb->postmeta}.meta_value DESC
				LIMIT {$pagefull} OFFSET {$offset}
			 ", 'nomination');
		} elseif (isset($_GET['pf-see']) && ('unread-only' == $_GET['pf-see'])){
			$pagefull = 20;
			$relate = new PF_RSS_Import_Relationship();
			$rt = $relate->table_name;
			$user_id = get_current_user_id();
			$read_id = pf_get_relationship_type_id('read');
			#var_dump($read_id); die();
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
		} elseif (isset($_GET['action']) && (isset($_POST['search-terms']))){
			$pagefull = 20;
			$relate = new PF_RSS_Import_Relationship();
			$rt = $relate->table_name;
			$user_id = get_current_user_id();
			$read_id = pf_get_relationship_type_id('archive');
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
			 ", 'nomination', '%'.$search.'%', '%'.$search.'%');
		} elseif (isset($_GET['pf-see']) && ('starred-only' == $_GET['pf-see'])){
			$pagefull = 20;
			$relate = new PF_RSS_Import_Relationship();
			$rt = $relate->table_name;
			$user_id = get_current_user_id();
			$read_id = pf_get_relationship_type_id('star');
			$q = $wpdb->prepare("
				SELECT DISTINCT wposts.*
				FROM {$wpdb->posts} wposts
				LEFT JOIN {$wpdb->postmeta} wpm1 ON (wposts.ID = wpm1.post_id
					AND wpm1.meta_key = 'sortable_item_date' AND wpm1.meta_value > 0 AND wposts.post_type = %s
				)
				LEFT JOIN {$wpdb->postmeta} wpm2 ON  (wposts.ID = wpm2.post_id
                       AND wpm2.meta_key = 'item_feed_post_id' AND wposts.post_type = %s )
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
			 #var_dump($q);
		}
	#$archivalposts = $wpdb->get_results($dquerystr, OBJECT);
	#return $archivalposts;
	return $q;
}

add_filter('upload_mimes', 'pf_custom_upload_opml');

function pf_custom_upload_opml ( $existing_mimes=array() ) {

	// add your ext => mime to the array
	$existing_mimes['opml'] = 'text/x-opml';

	// and return the new full result
	return $existing_mimes;

}

function pf_iterate_cycle_state($option_name, $option_limit = false, $echo = false){
	$default = array(
		'day' 			=> 0,
		'week'			=> 0,
		'month' 		=> 0,
		'next_day'		=> strtotime('+1 day'),
		'next_week'		=> strtotime('+1 week'),
		'next_month'	=> strtotime('+1 month')
	);
	$retrieval_cycle = get_option(PF_SLUG.'_'.$option_name,$default);
	if (!is_array($retrieval_cycle)){
		$retrieval_cycle = $default;
		update_option(PF_SLUG.'_'.$option_name, $retrieval_cycle);
	}
	if ($echo) {
		echo '<br />Day: '.$retrieval_cycle['day'];
		echo '<br />Week: '.$retrieval_cycle['week'];
		echo '<br />Month: '.$retrieval_cycle['month'];
	} else if(!$option_limit){
		return $retrieval_cycle;
	} else if($option_limit){
		$states = array('day','week','month');
		foreach ($states as $state){
			if (strtotime("now") >= $retrieval_cycle['next_'.$state]){
				$retrieval_cycle[$state] = 1;
				$retrieval_cycle['next_'.$state] = strtotime('+1 '.$state);
			} else {
				$retrieval_cycle[$state] = $retrieval_cycle[$state]+1;
			}
		}
		update_option(PF_SLUG.'_'.$option_name, $retrieval_cycle);
		return $retrieval_cycle;
	} else {
		if (strtotime("now") >= $retrieval_cycle['next_'.$option_limit]){
			$retrieval_cycle[$option_limit] = 1;
			$retrieval_cycle['next_'.$option_limit] = strtotime('+1 '.$option_limit);
		} else {
			$retrieval_cycle[$option_limit] = $retrieval_cycle[$option_limit]+1;
		}
		update_option(PF_SLUG.'_'.$option_name, $retrieval_cycle);
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
function pf_delete_item_tree( $item, $fake_delete = false ) {
	$item = get_post( $item );

	if ( ! $item || ! ( $item instanceof WP_Post ) ) {
		return false;
	}

	$feed_item_post_type = pf_feed_item_post_type();
	$feed_post_type      = pressforward()->pf_feeds->post_type;

	if ( ! in_array( $item->post_type, array( $feed_item_post_type, $feed_post_type, 'nomination' ) ) ) {
		return false;
	}

	$queued = get_option( 'pf_delete_queue', array() );
	if ( in_array( $item->ID, $queued ) ) {
		return false;
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

			if ($fake_delete){
				$fake_status = 'removed_'.$item->post_type;

				$wp_args = array(
					'post_type'    => pf_feed_item_post_type(),
					'post_status'  => $fake_status,
					'post_title'   => $item->post_title,
					'post_content' => '',
					'guid'         => pf_get_post_meta($item->ID, 'item_link'),
					'post_date'    => $item->post_date
				);

				$id = wp_insert_post($wp_args);
				pf_update_meta($id, 'item_id', create_feed_item_id( pf_get_post_meta($item->ID, 'item_link'), $item->post_title ) );
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
	}

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
	if ( ! $queued || ! is_array( $queued ) ) {
		return;
	}

	$post__not_in = $query->get( 'post__not_in' );
	$post__not_in = array_merge( $post__not_in, $queued );
	$query->set( 'post__not_in', $post__not_in );
}
add_action( 'pre_get_posts', 'pf_exclude_queued_items_from_queries', 999 );

/**
 * Detect and process a delete queue request.
 *
 * Request URLs are of the form example.com?pf_process_delete_queue=123, where '123' is a single-use nonce stored in
 * the 'pf_delete_queue_nonce' option.
 *
 * @since 3.6
 */
function pf_process_delete_queue() {
	if ( ! isset( $_GET['pf_process_delete_queue'] ) ) {
		return;
	}

	$nonce = $_GET['pf_process_delete_queue'];
	$saved_nonce = get_option( 'pf_delete_queue_nonce' );
	if ( $saved_nonce !== $nonce ) {
		return;
	}

	$queued = get_option( 'pf_delete_queue', array() );
	for ( $i = 0; $i <= 1; $i++ ) {
		$post_id = array_shift( $queued );
		if ( null !== $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}
	update_option( 'pf_delete_queue', $queued );
	delete_option( 'pf_delete_queue_nonce' );

	if ( ! $queued ) {
		delete_option( 'pf_delete_queue' );

		// Clean up empty taxonomy terms.
		$terms = get_terms( pressforward()->pf_feeds->tag_taxonomy, array(
			'hide_empty' => false,
		) );

		foreach ( $terms as $term ) {
			if ( 0 == $term->count ) {
				wp_delete_term( $term->term_id, pressforward()->pf_feeds->tag_taxonomy );
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

function pf_function_auto_logger($caller){
	if (isset($caller['class'])){
		$func_statement = '[ ' . $caller['class'] . '->' . $caller['function'] . ' ] ';
	} else {
		$func_statement = '[ ' . $caller['function'] . ' ] ';
	}
	return $func_statement;
}

function assure_log_string( $message ){
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

	if ( $return && ( 0 === $debug ) ){
		return assure_log_string($message);
	}

	if ( 0 === $debug ) {
		return;
	}

	if ( ! defined( 'PF_DEBUG' ) || ! PF_DEBUG ) {
		$debug = 0;
		return;
	}

	if ( ( ( true === $display ) ) ) {
		print_r($message);
	}

	// Default log location is in the uploads directory
	if ( ! defined( 'PF_DEBUG_LOG' ) ) {
		$upload_dir = wp_upload_dir();
		$log_path = $upload_dir['basedir'] . '/pressforward.log';
	} else {
		$log_path = PF_DEBUG_LOG;
	}

	if ($reset) {
		$fo = fopen($log_path, 'w') or print_r('Can\'t open log file.');
		fwrite($fo, "Log file reset.\n\n\n");
		fclose($fo);

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

	$message = assure_log_string($message);

	$trace=debug_backtrace();
	foreach ($trace as $key=>$call) {

		if ( in_array( $call['function'], array('call_user_func_array','do_action','apply_filter', 'call_user_func', 'do_action_ref_array', 'require_once') ) ){
			unset($trace[$key]);
		}

	}
	reset($trace);
	$first_call = next($trace);
	if (!empty($first_call)){
		$func_statement = pf_function_auto_logger( $first_call );
	} else {
		$func_statement = '[ ? ] ';
	}
	$second_call = next($trace);
	if ( !empty($second_call) ){
		if ( ('call_user_func_array' == $second_call['function']) ){
			$third_call = next($trace);
			if ( !empty($third_call) ) {
				$upper_func_statement = pf_function_auto_logger($third_call);
			} else {
				$upper_func_statement = '[ ? ] ';
			}
		} else {
			$upper_func_statement = pf_function_auto_logger($second_call);
		}
		$func_statement = $upper_func_statement . $func_statement;
	}

	error_log( '[' . gmdate( 'd-M-Y H:i:s' ) . '] ' . $func_statement . $message . "\n", 3, $log_path );

	if ($return){
		return $message;
	}
}

function pf_message( $message = '', $display = false, $reset = false ){
	$returned_message = pf_log( $message, false, $reset, true );
	return $returned_message;
}
