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

		// AJAX handlers
		add_action( 'wp_ajax_build_a_nomination', array( $this, 'build_a_nomination') );
		add_action( 'wp_ajax_build_a_nom_draft', array( $this, 'build_a_nom_draft') );
		add_action( 'wp_ajax_assemble_feed_for_pull', array( $this, 'trigger_source_data') );
		add_action( 'wp_ajax_reset_feed', array( $this, 'reset_feed') );
		add_action( 'wp_ajax_make_it_readable', array( $this, 'make_it_readable') );
		add_action( 'wp_ajax_archive_a_nom', array( $this, 'archive_a_nom') );
		add_action( 'wp_ajax_ajax_get_comments', array( $this, 'ajax_get_comments') );
		add_action( 'wp_ajax_pf_ajax_thing_deleter', array( $this, 'pf_ajax_thing_deleter') );
		add_action( 'wp_ajax_pf_ajax_retain_display_setting', array( $this, 'pf_ajax_retain_display_setting' ) );
		add_action( 'init', array( $this, 'register_feed_item_removed_status') );

		// Modify the Subscribed Feeds panel
		add_filter( 'manage_pf_feed_posts_columns', array( $this, 'add_last_retrieved_date_column' ) );
		add_action( 'manage_pf_feed_posts_custom_column', array( $this, 'last_retrieved_date_column_content' ), 10, 2 );
		add_action( 'manage_edit-pf_feed_sortable_columns', array( $this, 'make_last_retrieved_column_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_last_retrieved' ) );
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

		add_submenu_page(
			PF_MENU_SLUG,
			__('Subscribed Feeds', 'pf'),
			__('Subscribed Feeds', 'pf'),
			get_option('pf_menu_feeder_access', pf_get_defining_capability_by_role('editor')),
			'edit.php?post_type=' . pressforward()->pf_feeds->post_type
		);

		// Options page is accessible only to Administrators
		add_submenu_page(
			PF_MENU_SLUG,
			__('Preferences', 'pf'), // @todo sprintf
			__('Preferences', 'pf'),
			get_option('pf_menu_preferences_access', pf_get_defining_capability_by_role('administrator')),
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

	public function folderbox(){
		?>
			<div id="feed-folders">
					<?php printf(__('<h3>Folders</h3>'));
					pressforward()->pf_feeds->the_feed_folders();
					?>
			</div>
		<?php
	}

	public function toolbox($slug = 'allfeed', $version = 0, $deck = false){
		global $hook_suffix;
		if(!empty($hook_suffix)){
			$slug = $hook_suffix;
		}
		?>
		<div id="tools">
			<?php
		#Widgets
			#echo '<a href="#" id="settings" class="button">Settings</a>';
			echo '<div class="primary-btn-tools">';
			if ( $slug == 'pressforward_page_pf-review' && (get_bloginfo('version') >= 3.7) && $version >= 0 && current_user_can(pf_get_defining_capability_by_role('administrator'))){
				?>
						<button type="submit" class="btn btn-warning pull-right" id="archivebefore" value="<?php  _e('Archive before', 'pf');  ?>:" ><?php  _e('Archive before', 'pf');  ?>:</button>
						<select class="pull-right" id="archiveBeforeOption">
							<option value="1week">Older than 1 week</option>
							<option value="2weeks">Older than 2 weeks</option>
							<option value="1month">Older than 1 month</option>
							<option value="1year">Before this year</option>
						</select>
				<?php
			}
			echo '</div>';
				?>
                <div class="alert-box">
                    <h3><span>Feed Problems</span></h3>
                    <div class="inside">
                    <?php
                        self::pf_alert_displayer();
                    ?>
                    </div>
                </div>

			<?php if ($slug == 'toplevel_page_pf-menu' && $version >= 0 && current_user_can(pf_get_defining_capability_by_role('administrator'))){
				?>

						<button type="submit" class="delete btn btn-danger pull-right" id="deletefeedarchive" value="<?php  _e('Delete all items', 'pf');  ?>" ><?php  _e('Delete all items', 'pf');  ?></button>
				<?php
			}

			do_action('pf_side_menu_widgets', $slug);
			?>

		</div>
		<?php

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
				<div class="actions pf-btns <?php if($modal){ echo 'modal-btns '; } ?>">
					<?php
					$infoPop = 'top';
					if ($modal == false){
						$infoPop = 'bottom';
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
					echo '<button class="btn btn-small itemInfobutton" data-toggle="tooltip" title="' . __('Info', 'pf') .  '" id="info-' . $item['item_id'] . '-' . $infoPop . '" data-placement="' . $infoPop . '" data-class="info-box-popover" data-title="" data-target="'.$item['item_id'].'"><i class="icon-info-sign"></i></button>';

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
						if ( 1 == pf_get_relationship_value( 'archive', $metadata['nom_id'], $user_id ) ){
							$archive_status = 'btn-warning';
						}
						echo '<a class="btn btn-small nom-to-archive schema-actor '.$archive_status.'" pf-schema="archive" pf-schema-class="archived" data-toggle="tooltip" title="' . __('Archive', 'pf') .  '" form="' . $metadata['nom_id'] . '"><img src="' . PF_URL . 'assets/images/archive.png" /></button></a>';
						$draft_status = "";
						if ( 1 == pf_get_relationship_value( 'draft', $metadata['nom_id'], $user_id ) ){
							$draft_status = 'btn-success';
						}
						echo '<a href="#nominate" class="btn btn-small nom-to-draft schema-actor '. $draft_status .'" pf-schema="draft" pf-schema-class="btn-success" form="' . $metadata['item_id'] . '" data-original-title="' . __('Draft', 'pf') .  '"><img src="' . PF_URL . 'assets/images/pressforward-licon.png" /></a>';

					} else {
						#var_dump(pf_get_relationship('nominate', $id_for_comments, $user_id));
						if (1 == pf_get_relationship_value('nominate', $id_for_comments, $user_id)){
							echo '<button class="btn btn-small nominate-now btn-success schema-actor schema-switchable" pf-schema="nominate" pf-schema-class="btn-success" form="' . $item['item_id'] . '" data-original-title="' . __('Nominated', 'pf') .  '"><img src="' . PF_URL . 'assets/images/pressforward-single-licon.png" /></button>';
							# Add option here for admin-level users to send items direct to draft.
						} else {
							echo '<button class="btn btn-small nominate-now schema-actor schema-switchable" pf-schema="nominate" pf-schema-class="btn-success" form="' . $item['item_id'] . '" data-original-title="' . __('Nominate', 'pf') .  '"><img src="' . PF_URL . 'assets/images/pressforward-single-licon.png" /></button>';
							# Add option here for admin-level users to send items direct to draft.
						}
					}



					?>
						<!-- <script type="text/javascript">

						 </script> -->
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
					$id_for_comments = $metadata['item_feed_post_id'];

			$id_for_comments = $metadata['item_feed_post_id'];
			$readStat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
			if (!$readStat){ $readClass = ''; } else { $readClass = 'article-read'; }
			if (!isset($metadata['nom_id']) || empty($metadata['nom_id'])){ $metadata['nom_id'] = md5($item['item_title']); }
			if (empty($id_for_comments)){ $id_for_comments = $metadata['nom_id']; }
			if (empty($metadata['item_id'])){ $metadata['item_id'] = md5($item['item_title']); }

				} else {
					$feed_item_id = $item['item_id'];
					$id_for_comments = $item['post_id'];
				}
				$archive_status = pf_get_relationship_value( 'archive', $id_for_comments, wp_get_current_user()->ID );
				if (isset($_GET['pf-see'])){ } else { $_GET['pf-see'] = false; }
				if ($archive_status == 1 && ('archive-only' != $_GET['pf-see'])){
					$archived_status_string = 'archived';
					$dependent_style = 'display:none;';
				} elseif ( ($format === 'nomination') && (1 == pf_get_relationship_value( 'archive', $metadata['nom_id'], $user_id))  && ('archive-only' != $_GET['pf-see'])) {
					$archived_status_string = 'archived';
					$dependent_style = 'display:none;';
				} else {
					$dependent_style = '';
					$archived_status_string = '';
				}
		if ($format === 'nomination'){
			#$item = array_merge($metadata, $item);
			#var_dump($item);
			echo '<article class="feed-item entry nom-container ' . $archived_status_string . ' '. get_pf_nom_class_tags(array($metadata['submitters'], $metadata['nom_id'], $metadata['authors'], $metadata['nom_tags'], $metadata['item_tags'], $metadata['item_id'] )) . ' '.$readClass.'" id="' . $metadata['nom_id'] . '" style="' . $dependent_style . '" tabindex="' . $c . '" pf-post-id="' . $metadata['nom_id'] . '" pf-item-post-id="' . $id_for_comments . '" pf-feed-item-id="' . $metadata['item_id'] . '" pf-schema="read" pf-schema-class="article-read">';
		} else {
			$id_for_comments = $item['post_id'];
			$readStat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
			if (!$readStat){ $readClass = ''; } else { $readClass = 'article-read'; }
			echo '<article class="feed-item entry ' . pf_slugger(get_the_source_title($id_for_comments), true, false, true) . ' ' . $itemTagClassesString . ' '.$readClass.'" id="' . $item['item_id'] . '" tabindex="' . $c . '" pf-post-id="' . $item['post_id'] . '" pf-feed-item-id="' . $item['item_id'] . '" pf-item-post-id="' . $id_for_comments . '" >';
		}

			$readStat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
			echo '<div class="box-controls">';
			if (current_user_can( 'manage_options' )){
				if ($format === 'nomination'){
					echo '<i class="icon-remove pf-item-remove" pf-post-id="' . $metadata['nom_id'] .'" title="Delete"></i>';
				} else {
					echo '<i class="icon-remove pf-item-remove" pf-post-id="' . $id_for_comments .'" title="Delete"></i>';
				}
			}
			$archiveStat = pf_get_relationship_value( 'archive', $id_for_comments, $user_id );
			$extra_classes = '';
			if ($archiveStat){ $extra_classes .= ' relationship-button-active'; }
			echo '<i class="icon-eye-close hide-item pf-item-archive schema-archive schema-actor'.$extra_classes.'" pf-item-post-id="' . $id_for_comments .'" title="Hide" pf-schema="archive"></i>';

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
					echo '<em>' . __('Source', 'pf') . ': ' . get_the_source_title($id_for_comments) . '</em> | ';
					echo __('Author', 'pf').': '.get_the_item_author($id_for_comments).' | ';
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
			$page = $page-1;
		} else {
			$page = 0;
		}
		$count = $page * 20;
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
	<div class="pf_container full<?php echo $extra_class; ?>">
		<header id="app-banner">
			<div class="title-span title">
				<?php echo '<h1>' . PF_TITLE . '</h1>'; ?>
				<?php
					if ($page > 0) {
						$pageNumForPrint = sprintf( __('Page %1$d', 'pf'), $page);
						echo '<span> - ' . $pageNumForPrint . '</span>';
					}
					if (!empty($_POST['search-terms'])){
						echo ' | <span class="search-term-title">' . __('Search for:', 'pf') . ' ' . $_POST['search-terms'] . '</span>';
					}
				?>
				<span id="h-after"> &#8226; </span>
				<button type="submit" class="refreshfeed btn btn-small" id="refreshfeed" value="<?php  _e('Refresh', 'pf')  ?>"><?php  _e('Refresh', 'pf');  ?></button>
				<button class="btn btn-small" id="fullscreenfeed"> <?php  _e('Full Screen', 'pf');  ?> </button>
			</div><!-- End title -->
			<?php self::pf_search_template(); ?>

		</header><!-- End Header -->
		<div class="display">
			<div class="pf-btns pull-left">
			<button type="submit" id="gogrid" class="btn btn-small display-state">Grid</button>
			<button type="submit" id="golist" class="btn btn-small display-state">List</button>

			<?php echo '<button type="submit" class="btn btn-small feedsort" id="sortbyitemdate" value="' . __('Sort by item date', 'pf') . '" >' . __('Sort by item date', 'pf') . '</button>';
			echo '<button type="submit" class="btn btn-small feedsort" id="sortbyfeedindate" value="' . __('Sort by date entered feed', 'pf') . '">' . __('Sort by date entered feed', 'pf') . '</button>'; ?>
				<button type="submit" class="btn btn-info pull-right btn-small" id="showMyHidden" value="<?php  _e('Show hidden', 'pf');  ?>" ><?php  _e('Show hidden', 'pf');  ?></button>
				<button type="submit" class="btn btn-info pull-right btn-small" id="showMyNominations" value="<?php  _e('Show my nominations', 'pf');  ?>" ><?php  _e('Show my nominations', 'pf');  ?></button>
				<button type="submit" class="btn btn-info pull-right btn-small" id="showMyStarred" value="<?php  _e('Show my starred', 'pf');  ?>" ><?php  _e('Show my starred', 'pf');  ?></button>
				<?php
					if (isset($_GET['by']) || isset($_POST['search-terms']) || isset($_GET['reveal']) || isset($_GET['folder']) || isset($_GET['feed'])){
						?><button type="submit" class="btn btn-info btn-small pull-right" id="showNormal" value="<?php  _e('Show all', 'pf');  ?>" ><?php  _e('Show all', 'pf');  ?></button><?php
					}
								?>
			</div>
			<div class="pull-right text-right">
			<!-- or http://thenounproject.com/noun/list/#icon-No9479? -->
				<?php
					add_filter('ab_alert_specimens_post_types', array($this, 'alert_filterer'));
										add_filter('ab_alert_safe', array($this, 'alert_safe_filterer'));
										$alerts = the_alert_box()->get_specimens();
										remove_filter('ab_alert_safe', array($this, 'alert_safe_filterer'));
										remove_filter('ab_alert_specimens_post_types', array($this, 'alert_filterer'));

										if (!empty($alerts) && (0 != $alerts->post_count)){
											echo '<a class="btn btn-small btn-warning" id="gomenu" href="#">' . __('Menu', 'pf') . ' <i class="icon-tasks"></i> (!)</a>';
										} else {
											echo '<a class="btn btn-small" id="gomenu" href="#">' . __('Menu', 'pf') . ' <i class="icon-tasks"></i></a>';
										}
										echo '<a class="btn btn-small" id="gofolders" href="#">' . __('Folders', 'pf') . '</a>';
										?>

			</div>
		</div><!-- End btn-group -->
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
		if (isset($pageQ)){
			$pageQ = $_GET['by'];
			$pageQed = '&by=' . $pageQ;
			$pageNext .= $pageQed;
			$pageNext .= $pageQed;

		}
        if ($c > 19){

            echo '<div class="pf-navigation">';
            if ($pagePrev > -1){
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

	function ajax_get_comments(){
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
		?>
		<form action="<?php pf_admin_url(); ?>" method="post">
			<div class="wrap">
				<?php
				echo '<h2>Preferences</h2>';
				echo '<h3>Options</h3>';


				wp_nonce_field( 'pf_settings' );
				?>
					<br />

					<p><?php
					$default_pf_link_value = get_option('pf_link_to_source', 0);
					echo '<input id="pf_link_to_source" name="pf_link_to_source" type="number" class="pf_link_to_source_class" value="'.$default_pf_link_value.'" />';

					echo '<label class="description" for="pf_link_to_source"> ' .__('Seconds to redirect user to source. (0 means no redirect)', 'pf'). ' </label>';
					?></p>

					<p>
						<?php
						$default_pf_use_advanced_user_roles = get_option('pf_use_advanced_user_roles', 'no');
						?>
						<select id="pf_use_advanced_user_roles" name="pf_use_advanced_user_roles">
							<option value="yes" <?php if ($default_pf_use_advanced_user_roles == 'yes'){ echo 'selected="selected"'; }?>>Yes</option>
							<option value="no" <?php if ($default_pf_use_advanced_user_roles == 'no'){ echo 'selected="selected"'; }?>>No</option>
						</select>
						<label class="description" for="pf_use_advanced_user_roles"> <?php _e('Use advanced user role management? (May be needed if you customize user roles or capabilities).', 'pf'); ?> </label>
					</p>
					<p><?php
					$default_pf_present_author_value = get_option('pf_present_author_as_primary', 'yes');
					?>
						<select id="pf_present_author_as_primary" name="pf_present_author_as_primary">
							<option value="yes" <?php if ($default_pf_present_author_value == 'yes'){ echo 'selected="selected"'; }?>>Yes</option>
							<option value="no" <?php if ($default_pf_present_author_value == 'no'){ echo 'selected="selected"'; }?>>No</option>
						</select>
					<?php

					echo '<label class="description" for="pf_present_author_as_primary"> ' .__('Show item author as source.', 'pf'). ' </label>';
					?></p>
					<?php
					if (class_exists('The_Alert_Box')){ ?>
					<p>
					<?php
						#if (class_exists('The_Alert_Box')){
							$alert_settings = the_alert_box()->settings_fields();
							$alert_switch = $alert_settings['switch'];
							$check = the_alert_box()->setting($alert_switch, $alert_switch['default']);
							#var_dump($check);
								$check = the_alert_box()->setting($alert_switch, $alert_switch['default']);
								if ('true' == $check){
									$mark = 'checked';
								} else {
									$mark = '';
								}
							echo '<input id="alert_switch" type="checkbox" name="'.the_alert_box()->option_name().'['.$alert_switch['parent_element'].']['.$alert_switch['element'].']" value="true" '.$mark.' class="'.$alert_switch['parent_element'].' '.$alert_switch['element'].'" />  <label for="'.the_alert_box()->option_name().'['.$alert_switch['parent_element'].']['.$alert_switch['element'].']" class="'.$alert_switch['parent_element'].' '.$alert_switch['element'].'" >' . $alert_switch['label_for'] . '</label>';
						#}
					?>
					</p>
					<?php
					}
					?>
					<p>
					<?php
					$user_ID = get_current_user_id();
					$pf_user_scroll_switch = get_user_option('pf_user_scroll_switch', $user_ID);
					if ( empty($pf_user_scroll_switch) || 'true' == $pf_user_scroll_switch){
						$mark = 'checked';
					} else {
						$mark = '';
					}
					echo '<input id="pf_user_scroll_switch" type="checkbox" name="pf_user_scroll_switch" value="true" '.$mark.' class="user_setting" />
								<label for="pf_user_scroll_switch" >' . 'Infinite Scroll Active' . '</label>';
					?>
					</p>
					<p>
					<?php
					$user_ID = get_current_user_id();
					$default_pf_pagefull = get_user_option('pf_pagefull', $user_ID);
					if ( empty($default_pf_pagefull)){
						$default_pf_pagefull = 20;
					}
					echo '<input id="pf_pagefull" name="pf_pagefull" type="number" class="pf_pagefull" value="'.$default_pf_pagefull.'" />';

					echo '<label class="description" for="pf_pagefull"> ' .__('Number of feed items per page.', 'pf'). ' </label>';
					?></p>
					<p>
					<?php
					$default_pf_link_value = get_option('pf_retain_time', 2);
					echo '<input id="pf_retain_time" name="pf_retain_time" type="number" class="pf_retain_time" value="'.$default_pf_link_value.'" />';

					echo '<label class="description" for="pf_retain_time"> ' .__('Months to retain feed items.', 'pf'). ' </label>';
					?></p>
					<p><?php
					$default_pf_link_value = get_option(PF_SLUG.'_errors_until_alert', 3);
					echo '<input id="pf_errors_until_alert" name="pf_errors_until_alert" type="number" class="pf_errors_until_alert" value="'.$default_pf_link_value.'" />';

					echo '<label class="description" for="pf_errors_until_alert"> ' .__('Number of errors before a feed is marked as malfunctioning.', 'pf'). ' </label>';
					?></p>

					<p>
						<select name="<?php echo PF_SLUG; ?>_draft_post_status" id="<?php echo PF_SLUG; ?>_draft_post_status"><?php
							$post_statuses = get_post_statuses();
							$pf_draft_post_status_value = get_option(PF_SLUG.'_draft_post_status', 'draft');
							foreach ($post_statuses as $status_name => $status_label): ?>
								<option value="<?php echo $status_name; ?>" <?php if ($pf_draft_post_status_value === $status_name) echo 'selected="selected"'; ?>><?php echo $status_label; ?></option><?php
							endforeach; ?>
						</select>
						<label class="description" for="<?php echo PF_SLUG; ?>_draft_post_status"><?php echo __('Post status for new content.', 'pf'); ?></label>
					</p>

					<p>
						<select name="<?php echo PF_SLUG; ?>_draft_post_type" id="<?php echo PF_SLUG; ?>_draft_post_type"><?php
							$post_types = get_post_types(array('public' => true), 'objects');
							$pf_draft_post_type_value = get_option(PF_SLUG.'_draft_post_type', 'post');
							foreach ($post_types as $post_type): ?>
								<option value="<?php echo $post_type->name; ?>" <?php if ($pf_draft_post_type_value === $post_type->name) echo 'selected="selected"'; ?>><?php echo $post_type->label; ?></option><?php
							endforeach; ?>
						</select>
						<label class="description" for="<?php echo PF_SLUG; ?>_draft_post_type"><?php echo __('Post type for new content.', 'pf'); ?></label>
					</p>

				<br />

					<input type="submit" name="submit" class="button-primary" value="<?php _e( "Save Changes", 'pf' ) ?>" />
					<br />

					<h3><?php _e( 'User Control', 'pf' ) ?></h3>

				<?php

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
											'title'=>__( 'Feeder Menu', 'pf' )
										),
			'pf_menu_add_nomination_access'=>array(
											'default'=>'contributor',
											'title'=> __( 'Add Nomination Menu', 'pf' )
										)
		);

		$arrayedAdminRights = apply_filters('pf_setup_admin_rights',$arrayedAdminRights);

		foreach($arrayedAdminRights as $right=>$parts){

			?>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="<?php echo $right; ?>-enable"><?php echo $parts['title']; ?></label>
							</th>

							<td>
								<select id="<?php echo $right; ?>" name="<?php echo $right; ?>">
									<?php $this->pf_get_user_role_select($right, pf_get_defining_capability_by_role($parts['default'])); ?>
								</select>
							</td>
						</tr>
					</table>

				<br />

			<?php

		}
		?><input type="submit" name="submit" class="button-primary" value="<?php _e( "Save Changes", 'pf' ) ?>" /><?php
				do_action('pf_admin_user_settings');

			?>

				<h3><?php _e( 'Modules', 'pf' ) ?></h3>

				<p class="description"><?php _e( '<strong>PressForward Modules</strong> are addons to alter or improve the functionality of the plugin.', 'pf' ) ?></p>
					<?php
					do_action( 'pf_admin_op_page' );
					?>
					<input type="submit" name="submit" class="button-primary" value="<?php _e( "Save Changes", 'pf' ) ?>" />

			</div>
		</form>
		<?php
	}

	/**
	* Display function for Feeder panel
	*/
	function display_tools_builder() {

		echo '<header id="app-banner">
			<div class="title-span title">
				<h1>PressForward: Tools</h1>								<span id="h-after"> • </span>
				<button class="btn btn-small" id="fullscreenfeed"> Full Screen </button>
			</div><!-- End title -->
		</header>';

			if ( current_user_can('edit_posts') ) : ?>
				<h3 class="title"><?php _e('Nominate This', 'pf'); ?></h3>
				<p><?php _e('Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web.', 'pf');?></p>

				<p><?php _e('Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', 'pf'); ?></p>
				<p class="description"><?php _e('Drag-and-drop the following link to your bookmarks bar or right click it and add it to your favorites for a posting shortcut.', 'pf'); ?></p>
				<p class="pressthis"><a onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo htmlspecialchars( pf_get_shortcut_link() ); ?>"><span><?php _e('Nominate This', 'pf'); ?></span></a></p>
				<div class="pressthis-code" style="display:none;">
				<p class="description"><?php _e('If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type Press This into the name field and paste the code into the URL field.', 'pf'); ?></p>
				<p><textarea rows="5" cols="120" readonly="readonly"><?php echo htmlspecialchars( pf_get_shortcut_link() ); ?></textarea></p>
				</div>
			<?php
			endif;

		}

	/**
	 * Display function for Feeder panel
	 */
	function display_feeder_builder() {

		echo '<header id="app-banner">
			<div class="title-span title">
				<h1>PressForward: Add Feeds</h1>								<span id="h-after"> • </span>
				<button class="btn btn-small" id="fullscreenfeed"> Full Screen </button>
			</div><!-- End title -->
		</header>';

			if ( current_user_can('edit_posts') ) : ?>
			<div class="tool-box">
				<h3 class="title"><?php _e('Nominate This', 'pf'); ?></h3>
				<p><?php _e('Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web.', 'pf');?></p>

				<p><?php _e('Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', 'pf'); ?></p>
				<p class="description"><?php _e('Drag-and-drop the following link to your bookmarks bar or right click it and add it to your favorites for a posting shortcut.', 'pf'); ?></p>
				<p class="pressthis"><a onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo htmlspecialchars( pf_get_shortcut_link() ); ?>"><span><?php _e('Nominate This', 'pf'); ?></span></a></p>
				<div class="pressthis-code" style="display:none;">
				<p class="description"><?php _e('If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type Press This into the name field and paste the code into the URL field.', 'pf'); ?></p>
				<p><textarea rows="5" cols="120" readonly="readonly"><?php echo htmlspecialchars( pf_get_shortcut_link() ); ?></textarea></p>
				</div>

                <div class="alert-box postbox">
                    <div class="handlediv" title="Click to toggle"><br></div>
                    <h3 class="hndle"><span>Feed Problems</span></h3>
                    <div class="inside">
                    <?php
                        self::pf_alert_displayer();
                    ?>
                    </div>
                </div>
            </div>
			<?php
			endif;
			?><form method="post" action="options.php" enctype="multipart/form-data"><?php
            //settings_fields(PF_SLUG . '_feeder_options');
            //$options = get_option(PF_SLUG . '_plugin_feeder_options');

			do_action( 'feeder_menu' );

			#<input type="submit" class="button-primary" value="<?php _e('Save Options', 'pf'); />


			?></form><?php


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
			wp_register_script(PF_SLUG . '-views', PF_URL . 'assets/js/views.js', array( PF_SLUG . '-twitter-bootstrap', 'jquery-ui-core', 'jquery-effects-slide'  ));
			wp_register_script(PF_SLUG . '-readability-imp', PF_URL . 'assets/js/readability-imp.js', array( PF_SLUG . '-twitter-bootstrap', 'jquery', PF_SLUG . '-views' ));
			wp_register_script(PF_SLUG . '-infiniscroll', PF_URL . 'lib/jquery.infinitescroll.js', array( 'jquery', PF_SLUG . '-views', PF_SLUG . '-readability-imp', 'jquery' ));
			wp_register_script(PF_SLUG . '-scrollimp', PF_URL . 'assets/js/scroll-imp.js', array( PF_SLUG . '-infiniscroll', 'pf-relationships', PF_SLUG . '-views'));
			wp_register_script('pf-relationships', PF_URL . 'assets/js/relationships.js', array( 'jquery' ));
			wp_register_style( PF_SLUG . '-responsive-style', PF_URL . 'assets/css/pf-responsive.css', array(PF_SLUG . '-reset-style', PF_SLUG . '-style', PF_SLUG . '-bootstrap-style', PF_SLUG . '-susy-style'));
			wp_register_script(PF_SLUG . '-tinysort', PF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_register_script(PF_SLUG . '-media-query-imp', PF_URL . 'assets/js/media-query-imp.js', array( 'jquery', 'thickbox', 'media-upload' ));
			wp_register_script(PF_SLUG . '-sort-imp', PF_URL . 'assets/js/sort-imp.js', array( PF_SLUG . '-tinysort', PF_SLUG . '-twitter-bootstrap', PF_SLUG . '-jq-fullscreen' ));

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
			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style(PF_SLUG . '-bootstrap-style');
			wp_enqueue_style(PF_SLUG . '-bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			wp_enqueue_style( PF_SLUG . '-susy-style' );
			wp_enqueue_style( PF_SLUG . '-responsive-style' );
		}
		if (('nomination') == get_post_type()) {
			wp_enqueue_script(PF_SLUG . '-add-nom-imp', PF_URL . 'assets/js/add-nom-imp.js', array( 'jquery' ));
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

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$verifyPages = array();

		$pf_admin_pages = apply_filters('pf_admin_pages',$verifyPages);

		if (! in_array($_GET['page'], $pf_admin_pages)){
			return;
		}

		check_admin_referer( 'pf_settings' );

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

		$user_ID = get_current_user_id();
		if (isset( $_POST['pf_user_scroll_switch'] )){
			$pf_user_scroll_switch = $_POST['pf_user_scroll_switch'];
			//var_dump($pf_user_scroll_switch); die();
			update_user_option($user_ID, 'pf_user_scroll_switch', $pf_user_scroll_switch);
		} else {
			update_user_option($user_ID, 'pf_user_scroll_switch', 'false');
		}

		if (isset( $_POST['pf_pagefull'] )){
			$pf_pagefull = $_POST['pf_pagefull'];
			//var_dump($pf_user_scroll_switch); die();
			update_user_option($user_ID, 'pf_pagefull', $pf_pagefull);
		} else {
			update_user_option($user_ID, 'pf_pagefull', 'false');
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

		$pf_draft_post_type = (isset( $_POST[PF_SLUG . '_draft_post_type'] ))
			? $_POST[PF_SLUG . '_draft_post_type']
			: 'post';
		update_option(PF_SLUG . '_draft_post_type', $pf_draft_post_type);

		$pf_draft_post_status = (isset( $_POST[PF_SLUG . '_draft_post_status'] ))
			? $_POST[PF_SLUG . '_draft_post_status']
			: 'draft';
		update_option(PF_SLUG . '_draft_post_status', $pf_draft_post_status);

		if (isset( $_POST['pf_present_author_as_primary'] )){
			$pf_author_opt_check = $_POST['pf_present_author_as_primary'];
			//print_r($pf_links_opt_check); die();
			update_option('pf_present_author_as_primary', $pf_author_opt_check);
		} else {
			update_option('pf_present_author_as_primary', 'no');
		}

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

    public function dead_post_status(){
        register_post_status('removed_feed_item', array(
            'label'                 =>     _x('Removed Feed Item', 'pf'),
            'public'                =>      false,
            'exclude_from_search'   =>      true,
            'show_in_admin_all_list'=>      false
        ) );
    }

	/*
	 *
	 * A method to allow users to delete any CPT or post through AJAX.
	 * The goal here is to tie an easy use function to an AJAX action,
	 * that also cleans up all the extra data that PressForward
	 * can create.
	 *
	 * If a post is made readable, it will attempt (and often
	 * succeed) at pulling in images. This should remove those
	 * attached images and remove relationship schema data.
	 *
	 * We should also figure out the best way to call this when
	 * posts are 'expired' after 60 days.
	 *
	 * Takes:
	 *		Post ID
	 *		Post Readability Status
	 *
	 */

	function pf_thing_deleter($id = 0, $readability_status = false){
		if ($id == 0)
			return new WP_Error('noID', __("No ID supplied for deletion", 'pf'));

		# Note: this will also remove feed items if a feed is deleted, is that something we want?
		if ($readability_status || $readability_status > 0){
			$args = array(
				'post_parent' => $id
			);
			$attachments = get_children($args);
			foreach ($attachments as $attachment) {
				wp_delete_post($attachment->ID, true);
			}
		}

		$argup = array(
			'ID'			=> $id,
			'post_content' 	=> '',
			'post_status'	=>	'removed_feed_item'
		);

		$result = wp_update_post($argup);
		return $result;

	}

	function pf_ajax_thing_deleter() {
		ob_start();
		if(isset($_POST['post_id'])){
			$id = $_POST['post_id'];
		} else { die('Option not sent'); }
		if(isset($_POST['made_readable'])){
			$read_status = $_POST['made_readable'];
		} else { $read_status = false; }
		$returned = self::pf_thing_deleter($id, $read_status);
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
		$returned = self::pf_switch_display_setting($user_id, $read_state);
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

	function pf_switch_display_setting($user_id, $read_state){
		if ( !current_user_can( 'edit_user', $user_id ) ){
			return false;
		}

		$check = update_user_meta($user_id, 'pf_user_read_state', $read_state);
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
		unset( $posts_columns['date'] );
		$posts_columns['last_retrieved'] = 'Last Retrieved';
		return $posts_columns;
	}

	/**
	 * Content of the Last Retrieved column.
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
		if ( 'last_retrieved' !== $query->query_vars['orderby'] ) {
			return;
		}

		// Should never happen, but if someone's doing a meta_query,
		// bail or we'll mess it up
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			return;
		}

		$query->set( 'meta_key', 'pf_feed_last_retrieved' );
		$query->set( 'meta_type', 'DATETIME' );
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
		) );
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
		pressforward()->pf_retrieve->trigger_source_data();
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
}
