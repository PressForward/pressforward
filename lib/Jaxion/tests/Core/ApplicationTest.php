<?php
namespace Intraxia\Jaxion\Test\Core;

use Intraxia\Jaxion\Core\Application as App;
use Mockery;
use WP_Mock;
use stdClass;

class ApplicationTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		parent::setUp();
		WP_Mock::setUp();
	}

	public function test_should_throw_exception_if_not_booted() {
		$this->setExpectedException( 'Intraxia\Jaxion\Core\ApplicationNotBootedException' );

		App::instance();
	}

	public function test_should_get_instantiated_instance() {
		$this->mock_constructor_functions();

		$app1 = new App( __FILE__ );
		$app2 = App::instance();

		$this->assertSame( $app1, $app2 );
	}

	public function test_should_throw_exception_if_already_booted() {
		$this->mock_constructor_functions();

		new App( __FILE__ );

		$this->setExpectedException( 'Intraxia\Jaxion\Core\ApplicationAlreadyBootedException' );

		new App( __FILE__ );
	}

	public function test_should_shutdown() {
		$this->mock_constructor_functions();

		new App( __FILE__ );
		App::shutdown();

		$this->setExpectedException( 'Intraxia\Jaxion\Core\ApplicationNotBootedException' );
		App::instance();
	}

	public function test_should_have_constants() {
		$this->mock_constructor_functions();

		$app = new App( __FILE__ );

		$this->assertTrue( isset( $app['url'] ) );
		$this->assertTrue( isset( $app['path'] ) );
		$this->assertTrue( isset( $app['basename'] ) );
	}

	public function test_should_have_services() {
		$this->mock_constructor_functions();

		$app = new App( __FILE__ );

		$this->assertInstanceOf( 'Intraxia\Jaxion\Core\Loader', $app['loader'] );
	}

	public function test_should_throw_exception_if_not_loader() {
		$this->mock_constructor_functions();

		$app = new App( __FILE__ );

		$app->remove( 'loader' )
			->define( 'loader', function () {
				return new stdClass;
			} );

		$this->setExpectedException( 'UnexpectedValueException' );

		$app->boot();
	}

	public function test_should_register_actions() {
		$this->mock_constructor_functions();

		$app     = new App( __FILE__ );
		$actions = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\HasActions' );

		$app->remove( 'loader' )
			->define( 'loader', function () use ( $actions ) {
				$loader = Mockery::mock( 'Intraxia\Jaxion\Core\Loader' );
				$loader->shouldReceive( 'register_actions' )
					->once()
					->with( $actions );

				WP_Mock::expectActionAdded( 'plugins_loaded', array( $loader, 'run' ) );

				return $loader;
			} );
		$app->define( 'actions', $actions );

		$app->boot();
	}

	public function test_should_register_filters() {
		$this->mock_constructor_functions();

		$app     = new App( __FILE__ );
		$filters = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\HasFilters' );

		$app->remove( 'loader' )
			->define( 'loader', function () use ( $filters ) {
				$loader = Mockery::mock( 'Intraxia\Jaxion\Core\Loader' );
				$loader->shouldReceive( 'register_filters' )
					->once()
					->with( $filters );

				WP_Mock::expectActionAdded( 'plugins_loaded', array( $loader, 'run' ) );

				return $loader;
			} );
		$app->define( 'filters', $filters );

		$app->boot();
	}

	public function test_should_register_shortcode() {
		$this->mock_constructor_functions();

		$app     = new App( __FILE__ );
		$shortcode = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\HasShortcode' );

		$app->remove( 'loader' )
			->define( 'loader', function () use ( $shortcode ) {
				$loader = Mockery::mock( 'Intraxia\Jaxion\Core\Loader' );
				$loader->shouldReceive( 'register_shortcode' )
					->once()
					->with( $shortcode );

				WP_Mock::expectActionAdded( 'plugins_loaded', array( $loader, 'run' ) );

				return $loader;
			} );
		$app->define( 'filters', $shortcode );

		$app->boot();
	}

	protected function mock_constructor_functions() {
		WP_Mock::wpPassthruFunction( 'plugin_dir_url', array( 'times' => 1 ) );
		WP_Mock::wpPassthruFunction( 'plugin_dir_path', array( 'times' => 1 ) );
		WP_Mock::wpPassthruFunction( 'plugin_basename', array( 'times' => 1 ) );

		WP_Mock::wpPassthruFunction( 'load_plugin_textdomain', array( 'times' => 1 ) );
		WP_Mock::wpPassthruFunction( 'register_activation_hook', array( 'times' => 1 ) );
		WP_Mock::wpPassthruFunction( 'register_deactivation_hook', array( 'times' => 1 ) );
	}

	public function tearDown() {
		parent::tearDown();
		App::shutdown();
		Mockery::close();
		WP_Mock::tearDown();
	}
}
