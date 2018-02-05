<?php
namespace Intraxia\Jaxion\Test\Assets;

use Mockery;
use WP_Mock;
use Intraxia\Jaxion\Assets\Register;
use ReflectionProperty;

class RegisterTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Register
	 */
	public $assets;

	public function setUp() {
		parent::setUp();
		WP_Mock::setUp();
		$this->assets = new Register( 'test.com/' );
		$this->assets->set_debug( true );
	}

	public function test_should_toggle_debug_mode() {
		$min = new ReflectionProperty( $this->assets, 'min' );
		$min->setAccessible( true );

		$this->assets->set_debug( true );

		$this->assertSame( '', $min->getValue( $this->assets ) );

		$this->assets->set_debug( false );

		$this->assertSame( '.min', $min->getValue( $this->assets ) );
	}

	public function test_should_enqueue_web_script() {
		$this->assets->register_script( array(
			'type'      => 'web',
			'condition' => function () {
				return true;
			},
			'handle'    => 'web_script',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_script', array(
			'times' => 1,
			'args'  => array( 'web_script', 'test.com/test.js', array(), null, false ),
		) );

		$this->assets->enqueue_web_scripts();
	}

	public function test_should_not_enqueue_web_script_if_condition_false() {
		$this->assets->register_script( array(
			'type'      => 'web',
			'condition' => function () {
				return false;
			},
			'handle'    => 'web_script',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_script', array(
			'times' => 0,
		) );

		$this->assets->enqueue_web_scripts();
	}

	public function test_should_enqueue_web_style() {
		$this->assets->register_style( array(
			'type'      => 'web',
			'condition' => function () {
				return true;
			},
			'handle'    => 'web_style',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_style', array(
			'times' => 1,
			'args'  => array( 'web_style', 'test.com/test.css', array(), null, 'all' ),
		) );

		$this->assets->enqueue_web_styles();
	}

	public function test_should_not_enqueue_web_style_if_false_condition() {
		$this->assets->register_style( array(
			'type'      => 'web',
			'condition' => function () {
				return false;
			},
			'handle'    => 'web_style',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_style', array(
			'times' => 0,
		) );

		$this->assets->enqueue_web_styles();
	}

	public function test_should_enqueue_admin_script() {
		$this->assets->register_script( array(
			'type'      => 'admin',
			'condition' => function () {
				return true;
			},
			'handle'    => 'adminScript',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_script', array(
			'times' => 1,
			'args'  => array( 'adminScript', 'test.com/test.js', array(), null, false ),
		) );

		$this->assets->enqueue_admin_scripts( 'jaxion_test' );
	}

	public function test_should_not_enqueue_admin_script_if_false_condition() {
		$this->assets->register_script( array(
			'type'      => 'admin',
			'condition' => function () {
				return false;
			},
			'handle'    => 'adminScript',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_script', array(
			'times' => 0,
		) );

		$this->assets->enqueue_admin_styles( 'jaxion_test' );
	}

	public function test_should_enqueue_admin_style() {
		$this->assets->register_style( array(
			'type'      => 'admin',
			'condition' => function () {
				return true;
			},
			'handle'    => 'adminStyle',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_style', array(
			'times' => 1,
			'args'  => array( 'adminStyle', 'test.com/test.css', array(), null, 'all' ),
		) );

		$this->assets->enqueue_admin_styles( 'jaxion_test' );
	}

	public function test_should_not_enqueue_admin_style_if_false_condition() {
		$this->assets->register_style( array(
			'type'      => 'admin',
			'condition' => function () {
				return false;
			},
			'handle'    => 'adminStyle',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_style', array(
			'times' => 0,
		) );

		$this->assets->enqueue_admin_styles( 'jaxion_test' );
	}

	public function test_should_enqueue_shared_script() {
		$this->assets->register_script( array(
			'type'      => 'shared',
			'condition' => function () {
				return true;
			},
			'handle'    => 'shared_script',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_script', array(
			'times' => 2,
			'args'  => array( 'shared_script', 'test.com/test.js', array(), null, false ),
		) );

		$this->assets->enqueue_web_scripts();
		$this->assets->enqueue_admin_scripts( 'jaxion_test' );
	}

	public function test_should_not_enqueue_shared_script_if_false_condition() {
		$this->assets->register_script( array(
			'type'      => 'shared',
			'condition' => function () {
				return false;
			},
			'handle'    => 'shared_script',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_script', array(
			'times' => 0,
		) );

		$this->assets->enqueue_web_scripts();
		$this->assets->enqueue_admin_styles( 'jaxion_test' );
	}

	public function test_should_enqueue_shared_style() {
		$this->assets->register_style( array(
			'type'      => 'shared',
			'condition' => function () {
				return true;
			},
			'handle'    => 'shared_style',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_style', array(
			'times' => 2,
			'args'  => array( 'shared_style', 'test.com/test.css', array(), null, 'all' ),
		) );

		$this->assets->enqueue_web_styles();
		$this->assets->enqueue_admin_styles( 'jaxion_test' );
	}

	public function test_should_not_enqueue_shared_style_if_false_condition() {
		$this->assets->register_style( array(
			'type'      => 'shared',
			'condition' => function () {
				return false;
			},
			'handle'    => 'shared_style',
			'src'       => 'test',
		) );

		WP_Mock::wpFunction( 'wp_enqueue_style', array(
			'times' => 0,
		) );

		$this->assets->enqueue_web_styles();
		$this->assets->enqueue_admin_styles( 'jaxion_test' );
	}

	public function test_should_localize_script_if_set() {
		$this->assets->register_script( array(
			'type'      => 'web',
			'condition' => function () {
				return true;
			},
			'handle'    => 'web_script',
			'src'       => 'test',
			'localize'  => array(
				'name' => 'test_local',
				'data' => 'local_test',
			)
		) );

		WP_Mock::wpFunction( 'wp_enqueue_script', array(
			'times' => 1,
			'args'  => array( 'web_script', 'test.com/test.js', array(), null, false ),
		) );

		WP_Mock::wpFunction( 'wp_localize_script', array(
			'times' => 1,
			'args'  => array( 'web_script', 'test_local', 'local_test' ),
		) );

		$this->assets->enqueue_web_scripts();
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();
		WP_Mock::tearDown();
	}
}
