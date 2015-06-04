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
		//add_action( 'about_to_insert_pf_feed_items', array($this, 'subscribe_to_approved_feeds') );
		add_action( 'already_a_feed_item', array($this, 'add_folders_to_items') );
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

	public function folder_to_slug($folder){
		$category = $folder->title;
		$category = rawurlencode( urldecode( $category ) );
		$category = str_replace( '%2F', ' ', $category );
		$category = str_replace( '%20', ' ', $category );
		$category = str_replace( '/', ' ', $category );
		$slug  = sanitize_title( basename( $category ) );
		return $slug;
	}

	public function set_folder_as_term( $folder, $id ){
		if ( empty( $folder ) ){
			trigger_error('Attempting to set a folder with an empty folder object.');
			return false;
		}
		$slug = $this->folder_to_slug($folder);
		$tax = pressforward()->pf_feeds->tag_taxonomy;
		$check = term_exists($slug, strval($tax) );
		if ( !empty( $check ) ){
			$cat = term_exists($slug, strval($tax) );
			$cat_id = $cat['term_id'];
		} else {
			$cat = wp_insert_term( $folder->title, pressforward()->pf_feeds->tag_taxonomy,
					array(
							'description'	=>	$folder->text,
							'slug'	=>	$slug
						)
				);
			if (is_wp_error($cat) ){
				pf_log('Insert term with slug of '.$slug.' has failed with the following error:');
				pf_log($cat);
				return;
			}
			$cat_id = $cat['term_id'];
		}
		$cat_obj = get_term($cat_id, pressforward()->pf_feeds->tag_taxonomy);
		pf_log('Set category with slug of '.$slug);
		pf_log('Setting new category for '. $id . ' of ' . $slug . ' with term ID of '.$cat_obj->term_id);
		$check = wp_set_object_terms( $id, array( $cat_obj->term_id ), pressforward()->pf_feeds->tag_taxonomy, true );
		if ( is_wp_error($check) ){
			pf_log('Could not add category error:');
			pf_log($check);
		}
	}

	public function add_folders_to_items($args){
				$item = $args['item'];
		if (empty($item['obj']) || empty($item['obj']->feedUrl) ){
			return $item;
		}
		$post_id = $args['post_id'];
		pf_log('Do something with post ID '.$post_id);
		$feed_obj = $item['obj'];
		foreach ($feed_obj->folder as $folder){
			$slug = $this->folder_to_slug($folder);
			if ( !has_category( $slug, $post_id ) ){
				pf_log('Add category '.$slug. ' to existing feed '. $post_id);
				$this->set_folder_as_term($folder, $post_id);
			}
		}
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
		if (empty($item['obj']) || empty($item['obj']->feedUrl) ){
			return $item;
		}
		$feed_obj = $item['obj'];
		if (empty($item['parent_feed_id'])){
			$parent = 0;
		}
		else {
			$parent = get_post_meta($item['parent_feed_id'], 'user_added', true);
		}
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
			'user_added'    => $parent,
			'post_parent'	=> $item['parent_feed_id'],
			'module_added' 	=> 'opml-subscribe',
			'tags'    => array(),
		);
		$new_feed_id = pressforward()->pf_feeds->create($feed_obj->feedUrl, $feed_array);
		//Set up category here.
		foreach ($feed_obj->folder as $folder){
			pf_log('Setting new category for '.$feed_obj->title);
			$this->set_folder_as_term($folder, $new_feed_id);
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

				$item = array( 'obj' => $feed_obj, 'parent_feed_id' => $aOPML->ID );
				$this->subscribe_to_approved_feeds($item);

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

	private function make_a_folder_object_from_term( $term ){
		$entry['title'] = ( !empty( $term->name ) ? $term->name : $term->slug ) ;
		$entry['text'] = ( !empty( $term->description ) ? $term->description : $entry['title'] );
		return $this->master_opml_obj->make_a_folder_obj( $entry );
	}

	private function make_a_folder_object_from_term_slug( $slug ){
		$obj = get_term_by( 'slug', $slug, pressforward()->pf_feeds->tag_taxonomy );
		return $this->make_a_folder_object_from_term( $obj );
	}

	private function make_a_feed_object_from_post( $post_obj = false ){
		//var_dump(get_post_meta(get_the_ID()));
		$meta = get_post_meta(get_the_ID());
		$url_parts = parse_url( $meta['feedUrl'][0] );
		//var_dump($url_parts);
		$entry = array(
				'title'		=> get_the_title(),
				'text'		=> get_the_content(),
				'type'		=> ( 'rss-quick' == $meta['feed_type'] ? 'rss' : $meta['feed_type'] ),
				'feedUrl'	=> $meta['feedUrl'][0],
				'xmlUrl'	=> $meta['feedUrl'][0],
				'htmlUrl'	=> $url_parts['scheme'] . '://' . $url_parts['host']
			);
		return $this->master_opml_obj->make_a_feed_obj( $entry );
	}

	private function make_parent_folder_from_post( ){
		$terms = wp_get_post_terms( get_the_ID(), pressforward()->pf_feeds->tag_taxonomy );
		$folders = array();
		foreach ( $terms as $term ) {
			//var_dump($term->name);
			$folders[] = $this->master_opml_obj->get_folder($term->name);
		}
		return $folders;

	}

	public function make_OPML(){
		$site_name = get_bloginfo('name');
		if( empty($_GET['opml_folder']) ){
			$this->master_opml_obj = new OPML_Object(get_site_url().'?pf=opml' );
			$this->master_opml_obj->set_title('PressForward Subscription List for '.$site_name);
			$folders = get_terms(pressforward()->pf_feeds->tag_taxonomy);
			foreach ($folders as $folder){
				$folder_obj = $this->make_a_folder_object_from_term($folder);
				$this->master_opml_obj->set_folder($folder_obj);
			}
			$feed_query_args = array(
										'post_type'			=>	pressforward()->pf_feeds->post_type,
										'posts_per_page'	=>	-1
									);
			$feed_query = new WP_Query( $feed_query_args );

		} else {
			$folder_obj = $this->make_a_folder_object_from_term_slug($_GET['opml_folder']);
			$this->master_opml_obj = new OPML_Object(get_site_url().'?pf=opml&opml_folder='.$_GET['opml_folder'] );
			$this->master_opml_obj->set_title('PressForward Subscription List for the '.$_GET['opml_folder'].' folder on '.$site_name);
		}
					// The Loop
		if ( $feed_query->have_posts() ) {
			while ( $feed_query->have_posts() ) {
				$feed_query->the_post();
				$feed_obj = $this->make_a_feed_object_from_post( );
				//Use OPML internals to slugify attached terms, retrieve them from the OPML folder object, deliver them into feed.
				$parent = $this->make_parent_folder_from_post();
				$this->master_opml_obj->set_feed( $feed_obj, $parent );
			}
		} else {
			// no posts found
		}

		$opml = new OPML_Maker($this->master_opml_obj);
		echo $opml->template();

	}



}