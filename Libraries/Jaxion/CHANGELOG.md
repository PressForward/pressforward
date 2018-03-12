# Changelog

All Notable changes to `jaxion` will be documented in this file. This change log follows the [Keep a Changelog standards]. Versions follows [Semantic Versioning].

## [NEXT] ##

### Added ###
* Unit tests for all Model-related functionality.
* Exposed and memoized methods for key-related info for Models.
    * These will be used by the EntityManager to fill in all of the Model's attributes.

### Changed ###
* Moved all Database-related code to Axolotl namespace,
* Renamed `Model\Base` class to `Axolotl\Model` class.
* Removed `Model\Post` class.

## [0.0.1] ##

### Added ###
* Core framework classes:
  * Application container, for dependency injection
  * Loader, for registering services with WordPress
  * ServiceProvider contract, for registering services with the container
* Assets management classes:
  * Register, for easily registering assets with WordPress
* WP-API Http classes:
  * Router and Endpoint, for registering with the WP-API
  * Guards and Filters, for protecting your registered endpoints
* Model classes:
  * Base and Post, for providing a simple, unified interface over WP_Post and post_meta

  [Keep a Changelog standards]: http://keepachangelog.com/
  [Semantic Versioning]: http://semver.org/
  [0.0.1]: https://github.com/intraxia/jaxion/tree/0.0.1
  [NEXT]: http://github.com/intraxia/jaxion
