<?php
/**
 * OPML object.
 *
 * @package PressForward
 */

/**
 * OPML_Object class.
 */
class OPML_Object {
	/**
	 * Constructor.
	 *
	 * @param string $url URL.
	 */
	public function __construct( $url ) {
		$this->url = $url;
		$this->folders = array();
		$this->feeds = array();
	}

	/**
	 * Sets a folder.
	 *
	 * @param object $folder_obj Folder data.
	 */
	public function set_folder( $folder_obj ) {
		$folder_obj->slug = $this->slugify( $folder_obj->title );
		$this->folders[ $folder_obj->slug ] = $folder_obj;
	}

	/**
	 * Sets OPML title.
	 *
	 * @param string $title Title.
	 */
	public function set_title( $title ) {
		if ( empty( $title ) ) {
			$this->title = $this->url;
		} else {
			$this->title = (string) $title;
		}
	}

	/**
	 * Gets OPML title.
	 *
	 * @return string
	 */
	public function get_title() {
		if ( empty( $this->title ) ) {
			return $this->url;
		} else {
			return $this->title;
		}
	}

	/**
	 * Gets a folder.
	 *
	 * @param string $key Folder key.
	 * @return object
	 */
	public function get_folder( $key ) {
		$folders = $this->folders;
		$key = $this->slugify( $key );
		return $folders[ $key ];
	}

	/**
	 * Sets a feed.
	 *
	 * @param object       $feed_obj Feed object.
	 * @param string|array $folder   Optional. Associated folder(s).
	 */
	public function set_feed( $feed_obj, $folder = false ) {
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

			} elseif ( !empty( $feed_obj ) && ( empty( $feed_obj->folder ) || ! is_array( $feed_obj->folder ) ) ) {
				$feed_obj->folder = array();
			}

			if ( is_array( $folder ) ) {
				foreach ( $folder as $folder_type ) {
					$feed_obj->folder[] = $folder_type;
				}
			} else {
				$feed_obj->folder[] = $folder;
			}

			$this->feeds[ $feed_obj->id ] = $feed_obj;
		}
	}

	/**
	 * Checks for missing keys in an array.
	 *
	 * @param array $check  Array to check.
	 * @param array $keys   Keys to check for.
	 * @param bool  $strict If true, returns false on failed check.
	 * @return bool|array
	 */
	public function check_keys( $check, $keys, $strict = false ) {
		$check['missing'] = array();
		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $check, true ) ) {
				if ( $strict ) {
					return false;
				} else {
					$check[ $key ] = '';
					$check['missing'][] = $key;
				}
			}
		}
		return $array;
	}

	/**
	 * Ensures that an entry has text and a title.
	 *
	 * @param array $entry Entry data.
	 * @return array
	 */
	public function assure_title_and_text( $entry ) {
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

	/**
	 * Generates a folder object based on an entry.
	 *
	 * @param array $entry Entry data.
	 * @return object
	 */
	public function make_a_folder_obj( $entry ) {
		$folder = new \stdClass();

		$entry = (array) $entry;
		$entry = $this->check_keys( $entry, array( 'title', 'text' ) );

		$entry['title'] = ! empty( $entry['title'] ) ? $entry['title'] : false;
		$entry['text']  = ! empty( $entry['text'] ) ? $entry['text'] : false;

		$entry = $this->assure_title_and_text( $entry );

		$folder->title = $entry['title'];
		$folder->text  = $entry['text'];

		return $folder;
	}

	/**
	 * Generates a feed object based on an entry.
	 *
	 * @param array $entry Entry data.
	 * @return object
	 */
	public function make_a_feed_obj( $entry ) {
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

		$feed->title   = $entry['title'];
		$feed->text    = $entry['text'];
		$feed->type    = $entry['type'];
		$feed->xmlUrl  = str_replace( '&amp;', '&', $entry['xmlUrl'] );
		$feed->feedUrl = str_replace( '&amp;', '&', $entry['feedUrl'] );
		$feed->htmlUrl = str_replace( '&amp;', '&', $entry['htmlUrl'] );
		$feed->id      = md5( $feed->feedUrl );

		return $feed;
	}

	/**
	 * Sort callback that orders items by folder.
	 *
	 * @param object $a Item a.
	 * @param object $b Item b.
	 * @return int
	 */
	public function order_opml_entries( $a, $b ) {
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

	/**
	 * Forces feeds to be ordered by folder.
	 */
	public function order_feeds_by_folder() {
		usort( $this->feeds, array( $this, 'order_opml_entries' ) );
	}

	/**
	 * Gets feeds belonging to a folder.
	 *
	 * @param array $folder Folder data.
	 * @return array
	 */
	public function get_feeds_by_folder( $folder ) {
		$folder_a = array();

		if ( is_array( $folder ) && ! empty( $folder[0] ) ) {
			$folder = $folder[0];
		} elseif ( is_array( $folder ) && ! empty( $folder['slug'] ) ) {
			$folder = $folder['slug'];
		}

		foreach ( $this->feeds as $feed ) {
			if ( ! empty( $feed->folder ) ) {
				foreach ( $feed->folder as $feed_folder ) {
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

	/**
	 * Gets feeds that aren't in a folder.
	 *
	 * @return array
	 */
	public function get_feeds_without_folder() {
		$folder_a = array();
		foreach ( $this->feeds as $feed ) {
			if ( empty( $feed->folder ) ) {
				$folder_a[] = $feed;
			}
		}

		if ( empty( $folder_a ) ) {
			return false;
		}

		return $folder_a;
	}

	/**
	 * Gets a feed by its uid.
	 *
	 * @param int $unique_id Unique ID of the feed.
	 * @return object
	 */
	public function get_feed_by_id( $unique_id ) {
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
			return 'empty';
		}

		return $stringSlug;
	}
}
