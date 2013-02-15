<?php

/**
 * Miscellaneous utility functions
 *
 * @since 1.7
 */

function pressforward_register_module( $args ) {
	$defaults = array(
		'slug' => '',
		'class' => '',
	);
	$r = wp_parse_args( $args, $defaults );

	// We need the 'class' and 'slug' terms
	if ( empty( $r['class'] ) || empty( $r['slug'] ) ) {
		continue;
	}

	// Ensure the class exists before attempting to initialize it
	// @todo Should probably have better error reporting
	if ( ! class_exists( $r['class'] ) ) {
		continue;
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
				a=function(){if(!w.open(u,'t','toolbar=0,resizable=1,scrollbars=1,status=1,width=720,height=570'))l.href=u;};
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
 * @return string
 */
function pf_feed_item_post_type() {
	return pressforward()->get_feed_item_post_type();
}

/**
 * Get the feed item tag taxonomy name
 *
 * @since 1.7
 *
 * @return string
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
function pf_feed_object( $itemTitle='', $sourceTitle='', $itemDate='', $itemAuthor='', $itemContent='', $itemLink='', $itemFeatImg='', $itemUID='', $itemWPDate='', $itemTags='', $addedDate='', $sourceRepeat='' ) {

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
		'source_repeat'   => $sourceRepeat
	);

	return $itemArray;
}

/**
 * Get all posts with 'origin_item_ID' set to a given item id
 *
 * @since 1.7
 *
 * @param string $theDate MySQL-formatted date. Posts will only be fetched
 *   starting from this date
 * @param string $post_type The post type to limit results to
 * @param int $item_id The origin item id
 * @return object
 */
function pf_get_posts_by_id_for_check( $theDate, $post_type, $item_id ) {
	global $wpdb;

	 $querystr = "
			SELECT $wpdb->posts.*
			FROM $wpdb->posts, $wpdb->postmeta
			WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
			AND $wpdb->postmeta.meta_key = 'origin_item_ID'
			AND $wpdb->postmeta.meta_value = '" . $item_id . "'
			AND $wpdb->posts.post_type = '" . $post_type . "'
			AND $wpdb->posts.post_date >= '". $theDate . "'
			ORDER BY $wpdb->posts.post_date DESC
		 ";

	$postsAfter = $wpdb->get_results($querystr, OBJECT);

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

		echo '<input type="hidden" name="' . $itemKey . '" id="' . $itemKey . '_' . $itemid . '" id="' . $itemKey . '" value="' . $itemPart . '" />';

	}

}

/**
 * Converts an https URL into http, to account for servers without SSL access
 *
 * @since 1.7
 *
 * @param string $url
 * @return string $url
 */
function pf_de_https($url) {
	$urlParts = parse_url($url);
	if (in_array('https', $urlParts)){
		$urlParts['scheme'] = 'http';
		$url = $urlParts['scheme'] . '://'. $urlParts['host'] . $urlParts['path'] . $urlParts['query'];
	}
	return $url;
}

/**
 * Converts a list of terms to a set of slugs to be listed in the nomination CSS selector
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
 * Build an excerpt for a nomination
 *
 * @param string $text
 */
function pf_noms_excerpt( $text ) {
	global $post;
	$text = get_the_content('');
	$text = apply_filters('the_content', $text);
	$text = str_replace('\]\]\>', ']]&gt;', $text);
	$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
	$contentObj = new htmlchecker($text);
	$text = $contentObj->closetags($text);
	$text = strip_tags($text);

	$excerpt_length = 310;
	$words = explode(' ', $text, $excerpt_length + 1);
	if (count($words)> $excerpt_length) {
		array_pop($words);
		array_push($words, '...');
		$text = implode(' ', $words);
	}

	return $text;
}


//Based on http://seoserpent.com/wordpress/custom-author-byline
function pf_replace_author_presentation( $author ) {
	global $post;
	$custom_author = get_post_meta($post->ID, 'authors', TRUE);
	if($custom_author)
		return $custom_author;
	return $author;
}
add_filter( 'the_author', 'pf_replace_author_presentation' );

function pf_replace_author_uri_presentation( $author_uri ) {
	//global $authordata;
	global $post, $authordata;
	$custom_author_uri = get_post_meta($post->ID, 'nomination_permalink', TRUE);
	if($custom_author_uri)
		return $custom_author_uri;
	return $author_uri;
}

add_filter( 'author_link', 'pf_replace_author_uri_presentation' );

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
function pf_log( $message = '', $display = false, $reset = false ) {
	static $debug;

	if ( 0 === $debug ) {
		return;
	}

	if ( ! defined( 'PF_DEBUG' ) || ! PF_DEBUG ) {
		$debug = 0;
		return;
	}

	if ( ( true === $display ) ) {
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

	// Make sure we've got a string to log
	if ( is_wp_error( $message ) ) {
		$message = $message->get_error_message();
	}

	if ( is_array( $message ) ) {
		$message = print_r( $message, true );
	}

	if ( $message === true ) {
		$message = 'True';
	}

	if ( $message === false ) {
		$message = 'False';
	}

	error_log( '[' . gmdate( 'd-M-Y H:i:s' ) . '] ' . $message . "\n", 3, $log_path );
}
