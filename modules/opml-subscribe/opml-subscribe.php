<?php
/**
 * This module will allow you to subscribe to OPML files.
 * These subscriptions will populate your feedlist with new feeds
 * as they are added to the OPML file.
 *
 * @package PressForward
 */

/**
 * PF_OPML_Subscribe class.
 */
class PF_OPML_Subscribe extends PF_Module {
	/**
	 * Master OPML object.
	 *
	 * @access protected
	 * @var OPML_Object
	 */
	protected $master_opml_obj;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $pf;
		$this->feed_type = 'opml';
		parent::start();

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'already_a_feed_item', array( $this, 'add_folders_to_items' ) );
		if ( isset( $_GET['pf'] ) && ( 'opml' === $_GET['pf'] ) ) {
			add_action( 'feed_folders_registered', array( $this, 'make_OPML' ) );
		}

		add_filter( 'pf_tabs_pf-tools', array( $this, 'set_permitted_tools_tabs' ), 20, 1 );
		add_action( 'pf_do_pf-tools_tab_opml', array( $this, 'opml_tools' ) );
	}

	/**
	 * Registers permitted tools tabs for OPML module.
	 *
	 * @param array $permitted_tabs Tabs.
	 * @return array
	 */
	public function set_permitted_tools_tabs( $permitted_tabs ) {
		$permitted_tabs['opml'] = array(
			'title' => __( 'OPML Link', 'pressforward' ),
			'cap'   => pf_get_defining_capability_by_role( 'contributor' ),
		);
		return $permitted_tabs;
	}

	/**
	 * Registers permitted tabs for OPML module.
	 *
	 * @param array $permitted_tabs Tabs.
	 * @return array
	 */
	public function set_permitted_feeds_tabs( $permitted_tabs ) {
		$permitted_tabs['opml'] = array(
			'title' => __( 'OPML as Feed', 'pressforward' ),
			'cap'   => get_option( 'pf_menu_feeder_access', pf_get_defining_capability_by_role( 'editor' ) ),
		);
		return $permitted_tabs;
	}

	/**
	 * Gets a slug from a folder object.
	 *
	 * @param object $folder Folder object.
	 * @return string
	 */
	public function folder_to_slug( $folder ) {
		$category = $folder->title;
		$category = rawurlencode( urldecode( $category ) );
		$category = str_replace( '%2F', ' ', $category );
		$category = str_replace( '%20', ' ', $category );
		$category = str_replace( '/', ' ', $category );
		$slug     = sanitize_title( basename( $category ) );
		return $slug;
	}

	/**
	 * Sets a folder as a term.
	 *
	 * @param mixed $folder Folder object.
	 * @param int   $id     Item ID.
	 */
	public function set_folder_as_term( $folder, $id ) {
		if ( empty( $folder ) ) {
			pf_log( 'Attempting to set a folder with an empty folder object.' );
			return false;
		}
		$slug  = $this->folder_to_slug( $folder );
		$tax   = pressforward( 'schema.feeds' )->tag_taxonomy;
		$check = term_exists( $slug, strval( $tax ) );
		if ( ! empty( $check ) ) {
			$cat    = term_exists( $slug, strval( $tax ) );
			$cat_id = $cat['term_id'];
		} else {
			$cat = wp_insert_term(
				$folder->title,
				pressforward( 'schema.feeds' )->tag_taxonomy,
				array(
					'description' => $folder->text,
					'slug'        => $slug,
				)
			);
			if ( is_wp_error( $cat ) ) {
				pf_log( 'Insert term with slug of ' . $slug . ' has failed with the following error:' );
				pf_log( $cat );
				return;
			}
			$cat_id = $cat['term_id'];
		}
		$cat_obj = get_term( $cat_id, pressforward( 'schema.feeds' )->tag_taxonomy );
		pf_log( 'Set category with slug of ' . $slug );
		pf_log( 'Setting new category for ' . $id . ' of ' . $slug . ' with term ID of ' . $cat_obj->term_id );
		$check = wp_set_object_terms( $id, array( $cat_obj->term_id ), pressforward( 'schema.feeds' )->tag_taxonomy, true );
		if ( is_wp_error( $check ) ) {
			pf_log( 'Could not add category error:' );
			pf_log( $check );
		}
	}

	/**
	 * Adds folders to items.
	 *
	 * @param array $args Argument array.
	 */
	public function add_folders_to_items( $args ) {
		$item = $args['item'];
		if ( empty( $item['obj'] ) || empty( $item['obj']->feedUrl ) ) {
			return $item;
		}
		$post_id = $args['post_id'];
		pf_log( 'Do something with post ID ' . $post_id );
		$feed_obj = $item['obj'];
		if ( ! method_exists( $feed_obj, 'folder' ) || empty( $feed_obj->folder ) ) {
			pf_log( 'No folder available on' );
			pf_log( $args );
			return $item;
		}
		foreach ( $feed_obj->folder as $folder ) {
			$slug = $this->folder_to_slug( $folder );
			if ( ! has_category( $slug, $post_id ) ) {
				pf_log( 'Add category ' . $slug . ' to existing feed ' . $post_id );
				$this->set_folder_as_term( $folder, $post_id );
			}
		}
	}

	/**
	 * This function runs on the post data after it
	 * has been approved for insertion as a 'new' item.
	 * This means that the feed hasn't been passed into the database
	 * before and can safely be attempted to add to the feed list.
	 *
	 * @param array $item Item data.
	 */
	public function subscribe_to_approved_feeds( $item ) {
		if ( empty( $item['obj'] ) || empty( $item['obj']->feedUrl ) ) {
			return $item;
		}

		$feed_obj = $item['obj'];
		if ( empty( $item['parent_feed_id'] ) ) {
			$parent = 0;
		} else {
			$parent = pressforward( 'controller.metas' )->get_post_pf_meta( $item['parent_feed_id'], 'user_added', true );
		}

		$feed_array = array(
			'title'        => $feed_obj->title,
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			'url'          => $feed_obj->feedUrl,
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			'htmlUrl'      => $feed_obj->htmlUrl,
			'type'         => 'rss',
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			'feedUrl'      => $feed_obj->feedUrl,
			'description'  => $feed_obj->text,
			'feed_author'  => 'OPML',
			'feed_icon'    => false,
			'copyright'    => false,
			'thumbnail'    => false,
			'user_added'   => $parent,
			'post_parent'  => $item['parent_feed_id'],
			'module_added' => 'opml-subscribe',
			'tags'         => array(),
		);

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$new_feed_id = pressforward( 'schema.feeds' )->create( $feed_obj->feedUrl, $feed_array );
		pf_log( 'New feed created with ID of ' . $new_feed_id );
		if ( ! method_exists( $feed_obj, 'folder' ) || empty( $feed_obj->folder ) ) {
			return $new_feed_id;
		}
		// Set up category here.
		foreach ( $feed_obj->folder as $folder ) {
			pf_log( 'Setting new category for ' . $feed_obj->title );
			$this->set_folder_as_term( $folder, $new_feed_id );
		}
		return $new_feed_id;
	}

	/**
	 * Gets the data from an OPML file and turns it into a data array as expected by PF.
	 *
	 * @global $pf Used to access the feed_object() method
	 *
	 * @param WP_Post $a_opml OPML post object.
	 * @return array
	 */
	public function get_data_object( $a_opml ) {
		pf_log( 'Invoked: PF_OPML_Subscribe::get_data_object()' );
		$a_opml_id  = $a_opml->ID;
		$a_opml_url = pressforward( 'controller.metas' )->get_post_pf_meta( $a_opml_id, 'feedUrl', true );
		if ( empty( $a_opml_url ) || is_wp_error( $a_opml_url ) || ! $a_opml_url ) {
			$a_opml_url = $a_opml->post_title;
			pressforward( 'controller.metas' )->update_pf_meta( $a_opml_id, 'feedUrl', $a_opml_url );
		}
		pf_log( 'Getting OPML Feed at ' . $a_opml_url );
		$opml_reader = new OPML_reader( $a_opml_url );
		$opml_object = $opml_reader->get_OPML_obj();
		pf_log( 'OPML object received to turn into feeds.' );

		$c = 0;

		$opml_array = array();
		foreach ( $opml_object->feeds as $feed_obj ) {
			/**
			 * The Unique ID for this feed.
			 *
			 * Ok, so why don't we use the ->title property of the feed here?
			 * The reason is because a feed could potentially be added by more than
			 * one OPML file. BUT the ->title property is set by the owner of the
			 * OPML file, if it is even set at all. Which means it could be different
			 * across more than one OPML file. But we don't want to add a feed more
			 * than once, so we only use the feedUrl as a unique notifier.
			 */
			pf_log( 'Prepping item ' . $feed_obj->title );
			$id = $feed_obj->id;

			$rss_object[ 'opml_' . $c ] = get_transient( 'pf_opml_' . $id );
			if ( false === $rss_object[ 'opml_' . $c ] ) {
				$feed_obj->type = 'rss';

				if ( ! empty( $feed_obj->text ) ) {
					$content_obj    = pressforward( 'library.htmlchecker' );
					$feed_obj->text = $content_obj->closetags( $feed_obj->text );
				}

				if ( ! empty( $feed_obj->title ) ) {
					$content_obj     = pressforward( 'library.htmlchecker' );
					$feed_obj->title = $content_obj->closetags( $feed_obj->title );
				} else {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$feed_obj->title = $feed_obj->feedUrl;
				}

				$item = array(
					'obj'            => $feed_obj,
					'parent_feed_id' => $a_opml->ID,
				);
				$this->subscribe_to_approved_feeds( $item );

				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$content = 'Subscribed: ' . $feed_obj->title . ' - ' . $feed_obj->type . ' - ' . $feed_obj->feedUrl . ' on ' . gmdate( 'r' );

				$opml_array[ 'opml_' . $c ] = pf_feed_object(
					$feed_obj->title,
					'OPML Subscription from ' . $opml_object->get_title(),
					gmdate( 'r' ),
					'OPML Subscription ' . $opml_object->get_title(),
					$content,
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$feed_obj->feedUrl,
					'',
					$id,
					gmdate( 'r' ),
					'opml-feed', // tags.
					'', // added.
					'', // repeat.
					'',
					'made_readable',
					$feed_obj
				);

				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				pf_log( 'Setting new transient for ' . $feed_obj->feedUrl . ' of ' . $opml_object->get_title() . '.' );
				set_transient( 'pf_' . $id, $opml_array[ 'opml_' . $c ], 60 * 10 );
				++$c;

			}
		}

		return $opml_array;
	}

	/**
	 * Generates markup for adding item to feeder.
	 */
	public function add_to_feeder() {
		?>

			<div class="opml-box">
					<h3><span><?php esc_html_e( 'Subscribe to OPML as Feed', 'pressforward' ); ?></span></h3>
					<div>
						<div><?php esc_html_e( 'Add OPML Subscription', 'pressforward' ); ?> (OPML or XML)</div>
							<div class="pf_feeder_input_box">
								<input id="<?php echo esc_attr( PF_SLUG ) . '_feedlist[opml_single]'; ?>" class="regular-text pf_primary_media_opml_url" type="text" name="<?php echo esc_attr( PF_SLUG . '_feedlist[opml_single]' ); ?>" value="" />
								<label class="description" for="<?php echo esc_attr( PF_SLUG . '_feedlist[opml_single]' ); ?>"><?php esc_html_e( '*Complete URL path', 'pressforward' ); ?></label>

								<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Submit', 'pressforward' ); ?>" />
							</div>
					</div>
			</div>

		<?php
		// </div>
	}

	/**
	 * Validates Subscriber setting.
	 *
	 * @param array $input Setting array.
	 * @return array
	 */
	public static function pf_opml_subscriber_validate( $input ) {
		if ( ! empty( $input['opml_single'] ) ) {

			if ( ! ( is_array( $input['opml_single'] ) ) ) {
				if ( ! pressforward( 'schema.feeds' )->has_feed( $input['opml_single'] ) ) {
					pf_log( 'Adding OPML with url' . $input['opml_single'] );
					$check = pressforward( 'schema.feeds' )->create(
						$input['opml_single'],
						array(
							'title'        => 'OPML Subscription at ' . $input['opml_single'],
							'htmlUrl'      => $input['opml_single'],
							'type'         => 'opml',
							'tags'         => 'OPML Subscription',
							'module_added' => get_class(),
						)
					);
					pf_log( 'With result of:' );
					pf_log( $check );
					if ( is_wp_error( $check ) ) {
						// phpcs:ignore WordPress.Security.EscapeOutput
						wp_die( $check );
					}
					$subscribe_string = 'It could not be created.';
					if ( ! empty( $check ) ) {
						$edit_link        = get_edit_post_link( $check );
						$subscribe_string = " <a href=\"$edit_link\" target=\"_blank\">" . __( 'Edit.', 'pressforward' ) . '</a>';
					}
					add_settings_error( 'add_pf_feeds', 'pf_feeds_validation_response', __( 'You have submitted an OPML feed.', 'pressforward' ) . $subscribe_string, 'updated' );
				}
			} else {
				wp_die( 'Bad feed input. Why are you trying to place an array?' );
			}
		}
		return $input;
	}

	/**
	 * Registers settings for this module.
	 */
	public function register_settings() {
		register_setting( PF_SLUG . '_feedlist_group', PF_SLUG . '_feedlist', array( 'PF_OPML_Subscribe', 'pf_opml_subscriber_validate' ) );
	}

	/**
	 * Makes a folder object from a term.
	 *
	 * @param WP_Term $term Term object.
	 */
	private function make_a_folder_object_from_term( $term ) {
		$entry['title'] = ( ! empty( $term->name ) ? $term->name : $term->slug );
		$entry['text']  = ( ! empty( $term->description ) ? $term->description : $entry['title'] );
		return $this->master_opml_obj->make_a_folder_obj( $entry );
	}

	/**
	 * Makes a feed object from a post.
	 *
	 * @param int $post_id ID of the post.
	 */
	private function make_a_feed_object_from_post( $post_id = 0 ) {
		$meta = pressforward( 'controller.metas' )->get_all_metas( $post_id );
		if ( ! empty( $meta['feedUrl'][0] ) ) {
			if ( 'http' !== substr( $meta['feedUrl'][0], 0, 4 ) ) {
				$meta['feedUrl'][0] = 'http://' . $meta['feedUrl'][0];
			}
		} else {
			return '';
		}

		$post         = get_post( $post_id );
		$post_content = $post && $post instanceof \WP_Post ? $post->post_content : '';

		$url_parts = wp_parse_url( $meta['feedUrl'][0] );
		$entry     = array(
			'title'   => get_the_title( $post_id ),
			'text'    => $post_content,
			'type'    => $meta['feed_type'][0],
			'feedUrl' => $meta['feedUrl'][0],
			'xmlUrl'  => $meta['feedUrl'][0],
			'htmlUrl' => $url_parts['scheme'] . '://' . $url_parts['host'],
		);
		return $this->master_opml_obj->make_a_feed_obj( $entry );
	}

	/**
	 * Makes parent folder from post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function make_parent_folder_from_post( $post_id ) {
		$terms   = wp_get_post_terms( $post_id, pressforward( 'schema.feeds' )->tag_taxonomy );
		$folders = array();
		foreach ( $terms as $term ) {
			$folders[] = $this->master_opml_obj->get_folder( $term->name );
		}
		return $folders;
	}

	/**
	 * Makes OPML.
	 */
	public function make_OPML() {
		$site_name = get_bloginfo( 'name' );
		if ( empty( $_GET['opml_folder'] ) ) {
			$this->master_opml_obj = new OPML_Object( get_site_url() . '?pf=opml' );
			$this->master_opml_obj->set_title( 'PressForward Subscription List for ' . $site_name );
			$folders = get_terms( pressforward( 'schema.feeds' )->tag_taxonomy );
			foreach ( $folders as $folder ) {
				$folder_obj = $this->make_a_folder_object_from_term( $folder );

				if ( ! empty( $folder_obj->title ) ) {
					$this->master_opml_obj->set_folder( $folder_obj );
				}
			}
			$feed_query_args = array(
				'post_type'      => pressforward( 'schema.feeds' )->post_type,
				'posts_per_page' => -1,
			);
			$feed_query      = new WP_Query( $feed_query_args );

		} else {
			// @TODO this doesn't work yet.
			$opml_folder = sanitize_text_field( wp_unslash( $_GET['opml_folder'] ) );

			$this->master_opml_obj = new OPML_Object( get_site_url() . '?pf=opml&opml_folder=' . $opml_folder );
			$this->master_opml_obj->set_title( 'PressForward Subscription List for the ' . $opml_folder . ' folder on ' . $site_name );
		}

		// The Loop.
		if ( ! empty( $feed_query ) ) {
			if ( $feed_query->have_posts() ) {
				while ( $feed_query->have_posts() ) {
					$feed_query->the_post();
					$feed_obj = $this->make_a_feed_object_from_post( get_the_ID() );
					// Use OPML internals to slugify attached terms, retrieve them from the OPML folder object, deliver them into feed.
					$parent = $this->make_parent_folder_from_post( get_the_ID() );
					if ( empty( $parent ) ) {
						$parent = false;
					}
					$this->master_opml_obj->set_feed( $feed_obj, $parent );
				}
			}
		}

		header( 'Content-Type: text/x-opml' );
		$opml = new OPML_Maker( $this->master_opml_obj );

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $opml->template();
		die();
	}

	/**
	 * Generates output for OPML Tools subsection.
	 */
	public function opml_tools() {
		?>
			<p>
				<?php
				printf(
					// translators: link to PF OPML page.
					esc_html__( 'You can share your subscription list as an OPML file by linking people to %s', 'pressforward' ),
					sprintf(
						'<a href="%s" target="_blank">%s</a>',
						esc_attr( home_url( '/?pf=opml' ) ),
						esc_html( home_url( '/?pf=opml' ) )
					)
				);
				?>
			</p>
		<?php
	}
}
