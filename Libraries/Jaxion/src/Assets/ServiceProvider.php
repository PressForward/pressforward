<?php
namespace Intraxia\Jaxion\Assets;

use Intraxia\Jaxion\Contract\Core\Container;
use Intraxia\Jaxion\Contract\Core\ServiceProvider as ServiceProviderContract;

/**
 * Class AssetsServiceProvider
 *
 * @package Intraxia\Jaxion
 * @subpackage Assets
 */
class ServiceProvider implements ServiceProviderContract {
	/**
	 * Container to register with.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * {@inheritDoc}
	 *
	 * @param Container $container
	 */
	public function register( Container $container ) {
		$this->container = $container;

		$container->define(
			array( 'assets' => 'Intraxia\Jaxion\Contract\Assets\Register' ),
			$register = new Register( $container->fetch( 'url' ), $container->fetch( 'version' ) )
		);

		$this->add_assets( $register );
	}

	/**
	 * Registers the assets on the generated Register.
	 *
	 * This is a no-op by default by can be overwritten by the implementing developer
	 * to provide a single, clean location to register their assets.
	 *
	 * @param Register $register
	 *
	 * @codeCoverageIgnore
	 */
	protected function add_assets( Register $register ) {
		// no-op
	}
}
