<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Controllers\Metas;
use PressForward\Controllers\Stats;
use PressForward\Core\Utility\Forward_Tools;
use PressForward\Libraries\HTMLChecker;

use WP_Ajax_Response;
use WP_Error;
//use \WP_REST_Controller;

class StatsEndpoint implements HasActions {

	protected $api_base;

	function __construct( $api_base, Metas $metas, Stats $stats ) {
		$this->api_base = $api_base;
		$this->api_base['endpoint'] = 'stats';
		$this->metas = $metas;
		$this->stats = $stats;
	}

	public function action_hooks() {
		//add_action( 'rest_api_init', 'activate_pf_rest_controller', 11 );
		$actions = array(
			array(
				'hook' => 'rest_api_init',
				'method' => 'activate',
				'priority'	=> 11
			),
		);
		return $actions;
	}

	function activate() {
		$controller = $this;
		$controller->register_routes();
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$namespace = $this->api_base['base_namespace'] . $this->api_base['version'];
		$base = $this->api_base['endpoint'];
		register_rest_route( $namespace, '/' . $base . '/authors', array(
			array(
				'methods'         => \WP_REST_Server::READABLE,
				'callback'        => array( $this, 'author_stats' ),
				'args' => array(
			      'page' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Page of authors.', 'pf' ),
					// Set the argument to be required for the endpoint.
					'required'    => true,
					'default'      => 1,
			        'validate_callback' => function($page, $request_object){
							if ( is_numeric($page) ){
								return true;
							} else {
								return false;
							}
						},
			      ),
				  'year' 	=>	array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Limit query by year, use XXXX year notation.', 'pf' ),
					// Set the argument to be required for the endpoint.
					'required'    => true,
					'default'      => 1,
					'validate_callback' => function($page, $request_object){
							if ( is_numeric($page) ){
								return true;
							} else {
								return false;
							}
						},
				  ),
				  'month'	=>	array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Limit query by month, use number of month.', 'pf' ),
					// Set the argument to be required for the endpoint.
					'required'    => true,
					'default'      => 1,
					'validate_callback' => function($page, $request_object){
							if ( is_numeric($page) ){
								return true;
							} else {
								return false;
							}
						},
				  ),
			    ),
				'permission_callback' => function () {
			      return true; //current_user_can( 'edit_others_posts' );
			  	},
				'priority'  => 10,
			),
		));
		register_rest_route( $namespace, '/' . $base . '/pf_posted', array(
			array(
				'methods'         => \WP_REST_Server::READABLE,
				'callback'        => array( $this, 'pf_posted' ),
				'args' => array(
			      'page' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Page of posts created by PressForward.', 'pf' ),
					// Set the argument to be required for the endpoint.
					'required'    => true,
					'default'      => 1,
			        'validate_callback' => function($page, $request_object){
							if ( is_numeric($page) ){
								return true;
							} else {
								return false;
							}
						},
			      ),
			    ),
				'permission_callback' => function () {
			      return true; //current_user_can( 'edit_others_posts' );
			  	},
				'priority'  => 10,
			),
		));
	}


	/**
	 * This is our callback function that embeds our resource in a WP_REST_Response.
	 *
	 * The parameter is already sanitized by this point so we can use it without any worries.
	 */
	public function author_stats($request){
		if ( isset($request['page']) ){
			//\rest_ensure_response(
			$args = array(
				'paged'			 =>	$request['page'],
				//'no_found_rows' => true,
				'fields'	=>	'ids'
			);
			$q = $this->stats->stats_query_for_pf_published_posts( $args );
			//var_dump($q);
			$ids = $q->posts;
			$authors = array();
			foreach ($ids as $id) {
				$authors = $this->stats->set_author_into_leaderboard( $id, $authors );
			}

			return rest_ensure_response(
				$authors
			);
			// unencode via js with the html_entity_decode function we use elsewhere.
		}
		return new \WP_Error( 'rest_invalid', esc_html__( 'The page parameter is required.', 'pf' ), array( 'status' => 400 ) );
	}

	public function pf_posted( $request ){
		if ( isset($request['page']) ){
			//\rest_ensure_response(
			$args = array(
				'paged'			 =>	$request['page']
			);
			$q = $this->stats->stats_query_for_pf_published_posts( $args );

			$posts = $q->posts;
			foreach ( $posts as $post ){
				$post_content = $post->post_content;
				$post_content_cleaner = strip_tags( stripslashes( html_entity_decode( htmlspecialchars_decode( htmlspecialchars( $post_content ) ) ) ) );
				$post_content_cleaner = preg_replace('/[^A-Za-z0-9\-]/', ' ', $post_content_cleaner);
				$post_content_cleaner = str_replace(array("\n","\r", "\r\n"), ' ', $post_content_cleaner);
				$post->stripped_post_content = strip_tags($post_content);
				$post->wordcount = str_word_count( $post->stripped_post_content );
				$post->source = $this->metas->get_post_pf_meta( $post->ID, 'pf_source_link' );
			}

			return rest_ensure_response(
				$posts
			);
			// unencode via js with the html_entity_decode function we use elsewhere.
		}
		return new \WP_Error( 'rest_invalid', esc_html__( 'The page parameter is required.', 'pf' ), array( 'status' => 400 ) );
	}


}
