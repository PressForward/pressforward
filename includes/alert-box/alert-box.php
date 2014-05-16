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

        
        public function get_specimens(){
            
            $post_types = get_post_types('', 'names');
            $post_types = apply_filters('ab_alert_specimens_post_types', $post_types);
            $args = array(
                'post_type' =>  $post_types,  
                'post_status' => $this->status,
                'posts_per_page'=>-1,
                'nopaging'  => 'true'                
            );
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
        
        public function alert_box_insides_function(){
            
            $q = $this->get_specimens();
            if ( $q->have_posts() ) :
                while ( $q->have_posts() ) : $q->the_post(); 
                    echo '<p>';
                    edit_post_link(get_the_title(), '<span style="color:red;font-weight:bold;">'. __('Alert', 'pf') . '</span> for ', ': '.$this->get_bug_type(get_the_ID()));
                    echo ' ';
                    edit_post_link(__('Edit', 'pf'));
                    echo ' ';
                    echo '| <a href="'.get_delete_post_link( get_the_ID() ).'" title="Delete" >Delete</a>';
                    echo '</p>';
                endwhile;
                wp_reset_postdata();
            else:
                $return_string = __('No problems!', 'pf');
                $return_string = apply_filters('ab_alert_safe', $return_string);
                echo $return_string;
            
            endif;
        }
        
        public function alert_box_outsides(){
            $this->alert_box_insides_function();    
        }
    }
    
    function the_alert_box() {
	   return The_Alert_Box::init();
    }

    // Start me up!
    the_alert_box();

}
