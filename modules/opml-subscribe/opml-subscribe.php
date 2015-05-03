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
	 * has been approved for insertian as a 'new' item.
	 * This means that the feed hasn't been passed into the database
	 * before and can safely be attempted to add to the feed list.
	 *
	 * @param  array $data [description]
	 * @return [type]       [description]
	 */
	public function subscribe_to_approved_feeds($data){
		$opml_post_id = $data['post_parent'];
		$subscription_link = $data['item_link'];
		//feed_post_setup is what should go here.
		return $data;
	}

	/**
	 * Gets the data from an OPML file and turns it into a data object
	 * as expected by PF
	 *
	 * @global $pf Used to access the feed_object() method
	 */
	public function get_data_object($aOPML){
		$feed_obj = new PF_Feeds_Schema();
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
			$id = create_feed_item_id( $feed_obj->feedUrl, 'OPML' );
			#if ( false === ( $rssObject['opml_' . $c] = get_transient( 'pf_' . $id ) ) ) {
				# Adding this as a 'quick' type so that we can process the list quickly.
				if(!empty($feed_obj->type)){
					$feed_obj->type = $feed_obj->type.'-quick';
				} else {
					$feed_obj->type = 'rss-quick';
				}
				if(!empty($feed_obj->text)){
					$contentObj = new pf_htmlchecker($feed_obj['text']);
					$feed_obj->text = $contentObj->closetags($feed_obj['text']);
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
										'' #tags
										);

				pf_log('Setting new transient for ' . $feed_obj->feedUrl . ' of ' . $source . '.');
				set_transient( 'pf_' . $id, $opmlObject['opml_' . $c], 60*10 );
				$c++;

			#}
		}

		return $opmlObject;

	}

	public function add_to_feeder(){
		?><form method="post" action="options.php"><?php
        settings_fields( PF_SLUG . '_opml_group' );
		$feedlist = get_option( PF_SLUG . '_opml_module' );
        ?>
			<br />
			<br />
		<div><?php _e('Subscribe to OPML', 'pf'); ?></div>
			<div>
				<input id="<?php echo PF_SLUG . '_opml_sub[list]'; ?>" class="regular-text" type="text" name="<?php echo PF_SLUG . '_opml_sub[list]'; ?>" value="" />
                <label class="description" for="<?php echo PF_SLUG . '_opml_sub[list]'; ?>"><?php _e('*Complete URL for an OPML subscription', 'pf'); ?></label>


            </div>
			<p class="submit">
				<?php submit_button(); ?>
			</p>
		</form><?php
	}

	static function pf_opml_subscriber_validate($input){
		$feed_obj = new PF_Feeds_Schema();
		if (!empty($input['list'])){
			if (!(is_array($input['list']))){
				if (!$feed_obj->has_feed($input['list'])){
					$check = $feed_obj->create(
						$feedUrl,
						array(
							'title' => 'OPML Subscription at ' . $input['list'],
							'htmlUrl' => $input['list'],
							'type' => 'opml',
							'tags' => 'OPML Subscription',
							'module_added' => get_class($this)
						)
					);
					if (is_wp_error($check)){
						wp_die($check);
					}
				} else {
					$feed_obj->update_url($input['list']);
				}
			} else {
				wp_die('Bad feed input. Why are you trying to place an array?');
			}
		}
	}

	function register_settings(){
		register_setting(PF_SLUG . '_opml_group', PF_SLUG . '_opml_sub', array('PF_OPML_Subscribe', 'pf_opml_subscriber_validate'));
	}



}