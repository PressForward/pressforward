<?php
namespace PressForward\Core\API;

use PressForward\Controllers\Metas;

class APIWithMetaEndpoints {

	public function pf_route( $endpoint = '' ) {
		return 'pf/v1' . $endpoint;
	}

	public function valid_metas() {
		$metas      = $this->metas->structure();
		$post_metas = array();
		foreach ( $metas as $meta ) {
			// Don't use the serialized array.
			if ( $meta['name'] === 'pf_meta' ) {
				continue;
			}
			// Only use Post level data
			if ( ! in_array( $this->level, $meta['level'] ) ) {
				continue;
			}
			// Don't use metas that belong elsewhere
			if ( ! empty( $meta['move'] ) ) {
				continue;
			}
			// Only use metas marked for use in the top level API.
			if ( ! in_array( 'api', $meta['use'] ) ) {
				continue;
			}
			// Don't use metas marked as depreciated.
			if ( in_array( 'dep', $meta['type'] ) ) {
				continue;
			}
			$post_metas[] = $meta['name'];
		}
		return $post_metas;
	}

	/**
	 * Get valid metas for this post object type and register them as api fields
	 *
	 * @return [type] [description]
	 */
	public function register_rest_post_read_meta_fields() {
		global $wp_rest_server;
		// https://github.com/PressForward/pressforward/issues/859#issuecomment-257587107
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


	public function register_rest_post_read_field( $key, $action = false ) {
		// http://v2.wp-api.org/extending/modifying/
		if ( ! $action ) {
			$action = array( $this, $key . '_response' ); }
		if ( true === $action ) {
			$action = array( $this, 'meta_response' ); }
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


	public function meta_response( $object, $field_name, $request ) {
		$response = $this->metas->get_post_pf_meta( $object['id'], $field_name, true );
		if ( empty( $response ) || is_wp_error( $response ) ) {
			return 'false';
		} else {
			return $response;
		}
	}

	public function filter_an_api_data_link( $data, $links, $link, $term ) {
		if ( isset( $links[ $link ] ) ) {
			foreach ( $links[ $link ] as $key => $term_link ) {
				$pos = strpos( $term_link['href'], 'wp/v2/' . $term );
				if ( false !== $pos && 0 <= $pos ) {
					$term_found = true;
					$data->remove_link( $link );
					// var_dump('term found', $term);
					$term_link['href']      = str_replace( 'wp/v2/' . $term, 'pf/v1/' . $term, $term_link['href'] );
					$links[ $link ][ $key ] = $term_link;
					// var_dump($links);
				} else {
					$term_found = false;
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

	// Hook to filter 'rest_prepare_{post_type}' to actifate
	public function filter_wp_to_pf_in_terms( $data, $post, $request ) {
		// var_dump($data->add_link('https://api.w.org/term', array())); die();
		$links = $data->get_links();
		if ( isset( $links['https://api.w.org/term'] ) ) {
			$data->remove_link( 'https://api.w.org/term' );
			foreach ( $links['https://api.w.org/term'] as $key => $term_link ) {
				if ( 0 <= strpos( $term_link['href'], 'wp/v2/folders' ) ) {
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
		// var_dump($links); die();
		if ( isset( $links['https://api.w.org/post_type'] ) ) {
			$data->remove_link( 'https://api.w.org/post_type' );
			foreach ( $links['https://api.w.org/post_type'] as $key => $term_link ) {
				if ( 0 <= strpos( $term_link['href'], 'wp/v2/feeds' ) ) {
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
				if ( 0 <= strpos( $term_link['href'], 'wp/v2/folders' ) ) {
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
		// var_dump($data->get_links());
		return $data;
	}
}
