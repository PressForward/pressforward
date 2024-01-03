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
 * NominationExtension class.
 */
class NominationExtension extends APIWithMetaEndpoints implements HasActions, HasFilters {

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
		$this->post_type = 'nomination';
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
		);

		return $filter;
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
		$nominator_array = pressforward( 'utility.forward_tools' )->get_nomination_nominator_array( $post->ID );
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
					return pressforward( 'utility.forward_tools' )->get_nomination_nominator_array( $post_array['id'] );
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
