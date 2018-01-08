<?php
namespace Intraxia\Jaxion\Assets;

use Intraxia\Jaxion\Contract\Assets\Register as RegisterContract;

/**
 * Class Register
 *
 * Provides a consistent interface for registering static assets with WordPress.
 *
 * @package    Intraxia\Jaxion
 * @subpackage Register
 */
class Register implements RegisterContract {
	/**
	 * Minification string for enqueued assets.
	 *
	 * @var string
	 */
	private $min = '.min';

	/**
	 * Url to the plugin directory.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Script/plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Array of script definition arrays.
	 *
	 * @var array
	 */
	private $scripts = array();

	/**
	 * Array of style definition arrays.
	 *
	 * @var array
	 */
	private $styles = array();

	/**
	 * Instantiates a new instance of the Register class.
	 *
	 * The URL param should be relative to the plugin directory. The URL
	 * form should always end with a '/'. All asset location definitions
	 * should not begin with a slash and should be relative to the plugin's
	 * root directory. The URL provided by default from the Application
	 * class is compatible.
	 *
	 * @param string $url
	 * @param string $version
	 */
	public function __construct( $url, $version = null ) {
		$this->url     = $url;
		$this->version = $version ?: null; // Empty string should remain null.
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param bool $debug
	 */
	public function set_debug( $debug ) {
		if ( $debug ) {
			$this->min = '';
		} else {
			$this->min = '.min';
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $script
	 */
	public function register_script( $script ) {
		$this->scripts[] = $script;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $style
	 */
	public function register_style( $style ) {
		$this->styles[] = $style;
	}

	/**
	 * {@inheritDoc}
	 */
	public function enqueue_web_scripts() {
		foreach ( $this->scripts as $script ) {
			if ( in_array( $script['type'], array( 'web', 'shared' ) ) ) {
				$this->enqueue_script( $script );
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function enqueue_web_styles() {
		foreach ( $this->styles as $style ) {
			if ( in_array( $style['type'], array( 'web', 'shared' ) ) ) {
				$this->enqueue_style( $style );
			}
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $hook Passes a string representing the current page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		foreach ( $this->scripts as $script ) {
			if ( in_array( $script['type'], array( 'admin', 'shared' ) ) ) {
				$this->enqueue_script( $script, $hook );
			}
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $hook Passes a string representing the current page.
	 */
	public function enqueue_admin_styles( $hook ) {
		foreach ( $this->styles as $style ) {
			if ( in_array( $style['type'], array( 'admin', 'shared' ) ) ) {
				$this->enqueue_style( $style, $hook );
			}
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array[]
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'wp_enqueue_scripts',
				'method' => 'enqueue_web_scripts',
			),
			array(
				'hook'   => 'wp_enqueue_scripts',
				'method' => 'enqueue_web_styles',
			),
			array(
				'hook'   => 'admin_enqueue_scripts',
				'method' => 'enqueue_admin_scripts',
			),
			array(
				'hook'   => 'admin_enqueue_scripts',
				'method' => 'enqueue_admin_styles',
			),
		);
	}

	/**
	 * Enqueues an individual script if the style's condition is met.
	 *
	 * @param array  $script The script attachment callback.
	 * @param string $hook   The location hook. Only passed on admin side.
	 */
	protected function enqueue_script( $script, $hook = null ) {
		if ( $script['condition']( $hook ) ) {
			wp_enqueue_script(
				$script['handle'],
				$this->url . $script['src'] . $this->min . '.js',
				isset( $script['deps'] ) ? $script['deps'] : array(),
				$this->version,
				isset( $script['footer'] ) ? $script['footer'] : false
			);

			if ( isset( $script['localize'] ) ) {
				if ( is_callable( $script['localize'] ) ) { // @todo make all properties callables
					$script['localize'] = call_user_func( $script['localize'] );
				}

				wp_localize_script(
					$script['handle'],
					$script['localize']['name'],
					$script['localize']['data']
				);
			}
		}
	}

	/**
	 * Enqueues an individual stylesheet if the style's condition is met.
	 *
	 * @param array  $style The style attachment callback.
	 * @param string $hook  The location hook.
	 */
	protected function enqueue_style( $style, $hook = null ) {
		if ( $style['condition']( $hook ) ) {
			wp_enqueue_style(
				$style['handle'],
				$this->url . $style['src'] . $this->min . '.css',
				isset( $style['deps'] ) ? $style['deps'] : array(),
				$this->version,
				isset( $style['media'] ) ? $style['media'] : 'all'
			);
		}
	}
}
