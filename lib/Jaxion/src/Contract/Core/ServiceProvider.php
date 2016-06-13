<?php
namespace Intraxia\Jaxion\Contract\Core;

interface ServiceProvider {
	/**
	 * Register the provider's services on the container.
	 *
	 * This method is passed the container to register on, giving the service provider
	 * an opportunity to register its services on the container in an encapsulated way.
	 *
	 * @param Container $container
	 */
	public function register( Container $container );
}
