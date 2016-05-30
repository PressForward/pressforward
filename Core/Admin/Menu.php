<?php
namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;
use PressForward\Interfaces\SystemUsers;

class Menu implements HasActions, HasFilters {

	protected $basename;

	function __construct( $basename, SystemUsers $user_interface ){
		$this->basename = $basename;
		$this->user_interface = $user_interface;

	}


	public function action_hooks() {
		return array(
			array(
				'hook' => 'admin_menu',
				'method' => 'add_plugin_admin_menu',
			),
		);
	}

	public function filter_hooks(){
		return array(
			array(
				'hook' => 'admin_body_class',
				'method' => 'add_pf_body_class',
			),
		);
	}


	public function add_plugin_admin_menu() {
		remove_menu_page('edit.php?post_type=pf_feed_item');

		// Top-level menu page
		add_menu_page(
			PF_TITLE, // <title>
			PF_TITLE, // menu title
			get_option('pf_menu_group_access', $this->user_interface->pf_get_defining_capability_by_role('contributor') ), // cap required
			PF_MENU_SLUG, // slug
			array( $this, 'display_reader_builder' ), // callback
			PF_URL . 'pressforward-16.png', // icon URL
			24 // Position (just above comments - 25)
		);

		remove_submenu_page( PF_MENU_SLUG, 'edit.php?post_type=pf_feed' );
	}


	function add_pf_body_class($classes) {

		if (pressforward( 'controller.template_factory' )->is_a_pf_page()){
			$classes .= strtolower(PF_TITLE);
		}
		return $classes;
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

						pressforward('controller.template_factory')->the_page_headline();

					?>
					<button class="btn btn-small" id="fullscreenfeed"> <?php  _e('Full Screen', 'pf');  ?> </button>
				</div><!-- End title -->
				<?php pressforward('admin.templates')->search_template(); ?>

			</header><!-- End Header -->
			<?php
				pressforward('admin.templates')->nav_bar();
			?>
			<div role="main">
				<?php pressforward('admin.templates')->the_side_menu(); ?>
				<?php pressforward('schema.folders')->folderbox(); ?>
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

					pressforward('admin.templates')->nominate_this('as_feed_item');

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

				foreach ( pressforward('controller.loops')->archive_feed_to_display( $archive_feed_args ) as $item ) {

					pressforward('admin.templates')->form_of_an_item($item, $c);

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


				$query = new \WP_Query($query_arg);
				$post_count = $query->post_count;
				wp_reset_postdata();

		return $post_count;
	}

}
