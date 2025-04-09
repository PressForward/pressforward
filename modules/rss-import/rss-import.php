<?php
/**
 * RSS Import module.
 *
 * @package PressForward
 */

use PressForward\Interfaces\FeedSource;

/**
 * PF_RSS_Import class.
 */
class PF_RSS_Import extends PF_Module implements FeedSource {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->feed_type = 'rss';
		parent::start();

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Default settings for RSS Import module.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return array(
			'name'        => __( 'RSS Import Module', 'pressforward' ),
			'slug'        => 'rss-import',
			'description' => __( 'This module provides the core functionality to read RSS feeds and translate them into PressForward\'s system.', 'pressforward' ),
			'thumbnail'   => '',
			'options'     => '',
		);
	}

	/**
	 * Run any setup that has to happen after initial module registration.
	 */
	public function post_setup_module_info() {
		$this->includes();
	}

	/**
	 * Includes necessary files.
	 */
	public function includes() {}

	/**
	 * Used to return a value in seconds that WordPress should be caching feeds
	 * before retrieval.
	 *
	 * Filters to 'wp_feed_cache_transient_lifetime'.
	 *
	 * @param int $seconds Number of seconds.
	 * @return int
	 */
	public function return_cachetime( $seconds ) {
		return 1800;
	}

	/**
	 * Set up alert.
	 *
	 * @param int    $id      ID.
	 * @param object $the_feed Feed object.
	 */
	public function set_to_alert( $id, $the_feed ) {
		$immune_to_alert_check = pressforward( 'controller.metas' )->get_post_pf_meta( $id, 'pf_no_feed_alert', true );
		if ( 1 === (int) $immune_to_alert_check ) {
			return false;
		}

		$error_to_alert = get_option( PF_SLUG . '_errors_until_alert', 3 );
		$error_count    = pressforward( 'controller.metas' )->get_post_pf_meta( $id, PF_SLUG . '_feed_error_count' );

		if ( ! is_numeric( $error_count ) || empty( $error_count ) ) {
			$error_count = 0;
		}

		if ( $error_count >= $error_to_alert ) {
			pressforward( 'library.alertbox' )->switch_post_type( $id );
			pressforward( 'library.alertbox' )->add_bug_type_to_post( $id, __( 'Broken RSS feed.', 'pressforward' ) );
			$post_obj      = get_post( $id );
			$old_content   = $post_obj->post_content;
			$update_result = pressforward( 'controller.metas' )->update_pf_meta( $id, PF_SLUG . '_feed_error_count', 0 );
			if ( is_wp_error( $the_feed ) ) {
				$argup = array(
					'ID'           => $id,
					'post_content' => $old_content . ' <p>' . $the_feed->get_error_message() . '</p>',
				);

				$result = wp_update_post( $argup );
			}
			return true;
		} else {
			$update_result = pressforward( 'controller.metas' )->update_pf_meta( $id, PF_SLUG . '_feed_error_count', $error_count + 1 );
			return $update_result;
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param \PressForward\Core\Models\Feed $feed Feed object.
	 * @return \WP_Error|array
	 */
	public function fetch( $feed ) {
		$url = $feed->get( 'remote_feed_url' );
		pf_log( 'Getting RSS Feed at ' . $url );

		add_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'return_cachetime' ) );
		$simplepie_feed = pf_fetch_feed( $url );
		remove_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'return_cachetime' ) );

		if ( empty( $simplepie_feed ) || is_wp_error( $simplepie_feed ) ) {
			return new WP_Error( 'feed_error', 'Could not fetch feed.', $simplepie_feed );
		}

		$simplepie_feed->set_timeout( 60 );

		return $this->process_feed_items( $simplepie_feed, $feed );
	}

	/**
	 * Processes feed items.
	 *
	 * @param \SimplePie                     $simplepie_feed SimplePie feed object.
	 * @param \PressForward\Core\Models\Feed $feed           Feed object.
	 */
	public function process_feed_items( \SimplePie $simplepie_feed, \PressForward\Core\Models\Feed $feed ) {
		$retval = [];
		$c      = 0;

		foreach ( $simplepie_feed->get_items() as $item ) {
			$check_date = $item->get_date( 'U' );
			$dead_date  = time() - ( 60 * 60 * 24 * 60 ); // Get the unixdate for two months ago.
			/**
			 * Filter to set the date for checking if the item is too old.
			 *
			 * @param int $dead_date The date to check against.
			 * @param object $simplepie_feed The SimplePie feed object.
			 * @param object $item The item object.
			 */
			$dead_date = apply_filters( 'pf_rss_ingestion_retrieve_after_date', $dead_date, $simplepie_feed, $item );

			if ( ( $check_date <= $dead_date ) && ! empty( $check_date ) ) {
				pf_log( 'Feed item too old. Skip it.' );
				continue;
			}

			$item_link = $this->determine_item_link( $item );

			$id = pressforward_create_feed_item_id( $item_link, $item->get_title() );
			pf_log( 'Now on feed ID ' . $id . '.' );

			list( $r_item_date, $ymd_item_date ) = $this->determine_item_dates( $item );

			$authors = $this->determine_item_authors( $item, $feed );

			$item_content = $item->get_content();

			$i_feed = $item->get_feed();

			$item_categories_string = $this->determine_item_categories( $item, $feed );

			// One final cleanup of the content.
			$content_obj  = pressforward( 'library.htmlchecker' );
			$item_content = $content_obj->closetags( $item_content );
			$item_content = pressforward( 'controller.readability' )->process_in_oembeds( $item->get_link(), $item_content );
			pf_log( 'get_links' );
			pf_log( $item->get_links() );
			pf_log( $item->get_permalink() );

			// GUID.
			pf_log( $item->get_id() );

			$retval[ 'rss_' . $c ] = pf_feed_object(
				[
					'item_title'   => $item->get_title(),
					'source_title' => $i_feed->get_title(),
					'item_date'    => $r_item_date,
					'item_author'  => $authors,
					'item_content' => $item_content,
					'item_link'    => $item_link, // New proper link (hopefully).
					'item_id'      => $id,
					'item_wp_date' => $ymd_item_date,
					'item_tags'    => $item_categories_string,
					'description'  => $item->get_description(),
				]
			);

			pf_log( 'Setting new object for ' . $item->get_title() . ' of ' . $i_feed->get_title() . '.' );

			++$c;
			// What the hell RSS feed? This is just ridiculous.
			if ( $c > 300 ) {
				break;
			}
		}

		return $retval;
	}

	/**
	 * Gets feed items.
	 *
	 * @param \PressForward\Core\Models\Feed $feed Feed object.
	 * @return array
	 */
	public function get_feed_items( \PressForward\Core\Models\Feed $feed ) {
		$fetched = $this->fetch( $feed );

		return $fetched;
	}

	/**
	 * Determines the item link.
	 *
	 * @param object $item Item object.
	 * @return string
	 */
	public function determine_item_link( $item ) {
		$item_link    = '';
		$is_permalink = false;

		$guid = $item->get_item_tags( '', 'guid' );
		if ( is_array( $guid ) ) {
			$arr_it = new \RecursiveIteratorIterator( new \RecursiveArrayIterator( $guid[0] ) );
			foreach ( $arr_it as $sub ) {
				$sub_array = $arr_it->getSubIterator();
				if ( isset( $sub_array['isPermaLink'] ) && 'false' === $sub_array['isPermaLink'] ) {
					$is_permalink = false;
					break;
				} elseif ( isset( $sub_array['isPermaLink'] ) && $sub_array['isPermaLink'] && ( 'true' === $sub_array['isPermaLink'] ) ) {
					$is_permalink = true;
					break;
				}
			}
		}

		if ( $is_permalink ) {
			// This will check GUID first, then link, then title.
			$guid_hopefully = $item->get_id( false );
			$url_parts      = wp_parse_url( $guid_hopefully );
			if ( ! $url_parts || ( 'http' !== $url_parts['scheme'] && 'https' !== $url_parts['scheme'] ) ) {
				$item_link = $item->get_link();
			} else {
				$item_link = $guid_hopefully;
			}
		} else {
			$item_link = $item->get_link();
		}

		return $item_link;
	}

	/**
	 * Determines an items date.
	 *
	 * @param object $item Item object.
	 * @return array
	 */
	public function determine_item_dates( $item ) {
		$check_date = $item->get_date( 'U' );

		if ( empty( $check_date ) ) {
			$r_item_date   = gmdate( 'r' );
			$ymd_item_date = gmdate( 'Y-m-d' );
		} else {
			$r_item_date   = $item->get_date( 'r' );
			$ymd_item_date = $item->get_date( 'Y-m-d' );
		}

		return [ $r_item_date, $ymd_item_date ];
	}

	/**
	 * Determines an item's authors.
	 *
	 * @param object                         $item Item object.
	 * @param \PressForward\Core\Models\Feed $feed Parent feed object.
	 * @return string
	 */
	public function determine_item_authors( $item, \PressForward\Core\Models\Feed $feed ) {
		$authors = __( 'No author.', 'pressforward' );

		if ( $item->get_source() ) {
			$source_obj = $item->get_source();

			// Get the link of what created the RSS entry.
			$source = $source_obj->get_link( 0, 'alternate' );

			// Check if the feed item creator is an aggregator.
			$ag_status = $this->is_from_aggregator( $source );
		} else {
			// If we can't get source information then don't do anything.
			$ag_status = false;
		}

		// If there is less than 160 characters of content, than it isn't really giving us meaningful information.
		// So we'll want to get the good stuff from the source.
		if ( ( strlen( $item->get_content() ) ) < 160 ) {
			$ag_status = true;
		}

		if ( ! $ag_status ) {
			$authors = $this->get_rss_authors( $item );

			if ( __( 'No author.', 'pressforward' ) === $authors ) {
				// See if the parent feed has an author.
				$feed_author = $feed->get_feed_author();
				if ( ! empty( $feed_author ) ) {
					$authors = $feed_author;
				}
			}
		} else {
			$parent_value = $feed->get_default_author();
			if ( ! empty( $parent_value ) ) {
				$authors = $parent_value;
			} else {
				$authors = 'aggregation';
			}
		}

		return $authors;
	}

	/**
	 * Determines the categories string for an item.
	 *
	 * @param object                         $item Item object.
	 * @param \PressForward\Core\Models\Feed $feed Parent feed object.
	 * @return string
	 */
	public function determine_item_categories( $item, \PressForward\Core\Models\Feed $feed ) {
		$import_item_categories = $feed->get_do_import_tags();

		$item_categories = [];
		if ( $import_item_categories ) {
			$item_categories = $item->get_categories();
		}

		$item_terms = array();

		if ( ! empty( $item_categories ) ) {
			foreach ( $item_categories as $item_category ) {
				$item_terms[] = $item_category->get_term();
			}
			$item_categories_string = implode( ',', $item_terms );
		} else {
			$item_categories_string = '';
		}

		return $item_categories_string;
	}

	/**
	 * Gets the data from an RSS feed and turns it into a data object as expected by PF.
	 *
	 * @global $pf Used to access the feed_object() method.
	 *
	 * @param object $a_feed Feed object.
	 * @return array|bool
	 */
	public function get_data_object( $a_feed ) {
		pf_log( 'Invoked: PF_RSS_Import::get_data_object()' );

		$fetched = $this->fetch( $a_feed->guid );

		if ( is_wp_error( $fetched ) ) {
			pf_log( 'Can not use Simple Pie to retrieve the feed' );
			pf_log( $a_feed );
			$alert = $this->set_to_alert( $a_feed->ID, $fetched );
			pf_log( 'Set to alert resulted in:' );
			pf_log( $alert );
			if ( false === $alert ) {
				return false;
			} else {
				return false;
			}
		}

		$error_to_alert = get_option( PF_SLUG . '_errors_until_alert', 3 );
		$error_count    = pressforward( 'controller.metas' )->update_pf_meta( $a_feed->ID, PF_SLUG . '_feed_error_count', 0 );

		return [];
	}

	/**
	 * Tries to get the RSS item author for the meta.
	 *
	 * @param object $item Item object.
	 * @return string
	 */
	public function get_rss_authors( $item ) {
		// This needs error checking.
		$author_array = ( $item->get_authors() );
		if ( ! empty( $author_array ) ) {
			$name_array = [];
			foreach ( $author_array as $author ) {
				$name_array[] = $author->get_name();
			}

			// Remove empties.
			$name_array = array_filter( $name_array );

			$authors = implode( ', ', $name_array );
			$authors = $authors;

		} else {
			$authors = __( 'No author.', 'pressforward' );
		}

		return $authors;
	}

	/**
	 * Not used.
	 */
	public function pf_add_media_uploader_scripts() {}

	/**
	 * Registers permitted tabs for RSS Import module.
	 *
	 * @param array $permitted_tabs Tabs.
	 * @return array
	 */
	public function set_permitted_feeds_tabs( $permitted_tabs ) {
		$permitted_tabs['primary_feed_type'] = array(
			'title' => __( 'Subscribe to Feeds', 'pressforward' ),
			'cap'   => get_option( 'pf_menu_feeder_access', pf_get_defining_capability_by_role( 'editor' ) ),
		);

		$permitted_tabs['opml_import'] = array(
			'title' => __( 'Import OPML', 'pressforward' ),
			'cap'   => get_option( 'pf_menu_feeder_access', pf_get_defining_capability_by_role( 'editor' ) ),
		);

		return $permitted_tabs;
	}

	/**
	 * Generates markup for adding item to feeder.
	 */
	public function add_to_feeder() {
		wp_enqueue_media();
		wp_enqueue_script( 'pf-add-feeds' );
		wp_enqueue_style( 'pf-add-feeds' );

		$feedlist = get_option( PF_SLUG . '_feedlist' );

		?>
		<p>
			<?php esc_html_e( 'Enter the URL of an RSS feed. PressForward will watch this feed for updates, and will automatically import newly published items into the Feed Items tool.', 'pressforward' ); ?>
		</p>

		<p>
			<?php echo wp_kses_post( '<a href="https://en.wikipedia.org/wiki/RSS">Learn more about RSS</a>.' ); ?>
		</p>

		<div class="pf_feeder_input_box">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="pf_feedlist-single"><?php esc_html_e( 'Feed URL', 'pressforward' ); ?></label>
					</th>

					<td>
						<input id="pf_feedlist-single" class="regular-text pf_primary_media_opml_url" type="text" name="pf_feedlist[single]" value="" />

						<input id="rss-submit-button" type="submit" class="button-secondary" value="<?php esc_attr_e( 'Submit', 'pressforward' ); ?>" />

						<div class="feed-validation-message" id="feed-validation-message"></div>

						<input type="submit" id="rss-submit-button-js" class="rss-submit-button-js button-primary" value="<?php esc_attr_e( 'Subscribe to Feed', 'pressforward' ); ?>" />
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Validates a feedlist input.
	 *
	 * @param array $input Input array.
	 */
	public static function pf_feedlist_validate( $input ) {
		set_time_limit( 0 );

		$something_broke = false;
		$settings_error  = '';

		// Special case: an OPML file is being uploaded.
		// Cannot be handled natively by the Settings API.
		if ( ! empty( $_FILES['opml-upload']['name'] ) ) {
			// Process and save the OPML file, then pass the URL as $input['opml'].
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$opml_url = self::process_opml_upload( $_FILES['opml-upload'] );
			if ( $opml_url ) {
				$input['opml'] = $opml_url;
			} else {
				$something_broke = true;
				$settings_error  = __( 'You have provided an invalid OPML file.', 'pressforward' );
			}
		}

		pf_log( 'Add Feed Process Invoked: PF_RSS_IMPORT::pf_feedlist_validate' );
		pf_log( $input );

		if ( current_user_can( 'edit_posts' ) ) {
			pf_log( 'Yes, the current user can edit posts.' );
		} else {
			pf_log( 'No, the current user can not edit posts.' );
		}

		$feed_obj = pressforward( 'schema.feeds' );
		$subed    = array();

		if ( ! empty( $input['single'] ) ) {
			if ( ! $feed_obj->has_feed( $input['single'] ) ) {
				pf_log( 'The feed does not already exist.' );
				$check = $feed_obj->create(
					$input['single'],
					array(
						'type'         => 'rss',
						'module_added' => get_class(),
					)
				);

				if ( is_wp_error( $check ) || ! $check ) {
					pf_log( 'The feed did not enter the database.' );
					$something_broke = true;
					$description     = 'Feed failed initial attempt to add to database | ' . $check->get_error_message();
					$broken_id       = $feed_obj->create(
						$input['single'],
						array(
							'type'         => 'rss',
							'description'  => $description,
							'module_added' => get_called_class(),
						)
					);
					pressforward( 'library.alertbox' )->switch_post_type( $broken_id );
					pressforward( 'library.alertbox' )->add_bug_type_to_post( $broken_id, 'Broken feed.' );
				}
			} else {
				pf_log( 'The feed already exists, sending it to update.' );
				$check = $feed_obj->update_url( $input['single'] );
				pf_log( 'Our attempt to update resulted in:' );
				pf_log( $check );
			}

			$subed[] = 'a feed.';
		}

		if ( ! empty( $input['opml'] ) ) {
			self::process_opml( $input['opml'] );
			$subed[] = 'an OPML file.';
		}

		if ( ! empty( $input['opml_uploader'] ) ) {
			pf_log( 'Attempting to upload on OPML file.' );
			$keys = array_keys( $_FILES );
			$i    = 0;

			foreach ( $_FILES as $ofile ) {
				// if a files was upload.
				if ( $ofile['size'] ) {
					// if it is an OPML.
					// Pattern from http://www.sitepoint.com/wordpress-options-panel/ and http://codex.wordpress.org/Function_Reference/wp_handle_upload.
					if ( preg_match( '/(opml|xml)$/', $ofile['type'] ) ) {
						$override = array( 'test_form' => false );
						// save the file, and store an array, containing its location in $file.
						$file = wp_handle_upload( $ofile, $override );
						pf_log( 'File upload resulted in:' );
						pf_log( $file );
						self::process_opml( $file['url'] );
					} else {
						// Not an image.
						// Die and let the user know that they made a mistake.
						wp_die( 'No OPML file was uploaded.' );
					}
				}

				++$i;
			}

			$subed[] = 'an OPML uploaded file.';
		}

		$subscribe_string = '';

		if ( ( 1 === count( $subed ) ) && ! empty( $check ) ) {
			$edit_link = get_edit_post_link( $check );
			$subed[99] = " <a href=\"$edit_link\" target=\"_blank\">" . __( 'Edit.', 'pressforward' ) . '</a>';
		}

		foreach ( $subed as $sub ) {
			$subscribe_string .= $sub;
		}

		if ( $something_broke ) {
			if ( empty( $settings_error ) ) {
				$settings_error = sprintf(
					// translators: %s is the name of feed that failed to add.
					__( 'An error occurred while trying to add %s', 'pressforward' ),
					$subscribe_string
				);
			}

			add_settings_error(
				'add_pf_feeds',
				'pf_feeds_validation_response',
				$settings_error,
				'error'
			);
		} elseif ( ! empty( $subscribe_string ) ) {
			add_settings_error( 'add_pf_feeds', 'pf_feeds_validation_response', __( 'You have submitted ', 'pressforward' ) . $subscribe_string, 'updated' );
		}

		return $input;
	}

	/**
	 * Processes an OPML file.
	 *
	 * @param string $opml OPML URL.
	 */
	public static function process_opml( $opml ) {
		$opml_reader = new OPML_reader( $opml );
		$opml_obj    = $opml_reader->get_OPML_obj();
		$opml_array  = $opml_obj->feeds;

		foreach ( $opml_array as $key => $feed_obj ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$feed_xml = $feed_obj->feedUrl;
			$args     = array(
				'title'        => $feed_obj->title,
				'description'  => $feed_obj->text,
				'tags'         => array(),
				'feed_folders' => null,
			);

			$folder_names = wp_list_pluck( $feed_obj->folder, 'title' );
			if ( ! empty( $folder_names ) ) {
				$args['feed_folders'] = $folder_names;
			}

			$created = pressforward( 'schema.feeds' )->create( $feed_xml, $args );
		}
	}

	/**
	 * Processes an OPML upload.
	 *
	 * @param array $file File array.
	 * @return string
	 */
	public static function process_opml_upload( $file ) {
		$override = array( 'test_form' => false );
		$file     = wp_handle_upload( $file, $override );

		// Verify that it's an OPML file.
		$opml_reader   = new OPML_reader();
		$opml_contents = file_get_contents( $file['file'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$opml_reader->build_from_string( $opml_contents );

		if ( ! empty( $opml_reader->opml_file ) ) {
			return $file['url'];
		}

		return '';
	}

	/**
	 * Registers RSS Import settings with WP Settings API.
	 */
	public function register_settings() {
		register_setting(
			'pf_feedlist_group',
			'pf_feedlist',
			[ 'PF_RSS_Import', 'pf_feedlist_validate' ],
		);
	}

	/**
	 * Checks whether a URL is from an aggregator.
	 *
	 * @param string $xmlbase URL.
	 * @return bool
	 */
	public function is_from_aggregator( $xmlbase ) {
		$c = 0;

		$url_parts = wp_parse_url( $xmlbase );

		$aggregators = array(
			'tweetedtimes',
			'tweetedtimes.com',
			'www.tweetedtimes.com',
			'pipes.yahoo.com',
		);

		foreach ( $aggregators as $aggregator ) {
			if ( in_array( $aggregator, $url_parts, true ) ) {
				++$c;
			}
		}

		if ( $c > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Performs health check.
	 *
	 * @param string $url URL.
	 * @param bool  $is_new_feed Is new feed.
	 * @return void
	 */
	public function health_check( string $url, $is_new_feed = false ) {
		$feed_urls_to_test = [
			$feed_url,
			trailingslashit( $feed_url ) . 'rss/',
			trailingslashit( $feed_url ) . 'rss/index.xml',
		];

		$feed_is_valid = false;
		while ( ! $feed_is_valid && ! empty( $feed_urls_to_test ) ) {
			$feed_url = array_shift( $feed_urls_to_test );
			$the_feed = pf_fetch_feed( $feed_url );
			if ( ! is_wp_error( $the_feed ) ) {
				$feed_is_valid = true;
			}
		}

		$alert_box = pressforward( 'library.alertbox' );
		if ( ! $feed_is_valid ) {
			if ( $alert_box ) {
				$alert_box->switch_post_type( $this->get( 'id' ) );
				$alert_box->add_bug_type_to_post( $this->get( 'id' ), __( 'Broken RSS feed.', 'pressforward' ) );
			}
			return;
		}

		if ( $alert_box ) {
			$alert_box->dismiss_alert( $this->get( 'id' ) );
		}

		if ( $is_new_feed ) {
			$this->set( 'title', $the_feed->get_title() );
			$this->set( 'description', $the_feed->get_description() );
			$this->set( 'htmlUrl', $the_feed->get_link( 0 ) );

			$author      = $the_feed->get_author();
			$author_name = method_exists( $author, 'get_name' ) ? $author->get_name() : '';
			$this->set( 'feed_author', $author_name );

			$this->set( 'thumbnail', $the_feed->get_image_url() );

			$this->save();
		}
	}


}
