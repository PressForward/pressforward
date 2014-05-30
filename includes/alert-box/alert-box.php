<?php 
if (!class_exists('The_Alert_Box')){

    class The_Alert_Box {
        
        var $status;
        
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
            $this->status = 'alert_specimen';
            add_action( 'init', array( $this, 'register_bug_status') );
            add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget') );
            if (is_admin()){
			    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) ); 
                add_action( 'wp_ajax_nopriv_remove_alerted_posts', array( $this, 'remove_alerted_posts') );
			    add_action( 'wp_ajax_remove_alerted_posts', array( $this, 'remove_alerted_posts') );
            }
        }

        public function register_bug_status(){
            register_post_status($this->status, array(
                'label'                 =>     __('Alert', 'pf'),
                'public'                =>      false,
                'exclude_from_search'   =>      true,
                'show_in_admin_all_list'=>      true,
                'label_count'           =>      _n_noop( 
                					'Alert <span class="count">(%s)</span>', 
                					'Alerts <span class="count">(%s)</span>',
                					'pf'
                					
                				)
            ) );
        }

        public function add_bug_type_to_post($id, $string){
            $metas = get_post_meta($id, 'ab_alert_msg', false);
            if (!in_array($string, $metas)){
                $result = add_post_meta($id, 'ab_alert_msg', $string, false);
                return $result;
            }
            else {
                return false;
            }
            
        }
        
        public function get_bug_type($id){
            $result = get_post_meta($id, 'ab_alert_msg', false);
            $s_result = implode(', ', $result);
            return $s_result;
        }

        public function switch_post_type($id){
            $argup = array(
                'ID'			=> $id,
                'post_status'	=>	$this->status,
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
                'post_status' => $this->status,
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
            
            wp_add_dashboard_widget(
                'specimen_alert_box',
                __('Alerts', 'pf'),
                array($this, 'alert_box_insides_function')
            );
        }
        
        public function remove_alert_on_edit($post_id){
            $status = $this->status;
            if ( $status != $_POST['post_status'] ) {
                return;    
            }
            
            // unhook this function so it doesn't loop infinitely
            remove_action( 'save_post', array($this, 'remove_alert_on_edit') );
    
            $post_status_d = get_post_meta( $post_id, 'pre_alert_status', true);
            if (empty($post_type_d)){
                $post_status_d['status'] = 'draft';
                $post_status_d['type'] = $_POST['post_type'];
            }
            $post_status = apply_filters('ab_alert_specimens_update_post_type', $post_status_d);
            // update the post, which calls save_post again
            wp_update_post( array( 'ID' => $post_id, 'post_status' => $post_status['status'] ) );
    
            // re-hook this function
            add_action( 'save_post', array($this, 'remove_alert_on_edit') );
            
        }
        
        public function remove_alerted_posts(){
            ob_start();
            $filtered_post_types = $_POST['filtered_post_types'];
            
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
            if (!isset($alerts) || !$alerts){
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
                   'data'=> $alerts->post_count . __(' posts deleted.', 'pf'),
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
        
        public function alert_box_insides_function(){
            
            $q = $this->get_specimens();
            if ( $q->have_posts() ) :
                while ( $q->have_posts() ) : $q->the_post(); 
                    echo '<p>';
                    edit_post_link(get_the_title(), '<span style="color:red;font-weight:bold;">'. __('Alert', 'pf') . '</span> for ', ': '.$this->get_bug_type(get_the_ID()));
                    echo ' ';
                    edit_post_link(__('Edit', 'pf'));
                    echo ' ';
                    echo '| <a href="'.get_delete_post_link( get_the_ID() ).'" title="'. __('Delete', 'pf') .'" >'. __('Delete', 'pf') .'</a>';
                    echo '</p>';
                endwhile;
                wp_reset_postdata();
                $alertCheck = __('Are you sure you want to delete all posts with alerts?', 'pf');
                $alertCheck = apply_filters('ab_alert_specimens_check_message', $alertCheck);
            
                $deleteText = __('Delete all posts with alerts', 'pf');
                $deleteText = apply_filters('ab_alert_specimens_delete_all_text', $deleteText);
            
                echo '<p><a href="#" id="delete_all_alert_specimens" style="color:red;font-weight:bold;" title="' . __('Delete all posts with alerts', 'pf') . '" alert-check="' . $alertCheck . '" alert-types="'.implode(',',$q->query['post_type']).'" >' . $deleteText . '</a></p>';
            else:
                $return_string = __('No problems!', 'pf');
                $return_string = apply_filters('ab_alert_safe', $return_string);
                echo $return_string;
            
            endif;
        }
        
        public function alert_box_outsides(){
            $this->alert_box_insides_function();    
        }
        
        public function admin_enqueue_scripts(){
            $dir = plugins_url('/', __FILE__);
            wp_register_script('alert-box-handler', $dir . 'assets/js/alert-handler.js', array( 'jquery' ));
            if (is_admin()){
                wp_enqueue_script('alert-box-handler');
            }
        }
            
    }
    
    function the_alert_box() {
	   return The_Alert_Box::init();
    }

    // Start me up!
    the_alert_box();

}
