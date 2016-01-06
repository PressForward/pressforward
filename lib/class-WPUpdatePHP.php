<?php
// via https://github.com/WPupdatePHP/wp-update-php
class PF_WPUpdatePHP {
	/** @var String */
	private $minimum_version;

	/**
	 * @param $minimum_version
	 */
	public function __construct( $minimum_version ) {
		$this->minimum_version = $minimum_version;
	}

	/**
	 * @param $version
	 *
	 * @return bool
	 */
	public function does_it_meet_required_php_version(  ) {
		if ( $this->is_minimum_php_version( phpversion() ) ) {
			return true;
		}

		$this->load_minimum_required_version_notice();
		return false;
	}

	/**
	 * @param $version
	 *
	 * @return boolean
	 */
	private function is_minimum_php_version( $version ) {
		return version_compare( $this->minimum_version, $version, '<' );
	}

	/**
	 * @return void
	 */
	private function load_minimum_required_version_notice() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		}
	}

	public function admin_notice() {
		echo '<div class="error">';
		echo '<p>Unfortunately, this plugin should be run on PHP versions newer than '. $this->minimum_version .'. Read more information about <a href="http://www.wpupdatephp.com/update/">how you can update</a>.</p>';
		echo '</div>';
	}
}
