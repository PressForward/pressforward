<?php

/**
 * Setting up the admin interface, including menus and AJAX
 *
 * @since 1.7
 */

class PF_Admin {
	/**
	 * Constructor
	 *
	 * @since 1.7
	 */
	function __construct() {
		add_action( 'admin_menu', array( $this, 'register_pf_custom_menu_pages' ) );
        add_action( 'init', array( $this, 'dead_post_status') );

		// Adding javascript and css to admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
		add_action( 'wp_head', array( $this, 'pf_aggregation_forwarder'));
		add_filter( 'admin_body_class',  array( $this, 'add_pf_body_class'));
		add_filter( 'pf_admin_pages', array($this, 'state_pf_admin_pages'), 10,3);
		// Catch form submits
		add_action( 'admin_init', array($this, 'pf_options_admin_page_save') );
		add_action( 'admin_notices', array($this, 'admin_notices_action' ));

		// Launch a batch delete process, if necessary.
		add_action( 'admin_init', array( $this, 'launch_batch_delete' ) );

		//Modify the Singleton Edit page.
		add_action( 'post_submitbox_misc_actions', array( $this, 'posted_submitbox_pf_actions' ) );
		add_action( 'save_post', array( $this, 'save_submitbox_pf_actions' ) );

		// AJAX handlers
		add_action( 'wp_ajax_build_a_nomination', array( $this, 'build_a_nomination') );
		add_action( 'wp_ajax_build_a_nom_draft', array( $this, 'build_a_nom_draft') );
		add_action( 'wp_ajax_simple_nom_to_draft', array( $this, 'simple_nom_to_draft') );
		add_action( 'wp_ajax_assemble_feed_for_pull', array( $this, 'trigger_source_data') );
		add_action( 'wp_ajax_disassemble_item', array( $this, 'trigger_item_disassembly' ) );
		add_action( 'wp_ajax_reset_feed', array( $this, 'reset_feed') );
		add_action( 'wp_ajax_make_it_readable', array( $this, 'make_it_readable') );
		add_action( 'wp_ajax_archive_a_nom', array( $this, 'archive_a_nom') );
		add_action( 'wp_ajax_pf_ajax_get_comments', array( $this, 'pf_ajax_get_comments') );
		add_action( 'wp_ajax_pf_ajax_thing_deleter', array( $this, 'pf_ajax_thing_deleter') );
		add_action( 'wp_ajax_pf_ajax_retain_display_setting', array( $this, 'pf_ajax_retain_display_setting' ) );
		add_action( 'wp_ajax_pf_ajax_move_to_archive', array( $this, 'pf_ajax_move_to_archive' ) );
		add_action( 'wp_ajax_pf_ajax_move_out_of_archive', array( $this, 'pf_ajax_move_out_of_archive' ) );
		add_action( 'wp_ajax_pf_ajax_user_setting', array( $this, 'pf_ajax_user_setting' ));
		add_action( 'init', array( $this, 'register_feed_item_removed_status') );

		// Modify the Subscribed Feeds panel
		add_filter( 'manage_pf_feed_posts_columns', array( $this, 'add_last_retrieved_date_column' ) );
		add_action( 'manage_pf_feed_posts_custom_column', array( $this, 'last_retrieved_date_column_content' ), 10, 2 );
		add_action( 'manage_edit-pf_feed_sortable_columns', array( $this, 'make_last_retrieved_column_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_last_retrieved' ) );
		#add_filter( 'parse_query', array( $this, 'include_alerts_in_edit_feeds' ) );
		add_filter( 'ab_bug_status_args', array( $this, 'pf_ab_bug_status_args' ) );

		add_filter( 'manage_pf_feed_posts_columns', array( $this, 'add_last_checked_date_column' ) );
		add_action( 'manage_pf_feed_posts_custom_column', array( $this, 'last_checked_date_column_content' ), 10, 2 );
		add_action( 'manage_edit-pf_feed_sortable_columns', array( $this, 'make_last_checked_column_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_last_checked' ) );

		add_action( 'before_delete_post', array( $this, 'pf_delete_children_of_feeds' ) );
		add_action( 'wp_trash_post', array( $this, 'pf_trash_children_of_feeds' ) );

		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_field' ), 10, 2 );
		add_action( 'save_post', array( $this, 'quick_edit_save' ), 10, 2 );

		add_filter( 'heartbeat_received', array( $this, 'hb_check_feed_retrieve_status' ), 10, 2 );
	}

	/**
	 * Register menu pages
	 */
	function register_pf_custom_menu_pages() {

		// Top-level menu page
		add_menu_page(
			PF_TITLE, // <title>
			PF_TITLE, // menu title
			get_option('pf_menu_group_access', pf_get_defining_capability_by_role('contributor')), // cap required
			PF_MENU_SLUG, // slug
			array( $this, 'display_reader_builder' ), // callback
			PF_URL . 'pressforward-16.png', // icon URL
			24 // Position (just above comments - 25)
		);

		add_submenu_page(
			PF_MENU_SLUG,
			__('All Content', 'pf'),
			__('All Content', 'pf'),
			get_option('pf_menu_all_content_access', pf_get_defining_capability_by_role('contributor')),
			PF_MENU_SLUG,
			array($this, 'display_reader_builder')
		);

		add_submenu_page(
			PF_MENU_SLUG,
			__('Nominated', 'pf'),
			__('Nominated', 'pf'),
			get_option('pf_menu_under_review_access', pf_get_defining_capability_by_role('contributor')),
			PF_SLUG . '-review',
			array($this, 'display_review_builder')
		);

		// Feed-listing page is accessible only to Editors and above
		add_submenu_page(
			PF_MENU_SLUG,
			__('Add Feeds', 'pf'),
			__('Add Feeds', 'pf'),
			get_option('pf_menu_feeder_access', pf_get_defining_capability_by_role('editor')),
			PF_SLUG . '-feeder',
			array($this, 'display_feeder_builder')
		);

		if ( $alert_count = The_Alert_Box::alert_count() ) {
			$alert_count_notice = '<span class="feed-alerts count-' . intval( $alert_count ) . '"><span class="alert-count">' . number_format_i18n( $alert_count ) . '</span></span>';
			$subscribed_feeds_menu_text = sprintf( __( 'Subscribed Feeds %s', 'pf' ), $alert_count_notice );
		} else {
			$subscribed_feeds_menu_text = __( 'Subscribed Feeds', 'pf' );
		}

		add_submenu_page(
			PF_MENU_SLUG,
			__('Subscribed Feeds', 'pf'),
			$subscribed_feeds_menu_text,
			get_option('pf_menu_feeder_access', pf_get_defining_capability_by_role('editor')),
			'edit.php?post_type=' . pressforward()->pf_feeds->post_type
		);

		// Options page is accessible to contributors, setting visibility controlled by tab
		add_submenu_page(
			PF_MENU_SLUG,
			__('Preferences', 'pf'), // @todo sprintf
			__('Preferences', 'pf'),
			get_option('pf_menu_all_content_access', pf_get_defining_capability_by_role('contributor')),
			PF_SLUG . '-options',
			array($this, 'display_options_builder')
		);

		add_submenu_page(
			PF_MENU_SLUG,
			__('Tools', 'pf'),
			__('Tools', 'pf'),
			get_option('pf_menu_tools_access', pf_get_defining_capability_by_role('contributor')),
			PF_SLUG . '-tools',
			array($this, 'display_tools_builder')
		);

		add_submenu_page(
			PF_MENU_SLUG,
			__('Folders', 'pf'),
			__('Folders', 'pf'),
			get_option('pf_menu_feeder_access', pf_get_defining_capability_by_role('editor')),
			'edit-tags.php?taxonomy=' . pressforward()->pf_feeds->tag_taxonomy,
			''
		);
/**
		add_submenu_page(
			PF_MENU_SLUG,
			__('Add Nomination', 'pf'),
			__('Add Nomination', 'pf'),
			get_option('pf_menu_add_nomination_access', pf_get_defining_capability_by_role('contributor')),
			PF_NOM_POSTER
		);
**/

#		$verifyPages = array();

#		$pf_admin_pages = apply_filters('pf_admin_pages',$verifyPages);

	}

	function state_pf_admin_pages($thepages){

		$basePages = array(PF_SLUG . '-feeder',PF_SLUG . '-options',PF_SLUG . '-review',PF_MENU_SLUG);
		$thepages = array_merge($basePages, (array)$thepages);
		return $thepages;

	}

	function add_pf_body_class($classes) {

		$classes .= strtolower(PF_TITLE);

		return $classes;
	}

	function posted_submitbox_pf_actions(){
		global $post;
		$check = pf_get_post_meta($post->ID, 'item_link', true);
		if ( empty($check) ){
			return;
		}
	    $value = pf_get_post_meta($post->ID, 'pf_forward_to_origin', true);
	    if ( empty($value) ){

	    	$option_value = get_option('pf_link_to_source');
				if ( empty($option_value) ){
					$value = 'no-forward';
				} else {
					$value = 'forward';
				}
	    }

	    echo '<div class="misc-pub-section misc-pub-section-last">
				<label>
				<select id="pf_forward_to_origin_single" name="pf_forward_to_origin">
				  <option value="forward"'.( 'forward' == $value ? ' selected ' : '') .'>Forward</option>
				  <option value="no-forward"'.( 'no-forward' == $value ? ' selected ' : '') .'>Don\'t Forward</option>
				</select><br />
				to item\'s original URL</label></div>';
	}

	function save_submitbox_pf_actions( $post_id )
	{
	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){ return $post_id; }
	    if ( !current_user_can( 'edit_page', $post_id ) ){ return $post_id; }
		#var_dump($_POST['pf_forward_to_origin']); die();
		#$current = pf_get_post_meta();
			if ( !array_key_exists('pf_forward_to_origin', $_POST) ) {

 			} else {
				pf_update_meta($post_id, 'pf_forward_to_origin', $_POST['pf_forward_to_origin']);
			}

		return $post_id;
	}

	public function folderbox(){
		?>
			<div id="feed-folders">
					<?php printf(__('<h3>Folders</h3>'));
					pressforward()->pf_feeds->the_feed_folders();
					?>
				<div class="clear"></div>
			</div>
		<?php
	}

	public function tweet_intent($id){

		$url = 'https://twitter.com/intent/tweet?';
		$url .= 'text=' . urlencode(get_the_title($id));
		$url .= '&url=' . urlencode(get_the_item_link($id));
		$url .= '&via=' . urlencode('pressfwd');
		return $url;

	}

	public function dropdown_option($string, $id, $class = 'pf-top-menu-selection', $form_id = '', $schema_action = '', $schema_class = '', $href = '', $target = ''){

		$option = '<li role="presentation"><a role="menuitem" id="';
		$option .= $id;
		$option .= '" tabindex="-1" class="';
		$option .= $class;
		$option .= '"';

		$option .= ' href="';
		if (!empty($href)){
			$option .= $href;
		} else {
			$option .= '#';
		}
		$option .= '"';

		if (!empty($target)){
			$option .= ' target="'.$target.'"';
		}


		if (!empty($form_id)){
			$option .= ' data-form="' . $form_id . '" ';
		}

		if (!empty($schema_action)){
			$option .= ' pf-schema="' . $schema_action . '" ';
		}

		if (!empty($schema_class)){
			$option .= ' pf-schema-class="' . $schema_class . '" ';
		}

		$option .= '>';
		$option .= $string;
		$option .= '</a></li>';

		echo $option;

	}

	public function nav_bar($page = 'pf-menu'){
		?>
		<div class="display">
			<div class="pf-btns pull-left btn-toolbar">
				<?php if ( 'pf-review' != $page ) { ?>
					<div class="dropdown pf-view-dropdown btn-group" role="group">
					  <button class="btn btn-default btn-small dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
						<?php _e('View', 'pf'); ?>
						<span class="caret"></span>
					  </button>
						<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
						<?php
							$view_check = get_user_meta(pressforward()->form_of->user_id(), 'pf_user_read_state', true);
							if ('golist' == $view_check){
								self::dropdown_option(__('Grid', 'pf'), "gogrid", 'pf-top-menu-selection display-state');
								self::dropdown_option(__('List', 'pf'), "golist", 'pf-top-menu-selection unset display-state');
							} else {
								self::dropdown_option(__('Grid', 'pf'), "gogrid", 'pf-top-menu-selection unset display-state');
								self::dropdown_option(__('List', 'pf'), "golist", 'pf-top-menu-selection display-state');
							}
							$pf_user_scroll_switch = get_user_option('pf_user_scroll_switch', pressforward()->form_of->user_id());
							#empty or true
							if ('false' == $pf_user_scroll_switch){
								self::dropdown_option(__('Infinite Scroll (Reloads Page)', 'pf'), "goinfinite", 'pf-top-menu-selection scroll-toggler');
							} else {
								self::dropdown_option(__('Paginate (Reloads Page)', 'pf'), "gopaged", 'pf-top-menu-selection scroll-toggler');
							}

						?>
						 </ul>
					</div>
				<?php } ?>
				<div class="dropdown pf-filter-dropdown btn-group" role="group">
				  <button class="btn btn-default dropdown-toggle btn-small" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-expanded="true">
					<?php _e('Filter', 'pf'); ?>
					<span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu2">
					<?php
						if ( 'pf-review' != $page ){
							self::dropdown_option(__('Reset filter', 'pf'), "showNormal");
							self::dropdown_option(__('My starred', 'pf'), "showMyStarred");
							self::dropdown_option(__('Show hidden', 'pf'), "showMyHidden");
							self::dropdown_option(__('My nominations', 'pf'), "showMyNominations");
							self::dropdown_option(__('Unread', 'pf'), "showUnread");
							self::dropdown_option( __( 'Drafted', 'pf' ), "showDrafted" );
						} else {
							if ( isset($_POST['search-terms']) || isset($_GET['by']) || isset($_GET['pf-see']) || isset($_GET['reveal']) ) {
								self::dropdown_option(__('Reset filter', 'pf'), "showNormalNominations");
							}
							self::dropdown_option(__('My starred', 'pf'), "sortstarredonly", 'starredonly', null, null, null, get_admin_url(null, 'admin.php?page=pf-review&pf-see=starred-only'));
							self::dropdown_option(__('Toggle visibility of archived', 'pf'), "showarchived");
							self::dropdown_option(__('Only archived', 'pf'), "showarchiveonly", null, null, null, null, get_admin_url(null, 'admin.php?page=pf-review&pf-see=archive-only'));
							self::dropdown_option(__('Unread', 'pf'), "showUnreadOnly", null, null, null, null, get_admin_url(null, 'admin.php?page=pf-review&pf-see=unread-only'));
							self::dropdown_option( __( 'Drafted', 'pf' ), "showDrafted", null, null, null, null, get_admin_url(null, 'admin.php?page=pf-review&pf-see=drafted-only') );

						}
					?>
				  </ul>
				</div>
				<div class="dropdown pf-sort-dropdown btn-group" role="group">
				  <button class="btn btn-default dropdown-toggle btn-small" type="button" id="dropdownMenu3" data-toggle="dropdown" aria-expanded="true">
					<?php _e('Sort', 'pf'); ?>
					<span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu3">
					<?php
						self::dropdown_option(__('Reset', 'pf'), "sort-reset");
						self::dropdown_option(__('Date of item', 'pf'), "sortbyitemdate");
						self::dropdown_option(__('Date retrieved', 'pf'), "sortbyfeedindate");
						if ( 'pf-review' == $page ){
							self::dropdown_option(__('Date nominated', 'pf'), "sortbynomdate");
							self::dropdown_option(__('Nominations received', 'pf'), "sortbynomcount");
						}
					?>
					<?php #<li role="presentation"><a role="menuitem" tabindex="-1" href="#">Feed name</a></li> ?>
				  </ul>
				</div>
				<div class="btn-group" role="group">
					<a href="https://github.com/PressForward/pressforward/wiki" target="_blank" id="pf-help" class="btn btn-small"><?php _e('Need help?', 'pf'); ?></a>
				</div>
			</div>

			<div class="pull-right text-right">
			<!-- or http://thenounproject.com/noun/list/#icon-No9479? -->
				<?php
										add_filter('ab_alert_specimens_post_types', array($this, 'alert_filterer'));
										add_filter('ab_alert_safe', array($this, 'alert_safe_filterer'));
										$alerts = the_alert_box()->get_specimens();
										remove_filter('ab_alert_safe', array($this, 'alert_safe_filterer'));
										remove_filter('ab_alert_specimens_post_types', array($this, 'alert_filterer'));

					if ( 'pf-review' == $page ){
						echo '<button type="submit" class="delete btn btn-danger btn-small pull-left" id="archivenoms" value="' . __('Archive all', 'pf') . '" >' . __('Archive all', 'pf') . '</button>';
					}

					$user_ID = get_current_user_id();
					$pf_user_menu_set = get_user_option('pf_user_menu_set', $user_ID);
					if ('true' == $pf_user_menu_set){
						if (!empty($alerts) && (0 != $alerts->post_count)){
							echo '<a class="btn btn-small btn-warning" id="gomenu" href="#">' . __('Menu', 'pf') . ' <i class="icon-tasks"></i> (!)</a>';
						} else {
							echo '<a class="btn btn-small" id="gomenu" href="#">' . __('Menu', 'pf') . ' <i class="icon-tasks"></i></a>';
						}
					}
					echo '<a class="btn btn-small" id="gofolders" href="#">' . __('Folders', 'pf') . '</a>';
				?>

			</div>
		</div><!-- End btn-group -->
		<?php
	}

	public function toolbox($slug = 'allfeed', $version = 0, $deck = false){
		pressforward()->form_of->the_side_menu();

	}

	public function form_of_actions_btns($item, $c, $modal = false, $format = 'standard', $metadata = array(), $id_for_comments ){
			$item_id = 0;
			$user = wp_get_current_user();
			$user_id = $user->ID;
			if ($format == 'nomination'){
				$item_id = $metadata['item_id'];
			} else {
				$item_id = $item['item_id'];
			}
			?>

				<div class="actions pf-btns <?php if($modal){ echo 'modal-btns '; } else { echo ' article-btns '; } ?>">
					<?php
					$infoPop = 'top';
					$infoModalClass = ' modal-popover';
					if ($modal == false){
						#$infoPop = 'bottom';
						$infoModalClass = '';
						if ($format === 'nomination'){
							?><form name="form-<?php echo $metadata['item_id']; ?>" pf-form="<?php echo $metadata['item_id']; ?>"><?php
							pf_prep_item_for_submit($metadata);
							wp_nonce_field('nomination', PF_SLUG . '_nomination_nonce', false);
						} else {
						echo '<form name="form-' . $item['item_id'] . '">'
						 . '<div class="nominate-result-' . $item['item_id'] . '">'
						 . '<img class="loading-' . $item['item_id'] . '" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="' . __('Loading', 'pf') . '..." style="display: none" />'
						 . '</div>';
						pf_prep_item_for_submit($item);
						wp_nonce_field('nomination', PF_SLUG . '_nomination_nonce', false);
						}
						echo '</form>';
					}
					# Perhaps use http://twitter.github.com/bootstrap/javascript.html#popovers instead?
					echo '<button class="btn btn-small itemInfobutton" data-toggle="tooltip" title="' . __('Info', 'pf') .  '" id="info-' . $item['item_id'] . '-' . $infoPop . '" data-placement="' . $infoPop . '" data-class="info-box-popover'.$infoModalClass.'" data-title="" data-target="'.$item['item_id'].'"><i class="icon-info-sign"></i></button>';

					if (pf_is_item_starred_for_user( $id_for_comments, $user_id ) ){
						echo '<!-- item_id selected = ' . $item_id . ' -->';
						echo '<button class="btn btn-small star-item btn-warning" data-toggle="tooltip" title="' . __('Star', 'pf') .  '"><i class="icon-star"></i></button>';
					} else {
						echo '<button class="btn btn-small star-item" data-toggle="tooltip" title="' . __('Star', 'pf') .  '"><i class="icon-star"></i></button>';
					}

					# <a href="#" type="submit"  class="PleasePushMe"><i class="icon-plus"></i> Nominate</a>
					if (has_action('pf_comment_action_button')){
						$commentModalCall = '#modal-comments-' . $item['item_id'];
						$commentSet = array('id' => $id_for_comments, 'modal_state' => $modal);
						//echo $id_for_comments;
						do_action('pf_comment_action_button', $commentSet);

					}
					if ($format === 'nomination'){

						$nom_count_classes = 'btn btn-small nom-count';
						$metadata['nom_count'] = get_the_nomination_count();
						if ($metadata['nom_count'] > 0){
							$nom_count_classes .= ' btn-info';
						}

						echo '<a class="'.$nom_count_classes.'" data-toggle="tooltip" title="' . __('Nomination Count', 'pf') .  '" form="' . $metadata['nom_id'] . '">'.$metadata['nom_count'].'<i class="icon-play"></i></button></a>';
						$archive_status = '';
						if ( 1 == get_post_meta( $metadata['nom_id'], 'pf_archive', true ) ){
							$archive_status = 'btn-warning';
						}
						echo '<a class="btn btn-small nom-to-archive schema-switchable schema-actor '.$archive_status.'" pf-schema="archive" pf-schema-class="archived" pf-schema-class="btn-warning" data-toggle="tooltip" title="' . __('Archive', 'pf') .  '" form="' . $metadata['nom_id'] . '"><img src="' . PF_URL . 'assets/images/archive.png" /></button></a>';
						$draft_status = "";
						if ( ( 1 == pf_get_relationship_value( 'draft', $metadata['nom_id'], $user_id ) ) || ( 1 == pf_get_relationship_value( 'draft', $id_for_comments, $user_id ) ) ){
							$draft_status = 'btn-success';
						}
						echo '<a href="#nominate" class="btn btn-small nom-to-draft schema-actor '. $draft_status .'" pf-schema="draft" pf-schema-class="btn-success" form="' . $metadata['item_id'] . '" data-original-title="' . __('Draft', 'pf') .  '"><img src="' . PF_URL . 'assets/images/pressforward-licon.png" /></a>';

					} else {
						#var_dump(pf_get_relationship('nominate', $id_for_comments, $user_id));
						if ( ( 1 == pf_get_relationship_value('nominate', $id_for_comments, $user_id) ) || ( 1 == pf_get_relationship_value( 'draft', $id_for_comments, $user_id ) ) ){
							echo '<button class="btn btn-small nominate-now btn-success schema-actor schema-switchable" pf-schema="nominate" pf-schema-class="btn-success" form="' . $item['item_id'] . '" data-original-title="' . __('Nominated', 'pf') .  '"><img src="' . PF_URL . 'assets/images/pressforward-single-licon.png" /></button>';
							# Add option here for admin-level users to send items direct to draft.
						} else {
							echo '<button class="btn btn-small nominate-now schema-actor schema-switchable" pf-schema="nominate" pf-schema-class="btn-success" form="' . $item['item_id'] . '" data-original-title="' . __('Nominate', 'pf') .  '"><img src="' . PF_URL . 'assets/images/pressforward-single-licon.png" /></button>';
							# Add option here for admin-level users to send items direct to draft.

						}

					}

					$amplify_group_classes = 'dropdown btn-group amplify-group';
					$amplify_id = 'amplify-'.$item['item_id'];

					if($modal){
						$amplify_group_classes .= ' dropup';
						$amplify_id .= '-modal';
					}
					?>
					<div class="<?php echo $amplify_group_classes; ?>" role="group">
						<button type="button" class="btn btn-default btn-small dropdown-toggle pf-amplify" data-toggle="dropdown" aria-expanded="true" id="<?php echo $amplify_id; ?>"><i class="icon-bullhorn"></i><span class="caret"></button>
						<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="amplify-<?php echo $item['item_id']; ?>">
							<?php
								if (current_user_can( 'edit_others_posts' ) && 'nomination' != $format ){
									$send_to_draft_classes = 'amplify-option amplify-draft schema-actor';

									if ( 1 == pf_get_relationship_value( 'draft', $id_for_comments, $user_id ) ){
										$send_to_draft_classes .= ' btn-success';
									}

									self::dropdown_option(__('Send to ', 'pf').ucwords( get_option(PF_SLUG.'_draft_post_status', 'draft') ), "amplify-draft-".$item['item_id'], $send_to_draft_classes, $item['item_id'], 'draft', 'btn-success' );

							?>
									<li class="divider"></li>
							<?php
								}
								$tweet_intent = self::tweet_intent($id_for_comments);
								self::dropdown_option(__('Tweet', 'pf'), "amplify-tweet-".$item['item_id'], 'amplify-option', $item['item_id'], '', '', $tweet_intent, '_blank' );
								#self::dropdown_option(__('Facebook', 'pf'), "amplify-facebook-".$item['item_id'], 'amplify-option', $item['item_id'] );
								#self::dropdown_option(__('Instapaper', 'pf'), "amplify-instapaper-".$item['item_id'], 'amplify-option', $item['item_id'] );
								#self::dropdown_option(__('Tumblr', 'pf'), "amplify-tumblr-".$item['item_id'], 'amplify-option', $item['item_id'] );
								do_action( 'pf_amplify_buttons' );
							?>
						 </ul>
					</div>

					<?php
					if ($modal === true){
						?><button class="btn btn-small" data-dismiss="modal" aria-hidden="true">Close</button><?php
					}
					?>
				</div>

		<?php

				if (has_action('pf_comment_action_modal')){
						$commentModalCall = '#modal-comments-' . $item['item_id'];
						$commentSet = array('id' => $id_for_comments, 'modal_state' => $modal);
						//echo $id_for_comments;
						do_action('pf_comment_action_modal', $commentSet);

					}

	}

	/**
	 * Prep an item element for display based on position and element.
	 * Establishes the rules for item display.
	 * Position should be title, source, graf.
	**/

	public function display_a($string, $position = 'source', $page = 'list'){
		$title_ln_length = 30;
		$title_lns = 3;

		$source_ln_length = 48;
		$source_lns = 2;

		$graf_ln_length = 44;
		$graf_lns = 4;

		$max = 0;

		switch ($position){
			case 'title':
				$max = $title_ln_length * $title_lns;
				break;
			case 'source':
				$max = $source_ln_length * $source_lns;
				break;
			case 'graf':
				$max = $graf_ln_length * $graf_lns;
				break;
		}

		$cut = substr($string, 0, $max+1);
		$final_cut = substr($cut, 0, -4);
		if (strlen($cut) < $max){
			$cut = substr($string, 0, $max);
			return $cut;
		} else {
			$cut = $final_cut . ' ...';
			return $cut;
		}

	}

	/**
	 * Essentially the PF 'loop' template.
	 * $item = the each of the foreach
	 * $c = count.
	 * $format = format changes, to be used later or by plugins.
	**/
	public function form_of_an_item($item, $c, $format = 'standard', $metadata = array()){
		global $current_user;
		get_currentuserinfo();
		if ('' !== get_option('timezone_string')){
			//Allows plugins to introduce their own item format output.
			date_default_timezone_set(get_option('timezone_string'));
		}
		if (has_action('pf_output_items')){
			do_action('pf_output_items', $item, $c, $format);
			return;
		}
		$itemTagsArray = explode(",", $item['item_tags']);
		$itemTagClassesString = '';
				$user_id = $current_user->ID;
		foreach ($itemTagsArray as $itemTag) { $itemTagClassesString .= pf_slugger($itemTag, true, false, true); $itemTagClassesString .= ' '; }

				if ($format === 'nomination'){
					$feed_item_id = $metadata['item_id'];
					$id_for_comments = $metadata['item_feed_post_id']; //orig item post ID

					$id_for_comments = $metadata['item_feed_post_id'];
					$readStat = pf_get_relationship_value( 'read', $metadata['nom_id'], wp_get_current_user()->ID );
					if (!$readStat){ $readClass = ''; } else { $readClass = 'article-read'; }
					if (!isset($metadata['nom_id']) || empty($metadata['nom_id'])){ $metadata['nom_id'] = md5($item['item_title']); }
					if (empty($id_for_comments)){ $id_for_comments = $metadata['nom_id']; }
					if (empty($metadata['item_id'])){ $metadata['item_id'] = md5($item['item_title']); }

				} else {
					$feed_item_id = $item['item_id'];
					$id_for_comments = $item['post_id']; //orig item post ID
				}
				#$archive_status = pf_get_relationship_value( 'archive', $id_for_comments, wp_get_current_user()->ID );
				$archive_status = get_post_meta($id_for_comments, 'pf_archive', true);
				if (isset($_GET['pf-see'])){ } else { $_GET['pf-see'] = false; }
				if ($archive_status == 1 && ('archive-only' != $_GET['pf-see'])){
					$archived_status_string = 'archived';
					$dependent_style = 'display:none;';
				} elseif ( ($format === 'nomination') && (1 == get_post_meta($metadata['nom_id'], 'pf_archive', true))  && ('archive-only' != $_GET['pf-see'])) {
					$archived_status_string = 'archived';
					$dependent_style = 'display:none;';
				} else {
					$dependent_style = '';
					$archived_status_string = 'not-archived';
				}
		if ($format === 'nomination'){
			#$item = array_merge($metadata, $item);
			#var_dump($item);
			echo '<article class="feed-item entry nom-container ' . $archived_status_string . ' '. get_pf_nom_class_tags(array($metadata['submitters'], $metadata['nom_id'], $metadata['authors'], $metadata['nom_tags'], $metadata['item_tags'], $metadata['item_id'] )) . ' '.$readClass.'" id="' . $metadata['nom_id'] . '" style="' . $dependent_style . '" tabindex="' . $c . '" pf-post-id="' . $metadata['nom_id'] . '" pf-item-post-id="' . $id_for_comments . '" pf-feed-item-id="' . $metadata['item_id'] . '" pf-schema="read" pf-schema-class="article-read">';
			?> <a style="display:none;" name="modal-<?php echo $metadata['item_id']; ?>"></a> <?php
		} else {
			$id_for_comments = $item['post_id'];
			$readStat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
			if (!$readStat){ $readClass = ''; } else { $readClass = 'article-read'; }
			echo '<article class="feed-item entry ' . pf_slugger(get_the_source_title($id_for_comments), true, false, true) . ' ' . $itemTagClassesString . ' '.$readClass.'" id="' . $item['item_id'] . '" tabindex="' . $c . '" pf-post-id="' . $item['post_id'] . '" pf-feed-item-id="' . $item['item_id'] . '" pf-item-post-id="' . $id_for_comments . '" style="' . $dependent_style . '" >';
			?> <a style="display:none;" name="modal-<?php echo $item['item_id']; ?>"></a> <?php
		}

			if (empty($readStat)) {
				$readStat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
			}
			echo '<div class="box-controls">';
			if (current_user_can( 'manage_options' )){
				if ($format === 'nomination'){
					echo '<i class="icon-remove pf-item-remove" pf-post-id="' . $metadata['nom_id'] .'" title="Delete"></i>';
				} else {
					echo '<i class="icon-remove pf-item-remove" pf-post-id="' . $id_for_comments .'" title="Delete"></i>';
				}
			}
		if ($format != 'nomination'){
				$archiveStat =  pf_get_relationship_value( 'archive', $id_for_comments, $user_id );
				$extra_classes = '';
				if ($archiveStat){ $extra_classes .= ' schema-active relationship-button-active'; }
				echo '<i class="icon-eye-close hide-item pf-item-archive schema-archive schema-switchable schema-actor'.$extra_classes.'" pf-schema-class="relationship-button-active" pf-item-post-id="' . $id_for_comments .'" title="Hide" pf-schema="archive"></i>';
		}
			if (!$readStat){ $readClass = ''; } else { $readClass = 'marked-read'; }

			echo '<i class="icon-ok-sign schema-read schema-actor schema-switchable '.$readClass.'" pf-item-post-id="' . $id_for_comments .'" pf-schema="read" pf-schema-class="marked-read" title="Mark as Read"></i>';

			echo '</div>';
			?>
			<header> <?php
				echo '<h1 class="item_title"><a href="#modal-' . $item['item_id'] . '" class="item-expander schema-actor" role="button" data-toggle="modal" data-backdrop="false" pf-schema="read" pf-schema-targets="schema-read">' . self::display_a($item['item_title'], 'title') . '</a></h1>';
				echo '<p class="source_title">' . self::display_a(get_the_source_title($id_for_comments), 'source') . '</p>';
				if ($format === 'nomination'){
				?>
						<div class="sortable-hidden-meta" style="display:none;">
							<?php
							_e('UNIX timestamp from source RSS', 'pf');
							echo ': <span class="sortable_source_timestamp sortableitemdate">' . $metadata['timestamp_item_posted'] . '</span><br />';

							_e('UNIX timestamp last modified', 'pf');
							echo ': <span class="sortable_mod_timestamp">' . $metadata['timestamp_nom_last_modified'] . '</span><br />';

							_e('UNIX timestamp date nominated', 'pf');
							echo ': <span class="sortable_nom_timestamp">' . $metadata['timestamp_unix_date_nomed'] . '</span><br />';

							_e('Slug for origin site', 'pf');
							echo ': <span class="sortable_origin_link_slug">' . $metadata['source_slug'] . '</span><br />';

							//Add an action here for others to provide additional sortables.

						echo '</div>';
				}
									# Let's build an info box!
									//http://nicolasgallagher.com/pure-css-speech-bubbles/

									#$urlArray = parse_url($item['item_link']);
									$sourceLink = pressforward()->pf_feed_items->get_source_link($id_for_comments);
									$url_array = parse_url($sourceLink);
									$sourceLink = 'http://' . $url_array['host'];
									//http://nicolasgallagher.com/pure-css-speech-bubbles/demo/

									$ibox = '<div class="feed-item-info-box" id="info-box-' . $item['item_id'] . '">';
										$ibox .= '
										' . __('Feed', 'pf') . ': <span class="feed_title">' . get_the_source_title($id_for_comments) . '</span><br />
										' . __('Posted', 'pf') . ': <span class="feed_posted">' . date( 'M j, Y; g:ia' , strtotime($item['item_date'])) . '</span><br />
										' . __('Retrieved', 'pf') . ': <span class="item_meta item_meta_added_date">' . date( 'M j, Y; g:ia' , strtotime($item['item_added_date'])) . '</span><br />
										' . __('Authors', 'pf') . ': <span class="item_authors">' . $item['item_author'] . '</span><br />
										' . __('Origin', 'pf') . ': <span class="source_name"><a target ="_blank" href="' . $sourceLink . '">' . $sourceLink . '</a></span><br />
										' . __('Original Item', 'pf') . ': <span class="source_link"><a href="' . $item['item_link'] . '" class="item_url" target ="_blank">' . $item['item_title'] . '</a></span><br />
										' . __('Tags', 'pf') . ': <span class="item_tags">' . $item['item_tags'] . '</span><br />
										' . __('Times repeated in source', 'pf') . ': <span class="feed_repeat sortable_sources_repeat">' . $item['source_repeat'] . '</span><br />
										';
										if ($format === 'nomination'){

											$ibox .= __('Number of nominations received', 'pf')
											. ': <span class="sortable_nom_count">' . $metadata['nom_count'] . '</span><br />'
											. __('First submitted by', 'pf')
											. ': <span class="first_submitter">' . $metadata['submitters'] . '</span><br />'
											. __('Nominated on', 'pf')
											. ': <span class="nominated_on">' . date( 'M j, Y; g:ia' , strtotime($metadata['date_nominated'])) . '</span><br />'
											. __('Nominated by', 'pf')
											. ': <span class="nominated_by">' . get_the_nominating_users() . '</span><br />';
										}

										$draft_id = pf_is_drafted($feed_item_id);
										if ( false != $draft_id && (current_user_can('edit_post', $draft_id)) ){
											#http://codex.wordpress.org/Function_Reference/edit_post_link
											$edit_url = get_edit_post_link($draft_id );
											$ibox .= '<br /><a class="edit_draft_from_info_box" href="'.$edit_url.'">' . __('Edit the draft based on this post.', 'pf') . '</a><br/>';
										}

									$ibox .= '</div>';
									echo $ibox;
													?>
									<script type="text/javascript">

											var pop_title_<?php echo $item['item_id'] ?> = '';
											var pop_html_<?php echo $item['item_id'] ?> = jQuery('#<?php echo 'info-box-' . $item['item_id']; ?>');


									</script>
									<?php
				$this->form_of_actions_btns($item, $c, false, $format, $metadata, $id_for_comments);
				?>
			</header>
			<?php
						//echo '<a name="' . $c . '" style="display:none;"></a>';
/**
			echo '<script type="text/javascript">
					jQuery(window).load(function() {
						jQuery("#' . $item['item_id'] . '").on("show", function () {
							jQuery("#excerpt' . $c . '").hide("slow");
						});

						jQuery("#' . $item['item_id'] . '").on("hide", function () {
							jQuery("#excerpt' . $c . '").show("slow");
						});
					});
				</script>';
**/
			?>
			<div class="content">
				<?php
					if (($item['item_feat_img'] != '') && ($format != 'nomination')){
						echo '<div style="float:left; margin-right: 10px; margin-bottom: 10px;"><img src="' . $item['item_feat_img'] . '"></div>';
					}

				?> <div style="display:none;"> <?php
					echo '<div class="item_meta item_meta_date">Published on ' . $item['item_date'] . ' by <span class="item-authorship">' . $item['item_author'] . '</span>.</div>';
					echo 'Unix timestamp for item date:<span class="sortableitemdate">' . strtotime($item['item_date']) . '</span> and for added to feed date <span class="sortablerssdate">' . strtotime($item['item_added_date']) . '</span>.';
				?> </div> <?php

				echo '<div class="item_excerpt" id="excerpt' . $c . '">';
						if ($format === 'nomination'){
							echo'<p>' . pf_noms_excerpt($item['item_content']) . '</p>';
						} else {
							echo'<p>' . self::display_a(pf_feed_excerpt($item['item_content']), 'graf') . '</p>';
						}
					echo '</div>';
/**
						echo '<div id="collapse' . $c . '" class="accordion-body collapse">';
						echo '<div class="accordion-inner">';
						echo '<div class="row-fluid">';
							echo '<div class="span12 item_content">';
								echo '<div>' . $item['item_content'] . '</div>';
								echo '<br />';
								echo '<a target="_blank" href="' . $item['item_link'] . '">' . __('Read More', 'pf') . '</a>';
								echo '<br />';
								echo '<strong class="item-tags">' . __('Item Tags', 'pf') . '</strong>: ' . $item['item_tags'] . '.';
								echo '<br />';
							echo '</div><!-- end item_content span12 -->';
						echo '</div><!-- End row-fluid -->';
						echo '</div>';
						echo '</div>';
						//print_r($item);
						//print_r($ent = htmlentities($item['item_content']));
						//print_r(html_entity_decode($ent));
**/

				?>
			</div><!-- End content -->
			<footer>
				<p class="pubdate"><?php echo date( 'F j, Y; g:i a' , strtotime($item['item_date'])); ?></p>
			</footer>
			<?php
				//Allows plugins to introduce their own item format output.
				if (has_action('pf_output_modal')){
					do_action('pf_output_modal', $item, $c, $format);

				} else {
			?>
			<!-- Begin Modal -->
			<div id="modal-<?php echo $item['item_id']; ?>" class="modal hide fade pfmodal" tabindex="-1" role="dialog" aria-labelledby="modal-<?php echo $item['item_id']; ?>-label" aria-hidden="true" pf-item-id="<?php echo $item['item_id']; ?>" pf-post-id="<?php echo $item['post_id']; ?>" pf-readability-status="<?php echo $item['readable_status']; ?>">
			  <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
				<div class="modal-mobile-nav pull-right hidden-desktop">
					<div class="mobile-goPrev pull-left">

					</div>
					<div class="mobile-goNext pull-right">

					</div>
				</div>
				<h3 id="modal-<?php echo $item['item_id']; ?>-label" class="modal_item_title"><?php echo $item['item_title']; ?></h3>
				<?php
					echo '<em>' . __('Source', 'pf') . ': ' . get_the_source_title($id_for_comments) . '</em> | ';
					echo __('Author', 'pf').': '.get_the_item_author($id_for_comments);
				?>
			  </div>
			  <div class="row-fluid modal-body-row">
				  <div class="modal-body span9" id="modal-body-<?php echo $item['item_id']; ?>">
					<?php
					$contentObj = new pf_htmlchecker($item['item_content']);
					$text = $contentObj->closetags($item['item_content']);
					echo $text;

					?>
				  </div>
				  <div class="modal-sidebar span3 hidden-tablet">
					<div class="goPrev modal-side-item row-fluid">

					</div>
					<div class="modal-comments modal-side-item row-fluid">

					</div>
					<div class="goNext modal-side-item row-fluid">

					</div>
				  </div>
			  </div>
			  <div class="modal-footer">
				<div class="row-fluid">
				<div class="pull-left original-link">
					<a target="_blank" href="<?php echo $item['item_link']; ?>"><?php _e('Read Original', 'pf'); ?></a>
					<?php
					#if ($format != 'nomination'){
						?>
						| <a class="modal-readability-reset" target="#readable" href="<?php echo $item['item_link']; ?>" pf-item-id="<?php echo $item['item_id']; ?>" pf-post-id="<?php echo $item['post_id']; ?>" pf-modal-id="#modal-<?php echo $item['item_id']; ?>"><?php  _e('Reset Readability', 'pf'); ?></a>
						<?php
					#}
					?>
				</div>
				<div class="pull-right"><?php
				$this->form_of_actions_btns($item, $c, true, $format, $metadata, $id_for_comments);
				?></div><?php
				?>
				</div>
				<div class="item-tags pull-left row-fluid">
				<?php
					echo '<strong>' . __('Item Tags', 'pf') . '</strong>: ' . $item['item_tags'];
				?>
				</div>
			  </div>
			</div>
			<!-- End Modal -->
		</article><!-- End article -->
		<?php
		}
	}

	public function pf_search_template(){
		?>
			<form id="feeds-search" method="post" action="<?php echo basename($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING'] . '&action=post'; ?>">
					<label for="search-terms">Search</label>
				<input type="text" name="search-terms" id="search-terms" placeholder="Enter search terms">
				<input type="submit" class="btn btn-small" value="Search">
			</form>
		<?php
	}

	/**
	 * Display function for the main All Content panel
	 */
	public function display_reader_builder() {
		$userObj = wp_get_current_user();
		$user_id = $userObj->ID;
		//Calling the feedlist within the pf class.
		if (isset($_GET["pc"])){
			$page = $_GET["pc"];
			$page_c = $page-1;
		} else {
			$page = 0;
			$page_c = 0;
		}
		$count = $page_c * 20;
		$extra_class = '';
		if(isset($_GET['reveal']) && ('no_hidden' == $_GET['reveal'])){
			$extra_class .= ' archived_visible';
		}
		$view_state = ' grid';
		$view_check = get_user_meta($user_id, 'pf_user_read_state', true);
		if ('golist' == $view_check){
			$view_state = ' list';
		}
		$extra_class = $extra_class.$view_state;

	?>
	<div class="pf-loader"></div>
	<div class="pf_container pf-all-content full<?php echo $extra_class; ?>">
		<header id="app-banner">
			<div class="title-span title">
				<?php

					pressforward()->form_of->the_page_headline();

				?>
				<button class="btn btn-small" id="fullscreenfeed"> <?php  _e('Full Screen', 'pf');  ?> </button>
			</div><!-- End title -->
			<?php self::pf_search_template(); ?>

		</header><!-- End Header -->
		<?php
			self::nav_bar();
		?>
		<div role="main">
			<?php $this->toolbox(); ?>
			<?php $this->folderbox(); ?>
			<div id="entries">
				<?php echo '<img class="loading-top" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />';  ?>
				<div id="errors">
				<?php
					if (0 >= self::count_the_posts('pf_feed')){
						echo '<p>You need to add feeds, there are none in the system.</p>';
					}
				?>
				</div>


			<?php

				pressforward()->form_of->nominate_this('as_feed_item');

				//Use this foreach loop to go through the overall feedlist, select each individual feed item (post) and do stuff with it.
				//Based off SimplePie's tutorial at http://simplepie.org/wiki/tutorial/how_to_display_previous_feed_items_like_google_reader.
				$c = 1;
				$ic = 0;
				$c = $c+$count;
					//print_r($count);
			if (isset($_GET['by'])){
				$limit = $_GET['by'];
			} else {
				$limit = false;
			}
			#var_dump($limit);

			$archive_feed_args = array(
				'start'            => $count + 1,
				'posts_per_page'   => false,
				'relationship'     => $limit,
			);

			if ( isset( $_POST['search-terms'] ) ) {
				$archive_feed_args['search_terms'] = stripslashes( $_POST['search-terms'] );
				$archive_feed_args['exclude_archived'] = true;
			}

			if ( ! isset( $_GET['reveal'] ) ) {
				$archive_feed_args['exclude_archived'] = true;
			}

			if ( isset( $_GET['reveal'] ) ) {
				$archive_feed_args['reveal'] = stripslashes( $_GET['reveal'] );
			}

			foreach ( pressforward()->pf_feed_items->archive_feed_to_display( $archive_feed_args ) as $item ) {

				$this->form_of_an_item($item, $c);

				$c++;

				//check out the built comment form from EditFlow at https://github.com/danielbachhuber/Edit-Flow/blob/master/modules/editorial-comments/editorial-comments.php

				// So, we're going to need some AJAXery method of sending RSS data to a nominations post.
				// Best example I can think of? The editorial comments from EditFlow, see edit-flow/modules/editorial-comments/editorial-comments.php, esp ln 284
				// But lets start simple and get the hang of AJAX in WP first. http://wp.tutsplus.com/articles/getting-started-with-ajax-wordpress-pagination/
				// Eventually should use http://wpseek.com/wp_insert_post/ I think....
				// So what to submit? I could store all the post data in hidden fields and submit it within seperate form docs, but that's a lot of data.
				// Perhaps just an md5 hash of the ID of the post? Then use the retrieval function to find the matching post and submit it properly?
				// Something to experement with...
			} // End foreach

		?><div class="clear"></div><?php
		echo '</div><!-- End entries -->';
		?><div class="clear"></div><?php
	echo '</div><!-- End main -->';

		//Nasty hack because infinite scroll only works starting with page 2 for some reason.
		if ($page == 0){ $page = 1; }
		$pagePrev = $page-1;
		$pageNext = $page+1;
		if (!empty($_GET['by'])){
			$limit_q = '&by=' . $limit;
		} else {
			$limit_q = '';
		}
		$pagePrev = '?page=pf-menu'.$limit_q.'&pc=' . $pagePrev;
		$pageNext = '?page=pf-menu'.$limit_q.'&pc=' . $pageNext;
		if (isset($_GET['folder'])){
			$pageQ = $_GET['folder'];
			$pageQed = '&folder=' . $pageQ;
			$pageNext .= $pageQed;
			$pagePrev .= $pageQed;

		}
		if (isset($_GET['feed'])){
			$pageQ = $_GET['feed'];
			$pageQed = '&feed=' . $pageQ;
			$pageNext .= $pageQed;
			$pagePrev .= $pageQed;

		}
        if ($c > 19){

            echo '<div class="pf-navigation">';
            if (-1 > $pagePrev){
                echo '<!-- something has gone wrong -->';
            } elseif (1 > $pagePrev){
            	echo '<span class="feedprev"><a class="prevnav" href="admin.php?page=pf-menu">Previous Page</a></span> | ';
            } elseif ($pagePrev > -1) {
            	echo '<span class="feedprev"><a class="prevnav" href="admin.php' . $pagePrev . '">Previous Page</a></span> | ';
            }
            echo '<span class="feednext"><a class="nextnav" href="admin.php' . $pageNext . '">Next Page</a></span>';
            echo '</div>';
        }
	?><div class="clear"></div><?php
	echo '</div><!-- End container-fluid -->';
	}

	/**
	 * Display function for the Under Review panel
	 */
	function display_review_builder() {
		include( PF_ROOT . "/includes/under-review/under-review.php" );

	}

	function pf_ajax_get_comments(){
			if (has_action('pf_modal_comments')){
				$id_for_comments = $_POST['id_for_comments'];
				do_action('pf_modal_comments', $id_for_comments);
			}
			die();
	}

	function pf_get_user_role_select($option, $default){
		global $wp_roles;
		$roles = $wp_roles->get_names();
		$enabled = get_option($option, $default);
#		$roleObj = pf_get_role_by_capability($enabled, true, true);
#		$enabled_role = $roleObj->name;
		foreach ($roles as $slug=>$role){
			$defining_capability = pf_get_defining_capability_by_role($slug);
			?><option value="<?php echo $defining_capability ?>" <?php selected( $enabled, $defining_capability ) ?>><?php _e( $role, PF_SLUG ) ?></option><?php
		}
	}

	/**
	 * Display function for the Options panel
	 */
	function display_options_builder() {
		pressforward()->form_of->the_settings_page();
	}

	/**
	* Display function for Feeder panel
	*/
	function display_tools_builder() {
		pressforward()->tools->the_settings_page();
	}

	/**
	 * Display function for Feeder panel
	 */
	function display_feeder_builder() {

		pressforward()->add_feeds->the_settings_page();


	}

    public function pf_alert_displayer(){
        add_filter('ab_alert_specimens_post_types', array($this, 'alert_filterer'));
        add_filter('ab_alert_safe', array($this, 'alert_safe_filterer'));
        add_filter('ab_alert_specimens_check_message', array($this, 'alert_check_message'));
        add_filter('ab_alert_specimens_delete_all_text', array($this, 'alert_delete_all_message'));
            the_alert_box()->alert_box_outsides();
        remove_filter('ab_alert_specimens_delete_all_text', array($this, 'alert_delete_all_message'));
        remove_filter('ab_alert_specimens_check_message', array($this, 'alert_check_message'));
        remove_filter('ab_alert_safe', array($this, 'alert_safe_filterer'));
        remove_filter('ab_alert_specimens_post_types', array($this, 'alert_filterer'));
    }

    public function alert_filterer($post_types){
        return array(pressforward()->pf_feeds->post_type);
    }

    public function alert_check_message($msg){
        return __('Are you sure you want to delete all feeds with alerts?', 'pf');
    }

    public function alert_delete_all_message($msg){
        return __('Delete all feeds with alerts', 'pf');
    }

    public function alert_safe_filterer($safe_msg){
        return __('All feeds are ok!', 'pf');
    }

	function admin_notices_action() {
		settings_errors( 'add_pf_feeds' );
	}

	function include_alerts_in_edit_feeds( $query ){
		global $pagenow;
		if ( is_admin() && 'edit.php' === $pagenow && 'pf_feed' === $_GET['post_type'] ) {
			#$statuses = $query->query['post_status'];
			#var_dump('<pre>'); var_dump( $query ); die();
			#$query->query['post_status'] = '';
			#$query->query_vars['post_status'] = '';
		}
		return $query;
	}

	function pf_ab_bug_status_args( $args ){
		$args['public'] = true;

		return $args;
	}

	//This function can add js and css that we need to specific admin pages.
	function add_admin_scripts($hook) {

		//This gets the current page the user is on.
		global $pagenow;

		$user_ID = get_current_user_id();
		$pf_user_scroll_switch = get_user_option('pf_user_scroll_switch', $user_ID);

			wp_register_style( PF_SLUG . '-style', PF_URL . 'assets/css/style.css');
			wp_register_style( PF_SLUG . '-bootstrap-style', PF_URL . 'lib/twitter-bootstrap/css/bootstrap.css');
			wp_register_style( PF_SLUG . '-bootstrap-responsive-style', PF_URL . 'lib/twitter-bootstrap/css/bootstrap-responsive.css');
			wp_register_script(PF_SLUG . '-twitter-bootstrap', PF_URL . 'lib/twitter-bootstrap/js/bootstrap.js' , array( 'jquery' ));
			wp_register_style( PF_SLUG . '-susy-style', PF_URL . 'assets/css/susy.css');
			wp_register_style( PF_SLUG . '-reset-style', PF_URL . 'assets/css/reset.css');
			wp_register_style( PF_SLUG . '-settings-style', PF_URL . 'assets/css/pf-settings.css');

			wp_register_script(PF_SLUG . '-views', PF_URL . 'assets/js/views.js', array( PF_SLUG . '-twitter-bootstrap', 'jquery-ui-core', 'jquery-effects-slide'  ));
			wp_register_script(PF_SLUG . '-readability-imp', PF_URL . 'assets/js/readability-imp.js', array( PF_SLUG . '-twitter-bootstrap', 'jquery', PF_SLUG . '-views' ));
			wp_register_script(PF_SLUG . '-infiniscroll', PF_URL . 'lib/jquery.infinitescroll.js', array( 'jquery', PF_SLUG . '-views', PF_SLUG . '-readability-imp', 'jquery' ));
			wp_register_script(PF_SLUG . '-scrollimp', PF_URL . 'assets/js/scroll-imp.js', array( PF_SLUG . '-infiniscroll', 'pf-relationships', PF_SLUG . '-views'));
			wp_register_script('pf-relationships', PF_URL . 'assets/js/relationships.js', array( 'jquery' ));
			wp_register_style( PF_SLUG . '-responsive-style', PF_URL . 'assets/css/pf-responsive.css', array(PF_SLUG . '-reset-style', PF_SLUG . '-style', PF_SLUG . '-bootstrap-style', PF_SLUG . '-susy-style'));
			wp_register_script(PF_SLUG . '-tinysort', PF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_register_script(PF_SLUG . '-media-query-imp', PF_URL . 'assets/js/media-query-imp.js', array( 'jquery', 'thickbox', 'media-upload' ));
			wp_register_script(PF_SLUG . '-sort-imp', PF_URL . 'assets/js/sort-imp.js', array( PF_SLUG . '-tinysort', PF_SLUG . '-twitter-bootstrap', PF_SLUG . '-jq-fullscreen' ));
			wp_register_script( PF_SLUG . '-quick-edit', PF_URL . 'assets/js/quick-edit.js', array( 'jquery' ) );
			wp_register_script( PF_SLUG . '-settings-tools', PF_URL . 'assets/js/settings-tools.js', array( 'jquery' ) );
			wp_register_script( PF_SLUG . '-tools', PF_URL . 'assets/js/tools-imp.js', array( 'jquery' ) );

		wp_register_style('pf-alert-styles', PF_URL . 'assets/css/alert-styles.css');
		wp_enqueue_style( PF_SLUG . '-alert-styles' );
		if ( false != pressforward()->form_of->is_a_pf_page() ){
			//var_dump('heartbeat'); die();
			wp_enqueue_script( 'heartbeat' );
			wp_enqueue_script( 'jquery-ui-progressbar' );
			wp_enqueue_script( PF_SLUG . '-heartbeat', PF_URL . 'assets/js/pf-heartbeat.js', array( 'heartbeat', 'jquery-ui-progressbar', 'jquery' ) );

		}
		//print_r($hook);
		//This if loop will check to make sure we are on the right page for the js we are going to use.
		if (('toplevel_page_pf-menu') == $hook) {
			//And now lets enqueue the script, ensuring that jQuery is already active.

			wp_enqueue_script(PF_SLUG . '-tinysort');
			wp_enqueue_script(PF_SLUG . '-sort-imp');
			wp_enqueue_script(PF_SLUG . '-views');
			wp_enqueue_script(PF_SLUG . '-readability-imp');
			wp_enqueue_script(PF_SLUG . '-nomination-imp', PF_URL . 'assets/js/nomination-imp.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-twitter-bootstrap');
			wp_enqueue_script(PF_SLUG . '-jq-fullscreen', PF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			if (empty($pf_user_scroll_switch) || 'true' == $pf_user_scroll_switch){
				wp_enqueue_script(PF_SLUG . '-infiniscroll');
				wp_enqueue_script(PF_SLUG . '-scrollimp');
			}
			wp_enqueue_script('pf-relationships');
			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style(PF_SLUG . '-bootstrap-style');
			wp_enqueue_style(PF_SLUG . '-bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			wp_enqueue_style( PF_SLUG . '-susy-style' );
			wp_enqueue_style( PF_SLUG . '-responsive-style' );

		}
		if (('pressforward_page_pf-review') == $hook) {
			wp_enqueue_script(PF_SLUG . '-tinysort');
			wp_enqueue_script(PF_SLUG . '-sort-imp');
			wp_enqueue_script(PF_SLUG . '-jq-fullscreen', PF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-twitter-bootstrap');
			wp_enqueue_script(PF_SLUG . '-send-to-draft-imp', PF_URL . 'assets/js/send-to-draft-imp.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-archive-nom-imp', PF_URL . 'assets/js/nom-archive-imp.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-views');
			wp_enqueue_script(PF_SLUG . '-readability-imp');

			if (empty($pf_user_scroll_switch) || 'true' == $pf_user_scroll_switch){
				wp_enqueue_script(PF_SLUG . '-infiniscroll');
				wp_enqueue_script(PF_SLUG . '-scrollimp');
			}

			wp_enqueue_script('pf-relationships');
			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style(PF_SLUG . '-bootstrap-style');
			wp_enqueue_style(PF_SLUG . '-bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			wp_enqueue_style( PF_SLUG . '-susy-style' );
			wp_enqueue_script( 'post' );
			wp_enqueue_style( PF_SLUG . '-responsive-style' );
		}

		if (('pressforward_page_pf-tools') == $hook) {
			wp_enqueue_script(PF_SLUG . '-jq-fullscreen', PF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-twitter-bootstrap');
			wp_enqueue_script(PF_SLUG . '-tools');
			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style(PF_SLUG . '-bootstrap-style');
			wp_enqueue_style(PF_SLUG . '-bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			wp_enqueue_style( PF_SLUG . '-susy-style' );
			wp_enqueue_style( PF_SLUG . '-responsive-style' );
			wp_enqueue_style( PF_SLUG . '-settings-style' );
			wp_enqueue_script(PF_SLUG . '-settings-tools' );
		}
		if (('pressforward_page_pf-options') == $hook) {
			wp_enqueue_style( PF_SLUG . '-settings-style' );

			wp_enqueue_script(PF_SLUG . '-settings-tools' );
		}

		if (('nomination') == get_post_type()) {
			wp_enqueue_script(PF_SLUG . '-add-nom-imp', PF_URL . 'assets/js/add-nom-imp.js', array( 'jquery' ));
		}

		if ( 'edit.php' === $hook && 'pf_feed' === get_post_type() ) {
			wp_enqueue_script( PF_SLUG . '-quick-edit' );
			wp_enqueue_style(PF_SLUG . '-subscribed-styles', PF_URL . 'assets/css/pf-subscribed.css' );
		}

		if (('pressforward_page_pf-feeder') != $hook) { return; }
		else {
			//And now lets enqueue the script, ensuring that jQuery is already active.

			wp_enqueue_media();

			wp_enqueue_script(PF_SLUG . '-tinysort', PF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_enqueue_script(PF_SLUG . '-twitter-bootstrap');

			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style(PF_SLUG . '-bootstrap-style');
			wp_enqueue_style(PF_SLUG . '-bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			wp_enqueue_style( PF_SLUG . '-susy-style' );
			wp_enqueue_style( PF_SLUG . '-responsive-style' );
			wp_enqueue_style('thickbox');
			wp_enqueue_script( PF_SLUG . '-media-query-imp' );
			wp_enqueue_style( PF_SLUG . '-settings-style' );
			wp_enqueue_script(PF_SLUG . '-settings-tools' );

		}

	}

	/**
	 * @todo Looks like this was tester code that doesn't do anything important
	 */
	function widget_array(){
		$widgets = array(
				'first_widget' => array(
						'title' => 'Widget Title',
						'slug' => 'first_widget',
						'callback' => array($this, 'widget_one_call')
									)
							);

		return $widgets;
	}

	/**
	 * @todo Looks like this was tester code that doesn't do anything important
	 */
	function widget_one_call(){
		echo '<div class="navwidget">	Widget Body <br />	<a href="#20">Test link to item 20.</a>	</div>'	;
	}

	function pf_options_admin_page_save() {
		global $pagenow;

		if ( 'admin.php' != $pagenow ) {
			return;
		}

		if ( empty( $_POST['submit'] ) ) {
			return;
		}

		check_admin_referer( 'pf_settings' );

		if (current_user_can( get_option('pf_menu_all_content_access', pf_get_defining_capability_by_role('contributor')) ) ){
			$user_ID = get_current_user_id();
			if (isset( $_POST['pf_user_scroll_switch'] )){
				$pf_user_scroll_switch = $_POST['pf_user_scroll_switch'];
				//var_dump($pf_user_scroll_switch); die();
				update_user_option($user_ID, 'pf_user_scroll_switch', $pf_user_scroll_switch);
			} else {
				update_user_option($user_ID, 'pf_user_scroll_switch', 'false');
			}

			if (isset( $_POST['pf_user_menu_set'] )){
				$pf_user_menu_set = $_POST['pf_user_menu_set'];
				//var_dump($pf_user_scroll_switch); die();
				update_user_option($user_ID, 'pf_user_menu_set', $pf_user_menu_set);
			} else {
				update_user_option($user_ID, 'pf_user_menu_set', 'false');
			}

			if (isset( $_POST['pf_pagefull'] )){
				$pf_pagefull = $_POST['pf_pagefull'];
				//var_dump($pf_user_scroll_switch); die();
				update_user_option($user_ID, 'pf_pagefull', $pf_pagefull);
			} else {
				update_user_option($user_ID, 'pf_pagefull', 'false');
			}

		}

		$verifyPages = array();

		$pf_admin_pages = apply_filters('pf_admin_pages',$verifyPages);

		if (! in_array($_GET['page'], $pf_admin_pages)){
			return;
		}

		if ( current_user_can( get_option('pf_menu_preferences_access', pf_get_defining_capability_by_role('administrator')) ) ){


			$arrayedAdminRights = array(
				'pf_menu_group_access'	=>	array(
												'default'=>'contributor',
												'title'=>__( 'PressForward Menu Group', 'pf' )
											),
				'pf_menu_all_content_access'=>array(
												'default'=>'contributor',
												'title'=>__( 'All Content Menu', 'pf' )
											),
				'pf_menu_under_review_access'=>array(
												'default'=>'contributor',
												'title'=>__( 'Nominated Menu', 'pf' )
											),
				'pf_menu_preferences_access'=>array(
												'default'=>'administrator',
												'title'=>__( 'Preferences Menu', 'pf' )
											),
				'pf_menu_feeder_access'=>array(
												'default'=>'editor',
												'title'=>__( 'Add Feeds', 'pf' )
											),
				'pf_menu_add_nomination_access'=>array(
												'default'=>'contributor',
												'title'=> __( 'Add Nomination Menu', 'pf' )
											)
			);

			$arrayedAdminRights = apply_filters('pf_setup_admin_rights',$arrayedAdminRights);

			foreach($arrayedAdminRights as $right=>$parts){
				if (isset( $_POST[$right] )){
					$enabled = $_POST[$right];
					update_option( $right, $enabled );
				}
			}

			if (isset( $_POST['pf_link_to_source'] )){
				$pf_links_opt_check = $_POST['pf_link_to_source'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_link_to_source', $pf_links_opt_check);
			} else {
				update_option('pf_link_to_source', 0);
			}


			if (isset( $_POST['pf_retain_time'] )){
				$pf_links_opt_check = $_POST['pf_retain_time'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_retain_time', $pf_links_opt_check);
			} else {
				update_option('pf_retain_time', 2);
			}

			if (isset( $_POST['pf_errors_until_alert'] )){
				$pf_errors_until_alert = $_POST['pf_errors_until_alert'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_errors_until_alert', $pf_errors_until_alert);
			} else {
				update_option('pf_errors_until_alert', 3);
			}
			if (isset( $_POST[PF_SLUG.'_retrieval_frequency'] )){
				$pf_retrieval_frequency = $_POST[PF_SLUG.'_retrieval_frequency'];
				//print_r($pf_links_opt_check); die();
				update_option(PF_SLUG.'_retrieval_frequency', $pf_retrieval_frequency);
			} else {
				update_option(PF_SLUG.'_retrieval_frequency', 30);
			}
			if (isset( $_POST['pf_present_author_as_primary'] )){
				$pf_author_opt_check = $_POST['pf_present_author_as_primary'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_present_author_as_primary', $pf_author_opt_check);
			} else {
				update_option('pf_present_author_as_primary', 'no');
			}

			$pf_draft_post_type = (!empty( $_POST[PF_SLUG . '_draft_post_type'] ) )
				? $_POST[PF_SLUG . '_draft_post_type']
				: 'post';
			update_option(PF_SLUG . '_draft_post_type', $pf_draft_post_type);

			$pf_draft_post_status = (!empty( $_POST[PF_SLUG . '_draft_post_status'] ) )
				? $_POST[PF_SLUG . '_draft_post_status']
				: 'draft';
			update_option(PF_SLUG . '_draft_post_status', $pf_draft_post_status);

			if (class_exists('The_Alert_Box')){
				#var_dump($_POST);
				if(empty($_POST[the_alert_box()->option_name()])){
					#var_dump('<pre>'); var_dump($_POST); var_dump('</pre>');
					update_option(the_alert_box()->option_name(), 'false');
				} else {
					update_option(the_alert_box()->option_name(), $_POST[the_alert_box()->option_name()]);
				}
			}

			if (isset( $_POST['pf_use_advanced_user_roles'] )){
				$pf_author_opt_check = $_POST['pf_use_advanced_user_roles'];
				//print_r($pf_links_opt_check); die();
				update_option('pf_use_advanced_user_roles', $pf_author_opt_check);
			} else {
				update_option('pf_use_advanced_user_roles', 'no');
			}
		}

		do_action( 'pf_admin_op_page_save' );
	}

	function pf_aggregation_forwarder(){
		if(1 == get_option('pf_link_to_source',0)){
			//http://webmaster.iu.edu/tools-and-guides/maintenance/redirect-meta-refresh.phtml ?
			$linked = get_post_meta('item_link', true);
			//Need syndicate tag here.
			if (is_single() && ('' != $linked)){
				?>
				 <script type="text/javascript">alert('You are being redirected to the source item.');</script>
				<META HTTP-EQUIV="refresh" CONTENT="10;URL=<?php echo get_post_meta('item_link', true); ?>">
				<?php

			}
		}
	}


	public function register_feed_item_removed_status(){

		$args = array(
			'label'						=>	_x('Removed Feed Item', 'pf' ),
			'public'					=>	false,
			'exclude_from_search'		=>	true,
			'show_in_admin_all_list'	=>	false,
			'show_in_admin_status_list'	=>	false,
			'label_count'				=>	_n_noop( 'Removed <span class="count">(%s)</span>', 'Removed <span class="count">(%s)</span>' )
		);

		register_post_status('removed_feed_item', $args);

	}

	public function count_the_posts($post_type, $date_less = false){

				if (!$date_less){
					$query_arg = array(
						'post_type' 		=> $post_type,
						'posts_per_page' 	=> -1
					);
				} else {
					if (!empty($date_less) && $date_less < 12) {
						$y = date('Y');
						$m = date('m');
						$m = $m + $date_less;
					} elseif (!empty($date_less) && $date_less >= 12) {
						$y = date('Y');
						$y = $y - floor($date_less/12);
						$m = date('m');
						$m = $m - (abs($date_less)-(12*floor($date_less/12)));
					}
					$query_arg = array(
						'post_type' 		=> $post_type,
						'year'				=> $y,
						'monthnum'			=> $m,
						'posts_per_page' 	=> -1
					);
				}


				$query = new WP_Query($query_arg);
				$post_count = $query->post_count;
				wp_reset_postdata();

		return $post_count;
	}

	public function search_the_posts($s, $post_type){

		$args = array(
			's'			=>  $s,
			'post_type' => $post_type

		);

		$q = WP_Query($args);
		return $q;

	}


	public function pf_ajax_move_to_archive(){
		$item_post_id = $_POST['item_post_id'];
		$nom_id = $_POST['nom_id'];
		update_post_meta($nom_id, 'pf_archive', 1);
		update_post_meta($item_post_id, 'pf_archive', 1);
		$check = wp_update_post( array(
					'ID'			=>	$item_post_id,
					'post_status'	=>	'removed_feed_item'
				)
			);
		pf_log($check);
		die();
	}

	public function pf_ajax_move_out_of_archive(){
		$item_post_id = $_POST['item_post_id'];
		$nom_id = $_POST['nom_id'];
		update_post_meta($nom_id, 'pf_archive', 'false');
		update_post_meta($item_post_id, 'pf_archive', 'false');
		$check = wp_update_post( array(
					'ID'			=>	$item_post_id,
					'post_status'	=>	'publish'
				)
			);
		pf_log($check);
		die();
	}

    public function dead_post_status(){
        register_post_status('removed_feed_item', array(
            'label'                 =>     _x('Removed Feed Item', 'pf'),
            'public'                =>      false,
            'exclude_from_search'   =>      true,
            'show_in_admin_all_list'=>      false
        ) );
    }

    public function dead_feed_status(){
        register_post_status('removed_'.pressforward()->pf_feeds->post_type, array(
            'label'                 =>     _x('Removed Feed', 'pf'),
            'public'                =>      false,
            'exclude_from_search'   =>      true,
            'show_in_admin_all_list'=>      false
        ) );
    }

    public function pf_delete_children_of_feeds( $post_id ){
    	if ( pressforward()->pf_feeds->post_type == get_post_type( $post_id ) ){
    		pf_log('Delete a feed and all its children.');
		pf_delete_item_tree( $post_id );
    	}
    }

    public function pf_trash_children_of_feeds( $post_id ){
    	if ( pressforward()->pf_feeds->post_type == get_post_type( $post_id ) ){
    		pf_log('Trash a feed and all its children.');
    		$this->pf_thing_trasher( $post_id, true, pressforward()->pf_feeds->post_type );
    	}
    }

	function pf_thing_trasher($id = 0, $readability_status = false, $item_type = 'feed_item'){
		if ($id == 0)
			return new WP_Error('noID', __("No ID supplied for deletion", 'pf'));

		pf_log('On trash hook:');
		# Note: this will also remove feed items if a feed is deleted, is that something we want?
		if ($readability_status || $readability_status > 0){
			if ( 'feed_item' == $item_type ){
				$post_type = pf_feed_item_post_type();
			} else {
				$post_type = $item_type;
			}
			$args = array(
				'post_parent' => $id,
				'post_type'   => $post_type
			);
			$attachments = get_children($args);
			pf_log('Get Children of '.$id);
			pf_log($attachments);
			foreach ($attachments as $attachment) {
				wp_trash_post($attachment->ID, true);
			}
		}

		return $id;

	}

	function pf_bad_call($action, $msg = 'You made a bad call and it did not work. Try again.'){
		$response = array(
			'what'=>'pressforward',
			'action'=>$action,
			'id'=>pressforward()->form_of->user_id(),
			'data'=>$msg,
			'supplemental' => array(
					'buffered' => ob_get_contents(),
					'timestamp' => gmdate( 'd-M-Y H:i:s' )
			)
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		ob_end_clean();
		die();
	}

	function pf_ajax_thing_deleter() {
		ob_start();
		if(isset($_POST['post_id'])){
			$id = $_POST['post_id'];
		} else {
			self::pf_bad_call('pf_ajax_thing_deleter','Option not sent');
		}
		if(isset($_POST['made_readable'])){
			$read_status = $_POST['made_readable'];
		} else { $read_status = false; }
		$returned = pf_delete_item_tree( $id, true );
		var_dump($returned);
		$vd = ob_get_clean();
		ob_end_clean();
		$response = array(
		   'what'=>'pressforward',
		   'action'=>'pf_ajax_thing_deleter',
		   'id'=>$id,
		   'data'=>(string)$vd
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		die();

	}

	function pf_ajax_retain_display_setting() {
		ob_start();
		if(isset($_POST['pf_read_state'])){
			$read_state = $_POST['pf_read_state'];
		} else {
			$read_status = false;
		}
		$userObj = wp_get_current_user();
		$user_id = $userObj->ID;
		$returned = $this->pf_switch_display_setting($user_id, $read_state);
		#var_dump($user_id);

		$response = array(
			'what'=>'pressforward',
			'action'=>'pf_ajax_retain_display_setting',
			'id'=>$user_id,
			'data'=>(string) $returned
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		ob_end_clean();
		die();

	}

	function pf_ajax_user_setting() {
		ob_start();
		if(isset($_POST['pf_user_setting'])){
			$setting_name = $_POST['pf_user_setting'];
		} else {
			$setting_name = false;
			self::pf_bad_call('pf_ajax_user_setting', 'No setting name, try again.');
		}
		if(isset($_POST['setting'])){
			$setting = $_POST['setting'];
		} else {
			$setting = false;
		}

		$user_id = pressforward()->form_of->user_id();
		$returned = $this->pf_switch_user_option($user_id, $setting_name, $setting);
		#var_dump($user_id);

		$response = array(
			'what'=>'pressforward',
			'action'=>'pf_ajax_user_setting',
			'id'=>$user_id,
			'data'=>(string) $returned,
			'supplemental' => array(
					'buffered' => ob_get_contents(),
					'setting' => $setting_name,
					'set'		=> $setting
			)
		);
		$xmlResponse = new WP_Ajax_Response($response);
		$xmlResponse->send();
		ob_end_clean();
		die();

	}


	public function pf_switch_display_setting($user_id, $read_state){
		if ( !current_user_can( 'edit_user', $user_id ) ){
			return false;
		}

		$check = update_user_meta($user_id, 'pf_user_read_state', $read_state);
		return $check;
	}


	function pf_switch_user_option($user_id, $option, $state){
		if ( !current_user_can( 'edit_user', $user_id ) ){
			return false;
		}

		$check = update_user_option($user_id, $option, $state);
		return $check;
	}

	/**
	 * Add a Last Retrieved column to the pf_feed table.
	 *
	 * @since 3.4.0
	 *
	 * @param array $posts_columns Column headers.
	 * @return array
	 */
	public function add_last_retrieved_date_column( $posts_columns ) {
		#unset( $posts_columns['date'] );
		$posts_columns['last_retrieved'] = __('Last Time Feed Item Retrieved', 'pf');
		return $posts_columns;
	}

	/**
	 * Add a Last Checked column to the pf_feed table.
	 *
	 * @since 3.5.0
	 *
	 * @param array $posts_columns Column headers.
	 * @return array
	 */
	public function add_last_checked_date_column( $posts_columns ) {
		#unset( $posts_columns['date'] );
		$posts_columns['last_checked'] = __('Last Time Feed Checked', 'pf');
		return $posts_columns;
	}

	/**
	 * Content of the Last Retrieved column.
	 *
	 * We also hide the feed URL in this column, so we can reveal it on Quick Edit.
	 *
	 * @since 3.4.0
	 *
	 * @param string $column_name Column ID.
	 * @param int $post_id ID of the post for the current row in the table.
	 */
	public function last_retrieved_date_column_content( $column_name, $post_id ) {
		if ( 'last_retrieved' !== $column_name ) {
			return;
		}

		$last_retrieved = get_post_meta( $post_id, 'pf_feed_last_retrieved', true );

		if ( '' === $last_retrieved ) {
			$lr_text = '-';
		} else {
			// Modified from WP_Posts_List_Table
			$lr_unix = mysql2date( 'G', $last_retrieved, false );
			$time_diff = time() - $lr_unix;
			$t_time = date( 'Y/m/d g:i:s A', $lr_unix );

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$lr_text = sprintf( __( '%s ago' ), human_time_diff( $lr_unix ) );
			} else {
				$lr_text = mysql2date( __( 'Y/m/d' ), $last_retrieved );
			}

			$lr_text = '<abbr title="' . $t_time . '">' . $lr_text . '</abbr>';
		}

		$feed_url = get_post_meta( $post_id, 'feedUrl', true );
		$lr_text .= sprintf( '<input type="hidden" id="pf-feed-%d-url" value="%s" />', intval( $post_id ), esc_attr( $feed_url ) );

		echo $lr_text;
	}

	/**
	 * Content of the Last Checked column.
	 *
	 * We also hide the feed URL in this column, so we can reveal it on Quick Edit.
	 *
	 * @since 3.5.0
	 *
	 * @param string $column_name Column ID.
	 * @param int $post_id ID of the post for the current row in the table.
	 */
	public function last_checked_date_column_content( $column_name, $post_id ) {
		if ( 'last_checked' !== $column_name ) {
			return;
		}

		$last_retrieved = get_post_meta( $post_id, 'pf_feed_last_checked', true );

		if ( '' === $last_retrieved ) {
			$lr_text = '-';
		} else {
			// Modified from WP_Posts_List_Table
			$lr_unix = mysql2date( 'G', $last_retrieved, false );
			$time_diff = time() - $lr_unix;
			$t_time = date( 'Y/m/d g:i:s A', $lr_unix );

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$lr_text = sprintf( __( '%s ago' ), human_time_diff( $lr_unix ) );
			} else {
				$lr_text = mysql2date( __( 'Y/m/d' ), $last_retrieved );
			}

			$lr_text = '<abbr title="' . $t_time . '">' . $lr_text . '</abbr>';
		}

		$feed_url = get_post_meta( $post_id, 'feedUrl', true );
		$lr_text .= sprintf( '<input type="hidden" id="pf-feed-%d-url" value="%s" />', intval( $post_id ), esc_attr( $feed_url ) );

		echo $lr_text;
	}

	/**
	 * Add the Last Retrieved column to the list of sortable columns.
	 *
	 * @since 3.4.0
	 *
	 * @param array $sortable Sortable column identifiers.
	 * @return array
	 */
	public function make_last_retrieved_column_sortable( $sortable ) {
		$sortable['last_retrieved'] = array( 'last_retrieved', true );
		return $sortable;
	}

	/**
	 * Add the Last Checked column to the list of sortable columns.
	 *
	 * @since 3.5.0
	 *
	 * @param array $sortable Sortable column identifiers.
	 * @return array
	 */
	public function make_last_checked_column_sortable( $sortable ) {
		$sortable['last_checked'] = array( 'last_checked', true );
		return $sortable;
	}

	/**
	 * Enable 'last_retrieved' sorting.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_Query
	 */
	public function sort_by_last_retrieved( $query ) {
		// For now, only enable this sorting when on the edit-pf_feed screen
		// This could be lifted in the future to enable last_retrieved
		// sorting throughout PF
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen->id ) || 'edit-pf_feed' !== $screen->id ) {
			return;
		}

		// Sanity check: only modify pf_feed queries
		$feed_post_type = '';
		if ( ! empty( pressforward()->pf_feeds->post_type ) ) {
			$feed_post_type = pressforward()->pf_feeds->post_type;
		}

		if ( empty( $query->query_vars['post_type'] ) || $feed_post_type !== $query->query_vars['post_type'] ) {
			return;
		}

		// Only touch if we're sorting by last_retrieved
		if ( ! isset( $query->query_vars['orderby'] ) || 'last_retrieved' !== $query->query_vars['orderby'] ) {
			return;
		}

		// Should never happen, but if someone's doing a meta_query,
		// bail or we'll mess it up
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			return;
		}

		$query->set( 'orderby', 'pf_feed_last_retrieved' );

		// In order to ensure that we get the items without a
		// Last Retrieved key set, force the meta_query to an OR with
		// NOT EXISTS
		$query->set( 'meta_query', array(
			'relation' => 'OR',
			array(
				'key' => 'pf_feed_last_retrieved',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key' => 'pf_feed_last_retrieved',
				'compare' => 'EXISTS',
			)
		) );

		#var_dump($query); die();


	}

	/**
	 * Enable 'last_checked' sorting.
	 *
	 * @since 3.5.0
	 *
	 * @param WP_Query
	 */
	public function sort_by_last_checked( $query ) {
		// For now, only enable this sorting when on the edit-pf_feed screen
		// This could be lifted in the future to enable last_retrieved
		// sorting throughout PF
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen->id ) || 'edit-pf_feed' !== $screen->id ) {
			return;
		}

		// Sanity check: only modify pf_feed queries
		$feed_post_type = '';
		if ( ! empty( pressforward()->pf_feeds->post_type ) ) {
			$feed_post_type = pressforward()->pf_feeds->post_type;
		}

		if ( empty( $query->query_vars['post_type'] ) || $feed_post_type !== $query->query_vars['post_type'] ) {
			return;
		}

		// Only touch if we're sorting by last_retrieved
		if ( ! isset( $query->query_vars['orderby'] ) || 'last_checked' !== $query->query_vars['orderby'] ) {
			return;
		}

		// Should never happen, but if someone's doing a meta_query,
		// bail or we'll mess it up
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			return;
		}

		$query->set( 'orderby', 'pf_feed_last_checked' );

		// In order to ensure that we get the items without a
		// Last Retrieved key set, force the meta_query to an OR with
		// NOT EXISTS
		$query->set( 'meta_query', array(
			'relation' => 'OR',
			array(
				'key' => 'pf_feed_last_checked',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key' => 'pf_feed_last_checked',
				'compare' => 'EXISTS',
			)
		) );
	}

	/**
	 * Echo the output for the Feed URL field on Quick Edit.
	 *
	 * @since 3.5.0
	 *
	 * @param string $column_name Name of the Quick Edit column being output.
	 * @param string $post_type   Name of the current post type.
	 */
	public function quick_edit_field( $column_name, $post_type ) {
		if ( 'pf_feed' !== $post_type || 'last_retrieved' !== $column_name ) {
			return;
		}

		wp_nonce_field( 'pf-quick-edit', '_pf_quick_edit_nonce', false );

		?>
		<fieldset class="inline-edit-pressforward">
			<div class="inline-edit-col">
				<label for="pf-feed-url">
					<span class="title"><?php _e( 'Feed URL', 'pressforward' ) ?></span>
					<span class="input-text-wrap">
						<input class="inline-edit-pf-feed-input" type="text" value="" name="pf-quick-edit-feed-url" id="pf-quick-edit-feed-url" />
					</span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Process Quick Edit saves.
	 *
	 * Feed URL can be edited via Quick Save.
	 *
	 * @since 3.5.0
	 *
	 * @param int     $post_id ID of the post being edited.
	 * @param WP_Post $post    Post object.
	 */
	public function quick_edit_save( $post_id, $post ) {
		// Only process on the correct post type.
		if ( 'pf_feed' !== $post->post_type ) {
			return;
		}

		// Nonce check.
		if ( ! isset( $_POST['_pf_quick_edit_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_pf_quick_edit_nonce'], 'pf-quick-edit' ) ) {
			return;
		}

		// Don't process if the URL field is not present in the request.
		if ( ! isset( $_POST['pf-quick-edit-feed-url'] ) ) {
			return;
		}

		$feed_url = stripslashes( $_POST['pf-quick-edit-feed-url'] );

		update_post_meta( $post_id, 'feedUrl', $feed_url );
	}

	public function hb_check_feed_retrieve_status( $response, $data, $screen_id = '' ){
		/**
		 * $feed_hb_state = array(
		 * 'feed_id'	=>	$aFeed->ID,
		 * 'feed_title'	=> $aFeed->post_title,
		 * 'last_key'	=> $last_key,
		 * 'feeds_iteration'	=>	$feeds_iteration,
		 * 'total_feeds'	=>	count($feedlist)
		 * );
		**/
		if ( (array_key_exists('pf_heartbeat_request', $data)) && ('feed_state' == $data['pf_heartbeat_request']) ){
			$feed_hb_state = get_option( PF_SLUG.'_feeds_hb_state' );
			foreach ( $feed_hb_state as $key=>$state ){
				$response['pf_'.$key] = $state;
			}
		}

		return $response;

	}

	/**
	 * Launch a batch delete, if one is queued.
	 *
	 * @since 3.6
	 */
	public function launch_batch_delete() {
		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		pf_launch_batch_delete();
	}

	/////////////////////////
	//    AJAX HANDLERS    //
	/////////////////////////

	public function build_a_nomination() {
		pressforward()->nominations->build_nomination();
		die();
	}

	public function build_a_nom_draft() {
		pressforward()->nominations->build_nom_draft();
		die();
	}

	public function trigger_source_data() {
		$message = pressforward()->pf_retrieve->trigger_source_data(true);
		wp_send_json($message);
		die();
	}

	public function trigger_item_disassembly() {
		$message = pressforward()->pf_feed_items->ajax_feed_items_disassembler();
		#wp_send_json($message);
		die();
	}

	public function reset_feed() {
		pressforward()->pf_feed_items->reset_feed();
		die();
	}

	public function make_it_readable() {
		pressforward()->readability->make_it_readable();
		die();
	}

	public function archive_a_nom() {
		pressforward()->nominations->archive_a_nom();
		die();
	}

	public function simple_nom_to_draft(){
		pressforward()->nominations->simple_nom_to_draft();
		die();
	}
}
