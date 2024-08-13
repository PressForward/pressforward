<?php
/**
 * Stats utilities.
 *
 * @package PressForward
 */

namespace PressForward\Controllers;

use stdClass;
use WP_Ajax_Response;
use WP_Error;
use WP_Query;
use PressForward\Controllers\Metas;

/**
 * Stats utilities.
 */
class Stats {
	/**
	 * Slug.
	 *
	 * @access public
	 * @var string
	 */
	public $slug;

	/**
	 * Title.
	 *
	 * @access public
	 * @var string
	 */
	public $title;

	/**
	 * Root path.
	 *
	 * @access public
	 * @var string
	 */
	public $root;

	/**
	 * File path.
	 *
	 * @access public
	 * @var string
	 */
	public $file_path;

	/**
	 * URL.
	 *
	 * @access public
	 * @var string
	 */
	public $url;

	/**
	 * Stats version.
	 *
	 * @access public
	 * @var float
	 */
	public $ver;

	/**
	 * Feed post type.
	 *
	 * @access public
	 * @var string
	 */
	public $feed_post_type;

	/**
	 * Meta key for stats.
	 *
	 * @access public
	 * @var string
	 */
	public $meta_key;

	/**
	 * Metas object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Metas
	 */
	public $metas;

	/**
	 * Meta key for author.
	 *
	 * @access public
	 * @var string
	 */
	public $meta_author_key;

	/**
	 * Not used.
	 *
	 * @access public
	 * @var mixed
	 */
	public $base;

	/**
	 * Not used.
	 *
	 * @access public
	 * @var mixed
	 */
	public $access;

	/**
	 * Shortcodes object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\Stats_Shortcodes
	 */
	public $shortcodes;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Controllers\Metas $metas Metas object.
	 * @return void
	 */
	public function __construct( Metas $metas ) {
		$shortcodes  = new Stats_Shortcodes();
		$this->metas = $metas;
		$this->define_constants();
		$this->base();
		$this->includes();
		$this->access();
		$this->shortcodes( $shortcodes );
	}

	/**
	 * Sets up data for the Stats engine.
	 *
	 * @return void
	 */
	private function define_constants() {
		$this->slug            = 'pf_stats';
		$this->title           = __( 'PressForward Stats', 'pressforward' );
		$this->root            = PF_ROOT;
		$this->file_path       = $this->root . '/' . basename( __FILE__ );
		$this->url             = plugins_url( '/', __FILE__ );
		$this->ver             = 1.0;
		$this->feed_post_type  = 'pf_feed_item';
		$this->meta_key        = pressforward( 'controller.metas' )->get_key( 'item_id' );
		$this->meta_author_key = 'item_author';
	}

	/**
	 * Deprecated.
	 */
	private function includes() {}

	/**
	 * Not used.
	 */
	public function base() {}

	/**
	 * Not used.
	 */
	public function access() {}

	/**
	 * Sets up shortcodes for stats.
	 *
	 * @param \PressForward\Controllers\Stats_Shortcodes $shortcodes Shortcodes object.
	 */
	public function shortcodes( $shortcodes ) {
		$this->shortcodes = $shortcodes;
	}

	/**
	 * Gets the meta_query args for the stats query.
	 *
	 * @return array
	 */
	private function meta_query_args() {
		return array(
			'pf_item_check' => array(
				'key'     => $this->metas->get_key( 'item_id' ),
				'compare' => 'EXISTS',
			),
		);
	}

	/**
	 * Gets the post_status value for the stats query.
	 *
	 * @return string
	 */
	private function post_status_query_args() {
		return 'publish';
	}

	/**
	 * Gets the post type for the stats query.
	 *
	 * @return string
	 */
	private function post_type_for_query() {
		return pressforward_draft_post_type();
	}

	/**
	 * Creates argument array for a WP_Query.
	 *
	 * @param array $wp_query_args Overrides for default argument values.
	 * @return array
	 */
	private function establish_query( $wp_query_args ) {
		$default_meta_query = $this->meta_query_args();
		if ( isset( $wp_query_args['meta_query'] ) ) {
			$meta_query = wp_parse_args( $wp_query_args['meta_query'], $default_meta_query );
			unset( $wp_query_args['meta_query'] );
		} else {
			$meta_query = $default_meta_query;
		}
		$status_check = $this->post_status_query_args();
		$default_args = array(
			'posts_per_page' => 40,
			'post_type'      => $this->post_type_for_query(),
			'post_status'    => $status_check,
			'meta_query'     => $meta_query,
			'paged'          => 1,
		);
		$args         = wp_parse_args( $wp_query_args, $default_args );
		if ( ( isset( $args['posts_per_page'] ) && ( $args['posts_per_page'] < 0 ) ) || ( isset( $args['offset'] ) && $args['offset'] < 0 ) ) {
			$args['posts_per_page'] = -1;
			unset( $args['offset'] );
		}
		return $args;
	}

	/**
	 * Creates a WP_Query object for compiling stats.
	 *
	 * @param array $wp_query_args Arguments to pass to WP_Query.
	 * @return WP_Query
	 */
	public function stats_query_for_pf_published_posts( $wp_query_args ) {
		$args = $this->establish_query( $wp_query_args );

		do_action( 'pf_stats_query_before', $args );
		$args = apply_filters( 'pf_qualified_stats_post_query', $args );

		$q = new \WP_Query( $args );

		do_action( 'pf_stats_query_after', $q );
		return $q;
	}

	/**
	 * Loads info for a specific author into the leaderboard.
	 *
	 * @param int   $id      ID of the user.
	 * @param array $authors Array of author info.
	 * @return array
	 */
	public function set_author_into_leaderboard( $id, $authors ) {
		$author                = $this->metas->get_post_pf_meta( $id, $this->meta_author_key );
		$author_and_test       = explode( ' and ', $author );
		$author_comma_test     = explode( ',', $author );
		$author_ampersand_test = explode( '&', $author );
		if ( 1 < count( $author_and_test ) || 1 < count( $author_comma_test ) || 1 < count( $author_ampersand_test ) ) {
			if ( 1 < count( $author_and_test ) ) {
				$author_set = $author_and_test;
			} elseif ( 1 < count( $author_comma_test ) ) {
				$author_set = $author_comma_test;
			} elseif ( 1 < count( $author_ampersand_test ) ) {
				$author_set = $author_ampersand_test;
			} else {
				$author_set = array( $author );
			}
			foreach ( $author_set as $auther_from_set ) {
				$author_slug = str_replace( ' ', '_', trim( strtolower( $auther_from_set ) ) );
				if ( ! empty( $authors[ $author_slug ] ) ) {
					$authors = $this->set_author_count( $author_slug, $authors );
				} else {
					$authors = $this->set_new_author_object( $author_slug, $auther_from_set, $authors );
				}
			}
			return $authors;
		}
		$author_slug = str_replace( ' ', '_', strtolower( $author ) );
		if ( ! empty( $authors[ $author_slug ] ) ) {
			$authors = $this->set_author_count( $author_slug, $authors );
		} else {
			$authors = $this->set_new_author_object( $author_slug, $author, $authors );
		}

		return $authors;
	}

	/**
	 * Increases author count by 1.
	 *
	 * @param string $author_slug Author slug.
	 * @param array  $authors     Array of author info.
	 * @return array
	 */
	private function set_author_count( $author_slug, $authors ) {
		$authors[ $author_slug ]['count'] = $authors[ $author_slug ]['count'] + 1;
		return $authors;
	}

	/**
	 * Sets up data about an author.
	 *
	 * @param string $author_slug Slug for the author, used as a lookup key.
	 * @param string $author      Author name.
	 * @param array  $authors     Existing list of authors.
	 * @return array
	 */
	private function set_new_author_object( $author_slug, $author, $authors ) {
		$authors[ $author_slug ] = array(
			'count' => 1,
			'name'  => $author,
		);

		return $authors;
	}

	/**
	 * Gets a set of stat counts.
	 *
	 * @param array $args       Arguments for WP_Query.
	 * @param array $date_query Optional. Date query args.
	 * @return array
	 */
	public function counts( $args, $date_query = array() ) {
		$query                   = $args;
		$query['date_query']     = $date_query;
		$query['posts_per_page'] = 20;

		$r = array();

		$posts_q_args   = $query;
		$posts_q        = $this->stats_query_for_pf_published_posts( $posts_q_args );
		$r['published'] = array(
			'count' => $posts_q->found_posts,
		);

		$nominations_q_args                = $this->establish_query( $query );
		$nominations_q_args['post_status'] = 'any';
		$nominations_q_args['post_type']   = pressforward( 'schema.nominations' )->post_type;
		$nominations_q                     = new WP_Query( $nominations_q_args );
		$r['nominations']                  = array(
			'count' => $nominations_q->found_posts,
		);

		$items_q_args              = $this->establish_query( $query );
		$items_q_args['post_type'] = pressforward( 'schema.feed_item' )->post_type;
		$items_q                   = new WP_Query( $items_q_args );
		$r['items']                = array(
			'count' => $items_q->found_posts,
		);

		return $r;
	}
}
