<?php
/**
 * RSS Import module.
 *
 * @package PressForward
 */

/**
 * PF_RSS_Import class.
 */
class PF_RSS_Import extends PF_Module {
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
	 * Gets the data from an RSS feed and turns it into a data object as expected by PF.
	 *
	 * @global $pf Used to access the feed_object() method.
	 *
	 * @param object $a_feed Feed object.
	 * @return array|bool
	 */
	public function get_data_object( $a_feed ) {
		pf_log( 'Invoked: PF_RSS_Import::get_data_object()' );
		$a_feed_url = $a_feed->guid;

		pf_log( 'Getting RSS Feed at ' . $a_feed_url );
		add_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'return_cachetime' ) );
		$the_feed = pf_fetch_feed( $a_feed_url );
		remove_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'return_cachetime' ) );

		if ( empty( $the_feed ) || is_wp_error( $the_feed ) ) {
			pf_log( 'Can not use Simple Pie to retrieve the feed' );
			pf_log( $the_feed );
			$alert = $this->set_to_alert( $a_feed->ID, $the_feed );
			pf_log( 'Set to alert resulted in:' );
			pf_log( $alert );
			if ( false === $alert ) {
				return false;
			} else {
				return false;
			}
		} else {
			$error_to_alert = get_option( PF_SLUG . '_errors_until_alert', 3 );
			$error_count    = pressforward( 'controller.metas' )->update_pf_meta( $a_feed->ID, PF_SLUG . '_feed_error_count', 0 );
		}

		$the_feed->set_timeout( 60 );

		$rss_object = array();

		$c = 0;

		pf_log( 'Begin processing the feed.' );

		foreach ( $the_feed->get_items() as $item ) {
			pf_log( 'Feed looping through for the ' . $c . ' time.' );
			$check_date = $item->get_date( 'U' );
			$dead_date  = time() - ( 60 * 60 * 24 * 60 ); // Get the unixdate for two months ago.
			$dead_date  = apply_filters( 'pf_rss_ingestion_retrieve_after_date', $dead_date, $the_feed, $item );
			if ( ( $check_date <= $dead_date ) && ! empty( $check_date ) ) {
				pf_log( 'Feed item too old. Skip it.' );
			} else {
				$is_permalink = false;
				$guid         = $item->get_item_tags( '', 'guid' );
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

				$id = pressforward_create_feed_item_id( $item_link, $item->get_title() );
				pf_log( 'Now on feed ID ' . $id . '.' );

				if ( empty( $check_date ) ) {
					$r_item_date   = gmdate( 'r' );
					$ymd_item_date = gmdate( 'Y-m-d' );
				} else {
					$r_item_date   = $item->get_date( 'r' );
					$ymd_item_date = $item->get_date( 'Y-m-d' );
				}
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

				$item_content = $item->get_content();

				$i_feed = $item->get_feed();

				if ( ! $ag_status ) {
					$authors = $this->get_rss_authors( $item );
				} else {
					$parent_value = pressforward( 'controller.metas' )->get_post_pf_meta( $a_feed->ID, 'pf_feed_default_author', true );
					if ( ! empty( $parent_value ) ) {
						$authors = $parent_value;
					} else {
						$authors = 'aggregation';
					}
				}

				$item_categories = array();
				$item_categories = $item->get_categories();
				$item_terms      = array();

				if ( ! empty( $item_categories ) ) {
					foreach ( $item_categories as $item_category ) {
						$item_terms[] = $item_category->get_term();
					}
					$item_categories_string = implode( ',', $item_terms );
				} else {
					$item_categories_string = '';
				}

				// One final cleanup of the content.
				$content_obj  = pressforward( 'library.htmlchecker' );
				$item_content = $content_obj->closetags( $item_content );
				$item_content = pressforward( 'controller.readability' )->process_in_oembeds( $item->get_link(), $item_content );
				pf_log( 'get_links' );
				pf_log( $item->get_links() );
				pf_log( $item->get_permalink() );

				// GUID.
				pf_log( $item->get_id() );

				$rss_object[ 'rss_' . $c ] = pf_feed_object(
					$item->get_title(),
					$i_feed->get_title(),
					$r_item_date,
					$authors,
					$item_content,
					$item_link, // New proper link (hopefully).
					'',
					$id,
					$ymd_item_date,
					$item_categories_string
				);
				pf_log( 'Setting new object for ' . $item->get_title() . ' of ' . $i_feed->get_title() . '.' );

			}

			++$c;
			// What the hell RSS feed? This is just ridiculous.
			if ( $c > 300 ) {
				break;
			}
		}

		return $rss_object;
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
		return $permitted_tabs;
	}

	/**
	 * Generates markup for adding item to feeder.
	 */
	public function add_to_feeder() {
		wp_enqueue_media();
		wp_enqueue_script( 'pf-add-feeds' );

		$feedlist = get_option( PF_SLUG . '_feedlist' );

		// Check to see whether OPML uploads are allowed.
		$opml_is_allowed    = false;
		$allowed_mime_types = get_allowed_mime_types();
		foreach ( $allowed_mime_types as $ext => $mime ) {
			$exts = explode( '|', $ext );
			if ( in_array( 'opml', $exts, true ) ) {
				$opml_is_allowed = true;
				break;
			}
		}

		?>
		<div class="pf-opt-group">
			<div class="rss-box ">
				<h3 class="hndle"><span><?php esc_html_e( 'Subscribe to Feeds', 'pressforward' ); ?></span></h3>
				<div class="inside">
					<div><?php esc_html_e( 'Add Single Feed', 'pressforward' ); ?> <?php esc_html_e( '(RSS or Atom)', 'pressforward' ); ?></div>

					<div class="pf_feeder_input_box">
						<input id="<?php echo esc_attr( PF_SLUG ) . '_feedlist[single]'; ?>" class="regular-text pf_primary_media_opml_url" type="text" name="<?php echo esc_attr( PF_SLUG ) . '_feedlist[single]'; ?>" value="" />
						<label class="description" for="<?php echo esc_attr( PF_SLUG ) . '_feedlist[single]'; ?>"><?php esc_html_e( '*Complete URL path', 'pressforward' ); ?></label>
						<a href="http://en.wikipedia.org/wiki/RSS"><?php esc_html_e( 'What is an RSS Feed?', 'pressforward' ); ?></a>
					</div>

					<div><?php esc_html_e( 'Add OPML File', 'pressforward' ); ?></div>
					<div class="pf_feeder_input_box">
						<input id="<?php echo esc_attr( PF_SLUG ) . '_feedlist[opml]'; ?>" class="pf_opml_file_upload_field regular-text" type="text" name="<?php echo esc_attr( PF_SLUG ) . '_feedlist[opml]'; ?>" value="" />
						<label class="description" for="<?php echo esc_attr( PF_SLUG ) . '_feedlist[opml]'; ?>"><?php esc_html_e( '*Drop link to OPML here.', 'pressforward' ); ?></label>

						<?php if ( $opml_is_allowed ) : ?>
							or <a class="button-primary pf_primary_media_opml_upload" ><?php esc_html_e( 'Upload OPML file', 'pressforward' ); ?></a>
						<?php endif; ?>

						<p>&nbsp;<?php esc_html_e( 'Adding large OPML files may take some time.', 'pressforward' ); ?></p>
						<a href="http://en.wikipedia.org/wiki/Opml"><?php esc_html_e( 'What is an OPML file?', 'pressforward' ); ?></a>
					</div>

					<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Submit', 'pressforward' ); ?>" />
				</div>
			</div>
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

		pf_log( 'Add Feed Process Invoked: PF_RSS_IMPORT::pf_feedlist_validate' );
		pf_log( $input );

		if ( current_user_can( 'edit_posts' ) ) {
			pf_log( 'Yes, the current user can edit posts.' );
		} else {
			pf_log( 'No, the current user can not edit posts.' );
		}

		$feed_obj        = pressforward( 'schema.feeds' );
		$subed           = array();
		$something_broke = false;

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
			add_settings_error( 'add_pf_feeds', 'pf_feeds_validation_response', __( 'You have submitted ', 'pressforward' ) . $subscribe_string . ' ' . __( 'The feed was not found.', 'pressforward' ), 'updated' );
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
	 * Registers RSS Import settings with WP Settings API.
	 */
	public function register_settings() {
		register_setting( PF_SLUG . '_feedlist_group', PF_SLUG . '_feedlist', array( 'PF_RSS_Import', 'pf_feedlist_validate' ) );
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
}
