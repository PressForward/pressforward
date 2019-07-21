<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Core\Admin\PFTemplater;
use PressForward\Controllers\PF_JWT;
use PFOpenGraph;
use URLResolver;

use WP_Ajax_Response;
use WP_Error;

class MetaCheckEndpoint implements HasActions {

	protected $api_base;

	function __construct( $api_base, PF_JWT $jwt, PFOpenGraph $og, URLResolver $url_resolver ) {
		$this->api_base = $api_base;
		$this->api_base['endpoint'] = 'metachecks';
		$this->og = $og;
		$namespace = $this->api_base['base_namespace'] . $this->api_base['version'];
		$base = $this->api_base['endpoint'];
        $this->url_resolver = $url_resolver;
        $this->jwt = $jwt;
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
		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => \WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_nominate_this_script' ),
				'args' => array(
				  'url' => array(
					  // description should be a human readable description of the argument.
					'description' => esc_html__( 'URL of the page being analyzed.', 'pf' ),
					// type specifies the type of data that the argument should be.
					'type'        => 'string',
					// Set the argument to be required for the endpoint.
					'required'    => true,
					'default'	  => '#'
				  ),
                  'doc' => array(
                      // description should be a human readable description of the argument.
                    'description' => esc_html__( 'Document being scanned.', 'pf' ),
                    // type specifies the type of data that the argument should be.
                    'type'        => 'string',
                    // Set the argument to be required for the endpoint.
                    'required'    => false,
                    'default'	  => false
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
					} catch ( \Exception $e ) {
						if ( false === $return_var){
							return new WP_Error( 'auth_fail_whoknows', __( "Authentication failed for reasons unclear.", "pf" ) );
						} else {
							return $return_var;
						}
					}

				},
				'priority'  => 10,
			),
		));

	}

    private function check_keys_for_value($key, $obj){
        if ( !empty( $obj->metas->$key ) ){
            $obj->$key = $obj->metas->$key;
        }
        if ( !empty( $obj->twitter->$key ) ){
            $obj->$key = $obj->twitter->$key;
        }
        if ( !empty( $obj->open_graph->$key ) ){
            $obj->$key = $obj->open_graph->$key;
        }
        return $obj;
    }

	public function get_nominate_this_script(){
        $url = $_GET['url'];
        $obj = new stdClass();
        $metas = new stdClass();
        $og_data = new stdClass();
        $twitter_data = new stdClass();
        if (empty($_GET['doc'])){
            $og = pressforward( 'library.opengraph' )->fetch( $url );
        } else {
            $og = pressforward( 'library.opengraph' )->process( $_GET['doc'] );
        }
        if ( false !== $og ){
            if ( ! empty( $og ) && ! empty( $og->article_tag ) ) {
                $tags_retrieved[] = $og->article_tag;
            }
            if ( ! empty( $og ) && ! empty( $og->article_tag_additional ) ) {
                $tags_retrieved = array_merge( $tags_retrieved, $og->article_tag_additional );
            }
            if ( ! empty( $tags_retrieved ) ) {
                $tags_retrieved[] = 'via bookmarklet';
                $tags_retrieved = implode( ', ', $tags_retrieved );
            } else {
                $tags_retrieved = 'via bookmarklet';
            }
            $og_data->tags = $tags_retrieved;
            $obj->original_url = $url;

            if ( !empty($og->keywords) ){
                if (!is_array($og->keywords)){
                    $keywords = explode(',', $og->keywords);
                    if (empty($keywords)){
                        $keywords = explode(';', $og->keywords);
                    }
                    if (!empty($keywords)){
                        $metas->keywords = $keywords;
                    } else {
                        $metas->keywords = fasle;
                    }
                } else {
                    $metas->keywords = $og->keywords;
                }
                if ( !empty($obj->keywords) ){
                    $trim_keywords = array();
                    foreach ($metas->keywords as $keyword){
                        $trim_keywords[] = trim($keyword);
                    }
                    $metas->keywords = $trim_keywords;
                }
            }
                // var_dump($og); die();
                // $response_body = wp_remote_retrieve_body( $response );
                // $response_dom = pf_str_get_html( $response_body );
            if ( isset( $og->url ) ) {
                $url = $og->url;
                $og_data->url = $url;
            } else {
                $og_data->url = $url;
            }
            $og_data->title = $og->title;
            $og_data->image = $og->image;
            $og_data->description = $og->description;
            $og_data->type = $og->type;
            $og_data->site_name = $og->site_name;

            if ( isset( $og->{'article_location:latitude'} ) ){
                $og_data->latitude = $og->{'article_location:latitude'};
            }
            if ( isset( $og->{'article_location:longitude'} ) ){
                $og_data->longitude = $og->{'article_location:longitude'};
            }
            if ( isset( $og->{'latitude'} ) ){
                $og_data->latitude = $og->{'latitude'};
            }
            if ( isset( $og->{'longitude'} ) ){
                $og_data->longitude = $og->{'longitude'};
            }
            $twitter_keys = array(
                "twitter_card" => 'twitter_card',
                "twitter_site" => 'twitter_site',
                "twitter_creator" => 'twitter_creator',
                "twitter_title" =>  'title',
                "twitter_description"   =>  'description',
                "twitter_image" =>  'image'
            );
            foreach ( $twitter_keys as $key=>$prop ) {
                $twitter_data->$prop = $og->$key;
            };
            $obj->open_graph = $og_data;
            $obj->twitter = $twitter_data;
            $obj->metas = $metas;
            $obj->keywords = array_merge( (array) $metas->keywords, (array) $og_data->tags );
            $primary_keys = array(
                'title',
                'description',
                'image',
                'site_name',
                'type',
                'url',
            );
            foreach ( $primary_keys as $key ) {
                $obj = $this->check_keys_for_value( $key, $obj );
            }

        }
		header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );
		echo json_encode($obj);
		die();
	}


}
