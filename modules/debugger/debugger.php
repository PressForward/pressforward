<?php
/**
 * Test of module base class.
 *
 * @package PressForward
 */

/**
 * PF_Debugger class.
 */
class PF_Debugger extends PF_Module {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::start();
		add_filter( 'pf_setup_admin_rights', array( $this, 'control_menu_access' ) );
	}

	/**
	 * Register the admin menu items.
	 *
	 * The parent class will take care of registering them.
	 *
	 * @param array $admin_menus Admin menus.
	 */
	public function setup_admin_menus( $admin_menus ) {
		$admin_menus = array();

		$admin_menus[] = array(
			'page_title' => __( 'View Log', 'pressforward' ),
			'menu_title' => __( 'View Log', 'pressforward' ),
			'cap'        => get_option( 'pf_menu_log_access', pf_get_defining_capability_by_role( 'administrator' ) ),
			'slug'       => 'pf-debugger',
			'callback'   => array( $this, 'admin_menu_callback' ),
		);

		parent::setup_admin_menus( $admin_menus );
	}

	/**
	 * Sets up module.
	 */
	public function setup_module() {
		$enabled = 'no' === get_option( 'pf_debugger_enable' ) ? 'no' : 'yes';

		$mod_settings = array(
			'name'    => __( 'Debugger Log Viewing Module', 'pressforward' ),
			'slug'    => 'debugger',
			'options' => '',
		);
	}

	/**
	 * Default settings for the module.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return array(
			'name'        => __( 'Debugger Log Viewing Module', 'pressforward' ),
			'slug'        => 'debugger',
			'description' => __( 'This module provides a way to view the feed retrieval log within the dashboard.', 'pressforward' ),
			'thumbnail'   => '',
			'options'     => '',
		);
	}

	/**
	 * Counts the post in a post type.
	 *
	 * @param string $post_type Post type.
	 * @param int    $date_less Number of months.
	 * @return int
	 */
	public function count_the_posts( $post_type, $date_less = 0 ) {
		if ( ! $date_less ) {
			$y = gmdate( 'Y' );
			$m = gmdate( 'm' );
		} elseif ( $date_less < 12 ) {
			$y = gmdate( 'Y' );
			$m = gmdate( 'm' );
			$m = (int) $m + (int) $date_less;
		} else {
			// $date_less >= 12.
			$y = gmdate( 'Y' );
			$y = (int) $y - floor( $date_less / 12 );
			$m = gmdate( 'm' );
			$m = (int) $m - ( abs( $date_less ) - ( 12 * floor( $date_less / 12 ) ) );
		}

		$posts_per_page = 200;

		$query_arg = array(
			'post_type'      => $post_type,
			'year'           => (int) $y,
			'monthnum'       => (int) $m,
			'posts_per_page' => $posts_per_page,
			'offset'         => 0,
			'cache_results'  => false,
			'no_found_rows'  => true,
			'fields'         => 'ids',
		);

		$total  = 0;
		$last_q = $posts_per_page;

		while ( $last_q >= $posts_per_page ) {
			$count  = $this->query_counter( $query_arg );
			$last_q = $count;

			$total += $count;

			$query_arg['offset'] = $query_arg['offset'] + $posts_per_page;
		}

		return $total;
	}

	/**
	 * Query counter.
	 *
	 * @param array $query_arg      Query args.
	 * @param int   $posts_per_page Posts per page.
	 * @return int
	 */
	public function query_counter( $query_arg, $posts_per_page = 200 ) {
		if ( empty( $query_arg['offset'] ) ) {
			$query_arg['offset'] = 0;
		}

		if ( empty( $query_arg['posts_per_page'] ) ) {
			$query_arg['posts_per_page'] = $posts_per_page;
		}

		$query = new WP_Query( $query_arg );
		$total = 0;

		$pc = $query->post_count;

		wp_reset_postdata();

		$total += $pc;

		return $total;
	}

	/**
	 * Admin menu callback.
	 */
	public function admin_menu_callback() {
		global $wpdb;

		// Default log location is in the uploads directory.
		if ( ! defined( 'PF_DEBUG_LOG' ) ) {
			$upload_dir = wp_upload_dir();
			$log_path   = $upload_dir['basedir'] . '/pressforward.log';
		} else {
			$log_path = PF_DEBUG_LOG;
		}

		// phpcs:ignore WordPress.DB
		$action_count = $wpdb->get_results( "SELECT COUNT(*) FROM {$wpdb->prefix}pf_relationships", ARRAY_A );

		$ntp_args = array(
			'posts_per_page' => -1,
			'meta_key'       => 'item_link',
			'fields'         => 'ids',
		);

		$nominated_to_posts = get_posts( $ntp_args );

		$nomed_posts = (string) count( $nominated_to_posts );

		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Current Log', 'pressforward' ); ?></h2>
			<p><?php esc_html_e( 'Does not update in real time.', 'pressforward' ); ?></p>
			<p><?php esc_html_e( 'Total Current Feed Items', 'pressforward' ); ?>:
			<?php
				$feed_item = 'pf_feed_item';
				echo esc_html( (string) wp_count_posts( $feed_item )->publish );
			?>
			<br />
			<?php
				$feed_item = 'pf_feed_item';
				echo 'Month to date Feed Items: ' . esc_html( (string) $this->count_the_posts( $feed_item ) );
				echo '<br />Last month Feed Items: ' . esc_html( (string) $this->count_the_posts( $feed_item, -1 ) );
			?>
			</p>
			<p><?php esc_html_e( 'Total Current Nominations:', 'pressforward' ); ?>
			<?php
				echo esc_html( wp_count_posts( 'nomination' )->draft );
				echo '<br />Month to date Nominations: ' . esc_html( (string) $this->count_the_posts( 'nomination' ) );
				echo '<br />Last month Nominations: ' . esc_html( (string) $this->count_the_posts( 'nomination', -1 ) );

			?>
			</p>
			<p><?php esc_html_e( 'Total Actions Taken:', 'pressforward' ); ?>
			<?php
				echo esc_html( current( $action_count[0] ) );
			?>
			</p>
			<p><?php esc_html_e( 'Total Nominations Published:', 'pressforward' ); ?>
			<?php
				echo esc_html( $nomed_posts );
			?>
			</p>
			<p><?php esc_html_e( 'Total Retrieval Chunks Begun This:', 'pressforward' ); ?>
			<?php
				pf_iterate_cycle_state( 'retrieval_chunks_begun', '', true );
			?>
			</p>
			<p><?php esc_html_e( 'Total Retrieval Cycles Begun This:', 'pressforward' ); ?>
			<?php
				pf_iterate_cycle_state( 'retrieval_cycles_begun', '', true );
			?>
			</p>
			<p><?php esc_html_e( 'Total Retrieval Cycles Ended This:', 'pressforward' ); ?>
			<?php
				pf_iterate_cycle_state( 'retrieval_cycles_ended', '', true );
			?>
			</p>
			<br /><br />
			<?php

			if ( file_exists( $log_path ) ) {

				// If the log file is larger than 5MB, we will only grab the last 100,000 characters.
				$log_file_size = filesize( $log_path );
				$log_file_note = '';
				if ( $log_file_size > 5000000 ) {
					$log_content   = $this->fetch_last_characters( $log_path, 100000 );
					$log_file_note = __( 'Log file is larger than 5MB. Only the last 100,000 characters are shown. Use SSH or SFTP to access the entire log file.', 'pressforward' );
				} else {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					$log_content = file_get_contents( $log_path );
				}

				if ( $log_file_note ) {
					echo '<p class="description">' . esc_html( $log_file_note ) . '</p>';
				}

				// Place inside a scrollable window.
				echo '<div style="overflow: auto; height: 500px; border: 2px solid #aaa; padding: 8px;">';
				echo '<pre>' . esc_html( $log_content ) . '</pre>';
				echo '</div>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Fetches the last characters of a file.
	 *
	 * @param string $filepath File path.
	 * @param int    $number_of_characters Number of characters.
	 * @return string
	 */
	protected function fetch_last_characters( $filepath, $number_of_characters = 10000 ) {
		// phpcs:disable WordPress.WP.AlternativeFunctions

		// Open the file.
		$f = fopen( $filepath, 'rb' );
		if ( false === $f ) {
			return '';
		}

		// Move to the desired position in the file, based on $number_of_characters.
		fseek( $f, -$number_of_characters, SEEK_END );

		// Read the desired number of characters from the current position till the end of the file.
		$output = fread( $f, $number_of_characters );

		// Close file and return.
		fclose( $f );
		return $output;
		// phpcs:enable WordPress.WP.AlternativeFunctions
	}

	/**
	 * If this module has any styles to enqueue, do it in a method.
	 *
	 * If you have no styles, etc, just ignore this
	 */
	public function admin_enqueue_styles() {
		wp_register_style( PF_SLUG . '-debug-style', PF_URL . 'includes/debugger/css/style.css', [], $GLOBALS['wp_version'] );
	}

	/**
	 * Enqueues scripts for the admin.
	 */
	public function admin_enqueue_scripts() {
		global $pagenow;

		$hook = 0 !== func_num_args() ? func_get_arg( 0 ) : '';

		if ( ! in_array( $pagenow, array( 'admin.php' ), true ) ) {
			return;
		}

		if ( ! in_array( $hook, array( 'pressforward_page_pf-tools' ), true ) ) {
			return;
		}

		wp_enqueue_script( 'feed_control_script', PF_URL . '/assets/js/feeds_control.js', array( 'jquery', PF_SLUG . '-twitter-bootstrap' ), PF_VERSION, true );
	}

	/**
	 * Registers admin rights for this module.
	 *
	 * @param array $admin_rights Admin rights.
	 * @return array
	 */
	public function control_menu_access( $admin_rights ) {
		$admin_rights['pf_menu_log_access'] = array(
			'default' => 'administrator',
			'title'   => __( 'Debugging Log', 'pressforward' ),
			'details' => __( 'Debugging sets the user role required to access the Debugging page from the PressForward menu when it is turned on.', 'pressforward' ),
		);

		return $admin_rights;
	}
}
