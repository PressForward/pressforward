<?php
namespace Intraxia\Jaxion\Test\Core;

use Intraxia\Jaxion\Core\Loader;
use Mockery;
use WP_Mock;

class LoaderTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Loader
	 */
	protected $loader;

	public function setUp() {
		WP_Mock::setUp();
		$this->loader = new Loader;
	}

	public function test_should_add_action() {
		$service = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\HasActions' );
		$service->shouldReceive( 'action_hooks' )
			->once()
			->andReturn( array(
				array(
					'hook'     => 'test_action',
					'method'   => 'test',
					'priority' => 15,
					'args'     => 2,
				)
			) );

		WP_Mock::expectActionAdded( 'test_action', array( $service, 'test' ), 15, 2 );

		$this->loader->register_actions( $service );
		$this->loader->run();
	}

	public function test_should_add_filter() {
		$service = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\HasFilters' );
		$service->shouldReceive( 'filter_hooks' )
			->once()
			->andReturn( array(
				array(
					'hook'     => 'test_filter',
					'method'   => 'test',
					'priority' => 15,
					'args'     => 2,
				)
			) );

		WP_Mock::expectFilterAdded( 'test_filter', array( $service, 'test' ), 15, 2 );

		$this->loader->register_filters( $service );
		$this->loader->run();
	}

	public function test_should_add_shortcode() {
		$service = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\HasShortcode' );
		$service->shouldReceive( 'shortcode_name' )
			->once()
			->andReturn( 'shortcode' );

		\WP_Mock::wpFunction( 'add_shortcode', array(
			'times' => 1,
			'args'  => array( 'shortcode', array( $service, 'do_shortcode' ) ),
		) );

		$this->loader->register_shortcode( $service );
	}

	public function tearDown() {
		parent::tearDown();
		WP_Mock::tearDown();
		Mockery::close();
	}
}
