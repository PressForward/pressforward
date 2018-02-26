<?php

/**
 * Test of module base class
 */

// define( 'FEED_LOG', PF_ROOT . "/modules/rss-import/rss-import.txt" );
class PF_RSS_Import extends PF_Module {

	//
	// PARENT OVERRIDE METHODS //
	//
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->feed_type = 'rss';
		parent::start();

		// self::check_nonce = wp_create_nonce('retrieve-pressforward');
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// add_action( 'pf_do_pf-add-feeds_tab_primary_feed_type', array($this, 'add_to_feeder') );
		if ( is_admin() ) {
			add_action( 'wp_ajax_nopriv_remove_a_feed', array( $this, 'remove_a_feed' ) );
			add_action( 'wp_ajax_remove_a_feed', array( $this, 'remove_a_feed' ) );

		}
	}

	function module_setup() {
		$mod_settings = array(
			'name' 			=> 'RSS Import Module',
			'slug' 			=> 'rss-import',
			'description' 	=> 'This module provides the core functionality to read RSS feeds and translate them into PressForward\'s system.',
			'thumbnail' 	=> '',
			'options' 		=> '',
		);

		update_option( PF_SLUG . '_' . $this->id . '_settings', $mod_settings );

		// return $test;
	}


	/**
	 * Run any setup that has to happen after initial module registration
	 */
	public function post_setup_module_info() {
		$this->includes();
	}

	/**
	 * Includes necessary files
	 */
	public function includes() {

	}

	/**
	 * Used to return a value in seconds that WordPress should be caching feeds
	 * before retrieval.
	 *
	 * Filters to 'wp_feed_cache_transient_lifetime'
	 */

	public function return_cachetime( $seconds ) {

	 	return 1800;
	}

	function set_to_alert( $id, $theFeed ) {
		$immune_to_alert_check = pressforward( 'controller.metas' )->get_post_pf_meta( $id, 'pf_no_feed_alert', true );
		if ( 1 == $immune_to_alert_check ) {
			return false;
		}
		$error_to_alert = get_option( PF_SLUG . '_errors_until_alert', 3 );
		$error_count = pressforward( 'controller.metas' )->get_post_pf_meta( $id, PF_SLUG . '_feed_error_count' );
		if ( ( ! is_numeric( $error_count )) || ('' == $error_count) ) { $error_count = 0; }
		if ( $error_count >= $error_to_alert ) {
			pressforward( 'library.alertbox' )->switch_post_type( $id );
			pressforward( 'library.alertbox' )->add_bug_type_to_post( $id, __( 'Broken RSS feed.', 'pf' ) );
			$post_obj = get_post( $id );
			$old_content = $post_obj->post_content;
			$update_result = pressforward( 'controller.metas' )->update_pf_meta( $id, PF_SLUG . '_feed_error_count', 0 );
			if ( is_wp_error( $theFeed ) ) {
				$argup = array(
					'ID'			=> $id,
					'post_content'	=> $old_content . ' <p>' . $theFeed->get_error_message() . '</p>',
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
	 * Gets the data from an RSS feed and turns it into a data object
	 * as expected by PF
	 *
	 * @global $pf Used to access the feed_object() method
	 */
	public function get_data_object( $aFeed ) {
		pf_log( 'Invoked: PF_RSS_Import::get_data_object()' );
		$aFeed_url = $aFeed->guid;
		// $aFeed_id = $aFeed->ID;
		// $aFeed_url = pressforward('controller.metas')->get_post_pf_meta($aFeed_id, 'feedUrl', true);
		// if(empty($aFeed_url) || is_wp_error($aFeed_url) || !$aFeed_url){
		// $aFeed_url = $aFeed->post_title;
		// update_post_meta($aFeed_id, 'feedUrl', $aFeed_url);
		// }
		pf_log( 'Getting RSS Feed at ' . $aFeed_url );
		add_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'return_cachetime' ) );
		$theFeed = pf_fetch_feed( $aFeed_url );
		remove_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'return_cachetime' ) );
		// pf_log( 'Getting RSS Feed at '.$aFeed_url );
		if ( ! $theFeed || empty( $theFeed ) || is_wp_error( $theFeed ) ) {
			pf_log( 'Can not use Simple Pie to retrieve the feed' );
			pf_log( $theFeed );
			$alert = $this->set_to_alert( $aFeed->ID, $theFeed );
			pf_log( 'Set to alert resulted in:' );
			pf_log( $alert );
			if ( false === $alert ) {
				return false;
			} else {
				return false;
			}
		} else {
			$error_to_alert = get_option( PF_SLUG . '_errors_until_alert', 3 );
			$error_count = pressforward( 'controller.metas' )->update_pf_meta( $aFeed->ID, PF_SLUG . '_feed_error_count', 0 );
		}
		$theFeed->set_timeout( 60 );
		$rssObject = array();
		// $rssObject['parent_feed_id'] = $aFeed->ID;
		$c = 0;
		pf_log( 'Begin processing the feed.' );

		foreach ( $theFeed->get_items() as $item ) {
			pf_log( 'Feed looping through for the ' . $c . ' time.' );
			$check_date = $item->get_date( 'U' );
			$dead_date = time() - (60 * 60 * 24 * 60); // Get the unixdate for two months ago.
			$dead_date = apply_filters( 'pf_rss_ingestion_retrieve_after_date', $dead_date, $theFeed, $item );
			if ( ($check_date <= $dead_date) && ! empty( $check_date ) ) {
				pf_log( 'Feed item too old. Skip it.' );
			} else {
				$guid = $item->get_item_tags('','guid');
				$isPermalink = false;
				$arrIt = new RecursiveIteratorIterator(new RecursiveArrayIterator($guid[0]));
				foreach ($arrIt as $sub) {
					$subArray = $arrIt->getSubIterator();
					if (isset($subArray['isPermaLink']) && $subArray['isPermaLink'] == "false") {
						$isPermalink = false;
						break;
					} else if ($subArray['isPermaLink'] && ($subArray['isPermaLink'] == "true")){
						$isPermalink = true;
						break;
					}
				}
				if ($isPermalink){
					// This will check GUID first, then link, then title.
					$guidHopefully = $item->get_id(false);
					$urlParts = parse_url($guidHopefully);
					if (false == $urlParts || (($urlParts['scheme'] !== 'http') && ($urlParts['scheme'] !== 'https')) ){
						$item_link = $item->get_link();
					} else {
						$item_link = $guidHopefully;
					}
				} else {
					$item_link = $item->get_link();
				}
				$id = create_feed_item_id( $item_link, $item->get_title() ); // die();
				pf_log( 'Now on feed ID ' . $id . '.' );
				// print_r($item_categories_string); die();
				if ( empty( $check_date ) ) {
					$r_item_date = date( 'r' );
					$ymd_item_date = date( 'Y-m-d' );
				} else {
					$r_item_date = $item->get_date( 'r' );
					$ymd_item_date = $item->get_date( 'Y-m-d' );
				}
				if ( $item->get_source() ) {
					$sourceObj = $item->get_source();
					// Get the link of what created the RSS entry.
					$source = $sourceObj->get_link( 0,'alternate' );
					// Check if the feed item creator is an aggregator.
					$agStatus = $this->is_from_aggregator( $source );
				} else {
					// If we can't get source information then don't do anything.
					$agStatus = false;
				}
					// If there is less than 160 characters of content, than it isn't really giving us meaningful information.
					// So we'll want to get the good stuff from the source.
				if ( (strlen( $item->get_content() )) < 160 ) {
					$agStatus = true;
				}
					// override switch while rest is not working.
					// $agStatus = false;
					// This is where we switch off auto readability
					// And leave it to an AJAX function instead.
				// if ($agStatus){
				// # Get the origin post link.
				// $realLink = $item->get_link();
				// # Try and get the actual content of the post.
				// $realContent = $pf->get_content_through_aggregator($realLink);
				// # If we can't get the actual content, then just use what we've got from the RSS feed.
				// if (!$realContent){
							$item_content = $item->get_content();
				// } else {
				// $item_content = $realContent;
							// print_r($realContent);
				// }
				// } else {
				// $item_content = $item->get_content();
				// }
					$iFeed = $item->get_feed();
				if ( ! $agStatus ) {
					$authors = $this->get_rss_authors( $item );
				} else {
					$authors = 'aggregation';
				}
					$item_categories = array();
					$item_categories = $item->get_categories();
					$itemTerms = array();
				if ( ! empty( $item_categories ) ) {
					foreach ( $item_categories as $item_category ) {
						$itemTerms[] = $item_category->get_term();
					}
					$item_categories_string = implode( ',',$itemTerms );
				} else { $item_categories_string = ''; }
					// one final cleanup of the content.
					$contentObj = pressforward( 'library.htmlchecker' );
					$item_content = $contentObj->closetags( $item_content );
					$item_content = pressforward( 'controller.readability' )->process_in_oembeds( $item->get_link(), $item_content );
					pf_log('get_links');
					pf_log($item->get_links());
					pf_log($item->get_permalink());
					// GUID
					pf_log($item->get_id());

					$rssObject[ 'rss_' . $c ] = pf_feed_object(
						$item->get_title(),
						$iFeed->get_title(),
						$r_item_date,
						$authors,
						$item_content,
						$item_link, // New proper link (hopefully)
						'',
						$id,
						$ymd_item_date,
						$item_categories_string
					);
					pf_log( 'Setting new object for ' . $item->get_title() . ' of ' . $iFeed->get_title() . '.' );

			}
			$c++;
			// What the hell RSS feed? This is just ridiculous.
			if ( $c > 300 ) {break;}
		}

		// $this->advance_feeds();
		return $rssObject;

	}

	//
	// UTILITY METHODS         //
	//
	// Retrieve the set of items.

	// Tries to get the RSS item author for the meta.
	function get_rss_authors( $item ) {
		// This needs error checking.
		$authorArray = ($item->get_authors());
		if ( ! empty( $authorArray ) ) {
			foreach ( $authorArray as $author ) {

				$nameArray[] = $author->get_name();

			}
			$authors = implode( ', ', $nameArray );
			$authors = $authors;

		} else {
			$authors = __( 'No author.', 'pf' );
		}

		return $authors;
	}

	function pf_add_media_uploader_scripts() {

	}

	public function set_permitted_feeds_tabs( $permitted_tabs ) {
		$permitted_tabs['primary_feed_type'] = array(
										'title' => __( 'Subscribe to Feeds', 'pf' ),
										'cap'  => get_option( 'pf_menu_feeder_access', pf_get_defining_capability_by_role( 'editor' ) ),
									);
		return $permitted_tabs;
	}

	function add_to_feeder() {

		$feedlist = get_option( PF_SLUG . '_feedlist' );

		?>
		<div class="pf-opt-group">
            <div class="rss-box ">
                    <h3 class="hndle"><span><?php _e( 'Subscribe to Feeds', 'pf' ); ?></span></h3>
                    <div class="inside">
                        <div><?php _e( 'Add Single Feed', 'pf' ); ?> (RSS or Atom)</div>
                            <div class="pf_feeder_input_box">
                                <input id="<?php echo PF_SLUG . '_feedlist[single]'; ?>" class="regular-text pf_primary_media_opml_url" type="text" name="<?php echo PF_SLUG . '_feedlist[single]'; ?>" value="" />
                                <label class="description" for="<?php echo PF_SLUG . '_feedlist[single]'; ?>"><?php _e( '*Complete URL path', 'pf' ); ?></label>
                                <a href="http://en.wikipedia.org/wiki/RSS">What is an RSS Feed?</a>


                            </div>

                        <div><?php _e( 'Add OPML File', 'pf' ); ?></div>
                            <div class="pf_feeder_input_box">
                                <input id="<?php echo PF_SLUG . '_feedlist[opml]'; ?>" class="pf_opml_file_upload_field regular-text" type="text" name="<?php echo PF_SLUG . '_feedlist[opml]'; ?>" value="" />
                                <label class="description" for="<?php echo PF_SLUG . '_feedlist[opml]'; ?>"><?php _e( '*Drop link to OPML here. No HTTPS allowed.', 'pf' ); ?></label>
								or <a class="button-primary pf_primary_media_opml_upload" ><?php _e( 'Upload OPML file', 'pf' ); ?></a>

								<p>&nbsp;Adding large OPML files may take some time.</p>
                                <a href="http://en.wikipedia.org/wiki/Opml">What is an OPML file?</a>


                            </div>
                        <input type="submit" class="button-primary" value="<?php _e( 'Submit', 'pf' ); ?>" />
                    </div>
            </div>
		</div>
		<?php

	}

	public function feedlist_builder( $feedlist ) {
		if ( empty( $feedlist ) ) {
			echo __( 'No feeds added.', 'pf' );
			return;
		}
		foreach ( $feedlist as $feed ) {
			if ( ( ! is_array( $feed )) && $feed != '' ) {
				$feedID = md5( $feed );
				echo '<li id="feed-' . $feedID . '" class="feed-list-item">' . $feed . ' <input id="' . $feedID . '" type="submit" class="removeMyFeed icon-remove" value="   Remove"></input>';
				echo '<input type="hidden" name="feed_url" id="o_feed_url_' . $feedID . '" value="' . $feed . '"></li>';
			} elseif ( is_array( $feed ) ) {
				$this->feedlist_builder( $feed );
			}
		}

		return;
	}

	public static function pf_feedlist_validate( $input ) {
		set_time_limit( 0 );
		pf_log( 'Add Feed Process Invoked: PF_RSS_IMPORT::pf_feedlist_validate' );
		pf_log( $input );
		if ( current_user_can( 'edit_posts' ) ) {
				pf_log( 'Yes, the current user can edit posts.' );
		} else {
			pf_log( 'No, the current user can not edit posts.' );
		}

		$feed_obj = pressforward( 'schema.feeds' );
		$subed = array();
		$something_broke = false;
		if ( ! empty( $input['single'] ) ) {
			if ( ! (is_array( $input['single'] )) ) {
				pf_log( 'The feed is not an array;' );
				if ( ! $feed_obj->has_feed( $input['single'] ) ) {
					pf_log( 'The feed does not already exist.' );
					$check = $feed_obj->create( $input['single'], array( 'type' => 'rss', 'module_added' => get_class() ) );
					if ( is_wp_error( $check ) || ! $check ) {
						pf_log( 'The feed did not enter the database.' );
						// wp_die($check);
						$something_broke = true;
						$description = 'Feed failed initial attempt to add to database | ' . $check->get_error_message();
						$broken_id = $feed_obj->create( $input['single'], array( 'type' => 'rss-quick', 'description' => $description, 'module_added' => get_called_class() ) );
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
			} else {
				pf_log( 'The feed was an array, this does not work' );
				wp_die( 'Bad feed input. Why are you trying to place an array?' );
			}
		}
		// var_dump($_POST);
		// die();
		// print_r($inputSingle);
		if ( ! empty( $input['opml'] ) ) {
			self::process_opml( $input['opml'] );
			$subed[] = 'an OPML file.';
		}

		if ( ! empty( $input['opml_uploader'] ) ) {
			// var_dump($input); die();
			pf_log( 'Attempting to upload on OPML file.' );
			$keys = array_keys( $_FILES );
			$i = 0; foreach ( $_FILES as $ofile ) {
				// if a files was upload
				if ( $ofile['size'] ) {
					// if it is an OPML
					// Pattern from http://www.sitepoint.com/wordpress-options-panel/ and http://codex.wordpress.org/Function_Reference/wp_handle_upload
					if ( preg_match( '/(opml|xml)$/', $ofile['type'] ) ) {
						$override = array( 'test_form' => false );
						// save the file, and store an array, containing its location in $file
						$file = wp_handle_upload( $ofile, $override );
						pf_log( 'File upload resulted in:' );
						pf_log( $file );
						self::process_opml( $file['url'] );
					} else {
						// Not an image.
						// $options = get_option('plugin_options');
						// $plugin_options[$keys[$i]] = $options[$logo];
						// Die and let the user know that they made a mistake.
						wp_die( 'No OPML file was uploaded.' );
					}
				} // Else, the user didn't upload a file.
				// Retain the image that's already on file.
				else {
					// $options = get_option('plugin_options');
					// $plugin_options[$keys[$i]] = $options[$keys[$i]];
				}
				$i++;
			}

			$subed[] = 'an OPML uploaded file.';
		}
		// var_dump($_FILES); die();
		if ( ! empty( $_POST['o_feed_url'] ) ) {
				$offender = array_search( $_POST['o_feed_url'], $feedlist );
			if ( $offender !== false ) {
				unset( $feedlist[ $offender ] );
			}
		}
		$subscribe_string = '';
		if ( ( 1 == count( $subed ) ) && ! empty( $check ) ) {
			$edit_link = get_edit_post_link( $check );
			$subed[99] = " <a href=\"$edit_link\" target=\"_blank\">" . __( 'Edit.', 'pf' ) . '</a>';
		}
		foreach ( $subed as $sub ) {
			$subscribe_string .= $sub;
		}
		if ( $something_broke ) {
			add_settings_error( 'add_pf_feeds', 'pf_feeds_validation_response', __( 'You have submitted ','pf' ) . $subscribe_string . ' ' . __( 'The feed was not found.', 'pf' ), 'updated' );
		} elseif ( ! empty( $subscribe_string ) ) {
			add_settings_error( 'add_pf_feeds', 'pf_feeds_validation_response', __( 'You have submitted ', 'pf' ) . $subscribe_string, 'updated' );
		}
		return $input;

	}

	public static function process_opml( $opml ) {
		$OPML = new OPML_reader( $opml );
		$OPML_obj = $OPML->get_OPML_obj();
		$opml_array = $OPML_obj->feeds;
		// print_r($opml_array); die();
		foreach ( $opml_array as $key => $feedObj ) {
			$feedXml = $feedObj->feedUrl;
			$args = array(
				'title' => $feedObj->title,
				'description' => $feedObj->text,
				'tags'				=> array(),
			);
			foreach ( $feedObj->folder as $folder ) {
				$args['tags'][ $folder->slug ] = $folder->title;
			}
			// Adding this as a 'quick' type so that we can process the list quickly.
			pf_log( 'Adding this as a quick type so that we can process the list quickly' );
			$opml_array = pressforward( 'schema.feeds' )->progressive_feedlist_transformer( $opml_array, $feedXml, $key, $args );
			// @todo Tag based on folder structure
		}
		// $check_up = update_option( PF_SLUG . '_feedlist', $opml_array );
	}

	public function remove_a_feed() {

		if ( ! empty( $_POST['o_feed_url'] ) ) {
			$feedURL = $_POST['o_feed_url'];
			if ( ! wp_verify_nonce( $_POST[ PF_SLUG . '_o_feed_nonce' ], 'feedremove' ) ) {
				die( __( 'Nonce check failed. Please ensure you\'re supposed to be removing feeds.', 'pf' ) ); }
			/**
			$feedlist = get_option( PF_SLUG . '_feedlist' );

			$offender = array_search($feedURL, $feedlist);
			if ($offender !== false){
				unset($feedlist[$offender]);
			}
			//$modfeedlist = array_diff($feedlist, array($feedURL));
			//update_option( PF_SLUG . '_feedlist', '');
			//delete_option( PF_SLUG . '_feedlist' );
*/
			// The pf_feedlist setting is being filtered through the pf_feedlist_validate
			// method, as a result of being registered with register_setting(). We'll work
			// around this by unhooking the validation method during this update
			// remove_action( 'sanitize_option_pf_feedlist', array( 'PF_RSS_Import', 'pf_feedlist_validate' ) );
			$check = update_option( PF_SLUG . '_feedlist', $_POST );

			if ( ! $check ) {
				$result = __( 'The feedlist failed to update.', 'pf' );
			} else {
				$result = $feedURL . __( ' has been removed from your feedlist.', 'pf' );
			}

			die( $result );
		} else {
			die( __( 'Error', 'pf' ) );
		}

	}

	function register_settings() {
		register_setting( PF_SLUG . '_feedlist_group', PF_SLUG . '_feedlist', array( 'PF_RSS_Import', 'pf_feedlist_validate' ) );
	}

	// Checks the URL against a list of aggregators.
	public function is_from_aggregator( $xmlbase ) {
		$c = 0;
		$urlParts = parse_url( $xmlbase );

		$aggregators = array(
								'tweetedtimes',
								'tweetedtimes.com',
								'www.tweetedtimes.com',
								'pipes.yahoo.com',
							);
		foreach ( $aggregators as $aggregator ) {
			if ( in_array( $aggregator, $urlParts ) ) {
				$c++;
			}
		}
		if ( $c > 0 ) {

			return true;

		} else {
			return false;
		}

	}

	public function admin_enqueue_scripts() {
		global $pagenow;
		$pf = pressforward( 'modules' );

		$hook = 0 != func_num_args() ? func_get_arg( 0 ) : '';

		if ( ! in_array( $pagenow, array( 'admin.php' ) ) ) {
			return; }

		if ( ! in_array( $hook, array( 'pressforward_page_pf-feeder' ) ) ) {
			return; }

		wp_enqueue_script( 'feed-manip-ajax', $pf->modules['rss-import']->module_url . 'assets/js/feed-manip-imp.js', array( 'jquery', PF_SLUG . '-twitter-bootstrap' ) );
		wp_enqueue_style( PF_SLUG . '-feeder-style', $pf->modules['rss-import']->module_url . 'assets/css/feeder-styles.css' );
	}


}


function pf_test_import() {
	if ( is_super_admin() && ! empty( $_GET['pf_test_import'] ) ) {
		var_dump( pf_get_starred_items_for_user( get_current_user_id(), 'simple' ) );
		return;
		$feed = pf_fetch_feed( 'http://teleogistic.net/feed' );

		$source = $feed->subscribe_url();

		foreach ( $feed->get_items() as $item ) {
			$io = new PF_Feed_Item();

			// Check for existing items before importing
			$foo = $io->get( array(
				'url' => $item->get_link( 0 ),
				'foo' => 'bar',
			) );

			if ( empty( $foo ) ) {
				$tags = wp_list_pluck( $item->get_categories(), 'term' );
				$fi_id = $io->create( array(
					'title'   => $item->get_title(),
					'url'     => $item->get_link( 0 ),
					'content' => $item->get_content(),
					'source'  => $source,
					'date'    => strtotime( $item->get_date() ),
					'tags'    => $tags,
				) );
			} else {
				$fi_id = $foo[0]->ID;
			}

			pf_star_item_for_user( $fi_id, get_current_user_id() );
			if ( rand( 0, 1 ) ) {
				echo 'deleted:';
				var_dump( pf_unstar_item_for_user( $fi_id, get_current_user_id() ) );
			}

			echo 'starred: ';
			var_dump( pf_is_item_starred_for_user( $fi_id, get_current_user_id() ) );
			var_dump( $fi_id );
		}
	}
}
add_action( 'admin_init', 'pf_test_import' );
