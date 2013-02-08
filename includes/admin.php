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

		// AJAX handlers
		add_action( 'wp_ajax_nopriv_build_a_nomination', array( $this, 'build_a_nomination') );
		add_action( 'wp_ajax_build_a_nomination', array( $this, 'build_a_nomination') );
		add_action( 'wp_ajax_nopriv_build_a_nom_draft', array( $this, 'build_a_nom_draft') );
		add_action( 'wp_ajax_build_a_nom_draft', array( $this, 'build_a_nom_draft') );
		add_action( 'wp_ajax_nopriv_assemble_feed_for_pull', array($this, 'trigger_source_data') );
		add_action( 'wp_ajax_assemble_feed_for_pull', array( $this, 'trigger_source_data') );
		add_action( 'wp_ajax_nopriv_reset_feed', array($this, 'reset_feed') );
		add_action( 'wp_ajax_reset_feed', array( $this, 'reset_feed') );
		add_action( 'wp_ajax_nopriv_make_it_readable', array($this, 'make_it_readable') );
		add_action( 'wp_ajax_make_it_readable', array( $this, 'make_it_readable') );
		add_action( 'wp_ajax_nopriv_archive_a_nom', array($this, 'archive_a_nom') );
		add_action( 'wp_ajax_archive_a_nom', array( $this, 'archive_a_nom') );
	}

	/**
	 * Register menu pages
	 */
	function register_pf_custom_menu_pages() {
		// Top-level menu page
		add_menu_page(
			PF_TITLE, // <title>
			PF_TITLE, // menu title
			'edit_posts', // cap required
			PF_MENU_SLUG, // slug
			array( $this, 'display_reader_builder' ), // callback
			PF_URL . '/pressforward-16.png', // icon URL
			24 // Position (just above comments - 25)
		);

		add_submenu_page(
			PF_MENU_SLUG,
			__('All Content', 'pf'),
			__('All Content', 'pf'),
			'edit_posts',
			PF_MENU_SLUG,
			array($this, 'display_reader_builder')
		);

		add_submenu_page(
			PF_MENU_SLUG,
			__('Under Review', 'pf'),
			__('Under Review', 'pf'),
			'edit_posts',
			PF_SLUG . '-review',
			array($this, 'display_review_builder')
		);

		// Options page is accessible only to Administrators
		add_submenu_page(
			PF_MENU_SLUG,
			PF_TITLE . __(' Options', 'pf'), // @todo sprintf
			PF_TITLE . __(' Options', 'pf'), // @todo Too big to fit on a single line
			'manage_options',
			PF_SLUG . '-options',
			array($this, 'display_options_builder')
		);

		// Feed-listing page is accessible only to Editors and above
		add_submenu_page(
			PF_MENU_SLUG,
			PF_TITLE . __(' Feeder', 'pf'),
			PF_TITLE . __(' Feeder', 'pf'),
			'edit_others_posts',
			PF_SLUG . '-feeder',
			array($this, 'display_feeder_builder')
		);

		add_submenu_page(
			PF_MENU_SLUG,
			__('Add Nomination', 'pf'),
			__('Add Nomination', 'pf'),
			'edit_posts',
			PF_NOM_POSTER
		);
	}

	/**
	 * Display function for the main All Content panel
	 */
	public function display_reader_builder() {
		//Calling the feedlist within the pf class.
	echo '<div class="container-fluid">';
		echo '<div class="row-fluid">';
			echo '<div class="span9 title-span">';
				echo '<h1>' . PF_TITLE . '</h1>';
				echo '<img class="loading-top" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="Loading..." style="display: none" />';
				echo '<div id="errors"></div>';
			echo '</div><!-- End title 9 span -->';
		echo '</div><!-- End Row -->';
		echo '<div class="row-fluid">';

			echo 	'<div class="span6">
						<div class="btn-group">
							<button type="submit" class="refreshfeed btn btn-warning" id="refreshfeed" value="' . __('Refresh', 'pf') . '">' . __('Refresh', 'pf') . '</button>
							<button type="submit" class="btn btn-info feedsort" id="sortbyitemdate" value="' . __('Sort by item date', 'pf') . '" >' . __('Sort by item date', 'pf') . '</button>
							<button type="submit" class="btn btn-info feedsort" id="sortbyfeedindate" value="' . __('Sort by date entered feed', 'pf') . '">' . __('Sort by date entered feed', 'pf') . '</button>
							<button class="btn btn-inverse" id="fullscreenfeed">' . __('Full Screen', 'pf') . '</button>
						</div><!-- End btn-group -->
					</div><!-- End span6 -->';
			echo 	'<div class="span3 offset3">
						<button type="submit" class="delete btn btn-danger pull-right" id="deletefeedarchive" value="' . __('Delete entire feed archive', 'pf') . '" >' . __('Delete entire feed archive', 'pf') . '</button>
					</div><!-- End span3 -->';

		echo '</div><!-- End Row -->';
		//A testing method, to insure the feed is being received and processed.
		//print_r($theFeed);
		echo '<div class="row-fluid main-container">';

			# Some buttons to the left
			echo '<div class="span1 deck">';
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

		//Use this foreach loop to go through the overall feedlist, select each individual feed item (post) and do stuff with it.
		//Based off SimplePie's tutorial at http://simplepie.org/wiki/tutorial/how_to_display_previous_feed_items_like_google_reader.
		$c = 1;

			echo '<div class="span7 feed-container accordion" id="feed-accordion">';
		$ic = 0;
		# http://twitter.github.com/bootstrap/javascript.html#collapse
			if (isset($_GET["pc"])){
				$page = $_GET["pc"];
				$page = $page-1;
			} else {
				$page = 0;
			}
			$count = $page * 20;
			$c = $c+$count;
			//print_r($count);
		foreach(PF_RSS_Import_Feed_Item::archive_feed_to_display($count+1) as $item) {

			$itemTagsArray = explode(",", $item['item_tags']);
			$itemTagClassesString = '';
			foreach ($itemTagsArray as $itemTag) { $itemTagClassesString .= pf_slugger($itemTag, true, false, true); $itemTagClassesString .= ' '; }
			echo '<div class="well accordion-group feed-item row-fluid ' . pf_slugger(($item['source_title']), true, false, true) . ' ' . $itemTagClassesString . '" id="' . $item['item_id'] . '">';

				echo '<div class="span12" id="' . $c . '">';
							# Let's build an info box!
							//http://nicolasgallagher.com/pure-css-speech-bubbles/

							$urlArray = parse_url($item['item_link']);
							$sourceLink = 'http://' . $urlArray['host'];
							//http://nicolasgallagher.com/pure-css-speech-bubbles/demo/
							echo '<div class="feed-item-info-box well leftarrow" id="info-box-' . $item['item_id'] . '" style="display:none;">';
								echo '
								' . __('Feed', 'pf') . ': <span class="feed_title">' . $item['source_title'] . '</span><br />
								' . __('Posted on', 'pf') . ': <span class="feed_posted">' . $item['item_date'] . '</span><br />
								' . __('Added to feed on', 'pf') . '<span class="item_meta item_meta_added_date">' . $item['item_added_date'] . '.</span><br />
								' . __('Authors', 'pf') . ': <span class="item_authors">' . $item['item_author'] . '</span><br />
								' . __('Origin', 'pf') . ': <span class="source_name"><a target ="_blank" href="' . $sourceLink . '">' . $sourceLink . '</a></span><br />
								' . __('Original Item', 'pf') . ': <span class="source_link"><a href="' . $item['item_link'] . '" class="item_url" target ="_blank">' . $item['item_title'] . '</a></span><br />
								' . __('Tags', 'pf') . ': <span class="item_tags">' . $item['item_tags'] . '</span><br />
								' . __('Times repeated in source', 'pf') . ': <span class="feed_repeat">' . $item['source_repeat'] . '</span><br />
								';
							echo '</div>';
					echo '<div class="row-fluid accordion-heading">';
					//echo '<a name="' . $c . '" style="display:none;"></a>';

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


					echo '<a class="accordion-toggle" data-toggle="collapse" data-parent="#feed-accordion" href="#collapse' . $c . '">';
						if ($item['item_feat_img'] != ''){
						echo '<div class="span3">';
							echo '<div class="thumbnail">';
							echo '<div style="float:left; margin-right: 10px; margin-bottom: 10px;"><img src="' . $item['item_feat_img'] . '"></div>';
							echo '</div>';
						echo '</div><!-- End span3 -->';
						echo '<div class="span8">';
						} else {
						echo '<div class="span1">';
								echo '<div style="float:left; margin: 10px auto;">
										<div class="thumbnail" >
										<img src="' . PF_URL . 'assets/images/books.png">
										</div>
									</div>';
						echo '</div><!-- End span1 -->';
						echo '<div class="span10">';
						}

							echo $c . '. ';
							//The following is a fix as described in http://simplepie.org/wiki/faq/typical_multifeed_gotchas
							//$iFeed = $item->get_feed();
							echo '<span class="source_title">' . $item['source_title'] . '</span>';
							echo ' : ';
							echo '<h3>' . $item['item_title'] . '</h3>';
							//echo '<br />';
							echo '<div class="item_meta item_meta_date">Published on ' . $item['item_date'] . ' by <span class="item-authorship">' . $item['item_author'] . '</span>.</div>';
							echo '<div style="display:none;">Unix timestamp for item date:<span class="sortableitemdate">' . strtotime($item['item_date']) . '</span> and for added to feed date <span class="sortablerssdate">' . strtotime($item['item_added_date']) . '</span>.</div>';
							echo '<div class="item_excerpt" id="excerpt' . $c . '">' . pf_feed_excerpt($item['item_content']) . '</div>';
						echo '</div><!-- End span8 or 10 -->';
					echo '</a>';
						echo '<div class="span1">';
							# Perhaps use http://twitter.github.com/bootstrap/javascript.html#popovers instead?
							echo '<button class="btn btn-small itemInfobutton" id="' . $item['item_id'] . '"><i class="icon-info-sign"></i></button>';
						echo '</div>';
					echo '</div><!-- End row-fluid -->';

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
					//print_r($item);
					//print_r($ent = htmlentities($item['item_content']));
					//print_r(html_entity_decode($ent));

					echo '<div class="item_actions row-fluid">';
						echo '<div class="span12">';
							//This needs a nonce for security.
							echo '<form name="form-' . $item['item_id'] . '"><p>';
							pf_prep_item_for_submit($item);
							wp_nonce_field('nomination', PF_SLUG . '_nomination_nonce', false);
							//print_r($this->get_posts_after_for_check( 2011-01-03, 'nomination' ));
							//if(!($this->get_post_nomination_status('2012-08-10', $item['item_id'], 'post'))){
								//print_r( 'false < test.'); } else { print_r('true'); die();}
							echo '<input type="hidden" name="GreetingAll" class="GreetingAll" value="Hello Everyone!" />'
									. '<input type="submit" class="PleasePushMe" id="' . $item['item_id'] . '" value="' . __('Nominate', 'pf') . '" />'
									. '<div class="nominate-result-' . $item['item_id'] . '">'
									. '<img class="loading-' . $item['item_id'] . '" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="' . __('Loading', 'pf') . '..." style="display: none" />'
									. '</div></p>'
								  . '</form>';


					echo '</div><!-- End accordion Inner -->';
					echo '</div><!-- End accordion body -->';

						echo '</div>';
					echo '</div>';
				echo '</div><!-- End span12 -->';

			echo '</div><!-- End row-fluid -->';

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

		echo '</div><!-- End feed-container span7 -->';

		echo '<div class="span4 feed-widget-container">';
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

		echo '</div><!-- End feed-widget-container span4 -->';

	echo '</div><!-- End row -->';

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
					<input type="submit" name="submit" class="button-primary" value="<?php _e( "Save Changes", 'pf' ) ?>" />
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
				<p><textarea rows="5" cols="120" readonly="readonly"><?php echo htmlspecialchars( $this->pf_get_shortcut_link() ); ?></textarea></p>
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

		//print_r($hook);
		//This if loop will check to make sure we are on the right page for the js we are going to use.
		if (('toplevel_page_pf-menu') == $hook) {
			//And now lets enqueue the script, ensuring that jQuery is already active.

			wp_enqueue_script('tinysort', PF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_enqueue_script('sort-imp', PF_URL . 'assets/js/sort-imp.js', array( 'tinysort', 'twitter-bootstrap', 'jq-fullscreen' ));
			wp_enqueue_script('readability-imp', PF_URL . 'assets/js/readability-imp.js', array( 'twitter-bootstrap', 'jquery' ));
			wp_enqueue_script('nomination-imp', PF_URL . 'assets/js/nomination-imp.js', array( 'jquery' ));
			wp_enqueue_script('twitter-bootstrap', PF_URL . 'lib/twitter-bootstrap/js/bootstrap.js' , array( 'jquery' ));
			wp_enqueue_script('jq-fullscreen', PF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			wp_enqueue_script('infiniscroll', PF_URL . 'lib/jquery.infinitescroll.js', array( 'jquery' ));
			wp_enqueue_script('scrollimp', PF_URL . 'assets/js/scroll-imp.js', array( 'infiniscroll' ));

			wp_enqueue_style('bootstrap-style');
			wp_enqueue_style('bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );

		}
		if (('pressforward_page_pf-review') == $hook) {
			wp_enqueue_script('tinysort', PF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_enqueue_script('jq-fullscreen', PF_URL . 'lib/jquery-fullscreen/jquery.fullscreen.js', array( 'jquery' ));
			wp_enqueue_script('twitter-bootstrap', PF_URL . 'lib/twitter-bootstrap/js/bootstrap.js' , array( 'jquery' ));
			wp_enqueue_script('send-to-draft-imp', PF_URL . 'assets/js/send-to-draft-imp.js', array( 'jquery' ));
			wp_enqueue_script('archive-nom-imp', PF_URL . 'assets/js/nom-archive-imp.js', array( 'jquery' ));
			wp_enqueue_style('bootstrap-style');
			wp_enqueue_style('bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );
			wp_enqueue_script( 'post' );
		}
		if (('pressforward_page_pf-feeder') != $hook) { return; }
		else {
			//And now lets enqueue the script, ensuring that jQuery is already active.

			wp_enqueue_script('tinysort', PF_URL . 'lib/jquery-tinysort/jquery.tinysort.js', array( 'jquery' ));
			wp_enqueue_script('twitter-bootstrap', PF_URL . 'lib/twitter-bootstrap/js/bootstrap.js' , array( 'jquery' ));

			wp_register_style( PF_SLUG . '-style', PF_URL . 'assets/css/style.css');
			wp_register_style( 'bootstrap-style', PF_URL . 'lib/twitter-bootstrap/css/bootstrap.css');
			wp_register_style( 'bootstrap-responsive-style', PF_URL . 'lib/twitter-bootstrap/css/bootstrap-responsive.css');

			wp_enqueue_style('bootstrap-style');
			wp_enqueue_style('bootstrap-responsive-style');
			wp_enqueue_style( PF_SLUG . '-style' );

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


}
