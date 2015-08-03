<?php

/**
 * Functionality related to nominations
 */
class PF_Nominations {
	function __construct() {
		$this->post_type = 'nomination';
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action('edit_post', array( $this, 'send_nomination_for_publishing'));
		add_filter( 'manage_edit-nomination_columns', array($this, 'edit_nominations_columns') );
		add_action( 'manage_nomination_posts_custom_column',  array($this, 'nomination_custom_columns') );
		add_filter( "manage_edit-nomination_sortable_columns", array($this, "nomination_sortable_columns") );
		add_action( 'feeder_menu', array($this, "nominate_this_tile"), 11 );

	}

	/**
	 * Register the 'nomination' post type
	 *
	 * @since 1.7
	 */
	function register_post_type() {
		$args = array(
			'labels' => array(
				'name' => __( 'Nominations', 'pf' ),
				'singular_name' => __( 'Nomination', 'pf' ),
				'add_new' => __('Nominate', 'pf'),
				'add_new_item' => __('Add New Nomination', 'pf'),
				'edit_item' => __('Edit Nomination', 'pf'),
				'new_item' => __('New Nomination', 'pf'),
				'view_item' => __('View Nomination', 'pf'),
				'search_items' => __('Search Nominations', 'pf'),
				'not_found' => __('No nominations found', 'pf'),
				'not_found_in_trash' => __('No nominations found in Trash', 'pf')
			),
			'description' => __('Posts from around the internet nominated for consideration to public posting', 'pf'),
			//Not available to non-users.
			'public' => false,
			//I want a UI for users to use, so true.
			'show_ui' => true,
			//But not the default UI, we want to attach it to the plugin menu.
			'show_in_menu' => false,
			//Linking in the metabox building function.
			'register_meta_box_cb' => array($this, 'nominations_meta_boxes'),
			'capability_type' => 'post',
			//The type of input (besides the metaboxes) that it supports.
			'supports' => array('title', 'editor', 'thumbnail', 'revisions'),
			//I think this is set to false by the public argument, but better safe.
			'has_archive' => false
		);

		register_post_type('nomination', $args);


#		register_taxonomy_for_object_type( pressforward()->get_feed_folder_taxonomy(), $this->post_type );

	}

	/**
	 * Callback for registering meta boxes on 'nomination' post type
	 *
	 * @since 1.7
	 */
	public function nominations_meta_boxes() {
		add_meta_box(
			'pf-nominations',
			__('Nomination Data', 'pf'),
			array($this, 'nominations_box_builder'),
			'nomination',
			'side',
			'high'
		);
	}

	# The builder for the box that shows us the nomination metadata.
	public function nominations_box_builder() {
		global $post;
		//wp_nonce_field( 'nominate_meta', 'nominate_meta_nonce' );
		$origin_item_ID = pf_get_post_meta($post->ID, 'origin_item_ID', true);
		$nomination_count = pf_get_post_meta($post->ID, 'nomination_count', true);
		$submitted_by = pf_get_post_meta($post->ID, 'submitted_by', true);
		$source_title = pf_get_post_meta($post->ID, 'source_title', true);
		$posted_date = pf_get_post_meta($post->ID, 'posted_date', true);
		$nom_authors = pf_get_post_meta($post->ID, 'authors', true);
		$item_link = pf_get_post_meta($post->ID, 'item_link', true);
		$date_nominated = pf_get_post_meta($post->ID, 'date_nominated', true);
		$user = get_user_by('id', $submitted_by);
		$item_tags = pf_get_post_meta($post->ID, 'item_tags', true);
		$source_repeat = pf_get_post_meta($post->ID, 'source_repeat', true);
		if (!empty($origin_item_ID)){
			$this->meta_box_printer(__('Item ID', 'pf'), $origin_item_ID);
		}
		if (empty($nomination_count)){$nomination_count = 1;}
		$this->meta_box_printer(__('Nomination Count', 'pf'), $nomination_count);
		if (empty($user)){ $user = wp_get_current_user(); }
		$this->meta_box_printer(__('Submitted By', 'pf'), $user->display_name);
		if (!empty($source_title)){
			$this->meta_box_printer(__('Feed Title', 'pf'), $source_title);
		}
		if (empty($posted_date)){
			$this->meta_box_printer(__('Posted by source on', 'pf'), $posted_date);
		} else {
			$this->meta_box_printer(__('Source Posted', 'pf'), $posted_date);
		}
		$this->meta_box_printer(__('Source Authors', 'pf'), $nom_authors);
		$this->meta_box_printer(__('Source Link', 'pf'), $item_link, true, __('Original Post', 'pf'));
		$this->meta_box_printer(__('Item Tags', 'pf'), $item_tags);
		if (empty($date_nominated)){ $date_nominated = date(DATE_ATOM); }
		$this->meta_box_printer(__('Date Nominated', 'pf'), $date_nominated);
		if (!empty($source_repeat)){
			$this->meta_box_printer(__('Repeated in Feed', 'pf'), $source_repeat);
		}

	}

	public function get_the_source_statement($nom_id){

		$title_of_item = get_the_title($nom_id);
		$link_to_item = pf_get_post_meta($nom_id, 'item_link', true);
		$args = array(
		  'html_before' => "<p>",
		  'source_statement' => "Source: ",
		  'item_url' => $link_to_item,
		  'link_target' => "_blank",
		  'item_title' => $title_of_item,
		  'html_after' => "</p>",
		  'sourced' => true
		);
		$args = apply_filters('pf_source_statement', $args);
		if (true == $args['sourced']) {
			$statement = sprintf('%1$s<a href="%2$s" target="%3$s" pf-nom-item-id="%4$s">%5$s</a>',
				 esc_html($args['source_statement']),
				 esc_url($args['item_url']),
				 esc_attr($args['link_target']),
				 esc_attr($nom_id),
				 esc_html($args['item_title'])
			);
			$statement = $args['html_before'] . $statement . $args['html_after'];
		} else {
			$statement = '';
		}
		return $statement;

	}

	public function get_first_nomination($item_id, $post_type){
		$q = pf_get_posts_by_id_for_check($post_type, $item_id, true);
		if ( 0 < $q->post_count ){
			$nom = $q->posts;
			$r = $nom[0];
			return $r;
		} else {
			return false;
		}
	}

	public function is_nominated($item_id, $post_type = false, $update = false){
		if (!$post_type) {
			$post_type = array('post', 'nomination');
		}
		$attempt = $this->get_first_nomination($item_id, $post_type);
		if (!empty($attempt)){
			$r = $attempt;
			pf_log('Existing post at '.$r);
		} else {
			$r = false;
		}
		/* Restore original Post Data */
		wp_reset_postdata();
		return $r;
	}

	public function resolve_nomination_state($item_id){
		$pt = array('nomination');
		if ($this->is_nominated($item_id, $pt)){
			$attempt = $this->get_first_nomination($item_id, $pt);
			if (!empty($attempt)){
				$nomination_id = $attempt;
				$nominators = pf_retrieve_meta($nomination_id, 'nominator_array');
				if (empty($nominators)){
					pf_log('There is no one left who nominated this item.');
					pf_log('This nomination has been taken back. We will now remove the item.');
					pf_delete_item_tree( $nomination_id );
				} else {
					pf_log('Though one user retracted their nomination, there are still others who have nominated this item.');
				}
			} else {
				pf_log('We could not find the nomination to resolve the state of.');
			}
		} else {
			pf_log('There is no nomination to resolve the state of.');
		}
	}

	public function nominate_this_tile(){
		pressforward()->form_of->nominate_this('as_feed');
	}

	public function change_nomination_count($id, $up = true){
		$nom_count = pf_retrieve_meta($id, 'nomination_count');
		if ( $up ) {
			$nom_count++;
		} else {
			$nom_count--;
		}
		$check = pf_update_meta($id, 'nomination_count', $nom_count);
		pf_log('Nomination now has a nomination count of ' . $nom_count . ' applied to post_meta with the result of '.$check);
		return $check;
	}

	public function toggle_nominator_array($id, $update = true){
		$nominators = pf_retrieve_meta($id, 'nominator_array');
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		if ($update){
			$nominators[] = $user_id;
		} else {
			if(($key = array_search($user_id, $nominators)) !== false) {
				unset($nominators[$key]);
			}
		}
		$check = pf_update_meta($id, 'nominator_array', $nominators);
		return $check;
	}

	public function did_user_nominate($id, $user_id = false){
		$nominators = pf_retrieve_meta($id, 'nominator_array');
		if (!$user_id){
			$current_user = wp_get_current_user();
			$user_id = $current_user->ID;
		}
		if (!empty($nominators) && in_array($user_id, $nominators)){
			return true;
		} else {
			return false;
		}
	}

	public function handle_post_nomination_status($item_id, $force = false){
		$nomination_state = $this->is_nominated($item_id);
		$check = false;
		if (false != $nomination_state){
			if ( $this->did_user_nominate($nomination_state) ){
				$this->change_nomination_count($nomination_state, false);
				$this->toggle_nominator_array($nomination_state, false);
				$check = false;
				pf_log( 'user_unnonminated' );
				$this->resolve_nomination_state($item_id);
			} else {
				$this->change_nomination_count($nomination_state);
				$this->toggle_nominator_array($nomination_state);
				$check = false;
				pf_log('user_added_additional_nomination');
			}
		} else {
			$check = true;
		}
		pf_log($check);
		return $check;
	}

	public function send_nomination_for_publishing() {
		global $post;
		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		//if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		if ( isset( $_POST['post_status'] ) && isset( $_POST['post_type'] ) && ($_POST['post_status'] == 'publish') && ($_POST['post_type'] == 'nomination')){
		//print_r($_POST); die();
			$item_title = $_POST['post_title'];
			$item_content = $_POST['post_content'];
			$item_feed_post_id = pf_get_post_meta($_POST['ID'], 'item_feed_post_id', true);
			$url = pf_get_post_meta($_POST['ID'], 'item_link', true);
			$linked = get_option('pf_link_to_source', 0);
			if ($linked < 1){
				$item_content = $item_content . $this->get_the_source_statement( $item_feed_post_id );
			}
			$data = array(
				'post_status' => get_option(PF_SLUG.'_draft_post_status', 'draft'),
				'post_type' => get_option(PF_SLUG.'_draft_post_type', 'post'),
				'post_title' => $item_title,
				'post_content' => $item_content
			);
			//Will need to use a meta field to pass the content's md5 id around to check if it has already been posted.

			//We assume that it is already in nominations, so no need to check there. This might be why we can't use post_exists here.
			//No need to origonate the check at the time of the feed item either. It can't become a post with the proper meta if it wasn't a nomination first.
			$item_id = pf_get_post_meta($_POST['ID'], 'origin_item_ID', true);
			$nom_date = $_POST['aa'] . '-' . $_POST['mm'] . '-' . $_POST['jj'];

			$check = false;
			if (false != pf_is_drafted($item_id)){
				$check = true;
			}

			//Alternative check with post_exists? or use same as above?
			if ($post_check) {
				$newPostID = wp_insert_post( $data );
				#add_post_meta($newPostID, 'origin_item_ID', $item_id, true);
				pf_meta_transition_post($_POST['ID'], $newPostID, true);

				$already_has_thumb = has_post_thumbnail($_POST['ID']);
				if ($already_has_thumb)  {
					$post_thumbnail_id = get_post_thumbnail_id( $_POST['ID'] );
					set_post_thumbnail($newPostID, $post_thumbnail_id);
				}

			}
		}

	}

	public function remove_post_nomination($date, $item_id, $post_type, $updateCount = true){
		$postsAfter = pf_get_posts_by_id_for_check( $post_type, $item_id );
		//Assume that it will not find anything.
		$check = false;
		if ( $postsAfter->have_posts() ) : while ( $postsAfter->have_posts() ) : $postsAfter->the_post();

					$id = get_the_ID();
					$origin_item_id = pf_retrieve_meta($id, 'origin_item_ID');
					$current_user = wp_get_current_user();
					if ($origin_item_id == $item_id) {
						$check = true;
						$nomCount = pf_retrieve_meta($id, 'nomination_count');
						$nomCount--;
						pf_update_meta($id, 'nomination_count', $nomCount);
						if ( 0 != $current_user->ID ) {
							$nominators_orig = pf_retrieve_meta($id, 'nominator_array');
							if (true == in_array($current_user->ID, $nominators_orig)){
								$nominators_new = array_diff($nominators_orig, array($current_user->ID));
								if (empty($nominators_new)){
									wp_delete_post( $id );
								} else {
									pf_update_meta( $id, 'nominator_array', $nominators_new );
								}
							}
						}
					}
		endwhile;	else :
			pf_log(' No nominations found for ' . $item_id);
		endif;
		wp_reset_postdata();
	}

	public function get_post_nomination_status($date, $item_id, $post_type, $updateCount = true){
		global $post;
        //Get the query object, limiting by date, type and metavalue ID.
		pf_log('Get posts matching '.$item_id);
		$postsAfter = pf_get_posts_by_id_for_check( $post_type, $item_id );
		//Assume that it will not find anything.
		$check = false;
		pf_log('Check for nominated posts.');
		if ( $postsAfter->have_posts() ) : while ( $postsAfter->have_posts() ) : $postsAfter->the_post();

				$id = get_the_ID();
				pf_log('Deal with nominated post '.$id);
				$origin_item_id = pf_retrieve_meta($id, 'origin_item_ID');
                $current_user = wp_get_current_user();
				if ($origin_item_id == $item_id) {
					$check = true;
					//Only update the nomination count on request.
					if ($updateCount){
						if ( 0 == $current_user->ID ) {
							//Not logged in.
							//If we ever reveal this to non users and want to count nominations by all, here is where it will go.
							pf_log('Can not find user for updating nomionation count.');
                            $nomCount = pf_retrieve_meta($id, 'nomination_count');
                            $nomCount++;
                            pf_update_meta($id, 'nomination_count', $nomCount);
														$check = 'no_user';
						} else {
							$nominators_orig = pf_retrieve_meta($id, 'nominator_array');
                            if (!in_array($current_user->ID, $nominators_orig)){
                                $nominators = $nominators_orig;
                                $nominator = $current_user->ID;
																$nominators[] = $current_user->ID;
                                pf_update_meta($id, 'nominator_array', $nominator);
                                $nomCount = pf_get_post_meta($id, 'nomination_count', true);
                                pf_log('So far we have a nominating count of '.$nomCount);
																$nomCount++;
																pf_log('Now we have a nominating count of '.	$nomCount);
                                $check_meta = pf_update_meta($id, 'nomination_count', $nomCount);
																pf_log('Attempt to update the meta for nomination_count resulted in: ');
																pf_log($check_meta);
																$check = true;
                            } else {
                                $check = 'user_nominated_already';
                            }
						}


					return $check;
					break;
					}
				} else {
					pf_log('No nominations found for ' . $item_id);
					$check = 'unmatched_post';
				}
		endwhile;	else :
			pf_log(' No nominations found for ' . $item_id);
			$check = 'unmatched_post';
		endif;
		wp_reset_postdata();
		return $check;
	}

	/**
	 * Handles an archive action submitted via AJAX
	 *
	 * @since 1.7
	 */
	public static function archive_a_nom(){
		$pf_drafted_nonce = $_POST['pf_drafted_nonce'];
		if (! wp_verify_nonce($pf_drafted_nonce, 'drafter')){
			die($this->__('Nonce not recieved. Are you sure you should be archiving?', 'pf'));
		} else {
			$current_user = wp_get_current_user();
			$current_user_id = $current_user->ID;
			add_post_meta($_POST['nom_id'], 'archived_by_user_status', 'archived_' . $current_user_id);
			print_r(__('Archived.', 'pf'));
			die();
		}
	}


	public function meta_box_printer($title, $variable, $link = false, $anchor_text = 'Link'){
		echo '<strong>' . $title . '</strong>: ';
		if (empty($variable)){
			echo '<br /><input type="text" name="'.$title.'">';
		} else {
			if ($link === true){
				if ($anchor_text === 'Link'){
					$anchor_text = $this->__('Link', 'pf');
				}
				echo '<a href=';
				echo $variable;
				echo '" target="_blank">';
				echo $anchor_text;
				echo '</a>';
			} else {
				echo $variable;
			}
		}

		echo '<br />';
	}

	# This and the next few functions are to modify the table that shows up when you click "Nominations".
	function edit_nominations_columns ( $columns ){

		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Title', 'pf'),
			'date' => __('Last Modified', 'pf'),
			'nomcount' => __('Nominations', 'pf'),
			'nominatedby' => __('Nominated By', 'pf'),
			'original_author' => __('Original Author', 'pf'),
			'date_nominated' => __('Date Nominated', 'pf')
		);

		return $columns;

	}

	//Via http://slides.helenhousandi.com/wcnyc2012.html#15 and http://svn.automattic.com/wordpress/tags/3.4/wp-admin/includes/class-wp-posts-list-table.php
	function nomination_custom_columns ( $column ) {

		global $post;
		switch ($column) {
			case 'nomcount':
				echo pf_get_post_meta($post->ID, 'nomination_count', true);
				break;
			case 'nominatedby':
				$nominatorID = pf_get_post_meta($post->ID, 'submitted_by', true);
				$user = get_user_by('id', $nominatorID);
				if ( is_a( $user, 'WP_User' ) ) {
					echo $user->display_name;
				}
				break;
			case 'original_author':
				$orig_auth = pf_get_post_meta($post->ID, 'authors', true);
				echo $orig_auth;
				break;
			case 'date_nominated':
				$dateNomed = pf_get_post_meta($post->ID, 'date_nominated', true);
				echo $dateNomed;
				break;


		}
	}

	// Make these columns sortable
	function nomination_sortable_columns() {
	  return array(
		'title' => 'title',
		'date' => 'date',
		'nomcount' => 'nomcount',
		'nominatedby' => 'nominatedby',
		'original_author' => 'original_author',
		'date_nominated' => 'date_nominated'
	  );
	}

	function build_nomination() {

		// Verify nonce
		if ( !wp_verify_nonce($_POST[PF_SLUG . '_nomination_nonce'], 'nomination') )
			die( __( "Nonce check failed. Please ensure you're supposed to be nominating stories.", 'pf' ) );

		if ('' != (get_option('timezone_string'))){
			date_default_timezone_set(get_option('timezone_string'));
		}
		//ref http://wordpress.stackexchange.com/questions/8569/wp-insert-post-php-function-and-custom-fields, http://wpseek.com/wp_insert_post/
		$time = current_time('mysql', $gmt = 0);
		//@todo Play with post_exists (wp-admin/includes/post.php ln 493) to make sure that submissions have not already been submitted in some other method.
			//Perhaps with some sort of "Are you sure you don't mean this... reddit style thing?
			//Should also figure out if I can create a version that triggers on nomination publishing to send to main posts.


		//There is some serious delay here while it goes through the database. We need some sort of loading bar.
		ob_start();
		//set up nomination check
		$item_wp_date = $_POST['item_wp_date'];
		$item_id = $_POST['item_id'];
		//die($item_wp_date);

		//Going to check posts first on the assumption that there will be more nominations than posts.
		$nom_check = $this->handle_post_nomination_status($item_id);
		pf_log('We handle the item into a nomination?');
		pf_log($nom_check);
		/** The system will only check for nominations of the item does not exist in posts. This will stop increasing the user and nomination count in nominations once they are sent to draft.
		**/
		if ($nom_check) {
            //Record first nominator and/or add a nomination to the user's count.
            $current_user = wp_get_current_user();
            if ( 0 == $current_user->ID ) {
                //Not logged in.
                $userSlug = "external";
                $userName = __('External User', 'pf');
                $userID = 0;
            } else {
                // Logged in.
                self::user_nomination_meta();
            }
						$userID = $current_user->ID;
            $userString = $userID;

				//set up rest of nomination data
				$item_title = $_POST['item_title'];
				$item_content = $_POST['item_content'];
				$item_link = pf_retrieve_meta($_POST['item_post_id'], 'item_link');
				$readable_status = pf_retrieve_meta($_POST['item_post_id'], 'readable_status');
				$item_author = pf_retrieve_meta($_POST['item_post_id'], 'item_author');
				$parents = get_post_ancestors( $_POST['item_post_id'] );
				$parent_id = ($parents) ? $parents[0] : false;
				if ($readable_status != 1){
					$read_args = array('force' => '', 'descrip' => $item_content, 'url' => $item_link, 'authorship' => $item_author );
					$item_content_obj = pressforward()->readability->get_readable_text($read_args);
					$item_content = htmlspecialchars_decode($item_content_obj['readable']);
				} else {
					$item_content = htmlspecialchars_decode($_POST['item_content']);
				}

				//No need to define every post arg right? I should only need the ones I'm pushing through. Well, I guess we will find out.
				$data = array(
					'post_status' => 'draft',
					'post_type' => 'nomination',
					//'post_author' => $user_ID,
						//Hurm... what we really need is a way to pass the nominator's userID to this function to credit them as the author of the nomination.
						//Then we could create a leaderboard. ;
					//'post_date' => $_SESSION['cal_startdate'],
						//Do we want this to be nomination date or origonal posted date? Prob. nomination date? Optimally we can store and later sort by both.
					'post_title' => $item_title,//$item_title,
					'post_content' => $item_content,
					'post_parent' => $parent_id

				);

				$newNomID = wp_insert_post( $data );
				if ((1 == $readable_status) && ((!empty($item_content_obj['status'])) && ('secured' != $item_content_obj['status']))){
					pf_update_meta($_POST['item_post_id'], 'readable_status', 1);
				} elseif ((1 != $readable_status)) {
					pf_update_meta($_POST['item_post_id'], 'readable_status', 0);
				}

		if ($_POST['item_feat_img'] != '')
			pressforward()->pf_feed_items->set_ext_as_featured($newNomID, $_POST['item_feat_img']);
			//die($_POST['item_feat_img']);
			pf_update_meta($_POST['item_post_id'], 'nomination_count', 1);
			pf_update_meta($_POST['item_post_id'], 'submitted_by', $userString);
			pf_update_meta($_POST['item_post_id'], 'nominator_array', array($userID));
			pf_update_meta($_POST['item_post_id'], 'date_nominated', date('c'));
			pf_update_meta($_POST['item_post_id'], 'origin_item_ID', $item_id);
			pf_update_meta($_POST['item_post_id'], 'item_feed_post_id', $_POST['item_post_id']);
			pf_update_meta($_POST['item_post_id'], 'item_link', $_POST['item_link']);
				$item_date = $_POST['item_date'];
				if (empty($_POST['item_date'])){
					$newDate = gmdate('Y-m-d H:i:s');
					$item_date = $newDate;
				}
			pf_update_meta($_POST['item_post_id'], 'posted_date', $item_date);
			if ( !empty( $_POST['pf_amplify'] ) && ( '1' == $_POST['pf_amplify'] ) ){
				$amplify = true;
			} else {
				$amplify = false;
			}
			pf_meta_transition_post( $_POST['item_post_id'], $newNomID, $amplify );
				$response = array(
					'what' => 'nomination',
					'action' => 'build_nomination',
					'id' => $newNomID,
					'data' => $item_title . ' nominated.',
					'supplemental' => array(
						'content' => $item_content,
						'originID' => $item_id,
						'nominater' => $userID,
						'buffered' => ob_get_contents()
					)
				);
				$xmlResponse = new WP_Ajax_Response($response);
				$xmlResponse->send();
			ob_end_flush();
			die();
		} else {
			pf_log('User nominated already.');
			die('nominated_already');
		}
	}

	function user_nomination_meta($increase = true){
		$current_user = wp_get_current_user();
        $userID = $current_user->ID;
		if (get_user_meta( $userID, 'nom_count', true )){

						$nom_counter = get_user_meta( $userID, 'nom_count', true );
						if ($increase) {
							$nom_counter++;
						}	else {
							$nom_counter--;
						}
						update_user_meta( $userID, 'nom_count', $nom_counter, true );

		} elseif ($increase) {
						add_user_meta( $userID, 'nom_count', 1, true );

		} else {
			return false;
		}
	}

	public function simple_nom_to_draft($id = false){
		global $post;
		$pf_drafted_nonce = $_POST['pf_nomination_nonce'];
		if (! wp_verify_nonce($pf_drafted_nonce, 'nomination')){
			die(__('Nonce not recieved. Are you sure you should be drafting?', 'pf'));
		} else {
			if (!$id){
				$id = $_POST['nom_id'];
				$nom = get_post($id);
				$item_id = pf_retrieve_meta($id, 'item_id');
			}
			$post_check = $this->is_nominated($item_id, 'post', false);
			if (true != $post_check) {

				$item_link = pf_retrieve_meta($id, 'item_link');
				$author = get_the_item_author($id);
				$content = $nom->post_content;
				$linked = get_option('pf_link_to_source', 0);
				if ($linked < 1){
					$content = $content . $this->get_the_source_statement( $_POST['nom_id']);
				}
				$title = $nom->post_title;
				$data = array(
					'post_status' => get_option(PF_SLUG.'_draft_post_status', 'draft'),
					'post_type' => get_option(PF_SLUG.'_draft_post_type', 'post'),
					'post_title' => $title,
					'post_content' => $content
				);
				# Check if the item was rendered readable, if not, make it so.
				$readable_state = pf_get_post_meta($id, 'readable_status', true);
				if ($readable_state != 1){
					$readArgs = array(
						'force' => false,
						'descrip' => htmlspecialchars_decode($content),
						'url' => $item_link,
						'authorship' => $author
					);
					$readReady = pressforward()->readability->get_readable_text($readArgs);
					#var_dump($readReady); die();
					$data['post_content'] = $readReady['readable'];
				}

				$new_post_id = wp_insert_post( $data, true );
##Check
				add_post_meta($id, 'nom_id', $id, true);
				pf_meta_transition_post($id, $new_post_id, true);
				$already_has_thumb = has_post_thumbnail($id);
				if ($already_has_thumb)  {
					$post_thumbnail_id = get_post_thumbnail_id( $id );
					set_post_thumbnail($new_post_id, $post_thumbnail_id);
				}

				$response = array(
					'what' => 'draft',
					'action' => 'simple_nom_to_draft',
					'id' => $new_post_id,
					'data' => $data['post_content'] . ' drafted.',
					'supplemental' => array(
						'content' => $content,
						'originID' => $id,
						'repeat' => $post_check,
						'buffered' => ob_get_contents()
					)
				);

			} else {
				$response = array(
					'what' => 'draft',
					'action' => 'simple_nom_to_draft',
					'id' => $id,
					'data' => 'Failed due to existing nomination or lack of ID.',
					'supplemental' => array(
						'repeat' => $post_check,
						'buffered' => ob_get_contents()
					)
				);
			}
			$xmlResponse = new WP_Ajax_Response($response);
			$xmlResponse->send();
			ob_end_flush();
			die();
		}
	}

	function build_nom_draft() {
		global $post;
		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		//if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		$pf_drafted_nonce = $_POST['pf_drafted_nonce'];
		if (! wp_verify_nonce($pf_drafted_nonce, 'drafter')){
			die(__('Nonce not recieved. Are you sure you should be drafting?', 'pf'));
		} else {
##Check
		# print_r(__('Sending to Draft.', 'pf'));
##Check
		//print_r($_POST);
		ob_start();

			$item_content = $_POST['nom_content'];
			$item_content = htmlspecialchars_decode($item_content);
			#$args_fi['url'] = $_POST['item_link'];
			#$posts = $pf->pf_feed_items->get($args_fi);

			$linked = get_option('pf_link_to_source', 0);
			if ($linked < 1){
				$item_content = $item_content . $this->get_the_source_statement( $_POST['nom_id']);
			}

			$item_title = $_POST['nom_title'];
			$url = pf_get_post_meta($_POST['nom_id'], 'source_title');
			$data = array(
				'post_status' => get_option(PF_SLUG.'_draft_post_status', 'draft'),
				'post_type' => get_option(PF_SLUG.'_draft_post_type', 'post'),
				'post_title' => $item_title,
				'post_content' => $item_content
			);
			//Will need to use a meta field to pass the content's md5 id around to check if it has already been posted.

			//We assume that it is already in nominations, so no need to check there. This might be why we can't use post_exists here.
			//No need to origonate the check at the time of the feed item either. It can't become a post with the proper meta if it wasn't a nomination first.
			$item_id = $_POST['item_id'];
			//YYYY-MM-DD
			$nom_date = strtotime($_POST['nom_date']);
			$nom_date = date('Y-m-d', $nom_date);

			//Now function will not update nomination count when it pushes nomination to publication.
			$post_check = $this->is_nominated($item_id, 'post', false);
			$newPostID = 'repeat';

#
			# Check if the item was rendered readable, if not, make it so.
			$readable_state = pf_get_post_meta($_POST['nom_id'], 'readable_status', true);
			if ($readable_state != 1){
				$readArgs = array(
					'force' => false,
					'descrip' => htmlspecialchars_decode($item_content),
					'url' => $_POST['item_link'],
					'authorship' => $_POST['item_author']

				);
				$readReady = pressforward()->readability->get_readable_text($readArgs);
				#var_dump($readReady); die();
				$data['post_content'] = $readReady['readable'];
			}
#

			//Alternative check with post_exists? or use same as above?
			if ($post_check != true) {
##Check
				#var_dump($data); die();
				//print_r('No Post exists.');
				$newPostID = wp_insert_post( $data, true );
##Check
				add_post_meta($_POST['nom_id'], 'nom_id', $_POST['nom_id'], true);
				pf_meta_transition_post($_POST['nom_id'], $newPostID, true);

				$already_has_thumb = has_post_thumbnail($_POST['nom_id']);
				if ($already_has_thumb)  {
					$post_thumbnail_id = get_post_thumbnail_id( $_POST['nom_id'] );
					set_post_thumbnail($newPostID, $post_thumbnail_id);
				}

			}
			$response = array(
				'what' => 'draft',
				'action' => 'build_nom_draft',
				'id' => $newPostID,
				'data' => $data['post_content'] . ' drafted.',
				'supplemental' => array(
					'content' => $item_content,
					'originID' => $item_id,
					'repeat' => $post_check,
					'buffered' => ob_get_contents()
				)
			);
			$xmlResponse = new WP_Ajax_Response($response);
			$xmlResponse->send();
			ob_end_flush();
			die();
		}
	}

	#Take a nomination post to draft.
	public function nominate_to_draft($post){

	}

	#Take an object to nominate.
	public function obj_to_nominate($post){

	}




}
