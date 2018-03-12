<?php
namespace PressForward\Controllers;

use WP_Query;

class Stats_Shortcodes {

	public function __construct() {

		add_shortcode( 'pf_wordcount_last_thirty', array( $this, 'pf_wordcount_last_thirty' ) );
		add_shortcode( 'pf_wordcount_all', array( $this, 'pf_wordcount_all' ) );
		add_shortcode( 'pf_author_leaderboard', array( $this, 'author_leaderboard' ) );

	}

	public function pf_wordcount_all() {
		if ( $s = $this->check_pf_transient( 'wc_all' ) ) {
			$wc        = 0;
			$c         = 0;
			$the_query = new WP_Query(
				array(
					// 'post_type' => 'pf_feed_item',
														'meta_key' => pressforward_stats()->meta_key,
					'posts_per_page' => -1,
					'no_found_rows'  => true,
					'cache_results'  => false,

				)
			);
			if ( $the_query->have_posts() ) :

				while ( $the_query->have_posts() ) :
					$the_query->the_post();
					$content    = get_post_field( 'post_content', get_the_ID() );
					$word_count = str_word_count( strip_tags( $content ) );
					$wc         = $wc + $word_count;
					$c++;
					endwhile;

				wp_reset_postdata();

				$s = $this->the_shortcode(
					'pf_wordcount_all', array(
						'word_count' => $wc,
						'count'      => $c,
						'days'       => 'all',
					)
				);

			else :

				$s = $this->the_shortcode( 'read_nothing', array( 'days' => '30' ) );

			endif;
			$this->set_pf_transient( 'wc_all', $s );
		}
		return $s;
	}

	// Add Shortcode
	public function pf_wordcount_last_thirty() {
		if ( $s = $this->check_pf_transient( 'last_30' ) ) {
			$wc        = 0;
			$c         = 0;
			$week      = date( 'W' );
			$year      = date( 'Y' );
			$the_query = new WP_Query(
				array(
					// 'post_type' => 'pf_feed_item',
														'nopaging' => true,
					'no_found_rows' => true,
					'cache_results' => false,
					'meta_key'      => pressforward_stats()->meta_key,
					'date_query'    => array(
						array(
							'after' => '-2 months',
						),
					),

				)
			);
			// var_dump($the_query);
			if ( $the_query->have_posts() ) :

				while ( $the_query->have_posts() ) :
					$the_query->the_post();
					$content    = get_post_field( 'post_content', get_the_ID() );
					$word_count = str_word_count( strip_tags( $content ) );
					$wc         = $wc + $word_count;
					$c++;
					endwhile;

				wp_reset_postdata();

				$s = $this->the_shortcode(
					'pf_wordcount_last_thirty', array(
						'word_count' => $wc,
						'count'      => $c,
						'days'       => '30',
					)
				);

			else :

				$s = $this->the_shortcode( 'read_nothing', array( 'days' => '30' ) );

			endif;

			$this->set_pf_transient( 'last_30', $s );
		}
		return $s;
	}

	public function author_leaderboard() {
		if ( $s = $this->check_pf_transient( 'author_leader' ) ) {
			$c         = 0;
			$the_query = new WP_Query(
				array(
					// 'post_type' => 'pf_feed_item',
														'nopaging' => true,
					'no_found_rows'  => true,
					'cache_results'  => false,
					'posts_per_page' => -1,
					'meta_key'       => pressforward_stats()->meta_author_key,
				)
			);

			$authors = array();

			if ( $the_query->have_posts() ) :

				while ( $the_query->have_posts() ) :
					$the_query->the_post();
					$authors = $this->set_author_into_leaderboard( get_the_ID(), $authors );
					$c++;
				endwhile;

				wp_reset_postdata();

				$s = $this->the_shortcode( 'pf_author_leaderboard', array( 'authors' => $authors ) );

			else :

				$s = $this->the_shortcode( 'read_nothing', array( 'days' => '30' ) );

			endif;

			$this->set_pf_transient( 'author_leader', $s );
		}
		return $s;

	}

	private function cmp_authors( $a, $b ) {
		return $b['count'] - $a['count'];
	}

	private function get_author_leaderboard( $authors ) {
		uasort( $authors, array( $this, 'cmp_authors' ) );
		$total                 = 0;
		$count                 = 0;
		$singles               = 0;
		$male                  = 0;
		$female                = 0;
		$unknown               = 0;
		$article_count_male    = 0;
		$article_count_female  = 0;
		$article_count_unknown = 0;
		$more_than_two         = 0;
		$leaderboard           = '<ul>';
		foreach ( $authors as $author ) {
			$total = $total + $author['count'];
			if ( $author['count'] < 2 ) {
				$singles++;
			}
			if ( $author['count'] > 2 ) {
				$more_than_two++;
			}
			if ( ! empty( $author['gender'] ) ) {
				if ( 'MALE' == $author['gender'] ) {
					$male++;
					$article_count_male += $author['count'];
				}
				if ( 'FEMALE' == $author['gender'] ) {
					$female++;
					$article_count_female += $author['count'];
				}
				if ( 'UNKNOWN' == $author['gender'] ) {
					$unknown++;
					$article_count_unknown += $author['count'];
				}
			}
			$leaderboard .= $this->add_author_leaderboard_entry( $author );
			$count++;
		}
		$leaderboard  .= '</ul>';
		$more_than_one = $count - $singles;
		$leaderboard   = "<p>$count authors over $total articles. $singles authors archived only once. $more_than_one authors archived more than once. $more_than_two authors archived more than twice.</p>\n
			<p>$female authors are probably female, writing $article_count_female articles. $male authors are probably male, writing $article_count_male articles. $unknown number of authors can't have their gender algorithmically determined, writing $article_count_unknown articles.</p>
			\n" . $leaderboard;
		return $leaderboard;
	}


	public function the_shortcode( $code, $args ) {
		$s = '';
		switch ( $code ) {
			case 'pf_wordcount_last_thirty':
				$s = sprintf( __( "I've read %1\$s words across %2\$s posts in the past %3\$s days.", 'pf' ), $args['word_count'], $args['count'], $args['days'] );
				break;
			case 'pf_wordcount_all':
				$s = sprintf( __( "I've read %1\$s words across %2\$s posts in %3\$s time.", 'pf' ), $args['word_count'], $args['count'], $args['days'] );
				break;
			case 'pf_author_leaderboard':
				$s = $this->get_author_leaderboard( $args['authors'] );
				return $s;
				break;
			case 'read_nothing':
				$s = sprintf( __( 'I\'ve read nothing in the past %s days.', 'pf' ), $args['days'] );
				break;
		}
		return '<p>' . $s . '</p>';

	}

	private function check_pf_transient( $key ) {
		if ( WP_DEBUG || ( false === ( $value = get_transient( 'pf_stats_' . $key ) ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	private function set_pf_transient( $key, $value, $time = false ) {
		$time = ( false == $time ? ( 7 * DAY_IN_SECONDS ) : $time );
		if ( ! WP_DEBUG ) {
			return set_transient( 'pf_stats_' . $key, $value, $time );
		} else {
			return false;
		}
	}

}
