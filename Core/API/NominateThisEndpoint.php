<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Core\Admin\PFTemplater;

use WP_Ajax_Response;
use WP_Error;

class NominateThisEndpoint implements HasActions {

	protected $api_base;

	function __construct( $api_base, PFTemplater $templates ) {
		$this->api_base = $api_base;
		$this->api_base['endpoint'] = 'nominatethis';
		$this->templates = $templates;
		$namespace = $this->api_base['base_namespace'] . $this->api_base['version'];
		$base = $this->api_base['endpoint'];
		$this->api_base['authpoint'] = 'nominate';
		$this->api_base['scriptpoint'] = 'nomscript';
		$this->api_base['submit'] = 'submit-nomination';
		$this->endpoint_for_nominate_this_endpoint = $namespace. '/' . $base;
		$this->endpoint_for_nominate_this_script = $namespace. '/' . $this->api_base['scriptpoint'];
		$this->endpoint_for_nominate_endpoint = $namespace. '/' . 	$this->api_base['authpoint'];
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
		$base = $this->api_base['endpoint'];
		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => \WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_nominate_this_template' ),
				'args' => array(
			      'context' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Supplies the Nominate This template for building the bookmarklet.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => false,
					'default'	  => 'view'
			      ),
			    ),
				'permission_callback' => function () {
					if ( ! current_user_can( get_option( 'pf_menu_nominate_this_access', pressforward( 'controller.users' )->pf_get_defining_capability_by_role( 'contributor' ) ) ) ){
	  				  	wp_die( __( 'You do not have the capacity to access the Nominate This bookmarklet.', 'pf' ) );
	  				  	return false;
					} else {
						return true;
					} //$this->templates->users->pf_get_defining_capability_by_role( 'contributor' );
			  	},
				'priority'  => 10,
			),
		));
		register_rest_route($namespace, '/'.$this->api_base['authpoint'], array(
			array(
				'methods'         => \WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_nominate_this' ),
				'args' => array(
				  'context' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Supplies the Nominate This template for building the bookmarklet.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => false,
					'default'	  => 'view'
				  ),
				  'u' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Supplies the Nominate This template for building the bookmarklet.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => false,
					'default'	  => 'view'
				  ),
				  's' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Supplies the Nominate This template for building the bookmarklet.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => false,
					'default'	  => 'view'
				  ),
				  'v' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Supplies the Nominate This template for building the bookmarklet.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => false,
					'default'	  => ''
				  ),
				),
				'permission_callback' => function () {
					return true;
				},
				'priority'  => 10,
			),
		));
		register_rest_route($namespace, '/'.$this->api_base['scriptpoint'],
			array(
				'methods'         => \WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_nominate_this_script' ),
				'args' => array(
				  'context' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'Supplies the Nominate This js script for building the bookmarklet.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => false,
					'default'	  => 'view'
				  ),
                  'k' => array(
                      // description should be a human readable description of the argument.
                    'description' => esc_html__( 'Public Key.', 'pf' ),
                    // type specifies the type of data that the argument should be.
                    'type'        => 'string',
                    // Set the argument to be required for the endpoint.
                    'required'    => true,
                    'default'	  => '0'
                  ),
				),
				'permission_callback' => function () {
					//var_dump($_GET);
					$return_var = false;
					try {
						$key = pressforward('controller.jwt')->get_a_user_private_key_for_decrypt(hex2bin($_GET['k']));
						if (!$key){
							$return_var = new WP_Error( 'auth_fail_id', __( "Request was signed with incorrect key.", "pf" ) );
						}
						$return_var = true;
						return $return_var;
					} catch ( \UnexpectedValueException $e ){
						$return_var = new WP_Error( 'auth_fail_format', __( "Authentication key was not properly formated.", "pf" ) );
					} catch ( \InvalidArgumentException $e ){
						$return_var = new WP_Error( 'auth_fail_key', __( "Authentication key was not properly supplied.", "pf" ) );
					} catch ( \DomainException $e ){
						$return_var = new WP_Error( 'auth_fail_ssl', __( "SSL cannot be applied to the key.", "pf" ) );
					} finally {
						if ( false === $return_var){
							return new WP_Error( 'auth_fail_whoknows', __( "Authentication failed for reasons unclear.", "pf" ) );
						} else {
							return $return_var;
						}
					}

				},
				'priority'  => 10,
			)
		);

		register_rest_route($namespace, '/'.$this->api_base['submit'],
			array(
				'methods'         => \WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'handle_nomination_submission' ),
				'args' => array(
				  'context' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'The endpoint to which the Nominate This bookmarklet submits to.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => false,
					'default'	  => 'view'
				  ),
                  'k' => array(
                      // description should be a human readable description of the argument.
                    'description' => esc_html__( 'Public Key.', 'pf' ),
                    // type specifies the type of data that the argument should be.
                    'type'        => 'string',
                    // Set the argument to be required for the endpoint.
                    'required'    => true,
                    'default'	  => '0'
                  )
				),
				'permission_callback' => function ( $request ) {
					// return true;
					$return_var = false;
					$request_params = $request->get_json_params();
					//var_dump($request->get_json_params()); die();
					try {
						$key = pressforward('controller.jwt')->get_a_user_private_key_for_decrypt(hex2bin($request_params['user_key']));
						if (!$key){
							$return_var = new WP_Error( 'auth_fail_id', __( "Request was signed with incorrect key.", "pf" ) );
						}
						$return_var = true;
						return $return_var;
					} catch ( \UnexpectedValueException $e ){
						$return_var = new WP_Error( 'auth_fail_format', __( "Authentication key was not properly formated. ", "pf" ) );
					} catch ( \InvalidArgumentException $e ){
						$return_var = new WP_Error( 'auth_fail_key', __( "Authentication key was not properly supplied.", "pf" ) );
					} catch ( \DomainException $e ){
						$return_var = new WP_Error( 'auth_fail_ssl', __( "SSL cannot be applied to the key.", "pf" ) );
					} finally {
						if ( false === $return_var){
							return new WP_Error( 'auth_fail_whoknows', __( "Authentication failed for reasons unclear.", "pf" ) );
						} else {
							return $return_var;
						}
					}

				},
				'priority'  => 10,
			)
		);

	}

	public function get_nominate_this_template( $request ) {
		define( 'IFRAME_REQUEST', true );
		define( 'WP_ADMIN', false );
		global $pagenow;
		//wp_verify_nonce( $nonce, 'wp_rest' );
		return rest_ensure_response(
			include PF_ROOT . '/includes/nomthis/nominate-this-core.php'
		);
	}

	public function get_nominate_this($request){
		define( 'IFRAME_REQUEST' , true );
		define( 'WP_ADMIN', true );
		//var_dump($request); var_dump($_GET); die();
		global $pagenow;
		header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
		$nonce = wp_create_nonce( 'wp_rest' );
		ob_start();
		wp_login_form(array(
			'redirect'	=>	rest_url($this->endpoint_for_nominate_this_endpoint.'?nonce='.$nonce, 'html')
		));
		$login = ob_get_clean();
		echo $login;
		die();

	}

	public function get_file( $path ) {

		if ( function_exists( 'realpath' ) ) {
			$path = realpath( $path );
		}

		if ( ! $path || ! @is_file( $path ) ) {
			return '';
		}

		return @file_get_contents( $path );
	}

	public function assembleScripts( $load ) {
		/** Set ABSPATH for execution */

		if ( is_array( $load ) ) {
			$load = implode( '', $load );
		}

		$load = preg_replace( '/[^a-z0-9,_-]+/i', '', $load );
		$load = array_unique( explode( ',', $load ) );

		if ( empty( $load ) ) {
			exit;
		}

		// include_once ABSPATH . 'wp-admin/includes/noop.php';
		// require( ABSPATH . WPINC . '/script-loader.php' );
		// require( ABSPATH . WPINC . '/version.php' );

		$compress       = ( isset( $_GET['c'] ) && $_GET['c'] );
		$force_gzip     = ( $compress && 'gzip' == $_GET['c'] );
		$expires_offset = 31536000; // 1 year
		$out            = '';

		$wp_scripts = new \WP_Scripts();
		\wp_default_scripts( $wp_scripts );

		if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) === $wp_version ) {
			$protocol = $_SERVER['SERVER_PROTOCOL'];
			if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0' ) ) ) {
				$protocol = 'HTTP/1.0';
			}
			header( "$protocol 304 Not Modified" );
			exit();
		}

		foreach ( $load as $handle ) {
			if ( ! array_key_exists( $handle, $wp_scripts->registered ) ) {
				continue;
			}

			$path = ABSPATH . $wp_scripts->registered[ $handle ]->src;
			$out .= $this->get_file( $path ) . "\n";
		}

		// header( "Etag: $wp_version" );
		// header( 'Content-Type: application/javascript; charset=UTF-8' );
		// header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $expires_offset ) . ' GMT' );
		// header( "Cache-Control: public, max-age=$expires_offset" );

		if ( $compress && ! ini_get( 'zlib.output_compression' ) && 'ob_gzhandler' != ini_get( 'output_handler' ) && isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) {
			// header( 'Vary: Accept-Encoding' ); // Handle proxies
			if ( false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate' ) && function_exists( 'gzdeflate' ) && ! $force_gzip ) {
				// header( 'Content-Encoding: deflate' );
				// $out = gzdeflate( $out, 3 );
			} elseif ( false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) && function_exists( 'gzencode' ) ) {
				// header( 'Content-Encoding: gzip' );
				// $out = gzencode( $out, 3 );
			}
		}

		echo $out;
	}

	public function handle_nomination_submission( $request ) {
		// Already authorized at an upper API level.
		// var_dump('Test: ', $request->get_body()); die();
		// return esc_html( implode( $_REQUEST ) );
		$_POST = $request->get_json_params();
		$user_id = pressforward('controller.jwt')->get_user_by_key($_POST['user_key']);
		wp_set_current_user($user_id);
		return pressforward('bookmarklet.core')->nominate_it(false);
	}

	public function get_nominate_this_script(){
		$site_url = ABSPATH;
		// $basic_scripts = '/wp-admin/load-scripts.php?c=1&load%5B%5D=jquery,jquery-core,jquery-migrate,utils,moxiejs,plupload,jquery-ui-core,jquery-ui-widget';
		header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );
		echo 'window.pfSiteData = {}; ';
		echo 'window.pfSiteData.site_url = "'. \get_site_url() . '"; ';
		echo 'window.pfSiteData.plugin_url = "'. plugin_dir_url( dirname(dirname(__FILE__)) ) . '"; ';
		echo 'window.pfSiteData.submit_endpoint = "' . \get_site_url() . '/wp-json/pf/v1' . $namespace . '/' . $this->api_base['submit'] . '"; ';
		include_once PF_ROOT . '/assets/js/jws.js';
		include_once PF_ROOT . '/assets/js/jwt.js';

		// include_once $site_url . 'wp-includes/js/jquery/jquery.js';

		// $this->assembleScripts( 'load%5B%5D=jquery-core,jquery-migrate,utils,moxiejs,plupload,jquery-ui-core,jquery-ui-widget' );

		// // include_once $site_url . 'wp-includes/js/jquery/ui/position.min.js?';
		// include_once $site_url . 'wp-includes/js/jquery/ui/menu.min.js';
		// include_once $site_url . 'wp-includes/js/wp-a11y.min.js';
		// include_once $site_url . 'wp-includes/js/jquery/ui/autocomplete.min.js';
		// include_once $site_url . 'wp-admin/js/tags-suggest.min.js';
		// include_once $site_url . 'wp-admin/js/tags-box.min.js';
		// include_once $site_url . 'wp-includes/js/wp-sanitize.min.js';
		include_once PF_ROOT . '/Libraries/MozillaReadability/Readability.js';
		include_once PF_ROOT . '/Libraries/PFMetaDataParser.js';
		//include_once PF_ROOT . '/Libraries/tinymce/js/tinymce/tinymce.min.js';
		// include_once PF_ROOT . '/Libraries/SummerNote/summernote.js';
		include_once PF_ROOT . '/assets/js/nominate-tool.js';
		die();
	}


}
