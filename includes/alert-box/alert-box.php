<?php
if (!class_exists('The_Alert_Box')){

  class The_Alert_Box {

    public static $status = 'alert_specimen';
		public static $option_name = 'alert_box_options';
    public static $alert_meta_key = 'ab_alert_msg';
		var $settings;
		var $alert_name;

    public static function init() {
      static $instance;

      if ( ! is_a( $instance, 'The_Alert_Box' ) ) {
        $instance = new self();
      }

      return $instance;
    }

        /**
         * Constructor
         */
    public function __construct() {
			#$this->status = self::$status;
			#$this->option_name = self::$option_name;
			$this->settings = get_option( self::option_name(), array() );
			add_action( 'init', array( $this, 'register_bug_status') );

			if (is_admin()){
					add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget') );
					add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
					add_action( 'wp_ajax_nopriv_remove_alerted_posts', array( $this, 'remove_alerted_posts') );
					add_action( 'wp_ajax_remove_alerted_posts', array( $this, 'remove_alerted_posts') );
					add_action( 'wp_ajax_dismiss_alerts_ajax', array( $this, 'dismiss_alerts_ajax') );
          add_action( 'save_post', array($this, 'remove_alert_on_edit') );
			}
			$this->alert_name = $this->alert_name_maker();
			#add_action( 'admin_init', array($this, 'settings_field_settings_page') );
    }

		public function status(){
			return self::$status;
		}

		public function option_name(){
			return self::$option_name;
		}

    public function alert_meta_key(){
      return self::$alert_meta_key;
    }

        public function register_bug_status(){
          $default_args = array(
              'label'                 =>     __('Alert', 'pf'),
              'public'                =>      false,
              'exclude_from_search'   =>      true,
              'show_in_admin_all_list'=>      true,
              'label_count'           =>      _n_noop(
              					'Alert <span class="count">(%s)</span>',
              					'Alerts <span class="count">(%s)</span>',
              					'pf'

              				)
          );
          $args = apply_filters( 'ab_bug_status_args', $default_args );
          register_post_status(self::$status, $args );
        }

	public static function alert_count() {
		$q = new WP_Query( array(
			'post_type' => get_post_types( '', 'names' ),
			'post_status' => self::$status,
			'fields' => 'ids',
			'posts_per_page' => '-1',
		) );
		return $q->post_count;
	}

	private static function depreciated_alert_name_filters($alert_names){
      $alert_names['dismiss_all'] =  apply_filters('ab_alert_specimens_dismiss_all_text', $alert_names['dismiss_all']);
      $alert_names['delete_all_check'] =  apply_filters('ab_alert_specimens_check_message', $alert_names['delete_all_check']);
      $alert_names['dismiss_all_check'] =  apply_filters('ab_alert_specimens_check_dismiss_message', $alert_names['dismiss_all_check']);
      $alert_names['delete_all'] = apply_filters('ab_alert_specimens_delete_all_text', $alert_names['delete_all']);
      return $alert_names;
    }

    private static function alert_name_maker($label = false){
			$alert_names = array(
				'name'                => _x( 'Alerts', 'post type general name', 'pf' ),
				'singular_name'       => _x( 'Alert', 'post type singular name', 'pf' ),
				'menu_name'           => _x( 'Alerts', 'admin menu', 'pf' ),
				'name_admin_bar'      => _x( 'Alert', 'add new on admin bar', 'pf' ),
				'add_new'             => _x( 'Add Alert', 'alert', 'pf' ),
				'add_new_item'        => __( 'Add New Alert', 'pf' ),
				'new_item'            => __( 'New Alert', 'pf' ),
				'edit_item'           => __( 'Edit Alert', 'pf' ),
				'view_item'           => __( 'View Alert', 'pf' ),
				'all_items'           => __( 'All Alerts', 'pf' ),
				'search_items'        => __( 'Search Alerts', 'pf' ),
				'parent_item_colon'   => __( 'Parent Alerts:', 'pf' ),
				'not_found'           => __( 'No alerts found.', 'pf' ),
				'not_found_in_trash'  => __( 'No alerts found in Trash.', 'pf' ),
        'dismiss_one_check'   => __( 'Are you sure you want to dismiss the alert on', 'pf' ),
        'dismiss_all_check'   => __( 'Are you sure you want to dismiss all alerts?', 'pf' ),
        'dismiss_all'         => __( 'Dismiss all alerts', 'pf' ),
        'delete_all_check'    => __( 'Are you sure you want to delete all posts with alerts?', 'pf' ),
        'delete_all'          => __( 'Delete all posts with alerts', 'pf' ),
        'dismissed'           => __( 'Draft' ),
        'all_well'            => __( 'No problems!', 'pf' ),
        'turn_on'             => __( 'Turn alerts on.', 'pf' ),
        'activate_q'          => __( 'Active Alert Boxes?', 'pf' ),
        'turned_off'		      => __( 'Alert boxes not active.', 'pf')
			);
      $alert_names =  self::depreciated_alert_name_filters($alert_names);
			$alert_names = apply_filters('ab_alert_specimens_labels', $alert_names);
      if (!$label || !array_key_exists($label, $alert_names)) {
        return $alert_names;
      } else {
        return $alert_names[$label];
      }
		}

    public static function alert_label($label = 'name', $nocaps = false){
      $labels = self::alert_name_maker();
      if (empty($labels[$label])){
        return $labels['name'];
      }
      if ($nocaps) {
        return strtolower($labels[$label]);
      }
      return $labels[$label];
    }

        public function add_bug_type_to_post($id, $string){
            $metas = get_post_meta($id, self::alert_meta_key(), false);
            if (!in_array($string, $metas)){
                $result = add_post_meta($id, self::alert_meta_key(), $string, false);
                return $result;
            }
            else {
                return false;
            }

        }

        public function get_bug_type($id){
            $result = get_post_meta($id, self::alert_meta_key(), false);
            $s_result = implode(', ', $result);
            return $s_result;
        }

        public function switch_post_type($id){
            $argup = array(
                'ID'			=> $id,
                'post_status'	=>	$this->status(),
            );
            update_post_meta( $id, 'pre_alert_status', get_post_status($id) );
            $result = wp_update_post($argup);
            return $result;

        }


        public function get_specimens($page = 0, $post_types = false){
            if (0 != $page){
                $ppp = 100;
            } else {
                $ppp = -1;
            }
            if (!$post_types){
                $post_types = get_post_types('', 'names');
            }
            $post_types = apply_filters('ab_alert_specimens_post_types', $post_types);
            $args = array(
                'post_type' =>  $post_types,
                'post_status' => self::$status,
                'posts_per_page'=>$ppp
            );
            if (0 != $page){
                $args['paged'] = $page;
            } else {
                $args['nopaging']  = 'true';
            }

            $q = new WP_Query( $args );
            return $q;
        }

      public function add_dashboard_widget(){
           if(self::is_on()){
				wp_add_dashboard_widget(
					'specimen_alert_box',
					__('Alerts', 'pf'),
					array($this, 'alert_box_insides_function')
				);
		   }
      }

        public function remove_alert_on_edit($post_id){
            $status = $this->status();
            //var_dump(get_post_status( $post_id )); die();
            if ( ( '' != get_post_meta($post_id, self::alert_meta_key(), true) ) && ( 'publish' == get_post_status( $post_id ) ) ){
              self::dismiss_alert($post_id);
            } else {
                return $post_id;
            }


        }

		public function dismiss_all_alerts($page =  0, $post_types = false){
			if (current_user_can('edit_posts')){
				$q = $this->get_specimens($page, $post_types);
				if ( $q->have_posts() ) {
					while ( $q->have_posts() ) : $q->the_post();
						self::dismiss_alert( get_the_ID() );
					endwhile;
				} else {
					return false;
				}
				wp_reset_postdata();
				return $q->post_count;
			} else {
				return false;
			}
		}

		public function dismiss_alert($post_id){
			// unhook this function so it doesn't loop infinitely
            remove_action( 'save_post', array($this, 'remove_alert_on_edit') );

            $post_status_d = get_post_meta( $post_id, 'pre_alert_status', true);
            if (empty($post_status_d)){
				        $post_status_d = array();
                $post_status_d['status'] = 'publish';
                $post_status_d['id'] = $post_id;
            } elseif ( !is_array($post_status_d) ) {
                $a = array();
                $a['id'] = $post_id;
                $a['status'] = $post_status_d;
                $post_status_d = $a;
            }
            $post_status = apply_filters('ab_alert_specimens_update_post_type', $post_status_d);
            $current_post_status = get_post_status($post_id);
            if ( ('publish' != $current_post_status ) && ( $post_status != $current_post_status ) ){
              $id = wp_update_post( array( 'ID' => $post_id, 'post_status' => $post_status['status'] ) );
            }
            //var_dump($post_status);
			      // update the post, which calls save_post again
            //var_dump(self::alert_meta_key().' b'); die(); 
            update_post_meta($post_id, self::alert_meta_key(), '');

            // re-hook this function
            add_action( 'save_post', array($this, 'remove_alert_on_edit') );

			return $id;
		}

		public function dismiss_alerts_ajax(){
            ob_start();
            $filtered_post_types = sanitize_text_field($_POST['filtered_post_types']);
            if (!current_user_can('edit_posts')){
                $response = array(
                   'what'=>'the_alert_box',
                   'action'=>'remove_alerted_posts',
                   'id'=>0,
                   'data'=>__('You do not have permission to dismiss alerts.', 'pf')
                );
                $xmlResponse = new WP_Ajax_Response($response);
                $xmlResponse->send();
                ob_end_flush();
                die();
            }
            if (empty($filtered_post_types)){
                $fpt_array = false;
            } else {
                $fpt_array = explode(',', $filtered_post_types);
            }
			if (!empty($_POST['all_alerts'])){
				$alerts = the_alert_box()->dismiss_all_alerts(0, $fpt_array);
			} else {
				$alert = intval( $_POST['alert'] );
				$alerts = the_alert_box()->dismiss_alert($alert);
			}
            if (!isset($alerts) || !$alerts || (0 == $alerts)){
                $response = array(
                   'what'=>'the_alert_box',
                   'action'=>'dismiss_alerts_ajax',
                   'id'=>0,
                   'data'=>__('No alerted posts to dismiss.', 'pf')
                );
            } elseif ( empty($_POST['all_alerts'] ) ) {
              $response = array(
                 'what'=>'the_alert_box',
                 'action'=>'dismiss_alerts_ajax',
                 'id'=>$pages,
                 'data'=> sprintf(__('Alert on post ID %s dismissed.', 'pf'), $alert),
                  'supplemental' => array(
                      'buffered' => ob_get_contents()
                    )
              );
            } else {
                $response = array(
                   'what'=>'the_alert_box',
                   'action'=>'dismiss_alerts_ajax',
                   'id'=>$pages,
                   'data'=> __('Alerts dismissed.', 'pf'),
                    'supplemental' => array(
                        'buffered' => ob_get_contents()
				              )
                );
            }
            $xmlResponse = new WP_Ajax_Response($response);
            $xmlResponse->send();
            ob_end_flush();
			      die();
        }

        public function remove_alerted_posts(){
            ob_start();
            # @todo Nonce this function
            $filtered_post_types = $_POST['filtered_post_types'];
            if (!current_user_can('delete_others_posts')){
                $response = array(
                   'what'=>'the_alert_box',
                   'action'=>'remove_alerted_posts',
                   'id'=>0,
                   'data'=>__('You do not have permission to delete these posts.', 'pf')
                );
                $xmlResponse = new WP_Ajax_Response($response);
                $xmlResponse->send();
                ob_end_flush();
                die();
            }
            if (empty($filtered_post_types)){
                $fpt_array = false;
            } else {
                $fpt_array = explode(',', $filtered_post_types);
            }
            $alerts = the_alert_box()->get_specimens(0, $fpt_array);
            if (0 < $alerts->post_count){
                $count = 0;
                foreach ($alerts->query['post_type'] as $pt){
                    $counter = wp_count_posts($pt);
                    $atype = the_alert_box()->status;
                    $count += $counter->$atype;
                }
                $pages = $count/100;
                $pages = ceil($pages);
                $c = $pages;

                while (0 < $c){
                    $q = the_alert_box()->get_specimens($c, $fpt_array);
                    while ( $q->have_posts() ) : $q->the_post();
                        wp_delete_post(get_the_ID());

                    endwhile;
                    wp_reset_postdata();
                    $c--;
                }
            } else {
                $alerts = false;
            }
            if (!isset($alerts) || !$alerts || (0 == $alerts)){
                $response = array(
                   'what'=>'the_alert_box',
                   'action'=>'remove_alerted_posts',
                   'id'=>0,
                   'data'=>__('No alerted posts to delete.', 'pf')
                );
            } else {
                $response = array(
                   'what'=>'the_alert_box',
                   'action'=>'remove_alerted_posts',
                   'id'=>$pages,
                   'data'=> $alerts->post_count . ' ' . __('posts deleted.', 'pf'),
                    'supplemental' => array(
                        'buffered' => ob_get_contents()
				    )
                );
            }
            $xmlResponse = new WP_Ajax_Response($response);
            $xmlResponse->send();
            ob_end_flush();
			die();
        }

        public function the_alert(){

          if ( !current_user_can( 'edit_post', get_the_ID() ) ){
            return;
          }

          edit_post_link(get_the_title(), '<span style="color:red;font-weight:bold;">'. self::alert_label('singular_name') . '</span> for ', ': '.$this->get_bug_type(get_the_ID()));
          echo ' ';
          edit_post_link(__('Edit', 'pf'));
          echo ' ';
          if (current_user_can('edit_others_posts')){
            echo '| <a href="#" class="alert-dismisser" title="'. __('Dismiss', 'pf') . '" data-alert-post-id="'. get_the_ID() .'" data-alert-dismiss-check="'. self::alert_label('dismiss_one_check') . ' ' . get_the_title(). '?' .'" '.self::alert_box_type_data( get_post_type( get_the_ID() ) ).' >' . __('Dismiss', 'pf').'</a>';
          }
          echo ' ';
          if (current_user_can('delete_others_posts')){
            echo '| <a href="'.get_delete_post_link( get_the_ID() ).'" title="'. __('Delete', 'pf') .'" '.self::alert_box_type_data( get_post_type( get_the_ID() ) ).' >'. __('Delete', 'pf') .'</a>';
          }

        }

		public function alert_box_type_data($v){
			if ( is_string($v) ){
				return 'alert-types="'.$v.'"';
			} elseif ( is_bool($v) ) {
				return '';
			} else {
				return 'alert-types="'.implode(',',$v->query['post_type']).'"';
			}
		}

    public function alert_box_insides_function(){
            if(self::is_on()){
				$q = $this->get_specimens();
				if ( $q->have_posts() ) {
					while ( $q->have_posts() ) : $q->the_post();
						echo '<p>';
              the_alert_box()->the_alert();
            echo '</p>';
					endwhile;
					wp_reset_postdata();
					$alertCheck = self::alert_label('delete_all_check');
          if (current_user_can('edit_others_posts')){
              $editText = self::alert_label('dismiss_all');
              $editCheck = self::alert_label('dismiss_all_check');

                echo '<p><a href="#" id="dismiss_all_alert_specimens" style="color:GoldenRod;font-weight:bold;" title="' . $editText . '" data-dismiss-all-check="' . $editCheck . '" '.self::alert_box_type_data($q).' >' . $editText . '</a></p>';
          }
				if (current_user_can('delete_others_posts')){
    					$deleteText = self::alert_label('delete_all');
    					echo '<p><a href="#" id="delete_all_alert_specimens" style="color:red;font-weight:bold;" title="' . __('Delete all posts with alerts', 'pf') . '" alert-check="' . $alertCheck . '" '.self::alert_box_type_data($q).' >' . $deleteText . '</a></p>';
				}
				} else {
					$return_string = self::alert_label('all_well');
					echo $return_string;

				}
			} else {
				echo self::alert_label('turned_off');
			}
        }

        public function alert_box_outsides(){
            $this->alert_box_insides_function();
        }

        public function admin_enqueue_scripts(){
			if(self::is_on()){
				$dir = plugins_url('/', __FILE__);
				wp_register_script('alert-box-handler', $dir . 'assets/js/alert-handler.js', array( 'jquery' ));
				if (is_admin()){
					wp_enqueue_script('alert-box-handler');
				}
			}
        }

		public static function find_a_setting($args, $default = array(), $settings){
			if (!empty($settings) && !isset($_POST)){
				$settings = $settings;
			} else {
				$settings = get_option( self::$option_name, array() );
			}
		    if (empty($settings)) {
				$r = '';
			} elseif (empty($settings[$args['parent_element']]) || empty($settings[$args['parent_element']][$args['element']])){
				$r = '';
			} elseif (!empty($args['parent_element']) && !empty($args['element'])){
				$r = $settings[$args['parent_element']][$args['element']];
			} elseif (!empty($args['parent_element'])) {
				$r = $settings[$args['parent_element']];
			} else {
			  $r = '';
			}

			if (empty($r) && !empty($settings)){
				$r = 'false';
			}

			if (empty($r)){
				#$default = array($args['parent_element'] => array($args['element'] => ''));
				return $default;
			} else {
				return $r;
			}
		}

		public function setting($args, $default = array()){

			$settings = $this->settings;
			$r = self::find_a_setting($args, $default, $settings);
			return $r;

		}

		public function settings_field_maker($args){
		  $parent_element = $args['parent_element'];
		  $element = $args['element'];
		  $type = $args['type'];
		  $label = $args['label_for'];
		  $default = $args['default'];
		  switch ($type) {
			  case 'checkbox':
				$check = self::setting($args, $default);
				if ('true' == $check){
					$mark = 'checked';
				} else {
					$mark = '';
				}
				echo '<input id="'.$element.'" type="checkbox" name="'.$this->option_name.'['.$parent_element.']['.$element.']" value="true" '.$mark.' class="'.$args['parent_element'].' '.$args['element'].'" />  <label for="'.$this->option_name.'['.$parent_element.']['.$element.']" class="'.$args['parent_element'].' '.$args['element'].'" >' . $label . '</label>';
				break;
			  case 'text':
				echo "<input type='text' id='".$element."' name='".$this->option_name."[".$parent_element."][".$element."]' value='".esc_attr(self::setting($args, $default))."' class='".$args['parent_element']." ".$args['element']."' /> <label for='".$this->option_name."[".$parent_element."][".$element."]' class='".$args['parent_element']." ".$args['element']."' >" . $label . "</label>";
				break;
			}
		}

		public static function settings_fields(){
			$switch = array(
              'parent_element'   =>  'alert_check',
              'element'          =>  'alert_switch',
              'type'             =>  'checkbox',
              'label_for'        =>  self::alert_label('turn_on'),
              'default'          =>  'true'
			);
			return array('switch' => $switch);
		}

		public function settings_field_settings_page(){
			#var_dump('die');die();
			register_setting( 'general', self::$option_name, array($this, 'validator')  );
			$args = the_alert_box()->settings_fields();
			add_settings_field(	'alert_box_check', self::alert_label('activate_q'), array($this, 'settings_field_maker'), 'general', 'default', $args['switch']);
		}

		public function validator($input){
			#$output = get_option( $this->option_name );

			#update_option($this->option_name, $_POST['alert_box_options']);
			#var_dump($_POST['alert_box_options']); die();
			return $input;
		}

		public function is_on(){

			$alert_settings = self::settings_fields();
			$alert_switch = $alert_settings['switch'];
			#var_dump('<pre>');
			#var_dump($alert_switch);
			$check = self::find_a_setting($alert_switch, $alert_switch['default'], get_option( self::$option_name, array() ));#die();
			#var_dump($check);
			if ('true' == $check){
				$state = true;
			} else {
				$state = false;
			}
			return $state;
		}

  }

  function the_alert_box() {
	  return The_Alert_Box::init();
  }

  // Start me up!
  the_alert_box();

}
