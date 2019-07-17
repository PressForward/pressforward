<?php
namespace PressForward\Controllers;

use stdClass;
use WP_Ajax_Response;
use WP_Error;
use WP_Query;
use PressForward\Controllers\Metas;
use PressForward\Controllers\Stats_Shortcodes as Stats_Shortcodes;
// use \WP_REST_Controller;
class Stats {

	var $slug;
	var $title;
	var $root;
	var $file_path;
	var $url;
	var $ver;
	var $feed_post_type;
	var $meta_key;

	var $base;
	var $access;
	var $shortcodes;
	var $gender_checker;

	public function __construct( Metas $metas ) {
		$shortcodes  = new Stats_Shortcodes();
		$this->metas = $metas;
		$this->define_constants();
		$this->base();
		$this->includes();
		$this->access();
		$this->shortcodes( $shortcodes );
		$this->gender_checker();

	}
	private function define_constants() {
		$this->slug            = 'pf_stats';
		$this->title           = 'PressForward Stats';
		$this->root            = PF_ROOT;
		$this->file_path       = $this->root . '/' . basename( __FILE__ );
		$this->url             = plugins_url( '/', __FILE__ );
		$this->ver             = 1.0;
		$this->feed_post_type  = 'pf_feed_item';
		$this->meta_key        = pressforward( 'controller.metas' )->get_key( 'item_id' );
		$this->meta_author_key = 'item_author';
	}
	private function includes() {
		require_once $this->root . '/Libraries/enumeration/src/Eloquent/Enumeration/Multiton.php';
		require_once $this->root . '/Libraries/enumeration/src/Eloquent/Enumeration/Enumeration.php';
		if ( PHP_VERSION >= 5.4 ) {
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Gender.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Matchers/Traits/NameList.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Matchers/Interfaces/Matcher.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Matchers/BabyNamesWSMatch.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Matchers/RestNamesWSMatch.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Matchers/RegExpV1Match.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Matchers/RegExpV2Match.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Matchers/MetaphoneWeightedMatch.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Matchers/MetaphoneMatch.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Matchers/ListWeightedMatch.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/Matchers/ListMatch.php';
			require_once $this->root . '/Libraries/gender-checker/src/GenderEngine/GenderEngine.php';
		}
		require_once $this->root . '/Libraries/text-stats/src/DaveChild/TextStatistics/TextStatistics.php';
	}

	public function base() {

	}
	public function access() {

	}

	public function shortcodes( $shortcodes ) {
		if ( empty( $this->shortcodes ) ) {
			$this->shortcodes = $shortcodes;
		}
	}
	public function gender_checker() {
		if ( empty( $this->gender_checker ) ) {
			if ( PHP_VERSION >= 5.4 ) {
				$this->gender_checker = new \GenderEngine\GenderEngine();
			} else {
				$this->gender_checker = new stdClass();
			}
		}
	}

	private function meta_query_args() {
		return array(
			'pf_item_check' => array(
				'key'     => $this->metas->get_key( 'item_id' ),
				'compare' => 'EXISTS',
			),
		);
	}

	private function post_status_query_args() {
		$status = get_option( PF_SLUG . '_draft_post_status', 'draft' );
		if ( 'draft' !== $status ) {
			$status_check = $status;
		} else {
			$status_check = 'publish';
		}
		return $status_check;
	}

	private function post_type_for_query() {
		return get_option( PF_SLUG . '_draft_post_type', 'post' );
	}

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

	public function stats_query_for_pf_published_posts( $wp_query_args ) {

		$args = $this->establish_query( $wp_query_args );

		do_action( 'pf_stats_query_before', $args );
		// var_dump($args); die();
		$args = apply_filters( 'pf_qualified_stats_post_query', $args );
		// var_dump($args); die();
		// salon_sane()->slnm_log($args);
		$q = new \WP_Query( $args );
		do_action( 'pf_stats_query_after', $q );
		return $q;
	}

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

	private function set_author_count( $author_slug, $authors ) {
		$authors[ $author_slug ]['count'] = $authors[ $author_slug ]['count'] + 1;
		return $authors;
	}

	private function set_author_gender( $name ) {
		if ( PHP_VERSION >= 5.4 ) {
			$author_name             = (string) $name;
			$author_first_name_array = explode( ' ', $author_name );
			$author_first_name       = (string) $author_first_name_array[0];
			if ( empty( $author_first_name ) ) {
				if ( empty( $author_name ) ) {
					$author_first_name = 'No author found.';
				} else {
					$author_first_name = $name;
				}
			}
			// var_dump($author_first_name . ': ');
			$gender = $this->gender_checker->test( $author_first_name );
			return $gender;
		} else {
			return '';
		}
	}

	private function set_author_gender_confidence() {
		if ( PHP_VERSION >= 5.4 ) {
			// var_dump($gender . "\n");
			$confidence = $this->gender_checker->getPreviousMatchConfidence();
			$confidence = (string) $confidence;
			return $confidence;
		} else {
			return '';
		}
	}

	private function set_new_author_object( $author_slug, $author, $authors ) {
		$authors[ $author_slug ] = array(
			'count' => 1,
			'name'  => $author,
										// 'gender'          => $this->set_author_gender($author),
										// 'gender_confidence'   => $this->set_author_gender_confidence()
		);

		return $authors;
	}

	private function add_author_leaderboard_entry( $author ) {
		if ( empty( $author ) ) {
			$author          = array();
			$author['count'] = 0;
		}
		if ( ( empty( $author['name'] ) ) ) {
			$author['name'] = 'No author found.';
		}
		$s  = "\n<li>";
		$s .= $author['name'] . ' (' . $author['count'] . ')';
		// var_dump(pressforward_stats()->gender_checker->test($author['name']) ); var_dump( pressforward_stats()->gender_checker->getPreviousMatchConfidence() ); die();
		// $s .= ' This author is likely ' . $author['gender'] . '. Confidence: ' . $author['gender_confidence'];
		$s .= '</li>';
		return $s;
	}

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
