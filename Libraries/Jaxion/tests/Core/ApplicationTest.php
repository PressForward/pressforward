<?php
namespace Intraxia\Jaxion\Test\Core;

use Intraxia\Jaxion\Core\Application as App;
use Intraxia\Jaxion\Core\Config;
use Intraxia\Jaxion\Core\ConfigType;
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
		$this->mock_config_functions();
		$this->mock_constructor_functions();

		$app1 = new App( new Config( ConfigType::PLUGIN, __FILE__ ) );
		$app2 = App::instance();

		$this->assertSame( $app1, $app2 );
	}

	public function test_should_throw_exception_if_already_booted() {
		$this->mock_config_functions( 2 );
		$this->mock_constructor_functions();

		new App( new Config( ConfigType::PLUGIN, __FILE__ ) );

		$this->setExpectedException( 'Intraxia\Jaxion\Core\ApplicationAlreadyBootedException' );

		new App( new Config( ConfigType::PLUGIN, __FILE__ ) );
	}

	public function test_should_shutdown() {
		$this->mock_config_functions();
		$this->mock_constructor_functions();

		new App( new Config( ConfigType::PLUGIN, __FILE__ ) );
		App::shutdown();

		$this->setExpectedException( 'Intraxia\Jaxion\Core\ApplicationNotBootedException' );
		App::instance();
	}

	public function test_should_have_constants() {
		$this->mock_config_functions();
		$this->mock_constructor_functions();

		$app = new App( new Config( ConfigType::PLUGIN, __FILE__ ) );

		$this->assertTrue( isset( $app['url'] ) );
		$this->assertTrue( isset( $app['path'] ) );
		$this->assertTrue( isset( $app['basename'] ) );
	}

	public function test_should_have_services() {
		$this->mock_config_functions();
		$this->mock_constructor_functions();

		$app = new App( new Config( ConfigType::PLUGIN, __FILE__ ) );

		$this->assertInstanceOf( 'Intraxia\Jaxion\Core\Loader', $app['loader'] );
	}

	public function test_should_throw_exception_if_not_loader() {
		$this->mock_config_functions();
		$this->mock_constructor_functions();

		$app = new App( new Config( ConfigType::PLUGIN, __FILE__ ) );

		$app->remove( 'loader' )
			->define( 'loader', function () {
				return new stdClass;
			} );

		$this->setExpectedException( 'UnexpectedValueException' );

		$app->boot();
	}

	public function test_should_register_actions() {
		$this->mock_config_functions();
		$this->mock_constructor_functions();

		$app     = new App( new Config( ConfigType::PLUGIN, __FILE__ ) );
		$actions = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\HasActions' );
		$this->mock_loader( $app )
			->shouldReceive( 'register_actions' )
			->once()
			->with( $actions );
		$app->define( 'actions', $actions );

		$app->boot();
	}

	public function test_should_register_filters() {
		$this->mock_config_functions();
		$this->mock_constructor_functions();

		$app     = new App( new Config( ConfigType::PLUGIN, __FILE__ ) );
		$filters = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\HasFilters' );
		$this->mock_loader( $app )
			->shouldReceive( 'register_filters' )
			->once()
			->with( $filters );
		$app->define( 'filters', $filters );

		$app->boot();
	}

	public function test_should_register_shortcode() {
		$this->mock_config_functions();
		$this->mock_constructor_functions();

		$app     = new App( new Config( ConfigType::PLUGIN, __FILE__ ) );
		$shortcode = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\HasShortcode' );
		$this->mock_loader( $app )
			->shouldReceive( 'register_shortcode' )
			->once()
			->with( $shortcode );
		$app->define( 'filters', $shortcode );

		$app->boot();
	}

	protected function mock_config_functions( $count = 1 ) {
		WP_Mock::wpPassthruFunction( 'plugin_dir_url', array( 'times' => $count ) );
		WP_Mock::wpPassthruFunction( 'plugin_dir_path', array( 'times' => $count ) );
		WP_Mock::wpPassthruFunction( 'plugin_basename', array( 'times' => $count ) );
	}

	protected function mock_constructor_functions( $count = 1 ) {
		WP_Mock::wpPassthruFunction( 'register_activation_hook', array( 'times' => $count ) );
		WP_Mock::wpPassthruFunction( 'register_deactivation_hook', array( 'times' => $count ) );
	}

	public function tearDown() {
		parent::tearDown();
		App::shutdown();
		Mockery::close();
		WP_Mock::tearDown();
	}

	private function mock_loader( $app ) {
		$loader = Mockery::mock( 'Intraxia\Jaxion\Core\Loader' );
		$loader->shouldReceive( 'register_actions' )
		       ->once()
		       ->with( Mockery::type( 'Intraxia\Jaxion\Contract\Core\I18n' ) );
		WP_Mock::expectActionAdded( 'plugins_loaded', array( $loader, 'run' ) );

		$app->remove( 'loader' )
			->define( 'loader', function () use ( $loader ) {
				return $loader;
			} );

		return $loader;
	}
}
