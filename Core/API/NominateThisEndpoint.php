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
					return false;
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
						// $return_var = true;
						return $return_var;
					} catch ( \UnexpectedValueException $e ){
						$return_var = new WP_Error( 'auth_fail_format', __( "Authentication key was not properly formated.", "pf" ) );
					} catch ( \InvalidArgumentException $e ){
						$return_var = new WP_Error( 'auth_fail_key', __( "Authentication key was not properly supplied.", "pf" ) );
					} catch ( \DomainException $e ){
						$return_var = new WP_Error( 'auth_fail_ssl', __( "SSL cannot be applied to the key.", "pf" ) );
					} catch ( \Exception $e ) {
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
					$request_params = $request->get_params();
					// var_dump(hex2bin(trim($_POST['user_key']))); die();
					// var_dump($request->get_json_params()); die();
					try {
						$key = pressforward('controller.jwt')->get_a_user_private_key_for_decrypt(hex2bin(trim($request_params['user_key'])));
						// pf_log('Decode attempt 2 on');
						// pf_log($key);
						if (!$key){
							$return_var = new WP_Error( 'auth_fail_id', __( "Request was signed with incorrect key.", "pf" ) );
						} else {
							$return_var = true;
						}
						$return_var = false;
						return $return_var;
					} catch ( \UnexpectedValueException $e ){
						// var_dump($e, $_POST['user_key']);
						$return_var = new WP_Error( 'auth_fail_format', __( "Authentication key was not properly formated. ".$request_params['user_key'], "pf" ) );
					} catch ( \InvalidArgumentException $e ){
						$return_var = new WP_Error( 'auth_fail_key', __( "Authentication key was not properly supplied.", "pf" ) );
					} catch ( \DomainException $e ){
						$return_var = new WP_Error( 'auth_fail_ssl', __( "SSL cannot be applied to the key.", "pf" ) );
					} catch ( \Exception $e ) {
						if ( false === $return_var){
							return new WP_Error( 'auth_fail_whoknows', __( "Authentication failed for reasons unclear.", "pf" ) );
						} else {
							return $return_var;
						}
					}
					// var_dump($request->get_params());
					// var_dump($return_var); die();
					return $return_var;

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
		// return esc_html( implode( $_REQUEST ) );
		// $_POST = $request->get_json_params();
		pf_log('Nomination Submitted');
		$_POST = array_merge($_POST, $request->get_params());
		$private_key = pressforward('controller.jwt')->get_a_user_private_key_for_decrypt(hex2bin(trim($_POST['user_key'])));
		//$pk_portions = explode('.', $_POST['verify']);
		$verify = pressforward('controller.jwt')->decode_with_jwt(trim($_POST['verify']), $private_key);
		if ( ( false !== $verify ) && property_exists( $verify, 'date' ) ) {
			$date_obj = \date_create( '@' . ( $verify->date ) );
			$current_date_obj = new \DateTime();
			// 15 minutes
			$allowable_diff = new \DateInterval( 'PT15M' );
			$date_obj->add( $allowable_diff );
			if ( $date_obj < $current_date_obj ) {
				// Too old of a message
				//var_dump( 'bad date' );
				return '{"error": "bad date", "date_sent":"'.$date_obj->format('Y-m-d H:i:s').'", "date_internal":"'.$current_date_obj->format('Y-m-d H:i:s').'"}';
			}
		} else {
			return '{ error: "verification not available"}'.' pk:'.$private_key.' v:'.$verify.' vr:'.$_POST['verify'] . '  uk: '.hex2bin(trim($_POST['user_key'])). ' pk portions:'. $pk_portions[0]. '    '. $pk_portions[1];
		}

		$user_id = pressforward('controller.jwt')->get_user_by_key( $_POST['user_key'] );
		wp_set_current_user($user_id);
		$decrypted_data = pressforward('controller.jwt')->decode_with_jwt(trim($_POST['data']), $private_key);
		if ( false === $decrypted_data ){
			return '{ "error": "bad data" }';
		}
		pf_log('Nomination Data received: ');
		pf_log($decrypted_data);
		$_POST = array_merge( $_POST, (array) $decrypted_data );
		$_POST['post_title'] = urldecode($_POST['post_title']);
		$_POST['content'] = urldecode($_POST['content']);
		$_POST['publish'] = urldecode($_POST['publish']);
		$id = pressforward('bookmarklet.core')->nominate_it(false);
		$return_object = new \stdClass();
		$return_object->id = $id;
		$response = new \WP_REST_Response($return_object);
		$response->header( 'Content-Type', 'application/json' );
		// var_dump('Test: ', $request->get_body());
		return rest_ensure_response($response);
		// return $id;
		// return new WP_REST_Response($return_object);
	}

	public function get_nominate_this_script(){
		$fontFace = <<<EOF
@font-face {
    font-family: 'tinymce';
    src: url(data:application/font-woff2;charset=utf-8;base64,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) format('woff2'),
        url(data:application/font-woff;charset=utf-8;base64,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) format('woff');
    font-weight: normal;
    font-style: normal;
}

@font-face {
    font-family: 'tinymce';
    src: url(data:application/font-woff2;charset=utf-8;base64,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) format('woff2'),
        url(data:application/font-woff;charset=utf-8;base64,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) format('woff');
    font-weight: normal;
    font-style: normal;
}
EOF;
		$fontFaceJS = trim(preg_replace('/\s+/', ' ', $fontFace));;
		$site_url = ABSPATH;
		// $basic_scripts = '/wp-admin/load-scripts.php?c=1&load%5B%5D=jquery,jquery-core,jquery-migrate,utils,moxiejs,plupload,jquery-ui-core,jquery-ui-widget';
		header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );
		echo 'window.pfSiteData = {}; ';
		echo 'window.pfSiteData.site_url = "'. \get_site_url() . '"; ';
		echo 'window.pfSiteData.plugin_url = "'. plugin_dir_url( dirname(dirname(__FILE__)) ) . '"; ';
		echo 'window.pfSiteData.submit_endpoint = "' . trailingslashit(\get_site_url()) . 'wp-json/' . $this->api_base['base_namespace'] . $this->api_base['version'] . '/' . $this->api_base['submit'] . '"; ';
		echo 'window.pfSiteData.categories_endpoint = "'. trailingslashit(\get_site_url()) . 'wp-json/wp/v2/categories"; ';
		echo 'window.pfSiteData.fontFace = "' . $fontFaceJS . '"';
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
		// include_once PF_ROOT . '/Libraries/tinymce/js/tinymce/tinymce.min.js';
		// include_once PF_ROOT . '/Libraries/SummerNote/summernote.js';
		include_once PF_ROOT . '/assets/js/nominate-tool.js';
		die();
	}


}
