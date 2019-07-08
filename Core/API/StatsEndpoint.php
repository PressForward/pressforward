<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;

use PressForward\Controllers\Metas;
use PressForward\Controllers\Stats;
use PressForward\Core\Utility\Forward_Tools;
use PressForward\Libraries\HTMLChecker;

use DaveChild\TextStatistics as TS;
use DaveChild\TextStatistics\Text as Text;

use WP_Ajax_Response;
use WP_Error;
// use \WP_REST_Controller;
class StatsEndpoint implements HasActions {

	protected $api_base;

	function __construct( $api_base, Metas $metas, Stats $stats ) {
		$this->api_base             = $api_base;
		$this->api_base['endpoint'] = 'stats';
		$this->metas                = $metas;
		$this->stats                = $stats;
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
			$namespace, '/' . $base . '/overview', array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'overview' ),
					'args'                => array(
						'after_year'   => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by year, use XXXX year notation.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'after_month'  => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by month, use number of month.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'after_day'    => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by day, use number of day.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'before_year'  => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by year, use XXXX year notation.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'before_month' => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by month, use number of month.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'before_day'   => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by month, use number of month.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
					),
					'permission_callback' => function () {
						return true; // current_user_can( 'edit_others_posts' );
					},
					'priority'            => 10,
				),
			)
		);
		register_rest_route(
			$namespace, '/' . $base . '/authors', array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'author_stats' ),
					'args'                => array(
						'page'         => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Page of authors.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => true,
							'default'           => 1,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'after_year'   => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by year, use XXXX year notation.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'after_month'  => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by month, use number of month.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'after_day'    => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by day, use number of day.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'before_year'  => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by year, use XXXX year notation.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'before_month' => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by month, use number of month.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'before_day'   => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Limit query by month, use number of month.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => false,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
					),
					'permission_callback' => function () {
						return true; // current_user_can( 'edit_others_posts' );
					},
					'priority'            => 10,
				),
			)
		);
		register_rest_route(
			$namespace, '/' . $base . '/pf_posted', array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'pf_posted' ),
					'args'                => array(
						'page' => array(
							// description should be a human readable description of the argument.
							'description'       => esc_html__( 'Page of posts created by PressForward.', 'pf' ),
							// Set the argument to be required for the endpoint.
							'required'          => true,
							'default'           => 1,
							'validate_callback' => function( $page, $request_object ) {
								if ( is_numeric( $page ) ) {
									return true;
								} else {
									return false;
								}
							},
						),
						'per_page' => array(
							'default' => 40,
							'sanitize_callback' => 'absint',
						),
						'offset' => array(
							'required'          => false,
							'sanitize_callback' => 'intval',
						),
					),
					'permission_callback' => function () {
						return true; // current_user_can( 'edit_others_posts' );
					},
					'priority'            => 10,
				),
			)
		);
	}


	/**
	 * This is our callback function that embeds our resource in a WP_REST_Response.
	 *
	 * The parameter is already sanitized by this point so we can use it without any worries.
	 */
	public function author_stats( $request ) {
		if ( isset( $request['page'] ) ) {
			// \rest_ensure_response(
			$args = array(
				'paged'  => $request['page'],
				// 'no_found_rows' => true,
				'fields' => 'ids',
			);
			$date_limits = array(
				'year',
				'month',
				'day',
			);
			$date_query  = array();
			foreach ( $date_limits as $limit ) {
				if ( ! empty( $request[ 'after_' . $limit ] ) ) {
					if ( ! isset( $date_query['after'] ) ) {
						$date_query['after'] = array();
					}
					$date_query['after'][ $limit ] = $request[ 'after_' . $limit ];
				}
				if ( ! empty( $request[ 'before_' . $limit ] ) ) {
					if ( ! isset( $date_query['before'] ) ) {
						$date_query['before'] = array();
					}
					$date_query['before'][ $limit ] = $request[ 'before_' . $limit ];
				}
			}
			$args['date_query'] = $date_query;
			$q = $this->stats->stats_query_for_pf_published_posts( $args );
			// var_dump($q);
			$ids     = $q->posts;
			$authors = array();
			foreach ( $ids as $id ) {
				$authors = $this->stats->set_author_into_leaderboard( $id, $authors );
			}

			return rest_ensure_response(
				$authors
			);
			// unencode via js with the html_entity_decode function we use elsewhere.
		}
		return new \WP_Error( 'rest_invalid', esc_html__( 'The page parameter is required.', 'pf' ), array( 'status' => 400 ) );
	}

	public function pf_posted( $request ) {
		\ob_start();
		if ( isset( $request['page'] ) ) {
			$args = array();
			if ( isset( $request['per_page'] ) && is_numeric( $request['per_page'] ) ){
				$per_page = intval( $request['per_page'] );
				if ( $per_page > 100){
					$per_page = 100;
				}
				if ( $per_page < 1 ) {
					$per_page = 1;
				}
				$posts_per_page = $per_page;
			} else {
				$posts_per_page = 40;
			}
			$args['posts_per_page'] = $posts_per_page;
			if ( isset( $request['page'] ) && is_numeric( $request['page'] ) ){
				$page = intval( $request['page'] );
				if ( $page < 1 ) {
					$page = 1;
				}
			} else {
				$page = 1;
			}
			$args['paged'] = $page;
			if ( isset( $request['offset'] ) && is_numeric( $request['offset'] ) ){
				$offset = intval( $request['offset'] );
			} else {
				$offset = 0;
			}
			$offset_total = (($posts_per_page * ($page-1)) + $offset);
			if ($offset === 0 || $offset_total < 1){
				$args['offset'] = $offset_total;
			}

			$q    = $this->stats->stats_query_for_pf_published_posts( $args );

			$posts = $q->posts;
			foreach ( $posts as $post ) {
				$post_content                = $post->post_content;
				$post_content_cleaner        = strip_tags( stripslashes( html_entity_decode( htmlspecialchars_decode( htmlspecialchars( $post_content ) ) ) ) );
				$post_content_cleaner        = preg_replace( '/[^A-Za-z0-9\-]/', ' ', $post_content_cleaner );
				$post_content_cleaner        = str_replace( array( "\n", "\r", "\r\n" ), ' ', $post_content_cleaner );
				$post->stripped_post_content = strip_tags( $post_content );
				$post->wordcount             = str_word_count( $post->stripped_post_content );
				$post->sentences             = Text::sentenceCount( $post->stripped_post_content );
				$textStatistics              = new TS\TextStatistics();
				$reading_score               = $textStatistics->fleschKincaidReadingEase( $post->stripped_post_content );
				$post->flesch_kincaid_score  = $reading_score;
				$item_link                   = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'item_link' );
				$url_parts                   = parse_url( $item_link );
				unset($post->post_password);
				if ( ! empty( $url_parts ) && isset( $url_parts['host'] ) ) {
					$post->source_link = $url_parts['host'];
				} else {
					$post->source_link = 'No Source Found';
				}
				$post->nominators = pressforward( 'controller.metas' )->get_post_pf_meta( $post->ID, 'nominator_array' );
				$post = pressforward( 'controller.metas' )->attach_metas_by_use($post);
				// $post->source_link = $this->metas->get_post_pf_meta( $post->ID, 'pf_source_link' );
			}
			$response = rest_ensure_response(
				$posts
			);
			$response->header( 'X-PF-PageRequested', (int) $page );
			$response->header( 'X-WP-Total', (int) $q->found_posts );
			$response->header( 'X-WP-TotalPages', (int) $q->max_num_pages );
			\ob_end_flush();
			return $response;
			// unencode via js with the html_entity_decode function we use elsewhere.
		}
		return new \WP_Error( 'rest_invalid', esc_html__( 'The page parameter is required.', 'pf' ), array( 'status' => 400 ) );
	}

	/**
	 * This is our callback function that embeds our resource in a WP_REST_Response.
	 *
	 * The parameter is already sanitized by this point so we can use it without any worries.
	 */
	public function overview( $request ) {
		// \rest_ensure_response(
		$args = array(
			// 'no_found_rows' => true,
			'fields' => 'ids',
		);
		$date_limits = array(
			'year',
			'month',
			'day',
		);
		$date_query  = array();
		foreach ( $date_limits as $limit ) {
			if ( ! empty( $request[ 'after_' . $limit ] ) ) {
				if ( ! isset( $date_query['after'] ) ) {
					$date_query['after'] = array();
				}
				$date_query['after'][ $limit ] = $request[ 'after_' . $limit ];
			}
			if ( ! empty( $request[ 'before_' . $limit ] ) ) {
				if ( ! isset( $date_query['before'] ) ) {
					$date_query['before'] = array();
				}
				$date_query['before'][ $limit ] = $request[ 'before_' . $limit ];
			}
		}
		$counts = $this->stats->counts( $args, $date_query );

		return rest_ensure_response(
			$counts
		);
		// unencode via js with the html_entity_decode function we use elsewhere.
		// return new \WP_Error( 'rest_invalid', esc_html__( 'The page parameter is required.', 'pf' ), array( 'status' => 400 ) );
	}


}
