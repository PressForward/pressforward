<?php

require_once(RSSPF_ROOT . "/includes/linkfinder/AB_subscription_builder.php");

class RSSPF_AB_Subscribe extends RSSPF_Module {

	/////////////////////////////
	// PARENT OVERRIDE METHODS //
	/////////////////////////////

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::start();
		add_action( 'wp_ajax_refresh_ab_feeds', array( $this, 'refresh_ab_feeds_callback' ) );
		add_action( 'wp_ajax_finish_ab_feeds', array( $this, 'finish_ab_feeds_callback' ) );
	}

	function add_to_feeder(){
		echo '<h3>academicblogs.org</h3>';
		$this->render_refresh_ui();
		echo $this->build_ab_item_selector();
	}

	public function build_ab_item_selector() {
		$ABLinksArray = get_option( 'rsspf_ab_categories' );
		$ca = 0;
		$cb = 0;
		$cc = 0;

		if ( ! $ABLinksArray ) {
			return "No blogs found. Try refreshing the academicblogs.org list.";
		}

		// Echo the ABLinksArray array into a JS object
		$ab_items_selector  = '<script type="text/javascript">';
		$ab_items_selector .= 'var ABLinksArray = "' . addslashes( json_encode( $ABLinksArray ) ) . '";';
		$ab_items_selector .= '</script>';

		// Build the top-level dropdown
		$ab_items_selector .= '<label for="ab-cats">' . __( 'Category', 'rsspf' ) . '</label>';
		$ab_items_selector .= '<select class="ab-dropdown" name="ab-cats" id="ab-cats">';
		foreach ( (array) $ABLinksArray['categories'] as $cat_slug => $cat ) {
			$ab_items_selector .= '<option value="' . esc_attr( $cat_slug ) . '">' . esc_html( $cat['text'] ) . '</option>';
		}
		$ab_items_selector .= '</select>';

		// Add dummy dropdowns for subcategories
		$ab_items_selector .= '<label for="ab-subcats">' . __( 'Subcategory', 'rsspf' ) . '</label>';
		$ab_items_selector .= '<select class="ab-dropdown" name="ab-subcats" id="ab-subcats" disabled="disabled"><option>-</option></select>';

		// Add dummy dropdowns for blogs
		$ab_items_selector .= '<label for="ab-blogs">' . __( 'Blog', 'rsspf' ) . '</label>';
		$ab_items_selector .= '<select class="ab-dropdown" name="ab-blogs" id="ab-blogs" disabled="disabled"><option>-</option></select>';

/*
		foreach ( (array) $ABLinksArray['categories'] as $genSubject){
			if ($ca == 0){
				$ab_items_selector .= '<option disabled="disabled" value="0">----topic----<hr /></option>';
			}

			$ab_items_selector .= '<option value="' . $genSubject['slug'] . '">' . $genSubject['text'] . ' - ' . $ca . '</option>';
			if ($ca == 0){
				$ab_items_selector .= '<option disabled="disabled" value="0">--------<hr /></option>';
				$cb = 0;
			}
			$ca++;

			foreach ( (array) $genSubject['links'] as $subject){
				//if ($cb == 0){
					$ab_items_selector .= '<option disabled="disabled" value="0">----section----<hr /></option>';
				//}
				$ab_items_selector .= '<option value="' . $subject['slug'] . '">&nbsp;&nbsp;&nbsp;' . $subject['title'] . ' - ' . $cb . '</option>';

				$ab_items_selector .= '<option disabled="disabled" value="0">--------<hr /></option>';
				if ($cb == 0){
					$ca = 0;
					$cc = 0;
				}
				$cb++;

				if ( isset( $subject['blogs'] ) ) {
					foreach ($subject['blogs'] as $blogObj){

						$ab_items_selector .= '<option value="' . $blogObj['slug'] . '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $blogObj['title'] . ' - ' . $cc . '</option>';
						if ($cc == 0){
							//$ab_items_selector .= '<option disabled="disabled" value="0"><hr /></option>';

							$cb = 0;
						}
						$cc++;

					}
				}
			}

		}
*/
		$ab_items_selector .= '</select>';

		return $ab_items_selector;
	}

	/**
	 * Renders the UI for the "refresh feeds" section
	 */
	public function render_refresh_ui() {
		$cats = get_option( 'rsspf_ab_categories' );
		$last_refreshed = isset( $cats['last_updated'] ) ? date( 'M j, Y H:i', $cats['last_updated'] ) : 'never';
		?>

		<div id="refresh-ab-feeds">
			<p>The list of feeds from academicblogs.org was last refreshed at <strong><?php echo $last_refreshed ?></strong>. Click the Refresh button to do a refresh right now.</p>

			<a class="button" id="calc_submit">Refresh</a>

			<br />
			<div id="calc_progress"></div>
			<br />
		</div>

		<?php
	}

	/**
	 * The AJAX callback function for refreshing the academicblogs.org feeds
	 *
	 * This is called by the ajax request to 'refresh_ab_feeds' in
	 * modules/ab-subscribe/js/progressbar.js
	 *
	 * The value echoed from this function should be a percentage between
	 * 0 and 100. This value is used by the progressbar javascript to show
	 * the progress level
	 */
	public function refresh_ab_feeds_callback() {

		if ( ! isset( $_POST['start'] ) ) {
			return;
		}

		$start = intval( $_POST['start'] );

		if ( 0 === $start ) {
			// This is the beginning of a routine. Clear out previous caches
			// and refetch top-level cats
			delete_option( 'rsspf_ab_categories' );
			$cats = AB_subscription_builder::get_blog_categories();
			update_option( 'rsspf_ab_categories', $cats );

			// Set the percentage to 1%. This is sort of a fib
			$pct = 1;
		} else {
			// Anything but zero: Pull up the categories and pick
			// up where last left off
			$cats = get_option( 'rsspf_ab_categories' );
			$cats = AB_subscription_builder::add_category_links( $cats, 1 );

			if ( $cats['nodes_populated'] >= $cats['node_count'] ) {
				$cats['last_updated'] = time();
			}

			update_option( 'rsspf_ab_categories', $cats );

			// Calculate progress
			$pct = intval( 100 * ( $cats['nodes_populated'] / $cats['node_count'] ) );
		}

		echo $pct;
		die();
	}

	/**
	 * Manually set the last_updated key for the ab categories option
	 *
	 * Called by the ajax request to 'finish_ab_feeds'
	 *
	 * This is necessary because of a weird bug in the way the progressbar
	 * script works - I can't always tell on the server side whether we've
	 * hit 100%. So at the end of the process, the browser manually sends
	 * the request to finish up the process
	 */
	public function finish_ab_feeds() {
		$cats = get_option( 'rsspf_ab_categories' );
		$cats['last_updated'] = time();
		update_option( 'rsspf_ab_categories', $cats );
		die();
	}

	/**
	 * Enqueue our scripts and styles for the progressbar to work
	 */
	public function admin_enqueue_scripts() {
		global $rsspf;

		wp_enqueue_script( 'jquery-ui' );
		wp_enqueue_script( 'jquery-ui-progressbar' );
		wp_enqueue_script( 'ab-refresh-progressbar', $rsspf->modules['ab-subscribe']->module_url . 'js/progressbar.js', array( 'jquery', 'jquery-ui-progressbar') );
		wp_enqueue_script( 'ab-dropdowns', $rsspf->modules['ab-subscribe']->module_url . 'js/dropdowns.js', array( 'jquery' ) );
		wp_enqueue_style( 'ab-refresh-progressbar', $rsspf->modules['ab-subscribe']->module_url . 'css/progressbar.css' );
	}
}
