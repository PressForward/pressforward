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

	function __construct( $api_base, Metas $metas, Stats $stats, HTMLChecker $html_checker ) {
		$this->api_base = $api_base;
		$this->api_base['endpoint'] = 'stats';
		$this->metas = $metas;
		$this->stats = $stats;
		$this->html_checker = $html_checker;
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
					'description' => esc_html__( 'Pages worth of authors.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => true,
					'default'      => 1,
			        'validate_callback' => 'is_numeric',
			      ),
			    ),
				'permission_callback' => function () {
			      return true; //current_user_can( 'edit_others_posts' );
			  	},
				'priority'  => 10,
			),
		));

		register_rest_route($namespace, '/' . $base.'/url', array(
			array(
				'methods'         => \WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'get_readable_from_url' ),
				'args' => array(
				  'source_url' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'The source_url parameter takes a URL for keeping note of in readability.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => true,
					'validate_callback' => array( $this, 'is_valid_url' ),
					'sanitize_callback' => array( $this, 'is_sane_url' ),
				  ),
			    ),
				'permission_callback' => function () {
			      return false; //current_user_can( 'edit_others_posts' );
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
				'no_found_rows' => true,
				'fields'	=>	'ids'
			);
			$q = $this->stats->stats_query_for_pf_published_posts( $args );
			$ids = $q->posts;
			foreach ($ids as $id) {
				$author = pf_get_post_meta( $id, 'item_author' );
			}
			return rest_ensure_response(
				htmlspecialchars($this->readability->process_readability($request['html_doc'], $request['source_url']))
			);
			// unencode via js with the html_entity_decode function we use elsewhere.
		}
		return new \WP_Error( 'rest_invalid', esc_html__( 'The html_doc and source_url parameters are required.', 'pf' ), array( 'status' => 400 ) );
	}


}
