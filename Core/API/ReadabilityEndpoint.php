<?php
/**
 * Readability endpoint.
 *
 * @package PressForward
 */

namespace PressForward\Core\API;

use PressForward\Controllers\PF_Readability;
use PressForward\Core\Utility\Forward_Tools;
use PressForward\Libraries\HTMLChecker;

use WP_Error;

/**
 * Readability endpoint.
 */
class ReadabilityEndpoint implements \Intraxia\Jaxion\Contract\Core\HasActions {

	/**
	 * API base data.
	 *
	 * @access protected
	 * @var array
	 */
	protected $api_base;

	/**
	 * PFReadability object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PF_Readability
	 */
	public $readability;

	/**
	 * HTMLChecker object.
	 *
	 * @access public
	 * @var \PressForward\Core\Utility\Forward_Tools
	 */
	public $forward_tools;

	/**
	 * HTMLChecker object.
	 *
	 * @access public
	 * @var \PressForward\Libraries\HTMLChecker
	 */
	public $html_checker;

	/**
	 * Constructor.
	 *
	 * @param array                                    $api_base      API base data.
	 * @param \PressForward\Controllers\PF_Readability $readability   PF_Readability object.
	 * @param \PressForward\Core\Utility\Forward_Tools $forward_tools Forward_Tools object.
	 * @param \PressForward\Libraries\HTMLChecker      $html_checker  HTMLChecker object.
	 */
	public function __construct( $api_base, PF_Readability $readability, Forward_Tools $forward_tools, HTMLChecker $html_checker ) {
		$this->api_base             = $api_base;
		$this->api_base['endpoint'] = 'readability';
		$this->readability          = $readability;
		$this->forward_tools        = $forward_tools;
		$this->html_checker         = $html_checker;
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		$actions = array(
			array(
				'hook'     => 'rest_api_init',
				'method'   => 'activate',
				'priority' => 11,
			),
		);
		return $actions;
	}

	/**
	 * Sets up endpoint registration.
	 */
	public function activate() {
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
			$namespace,
			'/' . $base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'make_html_readable' ),
					'args'                => array(
						'html_doc'   => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'The html_doc parameter takes a block of HTML and parses it through readability to create a clean article.', 'pressforward' ),
							// type specifies the type of data that the argument should be.
							'type'              => 'string',
							// Set the argument to be required for the endpoint.
							'required'          => true,
							'validate_callback' => array( $this, 'is_valid_html' ),
							'sanitize_callback' => array( $this, 'is_sane_html' ),
						),
						'source_url' => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'The source_url parameter takes a URL for keeping note of in readability.', 'pressforward' ),
							// type specifies the type of data that the argument should be.
							'type'              => 'string',
							// Set the argument to be required for the endpoint.
							'required'          => true,
							'validate_callback' => array( $this, 'is_valid_url' ),
							'sanitize_callback' => array( $this, 'is_sane_url' ),
						),
					),
					'permission_callback' => function () {
						return false;
					},
					'priority'            => 10,
				),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $base . '/url',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_readable_from_url' ),
					'args'                => array(
						'source_url' => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'The source_url parameter takes a URL for keeping note of in readability.', 'pressforward' ),
							// type specifies the type of data that the argument should be.
							'type'              => 'string',
							// Set the argument to be required for the endpoint.
							'required'          => true,
							'validate_callback' => array( $this, 'is_valid_url' ),
							'sanitize_callback' => array( $this, 'is_sane_url' ),
						),
					),
					'permission_callback' => function () {
						return false;
					},
					'priority'            => 10,
				),
			)
		);
	}
	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed            $value   Value of the 'filter' argument.
	 * @param  \WP_REST_Request $request The current request object.
	 * @param  string           $param   Key of the parameter. In this case it is 'html_doc'.
	 * @return \WP_Error|boolean
	 */
	public function is_valid_html( $value, $request, $param ) {
		// If the argument is not a string return an error.
		if ( ! is_string( $value ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The html_doc argument must be a string.', 'pressforward' ), array( 'status' => 400 ) );
		} else {
			return true;
		}
	}
	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @param  mixed            $value   Value of the 'filter' argument.
	 * @param  \WP_REST_Request $request The current request object.
	 * @param  string           $param   Key of the parameter. In this case it is 'html_doc'.
	 * @return string
	 */
	public function is_sane_html( $value, $request, $param ) {
		// Readability will take care of sanitization.
		return $value;
	}

	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed            $value   Value of the 'filter' argument.
	 * @param  \WP_REST_Request $request The current request object.
	 * @param  string           $param   Key of the parameter. In this case it is 'source_url'.
	 * @return \WP_Error|boolean
	 */
	public function is_valid_url( $value, $request, $param ) {
		// If the argument is not a string return an error.
		if ( false === wp_parse_url( $value ) || ( filter_var( $value, FILTER_VALIDATE_URL ) === false ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The source_url argument must be a well formed URL.', 'pressforward' ), array( 'status' => 400 ) );
		} else {
			return true;
		}
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @param  mixed            $value   Value of the 'filter' argument.
	 * @param  \WP_REST_Request $request The current request object.
	 * @param  string           $param   Key of the parameter. In this case it is 'source_url'.
	 * @return \WP_Error|string
	 */
	public function is_sane_url( $value, $request, $param ) {
		return filter_var( $value, FILTER_SANITIZE_URL );
	}

	/**
	 * This is our callback function that embeds our resource in a WP_REST_Response.
	 *
	 * The parameter is already sanitized by this point so we can use it without any worries.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function make_html_readable( $request ) {
		if ( isset( $request['html_doc'] ) && isset( $request['source_url'] ) ) {
			return rest_ensure_response(
				htmlspecialchars( $this->readability->process_readability( $request['html_doc'], $request['source_url'] ) )
			);
			// unencode via js with the html_entity_decode function we use elsewhere.
		}
		return new \WP_Error( 'rest_invalid', esc_html__( 'The html_doc and source_url parameters are required.', 'pressforward' ), array( 'status' => 400 ) );
	}

	/**
	 * Callback for /readability/url endpoint.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_readable_from_url( $request ) {
		return rest_ensure_response(
			htmlspecialchars( $this->readability->readability_object( $request['source_url'] ) )
		);
	}
}
