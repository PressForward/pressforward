<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Controllers\PF_Readability;
use PressForward\Core\Utility\Forward_Tools;
use PressForward\Libraries\HTMLChecker;

use WP_Ajax_Response;
use WP_Error;
// use \WP_REST_Controller;
class ReadabilityEndpoint implements HasActions {

	protected $api_base;

	function __construct( $api_base, PF_Readability $readability, Forward_Tools $forward_tools, HTMLChecker $html_checker ) {
		$this->api_base             = $api_base;
		$this->api_base['endpoint'] = 'readability';
		$this->readability          = $readability;
		$this->forward_tools        = $forward_tools;
		$this->html_checker         = $html_checker;
	}

	public function action_hooks() {
		// add_action( 'rest_api_init', 'activate_pf_rest_controller', 11 );
		$actions = array(
			array(
				'hook'     => 'rest_api_init',
				'method'   => 'activate',
				'priority' => 11,
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
		$base      = $this->api_base['endpoint'];
		register_rest_route(
			$namespace, '/' . $base, array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'make_html_readable' ),
					'args'                => array(
						'html_doc'   => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'The html_doc parameter takes a block of HTML and parses it through readability to create a clean article.', 'pf' ),
							// type specifies the type of data that the argument should be.
							'type'              => 'string',
							// Set the argument to be required for the endpoint.
							'required'          => true,
							'validate_callback' => array( $this, 'is_valid_html' ),
							'sanitize_callback' => array( $this, 'is_sane_html' ),
						),
						'source_url' => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'The source_url parameter takes a URL for keeping note of in readability.', 'pf' ),
							// type specifies the type of data that the argument should be.
							'type'              => 'string',
							// Set the argument to be required for the endpoint.
							'required'          => true,
							'validate_callback' => array( $this, 'is_valid_url' ),
							'sanitize_callback' => array( $this, 'is_sane_url' ),
						),
					),
					'permission_callback' => function () {
						return false; // current_user_can( 'edit_others_posts' );
					},
					'priority'            => 10,
				),
			)
		);

		register_rest_route(
			$namespace, '/' . $base . '/url', array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_readable_from_url' ),
					'args'                => array(
						'source_url' => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'The source_url parameter takes a URL for keeping note of in readability.', 'pf' ),
							// type specifies the type of data that the argument should be.
							'type'              => 'string',
							// Set the argument to be required for the endpoint.
							'required'          => true,
							'validate_callback' => array( $this, 'is_valid_url' ),
							'sanitize_callback' => array( $this, 'is_sane_url' ),
						),
					),
					'permission_callback' => function () {
						return false; // current_user_can( 'edit_others_posts' );
					},
					'priority'            => 10,
				),
			)
		);

	}
	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the 'filter' argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter. In this case it is 'html_doc'.
	 * @return WP_Error|boolean
	 */
	public function is_valid_html( $value, $request, $param ) {
		// If the argument is not a string return an error.
		if ( ! is_string( $value ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The html_doc argument must be a string.', 'pf' ), array( 'status' => 400 ) );
		} else {
			return true;
		}
	}
	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the 'filter' argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter. In this case it is 'html_doc'.
	 * @return string
	 */
	public function is_sane_html( $value, $request, $param ) {
		return $value;
		// Readability will take care of sanitization.
		// return wp_kses_post($value);
	}

	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the 'filter' argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter. In this case it is 'source_url'.
	 * @return WP_Error|boolean
	 */
	public function is_valid_url( $value, $request, $param ) {
		// If the argument is not a string return an error.
		if ( false == parse_url( $value ) || ( filter_var( $value, FILTER_VALIDATE_URL ) === false ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The source_url argument must be a well formed URL.', 'pf' ), array( 'status' => 400 ) );
		} else {
			return true;
		}
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the 'filter' argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter. In this case it is 'source_url'.
	 * @return WP_Error|string
	 */
	public function is_sane_url( $value, $request, $param ) {
		return filter_var( $value, FILTER_SANITIZE_URL );
	}

	/**
	 * This is our callback function that embeds our resource in a WP_REST_Response.
	 *
	 * The parameter is already sanitized by this point so we can use it without any worries.
	 */
	public function make_html_readable( $request ) {
		if ( isset( $request['html_doc'] ) && isset( $request['source_url'] ) ) {
			// \rest_ensure_response(
			return rest_ensure_response(
				htmlspecialchars( $this->readability->process_readability( $request['html_doc'], $request['source_url'] ) )
			);
			// unencode via js with the html_entity_decode function we use elsewhere.
		}
		return new \WP_Error( 'rest_invalid', esc_html__( 'The html_doc and source_url parameters are required.', 'pf' ), array( 'status' => 400 ) );
	}

	public function get_readable_from_url( $request ) {
		return rest_ensure_response(
			htmlspecialchars( $this->readability->readability_object( $request['source_url'] ) )
		);
	}


}
