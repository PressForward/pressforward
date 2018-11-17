<?php

class OPML_Object {

	function __construct( $url ) {
		$this->url = $url;
		$this->folders = array();
		$this->feeds = array();
	}

	function set_folder( $folder_obj ) {
		$folder_obj->slug = $this->slugify( $folder_obj->title );
		$this->folders[ $folder_obj->slug ] = $folder_obj;
	}
	function set_title( $string ) {
		if ( empty( $string ) ) {
			$this->title = $this->url;
		} else {
			$this->title = (string) $string;
		}
	}
	function get_title() {
		if ( empty( $this->title ) ) {
			return $this->url;
		} else {
			return $this->title;
		}
	}
	function get_folder( $key ) {
		$folders = $this->folders;
		$key = $this->slugify( $key );
		return $folders[ $key ];

	}
	function set_feed( $feed_obj, $folder = false ) {
		if ( ! $folder ) {
			// Do not set an unsorted feed if it has already been set
			// as a sorted feed.
			if ( ! isset( $this->feeds[ $feed_obj->id ] ) ) {
				$feed_obj->folder = false;
				return array_push( $this->feeds, $feed_obj );
			}
		} else {
			if ( isset( $this->feeds[ $feed_obj->id ] ) ) {
				$feed_obj = $this->feeds[ $feed_obj->id ];
				// $feed_obj->folder[] = $folder;
			} elseif ( !empty($feed_obj) && ( empty( $feed_obj->folder ) || ! is_array( $feed_obj->folder ) ) ) {
				$feed_obj->folder = array();
				// $feed_obj->folder[] = $folder;
			} else {
				// $feed_obj->folder[] = $folder;
			}
			if ( is_array( $folder ) ) {
				foreach ( $folder as $folder_type ) {
					$feed_obj->folder[] = $folder_type;
				}
			} else {
				$feed_obj->folder[] = $folder;
			}
			// var_dump($feed_obj);
			$this->feeds[ $feed_obj->id ] = $feed_obj;
		}
	}
	public function check_keys( $array, $keys, $strict = false ) {
		$array['missing'] = array();
		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $array ) ) {
				if ( $strict ) {
					return false;
				} else {
					$array[ $key ] = '';
					$array['missing'][] = $key;
				}
			}
		}
		return $array;
	}
	function assure_title_and_text( $entry ) {
		if ( ! empty( $entry['title'] ) && empty( $entry['text'] ) ) {
			$entry['text'] = $entry['title'];
		} elseif ( ! empty( $entry['text'] ) && empty( $entry['title'] ) ) {
			$entry['title'] = $entry['text'];
		} elseif ( empty( $entry['title'] ) && empty( $entry['text'] ) && ! empty( $entry['feedUrl'] ) ) {
			$entry['text'] = $entry['feedUrl'];
			$entry['title'] = $entry['feedUrl'];
		}
		return $entry;
	}

	function make_a_folder_obj( $entry ) {
		$folder = new \stdClass();
		$entry = (array) $entry;
		$entry = $this->check_keys( $entry, array( 'title', 'text' ) );
		$entry['title'] = ( ! empty( $entry['title'] ) ? $entry['title'] : false);
		$entry['text'] = ( ! empty( $entry['text'] ) ? $entry['text'] : false);
		$entry = $this->assure_title_and_text( $entry );
		// var_dump($entry); die();
		$folder->title = $entry['title'];
		$folder->text = $entry['text'];
		// pf_log('Making folder with title of '.$folder->title);
		return $folder;
	}
	function make_a_feed_obj( $entry ) {
		$feed = new \stdClass();
		$entry = (array) $entry;
		if ( empty( $entry['xmlUrl'] ) ) {
			$entry['xmlUrl'] = $entry['htmlUrl'];
		}
		if ( empty( $entry['feedUrl'] ) ) {
			$entry['feedUrl'] = $entry['xmlUrl'];
		}
		$entry = $this->assure_title_and_text( $entry );
		$entry = $this->check_keys( $entry, array( 'title', 'text', 'type', 'xmlUrl', 'htmlUrl', 'feedUrl' ) );
		$feed->title = $entry['title'];
		$feed->text = $entry['text'];
		$feed->type = $entry['type'];
		$feed->xmlUrl = str_replace( '&amp;', '&', $entry['xmlUrl'] );
		$feed->feedUrl = str_replace( '&amp;', '&', $entry['feedUrl'] );
		$feed->htmlUrl = str_replace( '&amp;', '&', $entry['htmlUrl'] );
		$feed->id = md5( $feed->feedUrl );
		// pf_log('Making feed with URL of '.$feed->feedUrl);
		return $feed;
	}
	function order_opml_entries( $a, $b ) {
		if ( empty( $a->folder ) ) {
			return 1;
		}
		if ( empty( $b->folder ) ) {
			return -1;
		}
		$a = $a->folder[0];
		$b = $b->folder[0];
		if ( ! $a ) {
			return -1;
		}
		if ( strcasecmp( $a, $b ) == 0 ) {
			return 0;
		}
		if ( strcasecmp( $a, $b ) < 0 ) {
			return -1;
		} else {
			return 1;
		}
	}
	function order_feeds_by_folder() {
		usort( $this->feeds, array( $this, 'order_opml_entries' ) );
	}
	function get_feeds_by_folder( $folder ) {
		$folder_a = array();
		if ( is_array( $folder ) && ! empty( $folder[0] ) ) {
			$folder = $folder[0];
		} elseif ( is_array( $folder ) && ! empty( $folder['slug'] ) ) {
			$folder = $folder['slug'];
		}
		foreach ( $this->feeds as $feed ) {
			// var_dump($feed);
			if ( ! empty( $feed->folder ) ) {
				foreach ( $feed->folder as $feed_folder ) {
					// var_dump('folder: '.$folder);
					// var_dump($feed_folder);
					if ( ! is_object( $feed_folder ) ) {
						var_dump( 'Not an object' );
						var_dump( $feed_folder );
					}
					if ( $feed_folder->slug == $this->slugify( $folder ) ) {
						$folder_a[] = $feed;
					}
				}
			}
		}
		if ( empty( $folder_a ) ) {
			return false;
		}
		return $folder_a;
	}
	public function get_feeds_without_folder() {
		$folder_a = array();
		foreach ( $this->feeds as $feed ) {
			// var_dump($feed);
			if ( empty( $feed->folder ) ) {
				$folder_a[] = $feed;
			}
		}
		if ( empty( $folder_a ) ) {
			return false;
		}
		return $folder_a;
	}
	function get_feed_by_id( $unique_id ) {
		return $this->feeds[ $unique_id ];
	}
	function sanitize( $string, $force_lowercase = true, $anal = false ) {
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
	public function slugify( $string, $case = true, $strict = false, $spaces = false ) {
		// var_dump($string);
		if ( is_array( $string ) ) {
			$string = $string[0];
		}
		$string = strip_tags( $string );
		// replace non letter or digits by -
		$string = preg_replace( '~[^\\pL\d]+~u', '-', $string );
		if ( $spaces == false ) {
			$stringSlug = str_replace( ' ', '-', $string );
			$stringSlug = trim( $stringSlug );
			$stringSlug = str_replace( '&amp;','&', $stringSlug );
			// $charsToElim = array('?','/','\\');
			$stringSlug = $this->sanitize( $stringSlug, $case, $strict );
		} else {
			// $string = strip_tags($string);
			// $stringArray = explode(' ', $string);
			// $stringSlug = '';
			// foreach ($stringArray as $stringPart){
			// $stringSlug .= ucfirst($stringPart);
			// }
			$stringSlug = str_replace( '&amp;','&', $string );
			// $charsToElim = array('?','/','\\');
			$stringSlug = $this->sanitize( $stringSlug, $case, $strict );
		}

		$stringSlug = htmlspecialchars( $stringSlug, null, null, false );

		if ( empty( $stringSlug ) ) {
			// var_dump('probs: ' .$string); die();
			return 'empty';
		}

		return $stringSlug;
	}
}
