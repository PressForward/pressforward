<?php

/**
 * This module will allow you to subscribe to OPML files.
 * These subscriptions will populate your feedlist with new feeds
 * as they are added to the OPML file.
 **/

class PF_OPML_Subscribe extends PF_Module {

	/**
	 * Constructor
	 */
	public function __construct(){

		global $pf;
		$this->feed_type = 'opml';
		parent::start();

		add_action('admin_init', array($this, 'register_settings'));
		add_action( 'about_to_insert_pf_feed_items', array($this, 'subscribe_to_approved_feeds') );
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
		require_once(PF_ROOT . "/includes/opml-reader/opml-reader.php");
	}

	/**
	 * This function runs on the post data after it
	 * has been approved for insertion as a 'new' item.
	 * This means that the feed hasn't been passed into the database
	 * before and can safely be attempted to add to the feed list.
	 *
	 * @param  array $data [description]
	 * @return [type]       [description]
	 */
	public function subscribe_to_approved_feeds($item){
		if (empty($item['obj']) || empty($items['obj']->feedUrl) ){
			return $item;
		}
		$feed_obj = $item['obj'];
		$feed_array = array(
			'title'   		=> $feed_obj->title,
			'url'     		=> $feed_obj->feedUrl,
			'htmlUrl' 		=> $feed_obj->htmlUrl,
			'type'	  		=> 'rss-quick',
			'feedUrl'		=> $feed_obj->feedUrl,
			'description' 	=> $feed_obj->text,
			'feed_author' 	=> 'OPML',
			'feed_icon'  	=> false,
			'copyright'		=> false,
			'thumbnail'  	=> false,
			'user_added'    => get_post_meta($parent_id, 'user_added', true),
			'post_parent'	=> $item['parent_feed_id'],
			'module_added' 	=> 'opml-subscribe',
			'tags'    => array(),
		);
		$new_feed_id = pressforward()->pf_feeds->create($feed->feedUrl, $feed_array);
		//Set up category here.
		foreach ($feed_obj->folder as $folder){
			$category = $folder->title;
			$category = rawurlencode( urldecode( $category ) );
			$category = str_replace( '%2F', ' ', $category );
			$category = str_replace( '%20', ' ', $category );
			$category = str_replace( '/', ' ', $category );
			$slug  = sanitize_title( basename( $category ) );
			if( false == ( $cat_obj = get_category_by_slug( $slug )) ){
				$cat_id = wp_insert_category(
						array(
								'cat_name'	=>	$folder->title,
								'category_description'	=>	$folder->text,
								'category_nicename'	=>	$slug,
								'taxonomy'	=>	pressforward()->pf_feeds->tag_taxonomy
							)
					);
				$cat_obj = get_category($cat_id);
			}
			pf_log('Setting new category for '.$feed_obj->title . ' of ' . $slug);
			wp_set_post_categories( $new_feed_id, array( $cat_obj->term_id ), true );
		}
		return $new_feed_id;
	}

	/**
	 * Gets the data from an OPML file and turns it into a data object
	 * as expected by PF
	 *
	 * @global $pf Used to access the feed_object() method
	 */
	public function get_data_object($aOPML){
		//$feed_obj = new PF_Feeds_Schema();
		pf_log( 'Invoked: PF_OPML_Subscribe::get_data_object()' );
		$aOPML_id = $aOPML->ID;
		$aOPML_url = get_post_meta($aOPML_id, 'feedUrl', true);
		if(empty($aOPML_url) || is_wp_error($aOPML_url) || !$aOPML_url){
			$aOPML_url = $aOPML->post_title;
			update_post_meta($aOPML_id, 'feedUrl', $aOPML_url);
		}
		pf_log( 'Getting OPML Feed at '.$aOPML_url );
		$OPML_reader = new OPML_reader($aOPML_url);
		$opml_object = $OPML_reader->get_OPML_obj();
		pf_log('OPML object received to turn into feeds.');
		$c = 0;
		$opmlObject = array();
		foreach($opml_object->feeds as $feed_obj){
			/**
			 * The Unique ID for this feed.
			 *
			 * Ok, so why don't we use the ->title property of the feed here?
			 * The reason is because a feed could potentially be added by more than
			 * one OPML file. BUT the ->title property is set by the owner of the
			 * OPML file, if it is even set at all. Which means it could be different
			 * across more than one OPML file. But we don't want to add a feed more
			 * than once, so we only use the feedUrl as a unique notifier.
			 *
			 * @var string
			 */
			pf_log('Prepping item '.$feed_obj->title);
			$id = $feed_obj->id;
			#if ( false === ( $rssObject['opml_' . $c] = get_transient( 'pf_' . $id ) ) ) {
				# Adding this as a 'quick' type so that we can process the list quickly.
				if(!empty($feed_obj->type)){
					$feed_obj->type = $feed_obj->type.'-quick';
				} else {
					$feed_obj->type = 'rss-quick';
				}
				if(!empty($feed_obj->text)){
					$contentObj = new pf_htmlchecker($feed_obj->text);
					$feed_obj->text = $contentObj->closetags($feed_obj->text);
				}

				if(!empty($feed_obj->title)){
					$contentObj = new pf_htmlchecker($feed_obj->title);
					$feed_obj->title = $contentObj->closetags($feed_obj->title);
				} else {
					$feed_obj->title = $feed_obj->feedUrl;
				}

				$content = 'Subscribed: ' . $feed_obj->title . ' - ' . $feed_obj->type . ' - ' . $feed_obj->feedUrl . ' on ' . date('r');
				$opmlObject['opml_'.$c] = pf_feed_object(
										$feed_obj->title,
										'OPML Subscription from ' . $opml_object->get_title(),
										date('r'),
										'OPML Subscription ' . $opml_object->get_title(),
										$content,
										$feed_obj->feedUrl,
										'',
										$id,
										date('r'),
										'', #tags
										'', #added
										'', #repeat
										'',
										'',
										$feed_obj
										);

				pf_log('Setting new transient for ' . $feed_obj->feedUrl . ' of ' . $opml_object->get_title() . '.');
				set_transient( 'pf_' . $id, $opmlObject['opml_' . $c], 60*10 );
				$c++;

			#}
		}

		return $opmlObject;

	}

	public function add_to_feeder(){

        settings_fields( PF_SLUG . '_opml_group' );
		$feedlist = get_option( PF_SLUG . '_opml_module' );

        ?>
		<div class="pf-opt-group span6">
            <div class="opml-box postbox">
                    <div class="handlediv" title="Click to toggle"><br></div>
                    <h3 class="hndle"><span><?php _e('Subscribe to OPML as Feed', 'pf'); ?></span></h3>
                    <div class="inside">
                        <div><?php _e('Add OPML Subscription', 'pf'); ?> (RSS or Atom)</div>
                            <div class="pf_feeder_input_box">
                                <input id="<?php echo PF_SLUG . '_opml_sub[opml_single]'; ?>" class="regular-text pf_primary_media_opml_url" type="text" name="<?php echo PF_SLUG . '_opml_sub[opml_single]'; ?>" value="" />
                                <label class="description" for="<?php echo PF_SLUG . '_opml_sub[opml_single]'; ?>"><?php _e('*Complete URL path', 'pf'); ?></label>

                        		<input type="submit" class="button-primary" value="<?php _e('Save Options', 'pf'); ?>" />
                    		</div>
            		</div>
			</div>
		</div>
		<?php
	}

	static function pf_opml_subscriber_validate($input){
		//var_dump(get_class()); die();
		if (!empty($input['opml_single'])){
			if (!(is_array($input['opml_single']))){
				if (!pressforward()->pf_feeds->has_feed($input['opml_single'])){
					$check = pressforward()->pf_feeds->create(
						$input['opml_single'],
						array(
							'title' => 'OPML Subscription at ' . $input['opml_single'],
							'htmlUrl' => $input['opml_single'],
							'type' => 'opml',
							'tags' => 'OPML Subscription',
							'module_added' => get_class()
						)
					);
					if (is_wp_error($check)){
						wp_die($check);
					}
				} else {
					#var_dump($input); die();
					#pressforward()->pf_feeds->update_url($input['opml_single']);
				}
			} else {
				wp_die('Bad feed input. Why are you trying to place an array?');
			}
		}
		return $input;
	}

	function register_settings(){
		register_setting(PF_SLUG . '_opml_group', PF_SLUG . '_opml_sub', array('PF_OPML_Subscribe', 'pf_opml_subscriber_validate'));
	}



}