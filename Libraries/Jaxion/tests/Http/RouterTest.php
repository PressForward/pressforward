<?php
namespace Intraxia\Jaxion\Test\Http;

use Intraxia\Jaxion\Http\Router;
use Mockery;
use WP_Mock;

class RouterTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Router
	 */
	protected $router;

	public function setUp() {
		parent::setUp();
		WP_Mock::setUp();

		$this->router = new Router();
		$this->router->set_vendor( 'jaxion' );
		$this->router->set_version( 1 );
	}

	public function test_should_require_vendor() {
		$this->setExpectedException( 'Intraxia\Jaxion\Http\VendorNotSetException' );

		$router = new Router();
		$router->set_version( 1 );
		$router->register();
	}

	public function test_should_require_version() {
		$this->setExpectedException( 'Intraxia\Jaxion\Http\VersionNotSetException' );

		$router = new Router();
		$router->set_vendor( 'jaxion' );
		$router->register();
	}

	public function test_should_throw_exception_on_unknown_method() {
		$this->setExpectedException( 'Intraxia\Jaxion\Http\UnknownMethodException' );

		$this->router->postable();
	}

	public function test_should_require_route() {
		$this->setExpectedException( 'Intraxia\Jaxion\Http\MissingArgumentException' );

		$this->router->get();
	}

	public function test_should_require_callback() {
		$this->setExpectedException( 'Intraxia\Jaxion\Http\MissingArgumentException' );

		$this->router->get( '/widgets' );
	}

	public function test_should_require_callable_callback() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->router->get( '/widgets', 'not_a_function' );
	}

	public function test_should_reject_malformed_route() {
		$callback = function () {
			return true;
		};

		$this->setExpectedException( 'Intraxia\Jaxion\Http\MalformedRouteException' );

		$this->router->get( '/widgets/', $callback );

		$this->setExpectedException( 'Intraxia\Jaxion\Http\MalformedRouteException' );

		$this->router->get( 'widgets', $callback );
	}

	public function test_should_register_new_get_route() {
		$callback = function () {
			return true;
		};

		$this->router->get( '/widgets', $callback );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets',
				array(
					'methods'  => 'GET',
					'callback' => $callback,
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_register_new_post_route() {
		$callback = function () {
			return true;
		};

		$this->router->post( '/widgets', $callback );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets',
				array(
					'methods'  => 'POST',
					'callback' => $callback,
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_register_new_put_route() {
		$callback = function () {
			return true;
		};

		$this->router->put( '/widgets', $callback );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets',
				array(
					'methods'  => 'PUT',
					'callback' => $callback,
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_register_new_patch_route() {
		$callback = function () {
			return true;
		};

		$this->router->patch( '/widgets', $callback );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets',
				array(
					'methods'  => 'PATCH',
					'callback' => $callback,
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_register_new_delete_route() {
		$callback = function () {
			return true;
		};

		$this->router->delete( '/widgets', $callback );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets',
				array(
					'methods'  => 'DELETE',
					'callback' => $callback,
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_register_new_editable_route() {
		$callback = function () {
			return true;
		};

		$this->router->editable( '/widgets', $callback );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets',
				array(
					'methods'  => 'POST, PUT, PATCH',
					'callback' => $callback,
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_register_new_all_methods_route() {
		$callback = function () {
			return true;
		};

		$this->router->all( '/widgets', $callback );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets',
				array(
					'methods'  => 'GET, POST, PUT, PATCH, DELETE',
					'callback' => $callback,
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_apply_guard() {
		$callback = function () {
			return true;
		};
		$guard    = Mockery::mock( 'Intraxia\Jaxion\Contract\Http\Guard' );

		$this->router->get( '/widgets', $callback, array(
			'guard' => $guard,
		) );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets',
				array(
					'methods'             => 'GET',
					'callback'            => $callback,
					'permission_callback' => array( $guard, 'authorized' ),
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_apply_filter() {
		$callback = function () {
			return true;
		};
		$args     = array(
			'ID' => array(
				'validate_callback' => 'is_int'
			)
		);
		$filter   = Mockery::mock( 'Intraxia\Jaxion\Contract\Http\Filter' );
		$filter
			->shouldReceive( 'rules' )
			->once()
			->andReturn( $args );

		$this->router->get( '/widgets', $callback, array(
			'filter' => $filter,
		) );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets',
				array(
					'methods'  => 'GET',
					'callback' => $callback,
					'args'     => $args,
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_ignore_unneeded_options() {
		$callback = function () {
			return true;
		};

		$this->router->get( '/widgets', $callback, array(
			'random' => 'random',
		) );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets',
				array(
					'methods'  => 'GET',
					'callback' => $callback,
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_reject_malformed_prefix() {
		$callback = function () {
			return true;
		};

		$this->setExpectedException( 'Intraxia\Jaxion\Http\MalformedRouteException' );

		$this->router->group( array( 'prefix' => 'widgets' ), function ( $router ) use ( $callback ) {
			$router->get( '/first', $callback );
			$router->post( '/second', $callback );
		} );

		$this->setExpectedException( 'Intraxia\Jaxion\Http\MalformedRouteException' );

		$this->router->group( array( 'prefix' => '/widgets/' ), function ( $router ) use ( $callback ) {
			$router->get( '/first', $callback );
			$router->post( '/second', $callback );
		} );
	}

	public function test_should_apply_prefix_to_group() {
		$callback = function () {
			return true;
		};

		$this->router->group( array( 'prefix' => '/widgets' ), function ( $router ) use ( $callback ) {
			$router->get( '/first', $callback );
			$router->post( '/second', $callback );
		} );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets/first',
				array(
					'methods'  => 'GET',
					'callback' => $callback,
				),
			)
		) );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/widgets/second',
				array(
					'methods'  => 'POST',
					'callback' => $callback,
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_apply_guard_to_group() {
		$callback = function () {
			return true;
		};
		$guard    = Mockery::mock( 'Intraxia\Jaxion\Contract\Http\Guard' );

		$this->router->group( array( 'guard' => $guard ), function ( $router ) use ( $callback ) {
			$router->get( '/first', $callback );
			$router->post( '/second', $callback );
		} );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/first',
				array(
					'methods'             => 'GET',
					'callback'            => $callback,
					'permission_callback' => array( $guard, 'authorized' ),
				),
			)
		) );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/second',
				array(
					'methods'             => 'POST',
					'callback'            => $callback,
					'permission_callback' => array( $guard, 'authorized' ),
				),
			)
		) );

		$this->router->register();
	}

	public function test_should_apply_filter_to_group() {
		$callback = function () {
			return true;
		};
		$args     = array(
			'ID' => array(
				'validate_callback' => 'is_int'
			)
		);
		$filter   = Mockery::mock( 'Intraxia\Jaxion\Contract\Http\Filter' );
		$filter
			->shouldReceive( 'rules' )
			->twice()
			->andReturn( $args );

		$this->router->group( array( 'filter' => $filter ), function ( $router ) use ( $callback ) {
			$router->get( '/first', $callback );
			$router->post( '/second', $callback );
		} );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/first',
				array(
					'methods'  => 'GET',
					'callback' => $callback,
					'args'     => $args,
				),
			)
		) );

		WP_Mock::wpFunction( 'register_rest_route', array(
			'times' => 1,
			'args'  => array(
				'jaxion/v1',
				'/second',
				array(
					'methods'  => 'POST',
					'callback' => $callback,
					'args'     => $args,
				),
			)
		) );

		$this->router->register();
	}

	public function tearDown() {
		parent::tearDown();
		WP_Mock::tearDown();
	}
}
