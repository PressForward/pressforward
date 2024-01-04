<?php
/**
 * Adds meta fields to post endpoint.
 *
 * @package PressForward
 */

namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

/**
 * PostExtension class.
 */
class PostExtension extends APIWithMetaEndpoints implements HasActions, HasFilters {

	/**
	 * Basename.
	 *
	 * @access protected
	 * @var string
	 */
	protected $basename;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $metas;

	/**
	 * Post type.
	 *
	 * @access public
	 * @var string
	 */
	public $post_type;

	/**
	 * Level.
	 *
	 * @access public
	 * @var string
	 */
	public $level;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\Metas $metas Metas object.
	 */
	public function __construct( Metas $metas ) {
		$this->metas     = $metas;
		$this->post_type = 'post';
		$this->level     = 'post';
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		$actions = array(
			array(
				'hook'   => 'rest_api_init',
				'method' => 'register_rest_fields',
			),
		);
		return $actions;
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_hooks() {
		$filter = array(
			array(
				'hook'     => 'rest_prepare_' . $this->post_type,
				'method'   => 'add_rest_post_links',
				'priority' => 10,
				'args'     => 3,
			),
			array(
				'hook'     => 'rest_prepare_' . $this->post_type,
				'method'   => 'filter_wp_to_pf_in_terms',
				'priority' => 10,
				'args'     => 3,
			),
		);
		return $filter;
	}

	/**
	 * Generates hook for rest_api_init.
	 *
	 * @param string $action Request method.
	 * @return array
	 */
	public function rest_api_init_extension_hook( $action ) {
		return array(
			'hook'   => 'rest_api_init',
			'method' => $action,
		);
	}

	/**
	 * Generates read-only hook for rest_api_init.
	 *
	 * @param string $action Request method.
	 * @return array
	 */
	public function rest_api_init_extension_hook_read_only( $action ) {
		return array(
			'hook'   => 'rest_api_init',
			'method' => function () use ( $action ) {
				$this->register_rest_post_read_field( $action, true );
			},
		);
	}

	/**
	 * Adds PF 'links' fields to post data in response object.
	 *
	 * @param \WP_REST_Response $data    Response object.
	 * @param \WP_Post          $post    Post object.
	 * @param \WP_REST_Request  $request Request object.
	 * @return \WP_REST_Response
	 */
	public function add_rest_post_links( $data, $post, $request ) {
		// http://v2.wp-api.org/extending/linking/.
		// https://1fix.io/blog/2015/06/26/adding-fields-wp-rest-api/.
		$feed_id = 'false';
		if ( ! empty( $post->post_parent ) ) {
			$feed_id = $post->post_parent;
			if ( 'pf_feed' === get_post_type( $feed_id ) ) {
				$data->add_links(
					array(
						'feed' => array(
							'href'       => rest_url( '/pf/v1/feeds/' . $feed_id ),
							'embeddable' => true,
						),
					)
				);
			}
		}

		$nominator_array = pressforward( 'utility.forward_tools' )->get_post_nominator_array( $post->ID );

		if ( $nominator_array ) {
			$data->add_links(
				[
					'nominator' => array_map(
						function ( $nominator ) {
							return array(
								'href'       => rest_url( '/wp/v2/user/' . $nominator['user_id'] ),
								'embeddable' => false,
							);
						},
						$nominator_array
					),
				]
			);
		}

		return $data;
	}

	/**
	 * Registers meta fields for post endpoint.
	 *
	 * @return void
	 */
	public function register_rest_fields() {
		// Most REST fields are registered by the central meta schema.
		$this->register_rest_post_read_meta_fields();

		register_rest_field(
			$this->post_type,
			'nominators',
			array(
				'get_callback' => function ( $post_array ) {
					return pressforward( 'utility.forward_tools' )->get_post_nominator_array( $post_array['id'] );
				},
			)
		);
	}

	/**
	 * Registers 'item_id' post field.
	 */
	public function item_id() {
		$this->register_rest_post_read_field( 'item_id', true );
	}
}
