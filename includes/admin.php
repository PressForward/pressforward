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

		// Adding javascript and css to admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
		add_action( 'wp_head', array( $this, 'pf_aggregation_forwarder'));
		add_filter('admin_body_class',  array( $this, 'add_pf_body_class'));

		// Catch form submits
		add_action( 'admin_init', array($this, 'pf_options_admin_page_save') );

		// AJAX handlers
		add_action( 'wp_ajax_build_a_nomination', array( $this, 'build_a_nomination') );
		add_action( 'wp_ajax_build_a_nom_draft', array( $this, 'build_a_nom_draft') );
		add_action( 'wp_ajax_assemble_feed_for_pull', array( $this, 'trigger_source_data') );
		add_action( 'wp_ajax_reset_feed', array( $this, 'reset_feed') );
		add_action( 'wp_ajax_make_it_readable', array( $this, 'make_it_readable') );
		add_action( 'wp_ajax_archive_a_nom', array( $this, 'archive_a_nom') );
		add_action( 'wp_ajax_ajax_get_comments', array( $this, 'ajax_get_comments') );
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
			__('Under Review', 'pf'),
			__('Under Review', 'pf'),
			get_option('pf_menu_under_review_access', pf_get_defining_capability_by_role('contributor')),
			PF_SLUG . '-review',
			array($this, 'display_review_builder')
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

		// Feed-listing page is accessible only to Editors and above
		add_submenu_page(
			PF_MENU_SLUG,
			__('Feeder', 'pf'),
			__('Feeder', 'pf'),
			get_option('pf_menu_feeder_access', pf_get_defining_capability_by_role('editor')),
			PF_SLUG . '-feeder',
			array($this, 'display_feeder_builder')
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
	}
	
	function add_pf_body_class($classes) {
		
		$classes .= strtolower(PF_TITLE);

		return $classes;
	}
	
	public function toolbox($slug = 'allfeed', $version = 0, $deck = false){
		global $hook_suffix;
		if(!empty($hook_suffix)){
			$slug = $hook_suffix;
		}
		?>
		<div id="tools">
			<?php if ($version > 0){ ?>
				<ul class="nav nav-tabs nav-stacked">
					<li><a href="#">Top Blogs</a></li>
					<li><a href="#">Starred Items</a></li>
					<li><a href="#">Content from Twitter</a></li>
				</ul>

				<form id="filters">
					<h2>Filters</h2>
					
					<label><input type="checkbox"> Shared on Twitter</label>
					<label><input type="checkbox"> Long Articles</label>
					<label><input type="checkbox"> Short Articles</label>
					<label><input type="checkbox"> Recommended by Algorithm</label>
					<label><input type="checkbox"> High Number of Comments</label>
					
					<input type="submit" class="btn btn-small" value="Reset Filters">
				</form>

				<form id="subscription" method="post" action="">
					<h2>New Subscription</h2>
					<input type="text" placeholder="http://example.com/feed">
				<input type="submit" class="btn btn-small" value="Subscribe">
				</form>


				<?php 
				# Some buttons to the left
				if ($deck){ 
				echo '<div class="deck">';
						echo '<div class="row-fluid">
								<div class="span12 main-card card well">
									<div class="tapped">
										' . __('Main Feed', 'pf') . '
									</div>
								</div>
							</div>
						';

						# Auto add these actions depending on if the module presents a stream?
						//do_action( 'module_stream' );

						echo '<div class="row-fluid">
								<div class="span12 sub-card card well">
									<div class="tapped">
										' . __('Module Feed', 'pf') . '
									</div>
								</div>
							</div>
						';
				echo '</div><!-- End span1 -->';				

		#Widgets
				echo '<div class="feed-widget-container">';
					# Some widgets go here.
						# Does this work? [nope...]
						$blogusers = get_users('orderby=nom_count');
						$uc = 1;
						echo '<div class="row-fluid">
						<div class="pf-right-widget well span12">
								<div class="widget-title">
									' . __('Nominator Leaderboard', 'pf') . '
								</div>
								<div class="widget-body">
									<div class="navwidget">
										<ol>';
										foreach ($blogusers as $user){
											if ($uc <= 5){
												if (get_user_meta( $user->ID, 'nom_count', true )){
												$userNomCount = get_user_meta( $user->ID, 'nom_count', true );

												} else {
													$userNomCount = 0;
												}
												$uc++;
												echo '<li>' . $user->display_name . ' - ' . $userNomCount . '</li>';
											}

										}
						echo			'</ol>
									</div>
								</div>
						</div>
						</div>
						';

						$widgets_array = $this->widget_array();
						$all_widgets_array = apply_filters( 'dash_widget_bar', $widgets_array );

						//$all_widgets_array = array_merge($widgets_array, $mod_widgets);
						foreach ($all_widgets_array as $dash_widget) {

							$defaults = array(
								'title' => '',
								'slug'       => '',
								'callback'   => '',
							);
							$r = wp_parse_args( $dash_widget, $defaults );

							// add_submenu_page() will fail if any arguments aren't passed
							if ( empty( $r['title'] ) || empty( $r['slug'] ) || empty( $r['callback'] ) ) {
								continue;
							} else {

								echo '<div class="row-fluid">
								<div class="pf-right-widget well span12 ' . $r['slug'] . '">';
									echo '<div class="widget-title">' .
										$r['title']
									. '</div>';
									echo '<div class="widget-body">';
										call_user_func($r['callback']);
									echo '</div>';
								echo '</div>
								</div>';

							}

						}

						/**
						// Loop through each module to get its source data
						foreach ( $this->modules as $module ) {
							//$source_data_object = array_merge( $source_data_object, $module->get_widget_object() );

							echo '<div class="row-fluid">
							<div class="pf-right-widget well span12">';

							echo '</div>
							</div>';
						}
						**/
				/**
				echo '</div><!-- End feed-widget-container span4 -->';	
				**/				 
				}		
				
			}
			if ($slug == 'toplevel_page_pf-menu' && $version >= 0 && current_user_can(pf_get_defining_capability_by_role('administrator'))){
				?>
					<a href="#" id="settings" class="button">Settings</a>
					<div class="btn-group">
						<button type="submit" class="delete btn btn-danger pull-right" id="deletefeedarchive" value="<?php  _e('Delete entire feed archive', 'pf');  ?>" ><?php  _e('Delete entire feed archive', 'pf');  ?></button>
					</div>
				<?php 
			}			
				?>
				<div id="nom-this-toolbox">
					<h3 class="title"><?php _e('Nominate This', 'pf'); ?></h3>
					<p><?php _e('Nominate This is a bookmarklet: a little app that runs in your browser and lets you grab bits of the web.', 'pf');?></p>

					<p><?php _e('Use Nominate This to clip text, images and videos from any web page. Then edit and add more straight from Nominate This before you save or publish it in a post on your site.', 'pf'); ?></p>
					<p class="description"><?php _e('Drag-and-drop the following link to your bookmarks bar or right click it and add it to your favorites for a posting shortcut.', 'pf'); ?></p>
					<p class="pressthis"><a onclick="return false;" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1)jQuery('.pressthis-code').show().find('textarea').focus().select();return false;" href="<?php echo htmlspecialchars( pf_get_shortcut_link() ); ?>"><span><?php _e('Nominate This', 'pf'); ?></span></a></p>
					<div class="pressthis-code" style="display:none;">
					<p class="description"><?php _e('If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type Press This into the name field and paste the code into the URL field.', 'pf'); ?></p>
					<p><textarea rows="5" cols="120" readonly="readonly"><?php echo htmlspecialchars( pf_get_shortcut_link() ); ?></textarea></p>
					</div>
				</div>
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
				<div class="actions <?php if($modal){ echo 'modal-btns '; } ?>btn-group">
					<?php
					$infoPop = 'top';
					if ($modal == false){
						$infoPop = 'bottom';
						if ($format === 'nomination'){
							?><form name="form-<?php echo $metadata['item_id']; ?>" pf-form="<?php echo $metadata['item_id']; ?>"><?php 
							pf_prep_item_for_submit($metadata);
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
					echo '<button class="btn btn-small itemInfobutton" id="info-' . $item['item_id'] . '-' . $infoPop . '" data-placement="' . $infoPop . '" data-class="info-box-popover"><i class="icon-info-sign"></i></button>';
					
					if (pf_is_item_starred_for_user( $id_for_comments, $user_id ) ){
						echo '<!-- item_id selected = ' . $item_id . ' -->';
						echo '<button class="btn btn-small star-item btn-warning"><i class="icon-star"></i></button>';
					} else {
						echo '<button class="btn btn-small star-item"><i class="icon-star"></i></button>';
					}
					
					# <a href="#" type="submit"  class="PleasePushMe"><i class="icon-plus"></i> Nominate</a>
					if (has_action('pf_comment_action_button')){
						$commentModalCall = '#modal-comments-' . $item['item_id'];
						$commentSet = array('id' => $id_for_comments, 'modal_state' => $modal);
						//echo $id_for_comments;
						do_action('pf_comment_action_button', $commentSet);
					
					} 
					if ($format === 'nomination'){
					
						$nom_count_classes = 'btn btn-small';
						if ($metadata['nom_count'] > 0){
							$nom_count_classes .= ' btn-info';
						} 
					
						echo '<a class="'.$nom_count_classes.'" data-toggle="tooltip" title="' . __('Nomination Count', 'pf') .  '" form="' . $metadata['nom_id'] . '">'.$metadata['nom_count'].'<i class="icon-play"></i></button></a>';
					
						echo '<a class="btn btn-small nom-to-archive schema-actor" pf-schema="archive" pf-schema-class="archived" data-toggle="tooltip" title="' . __('Archive', 'pf') .  '" form="' . $metadata['nom_id'] . '"><img src="' . PF_URL . 'assets/images/archive.png" /></button></a>';
						$arcive_status = "";
						if ( 1 == pf_get_relationship_value( 'draft', $metadata['item_feed_post_id'], $user_id ) ){
							$arcive_status = 'btn-success';
						}
						echo '<a href="#nominate" class="btn btn-small nom-to-draft schema-actor '. $arcive_status .'" pf-schema="draft" pf-schema-class="btn-success" form="' . $metadata['item_id'] . '" data-original-title="' . __('Draft', 'pf') .  '"><img src="' . PF_URL . 'assets/images/pressforward-licon.png" /></a>';
					
					} else {
						#var_dump(pf_get_relationship('nominate', $id_for_comments, $user_id));
						if (1 == pf_get_relationship_value('nominate', $id_for_comments, $user_id)){
							echo '<button class="btn btn-small nominate-now btn-success schema-actor" pf-schema="nominate" pf-schema-class="btn-success" form="' . $item['item_id'] . '" data-original-title="' . __('Nominated', 'pf') .  '"><img src="' . PF_URL . 'assets/images/pressforward-licon.png" /></button>';
							# Add option here for admin-level users to send items direct to draft. 
						} else {
							echo '<button class="btn btn-small nominate-now schema-actor" pf-schema="nominate" pf-schema-class="btn-success" form="' . $item['item_id'] . '" data-original-title="' . __('Nominate', 'pf') .  '"><img src="' . PF_URL . 'assets/images/pressforward-licon.png" /></button>';
							# Add option here for admin-level users to send items direct to draft. 
						}
					}
					
					
	
					?>
						<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery(function(){
								jQuery("#<?php echo 'info-' . $item['item_id'] . '-' . $infoPop; ?>").popover({
									title: pop_title_<?php echo $item['item_id'] ?>,
									html: true,
									content: pop_html_<?php echo $item['item_id'] ?>,
									placement: "<?php echo $infoPop ?>",
									container: ".actions"
								})
								.on("click", function(){
									jQuery('.popover').addClass(jQuery(this).data("class")); //Add class .dynamic-class to <div>
								});
							});
							jQuery(".modal.pfmodal").on('hide', function(evt){
								jQuery("#<?php echo 'info-' . $item['item_id'] . '-' . $infoPop; ?>").popover('hide');
							})
						});
						</script>
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
					$feed_ited_id = $metadata['item_id'];
					$id_for_comments = $metadata['item_feed_post_id'];
			
			$id_for_comments = $metadata['item_feed_post_id'];
			$readStat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
			if (!$readStat){ $readClass = ''; } else { $readClass = 'article-read'; }
			if (empty($metadata['nom_id'])){ $metadata['nom_id'] = md5($item['item_title']); }
			if (empty($id_for_comments)){ $id_for_comments = $metadata['nom_id']; }
			if (empty($metadata['item_id'])){ $metadata['item_id'] = md5($item['item_title']); }	
			
				} else {
					$feed_ited_id = $item['item_id'];
					$id_for_comments = $item['post_id'];
				}
				$archive_status = pf_get_relationship_value( 'archive', $id_for_comments, wp_get_current_user()->ID );
				if ($archive_status == 1){
					$archived_status_string = 'archived';
					$dependent_style = 'display:none;';
				} else {
					$dependent_style = '';
					$archived_status_string = '';
				}
		if ($format === 'nomination'){
			
			echo '<article class="feed-item entry nom-container schema-actor ' . $archived_status_string . ' '. get_pf_nom_class_tags(array($metadata['submitters'], $metadata['nom_id'], $metadata['authors'], $metadata['nom_tags'], $metadata['nominators'], $metadata['item_tags'], $metadata['item_id'] )) . ' '.$readClass.'" id="' . $metadata['nom_id'] . '" style="' . $dependent_style . '" tabindex="' . $c . '" pf-post-id="' . $metadata['nom_id'] . '" pf-item-post-id="' . $id_for_comments . '" pf-feed-item-id="' . $metadata['item_id'] . '" pf-schema="read" pf-schema-class="article-read">';
		} else {
			$id_for_comments = $item['post_id'];
			$readStat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
			if (!$readStat){ $readClass = ''; } else { $readClass = 'article-read'; }
			echo '<article class="feed-item entry ' . pf_slugger(($item['source_title']), true, false, true) . ' ' . $itemTagClassesString . ' '.$readClass.'" id="' . $item['item_id'] . '" tabindex="' . $c . '" pf-post-id="' . $item['post_id'] . '" pf-feed-item-id="' . $item['item_id'] . '" pf-item-post-id="' . $id_for_comments . '" >';
		}
		
			$readStat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
			if (!$readStat){ $readClass = ''; } else { $readClass = 'marked-read'; }
			echo '<i class="icon-ok-sign schema-read schema-actor schema-switchable '.$readClass.'" pf-item-post-id="' . $id_for_comments .'" pf-schema="read" pf-schema-class="marked-read" title="Mark as Read"></i>'
			?> 
			<header> <?php 
				echo '<h1 class="item_title"><a href="#modal-' . $item['item_id'] . '" class="item-expander schema-actor" role="button" data-toggle="modal" data-backdrop="false" pf-schema="read" pf-schema-targets="schema-read">' . $item['item_title'] . '</a></h1>';
				echo '<p class="source_title">' . $item['source_title'] . '</p>';
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

							_e('Slug for origon site', 'pf');
							echo ': <span class="sortable_origin_link_slug">' . $metadata['source_slug'] . '</span><br />';

							//Add an action here for others to provide additional sortables.

						echo '</div>';	
				}
									# Let's build an info box!
									//http://nicolasgallagher.com/pure-css-speech-bubbles/

									$urlArray = parse_url($item['item_link']);
									$sourceLink = 'http://' . $urlArray['host'];
									//http://nicolasgallagher.com/pure-css-speech-bubbles/demo/

									$ibox = '<div class="feed-item-info-box" id="info-box-' . $item['item_id'] . '">';
										$ibox .= '
										' . __('Feed', 'pf') . ': <span class="feed_title">' . $item['source_title'] . '</span><br />
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
											. ': <span class="nominated_on">' . date( 'M j, Y; g:ia' , strtotime($metadata['date_nominated'])) . '</span><br />';		
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
					jQuery(document).ready(function() {
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
							echo'<p>' . pf_feed_excerpt($item['item_content']) . '</p>';
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
				<h3 id="modal-<?php echo $item['item_id']; ?>-label" class="modal_item_title source_title"><?php echo $item['item_title']; ?></h3>
			  </div>
			  <div class="row-fluid modal-body-row">
				  <div class="modal-body span9">
					<?php 
					$contentObj = new htmlchecker($item['item_content']);
					$text = $contentObj->closetags($item['item_content']);
					echo $text; 
					
					?>
				  </div>
				  <div class="modal-sidebar span3">
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
					if ($format != 'nomination'){
						?>
						| <a class="modal-readability-reset" target="#readable" href="<?php echo $item['item_link']; ?>" pf-item-id="<?php echo $item['item_id']; ?>" pf-post-id="<?php echo $item['post_id']; ?>" pf-modal-id="#modal-<?php echo $item['item_id']; ?>"><?php  _e('Reset Readability', 'pf'); ?></a>
						<?php 
					}
					?>
				</div>
				<div class="pull-right"><?php 
				$this->form_of_actions_btns($item, $c, true, $format, $metadata, $id_for_comments); 
				?></div><?php 
				?>	
				</div>
				<div class="item-tags pull-left row-fluid">
				<?php
					echo '<em>' . __('Source', 'pf') . ': ' . $item['source_title'] . '</em> | ';
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

	/**
	 * Display function for the main All Content panel
	 */
	public function display_reader_builder() {
	
		//Calling the feedlist within the pf class.
		if (isset($_GET["pc"])){
			$page = $_GET["pc"];
			$page = $page-1;
		} else {
			$page = 0;
		}
		$count = $page * 20;	
	?>
	<div class="grid pf_container full">
		<header id="app-banner">
			<div class="title-span title">
				<?php echo '<h1>' . PF_TITLE . '</h1>'; ?>
				<?php 
					if ($page > 0) {
						$pageNumForPrint = sprintf( __('Page %1$d', 'pf'), $page);
						echo '<span> - ' . $pageNumForPrint . '</span>';
					}
				?>
				<span id="h-after"> &#8226; </span>
				<button type="submit" class="refreshfeed btn btn-small" id="refreshfeed" value="<?php  _e('Refresh', 'pf')  ?>"><?php  _e('Refresh', 'pf');  ?></button>
				<button class="btn btn-small" id="fullscreenfeed"> <?php  _e('Full Screen', 'pf');  ?> </button>
			</div><!-- End title -->
			<form id="feeds-search">
					<label for="search-terms">Search</label>
				<input type="text" name="search-terms" id="search-terms" placeholder="Enter search terms">
				<input type="submit" class="btn btn-small" value="Search">
			</form>			
		</header><!-- End Header -->
		<div role="main">
			<?php $this->toolbox(); ?>
			<div id="entries">
				<?php echo '<img class="loading-top" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />';  ?>
				<div id="errors"></div>
				<div class="display">
					<div class="btn-group pull-left">
					<button type="submit" id="gogrid" class="btn btn-small">Grid</button>
					<button type="submit" id="golist" class="btn btn-small">List</button>

					<?php echo '<button type="submit" class="btn btn-small feedsort" id="sortbyitemdate" value="' . __('Sort by item date', 'pf') . '" >' . __('Sort by item date', 'pf') . '</button>';
					echo '<button type="submit" class="btn btn-small feedsort" id="sortbyfeedindate" value="' . __('Sort by date entered feed', 'pf') . '">' . __('Sort by date entered feed', 'pf') . '</button>'; ?>
					</div>
					<div class="pull-right text-right">
					<!-- or http://thenounproject.com/noun/list/#icon-No9479? -->
					<a class="btn btn-small" id="gomenu" href="#">Menu <i class="icon-tasks"></i></a>
					</div>
				</div><!-- End btn-group -->
		
			<?php 
		
				//Use this foreach loop to go through the overall feedlist, select each individual feed item (post) and do stuff with it.
				//Based off SimplePie's tutorial at http://simplepie.org/wiki/tutorial/how_to_display_previous_feed_items_like_google_reader.
				$c = 1;
				$ic = 0;
				$c = $c+$count;	
					//print_r($count);
			foreach(PF_Feed_Item::archive_feed_to_display($count+1) as $item) {
				
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

		echo '</div><!-- End entries -->';

	echo '</div><!-- End main -->';

		//Nasty hack because infinite scroll only works starting with page 2 for some reason.
		if ($page == 0){ $page = 1; }
		$pagePrev = $page-1;
		$pageNext = $page+1;
		echo '<div class="pf-navigation">';
		if ($pagePrev > -1){
			echo '<span class="feedprev"><a class="prevnav" href="admin.php?page=pf-menu&pc=' . $pagePrev . '">Previous Page</a></span> | ';
		}
		echo '<span class="feednext"><a class="nextnav" href="admin.php?page=pf-menu&pc=' . $pageNext . '">Next Page</a></span>';
		echo '</div>';

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
				echo 'Options';

				?>
				
					<h3><?php _e( 'Modules', 'pf' ) ?></h3>

					<p class="description"><?php _e( '<strong>PressForward Modules</strong> are addons to alter or improve the functionality of the plugin.', 'pf' ) ?></p>
				<?php
				do_action( 'pf_admin_op_page' );
				wp_nonce_field( 'pf_settings' );
				?>
					<br />
					
					<p><?php
					$default_pf_link_value = get_option('pf_link_to_source', 0);	
					echo '<input id="pf_link_to_source" name="pf_link_to_source" type="number" class="pf_link_to_source_class" value="'.$default_pf_link_value.'" />';
					
					echo '<label class="description" for="pf_link_to_source"> ' .__('Seconds to redirect user to source. (0 means no redirect)', 'pf'). ' </label>';						
					?></p>

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
											'title'=>__( 'Under Review Menu', 'pf' )
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
						
			</div>
		</form>
		<?php
	}

	/**
	 * Display function for Feeder panel
	 */
	function display_feeder_builder() {

		echo 'Feeder. <br />';

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
			</div>
			<?php
			endif;
			?><form method="post" action="options.php"><?php
            //settings_fields(PF_SLUG . '_feeder_options');
            //$options = get_option(PF_SLUG . '_plugin_feeder_options');

			do_action( 'feeder_menu' );

			?><input type="submit" class="button-primary" value="<?php _e('Save Options', 'pf'); ?>" />
			</form><?php


	}

	//This function can add js and css that we need to specific admin pages.
	function add_admin_scripts($hook) {

		//This gets the current page the user is on.
		global $pagenow;

			wp_register_style( PF_SLUG . '-style', PF_URL . 'assets/css/style.css');
			wp_register_style( 'bootstrap-style', PF_URL . 'lib/twitter-bootstrap/css/bootstrap.css');
			wp_register_style( 'bootstrap-responsive-style', PF_URL . 'lib/twitter-bootstrap/css/bootstrap-responsive.css');
			wp_register_style( PF_SLUG . '-susy-style', PF_URL . 'assets/css/susy.css');
			wp_register_style( PF_SLUG . '-reset-style', PF_URL . 'assets/css/reset.css');
			wp_register_script('views', PF_URL . 'assets/js/views.js', array( 'twitter-bootstrap', 'jquery-ui-core', 'jquery-effects-slide'  ));	
			wp_register_script('readability-imp', PF_URL . 'assets/js/readability-imp.js', array( 'twitter-bootstrap', 'jquery', 'views' ));
			wp_register_script('infiniscroll', PF_URL . 'lib/jquery.infinitescroll.js', array( 'jquery', 'views', 'readability-imp' ));
			wp_register_script('scrollimp', PF_URL . 'assets/js/scroll-imp.js', array( 'infiniscroll', 'pf-relationships' ));
			wp_register_script('pf-relationships', PF_URL . 'assets/js/relationships.js', array( 'jquery' ));
			wp_register_style( PF_SLUG . '-responsive-style', PF_URL . 'assets/css/pf-responsive.css', array(PF_SLUG . '-reset-style', PF_SLUG . '-style', 'bootstrap-style', PF_SLUG . '-susy-style'));
			wp_register_script('tinysort', PF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_register_script('sort-imp', PF_URL . 'assets/js/sort-imp.js', array( 'tinysort', 'twitter-bootstrap', 'jq-fullscreen' ));

		//print_r($hook);
		//This if loop will check to make sure we are on the right page for the js we are going to use.
		if (('toplevel_page_pf-menu') == $hook) {
			//And now lets enqueue the script, ensuring that jQuery is already active.

			wp_enqueue_script('tinysort');
			wp_enqueue_script('sort-imp');
			wp_enqueue_script('views');			
			wp_enqueue_script('readability-imp');
			wp_enqueue_script('nomination-imp', PF_URL . 'assets/js/nomination-imp.js', array( 'jquery' ));
			wp_enqueue_script('twitter-bootstrap', PF_URL . 'lib/twitter-bootstrap/js/bootstrap.js' , array( 'jquery' ));
			wp_enqueue_script('jq-fullscreen', PF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			wp_enqueue_script('infiniscroll');
			wp_enqueue_script('scrollimp');
			wp_enqueue_script('pf-relationships');
			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style('bootstrap-style');
			wp_enqueue_style('bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			wp_enqueue_style( PF_SLUG . '-susy-style' );
			wp_enqueue_style( PF_SLUG . '-responsive-style' );

		}
		if (('pressforward_page_pf-review') == $hook) {
			wp_enqueue_script('tinysort');
			wp_enqueue_script('sort-imp');
			wp_enqueue_script('jq-fullscreen', PF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			wp_enqueue_script('twitter-bootstrap', PF_URL . 'lib/twitter-bootstrap/js/bootstrap.js' , array( 'jquery' ));
			wp_enqueue_script('send-to-draft-imp', PF_URL . 'assets/js/send-to-draft-imp.js', array( 'jquery' ));
			wp_enqueue_script('archive-nom-imp', PF_URL . 'assets/js/nom-archive-imp.js', array( 'jquery' ));
			wp_enqueue_script('views');			
			wp_enqueue_script('readability-imp');
			wp_enqueue_script('infiniscroll');
			wp_enqueue_script('scrollimp');			
			wp_enqueue_script('pf-relationships');
			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style('bootstrap-style');
			wp_enqueue_style('bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			wp_enqueue_style( PF_SLUG . '-susy-style' );
			wp_enqueue_script( 'post' );
			wp_enqueue_style( PF_SLUG . '-responsive-style' );
		}
		if (('nomination') == get_post_type()) {
			wp_enqueue_script('add-nom-imp', PF_URL . 'assets/js/add-nom-imp.js', array( 'jquery' ));
		}		
		if (('pressforward_page_pf-feeder') != $hook) { return; }
		else {
			//And now lets enqueue the script, ensuring that jQuery is already active.

			wp_enqueue_script('tinysort', PF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_enqueue_script('twitter-bootstrap', PF_URL . 'lib/twitter-bootstrap/js/bootstrap.js' , array( 'jquery' ));

			wp_enqueue_style( PF_SLUG . '-reset-style' );
			wp_enqueue_style('bootstrap-style');
			wp_enqueue_style('bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			wp_enqueue_style( PF_SLUG . '-susy-style' );
			wp_enqueue_style( PF_SLUG . '-responsive-style' );

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
											'title'=>__( 'Under Review Menu', 'pf' )
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
		
		if (isset( $_POST['pf_present_author_as_primary'] )){
			$pf_author_opt_check = $_POST['pf_present_author_as_primary'];
			//print_r($pf_links_opt_check); die();
			update_option('pf_present_author_as_primary', $pf_author_opt_check);
		} else {
			update_option('pf_present_author_as_primary', 'no');
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
		pressforward()->modules['rss-import']->trigger_source_data();
		die();
	}

	public function reset_feed() {
		PF_Feed_Item::reset_feed();
		die();
	}

	public function make_it_readable() {
		PF_Readability::make_it_readable();
		die();
	}

	public function archive_a_nom() {
		PF_Nominations::archive_a_nom();
		die();
	}
}
