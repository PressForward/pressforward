<?php namespace Intraxia\Jaxion\Core;

use Intraxia\Jaxion\Contract\Core\Application as ApplicationContract;
use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;
use Intraxia\Jaxion\Contract\Core\HasShortcode;
use Intraxia\Jaxion\Contract\Core\Loader as LoaderContract;
use UnexpectedValueException;

/**
 * Class Application
 *
 * @package Intraxia\Jaxion
 */
class Application extends Container implements ApplicationContract {
	/**
	 * Define plugin version on Application.
	 */
	const VERSION = '';

	/**
	 * Singleton instance of the Application object
	 *
	 * @var Application
	 */
	protected static $instance = null;

	/**
	 * Instantiates a new Application container.
	 *
	 * The Application constructor enforces the presence of of a single instance
	 * of the Application. If an instance already exists, an Exception will be thrown.
	 *
	 * @param string $file
	 * @param array  $providers
	 *
	 * @throws ApplicationAlreadyBootedException
	 */
	public function __construct( $file, array $providers = array() ) {
		if ( null !== static::$instance ) {
			throw new ApplicationAlreadyBootedException;
		}

		static::$instance = $this;

		$this->register_constants( $file );
		$this->register_core_services();
		$this->load_i18n();

		register_activation_hook( $file, array( $this, 'activate' ) );
		register_deactivation_hook( $file, array( $this, 'deactivate' ) );

		parent::__construct( $providers );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws UnexpectedValueException
	 */
	public function boot() {
		$loader = $this->fetch( 'loader' );

		if ( ! $loader instanceof LoaderContract ) {
			throw new UnexpectedValueException;
		}

		foreach ( $this as $alias => $value ) {
			if ( $value instanceof HasActions ) {
				$loader->register_actions( $value );
			}

			if ( $value instanceof HasFilters ) {
				$loader->register_filters( $value );
			}

			if ( $value instanceof HasShortcode ) {
				$loader->register_shortcode( $value );
			}
		}

		add_action( 'plugins_loaded', array( $loader, 'run' ) );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @codeCoverageIgnore
	 */
	public function activate() {
		// no-op
	}

	/**
	 * {@inheritdoc}
	 *
	 * @codeCoverageIgnore
	 */
	public function deactivate() {
		// no-op
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return Application
	 * @throws ApplicationNotBootedException
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			throw new ApplicationNotBootedException;
		}

		return static::$instance;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function shutdown() {
		if ( null !== static::$instance ) {
			static::$instance = null;
		}
	}

	/**
	 * Sets the plugin's url, path, and basename.
	 *
	 * @param string $file
	 */
	private function register_constants( $file ) {
		$this->share( 'url', plugin_dir_url( $file ) );
		$this->share( 'path', plugin_dir_path( $file ) );
		$this->share( 'basename', $basename = plugin_basename( $file ) );
		$this->share( 'slug', dirname( $basename ) );
		$this->share( 'version', static::VERSION );
	}

	/**
	 * Registers the built-in services with the Application container.
	 */
	private function register_core_services() {
		$this->share( array( 'loader' => 'Intraxia\Jaxion\Contract\Core\Loader' ), function ( $app ) {
			return new Loader( $app );
		} );
	}

	/**
	 * Load's the plugin's translation files.
	 */
	private function load_i18n() {
		load_plugin_textdomain(
			$this->fetch( 'basename' ),
			false,
			basename( $this->fetch( 'path' ) ) . '/languages/'
		);
	}
}
