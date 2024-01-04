<?php
/**
 * REST API utilities.
 *
 * @package PressForward
 */

namespace PressForward\Core\API;

use PressForward\Controllers\Metas;

/**
 * REST API utilities.
 */
class APIWithMetaEndpoints {
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
	 * Gets endpoint route path.
	 *
	 * @param string $endpoint Endpoint path.
	 * @return string
	 */
	public function pf_route( $endpoint = '' ) {
		return 'pf/v1' . $endpoint;
	}

	/**
	 * Gets a list of registered metadata.
	 *
	 * @return array
	 */
	public function valid_metas() {
		$metas      = $this->metas->structure();
		$post_metas = array();

		foreach ( $metas as $meta ) {
			// Don't use the serialized array.
			if ( 'pf_meta' === $meta['name'] ) {
				continue;
			}

			// Only use Post level data.
			if ( ! in_array( $this->level, $meta['level'], true ) ) {
				continue;
			}

			// Don't use metas that belong elsewhere.
			if ( ! empty( $meta['move'] ) ) {
				continue;
			}

			// Only use metas marked for use in the top level API.
			if ( ! in_array( 'api', $meta['use'], true ) ) {
				continue;
			}

			// Don't use metas marked as depreciated.
			if ( in_array( 'dep', $meta['type'], true ) ) {
				continue;
			}

			$post_metas[] = $meta['name'];
		}

		return $post_metas;
	}

	/**
	 * Get valid metas for this post object type and register them as API fields.
	 */
	public function register_rest_post_read_meta_fields() {
		global $wp_rest_server;

		// https://github.com/PressForward/pressforward/issues/859#issuecomment-257587107.
		if ( isset( $wp_rest_server ) ) {
			$routes = $wp_rest_server->get_routes();
			if ( ( 'post' === $this->level ) && ( ! isset( $routes['/wp/v2/posts'] ) ) ) {
				return false;
			}
		} else {
			return false;
		}

		foreach ( $this->valid_metas() as $key ) {
			$this->register_rest_post_read_field( $key, true );
		}
	}

	/**
	 * Registers meta fields for display in the API.
	 *
	 * @param string      $key    Meta key.
	 * @param bool|string $action Action.
	 */
	public function register_rest_post_read_field( $key, $action = false ) {
		// http://v2.wp-api.org/extending/modifying/.
		if ( ! $action ) {
			$action = array( $this, $key . '_response' );
		}

		if ( true === $action ) {
			$action = array( $this, 'meta_response' );
		}

		register_rest_field(
			$this->post_type,
			$key,
			array(
				'get_callback'    => $action,
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * Generates a meta response for a field.
	 *
	 * @param array                      $the_object Meta information.
	 * @param string                     $field_name Field name.
	 * @param \WP_REST_Request|\WP_Error $request    Request object.
	 * @return mixed
	 */
	public function meta_response( $the_object, $field_name, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$response = $this->metas->get_post_pf_meta( $the_object['id'], $field_name, true );
		if ( empty( $response ) || is_wp_error( $response ) ) {
			return 'false';
		} else {
			return $response;
		}
	}

	/**
	 * Generates full API link from passed parameters.
	 *
	 * @param \WP_REST_Response $data  The response object.
	 * @param array             $links Links.
	 * @param string            $link  Link.
	 * @param string            $term  Term endpoint.
	 * @return \WP_REST_Response
	 */
	public function filter_an_api_data_link( $data, $links, $link, $term ) {
		if ( isset( $links[ $link ] ) ) {
			$term_found = false;
			foreach ( $links[ $link ] as $key => $term_link ) {
				$pos = strpos( $term_link['href'], 'wp/v2/' . $term );
				if ( false !== $pos ) {
					$term_found = true;
					$data->remove_link( $link );
					$term_link['href']      = str_replace( 'wp/v2/' . $term, 'pf/v1/' . $term, $term_link['href'] );
					$links[ $link ][ $key ] = $term_link;
				}
			}

			if ( $term_found ) {
				$data->add_links(
					array(
						$link => $links[ $link ],
					)
				);
			}
		}

		return $data;
	}

	/**
	 * Callback to add PF data to 'links' object on API response.
	 *
	 * Hook to filter 'rest_prepare_{post_type}' to activate.
	 *
	 * @param \WP_REST_Response $data    Response object.
	 * @param \WP_Post          $post    Post object.
	 * @param \WP_REST_Request  $request Request object.
	 * @return \WP_REST_Response
	 */
	public function filter_wp_to_pf_in_terms( $data, $post, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$links = $data->get_links();
		if ( isset( $links['https://api.w.org/term'] ) ) {
			$data->remove_link( 'https://api.w.org/term' );
			foreach ( $links['https://api.w.org/term'] as $key => $term_link ) {
				if ( false === strpos( $term_link['href'], 'wp/v2/folders' ) ) {
					$term_link['href']                       = str_replace( 'wp/v2/folders', 'pf/v1/folders', $term_link['href'] );
					$links['https://api.w.org/term'][ $key ] = $term_link;
				}
			}
			$data->add_links(
				array(
					'https://api.w.org/term' => $links['https://api.w.org/term'],
				)
			);
		}
		if ( isset( $links['https://api.w.org/post_type'] ) ) {
			$data->remove_link( 'https://api.w.org/post_type' );
			foreach ( $links['https://api.w.org/post_type'] as $key => $term_link ) {
				if ( false === strpos( $term_link['href'], 'wp/v2/feeds' ) ) {
					$term_link['href']                            = str_replace( 'wp/v2/feeds', 'pf/v1/feeds', $term_link['href'] );
					$links['https://api.w.org/post_type'][ $key ] = $term_link;
				}
			}
			$data->add_links(
				array(
					'https://api.w.org/post_type' => $links['https://api.w.org/post_type'],
				)
			);
		}

		if ( isset( $links['https://api.w.org/items'] ) ) {
			$data->remove_link( 'https://api.w.org/items' );
			foreach ( $links['https://api.w.org/items'] as $key => $term_link ) {
				if ( false === strpos( $term_link['href'], 'wp/v2/folders' ) ) {
					$term_link['href']                        = str_replace( 'wp/v2/folders', 'pf/v1/folders', $term_link['href'] );
					$links['https://api.w.org/items'][ $key ] = $term_link;
				}
			}
			$data->add_links(
				array(
					'https://api.w.org/items' => $links['https://api.w.org/items'],
				)
			);
		}

		$data = $this->filter_an_api_data_link( $data, $links, 'about', 'taxonomies/pf_feed_category' );
		$data = $this->filter_an_api_data_link( $data, $links, 'about', 'types/pf_feed' );
		$data = $this->filter_an_api_data_link( $data, $links, 'about', 'types/pf_feed_item' );
		return $data;
	}
}
