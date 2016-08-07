<?php
namespace Intraxia\Jaxion\Test\Model;

use Intraxia\Jaxion\Model\Post;
use Mockery;
use WP_Mock;

class PostTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var Mockery\Mock
	 */
	protected $mock_post;

	public function setUp() {
		parent::setUp();
		$this->mock_post = Mockery::mock( 'overload:WP_Post' );
	}

	public function test_should_set_ID_from_WP_Post() {
		$this->mock_post->ID = 1;

		$post = new Post( array( 'post' => $this->mock_post ) );

		$this->assertSame( 1, $post->ID );
	}

	public function test_should_set_WP_Post_ID() {
		$post     = new Post;
		$post->ID = 1;

		$this->assertSame( 1, $post->get_underlying_post()->ID );
	}

	public function test_should_set_author_from_WP_Post() {
		$this->mock_post->post_author = '1';

		$post = new Post( array( 'post' => $this->mock_post ) );

		$this->assertSame( '1', $post->author );
	}

	public function test_should_set_WP_Post_author() {
		$post         = new Post;
		$post->author = '1';

		$this->assertSame( '1', $post->get_underlying_post()->post_author );
	}

	public function test_should_set_slug_from_WP_Post() {
		$this->mock_post->post_name = 'post-name';

		$post = new Post( array( 'post' => $this->mock_post ) );

		$this->assertSame( 'post-name', $post->slug );
	}

	public function test_should_set_WP_Post_slug() {
		$post       = new Post( array() );
		$post->slug = 'post-name';

		$this->assertSame( 'post-name', $post->get_underlying_post()->post_name );
	}

	public function test_should_not_set_type_from_WP_Post() {
		$this->mock_post->post_type = 'some-cpt';

		$post = new Post( array( 'post' => $this->mock_post ) );

		$this->assertSame( 'post', $post->type );
	}

	public function test_should_not_set_WP_Post_type() {
		$post       = new Post;
		$post->type = 'some-cpt';

		$this->assertSame( 'post', $post->get_underlying_post()->post_type );
	}

	public function test_should_set_title_from_WP_Post() {
		$this->mock_post->post_title = 'Post name';

		$post = new Post( array( 'post' => $this->mock_post ) );

		$this->assertSame( 'Post name', $post->title );
	}

	public function testShouldSetWPPostTitle() {
		$post        = new Post( array() );
		$post->title = 'Post name';

		$this->assertSame( 'Post name', $post->get_underlying_post()->post_title );
	}

	public function test_should_set_publish_date_from_WP_Post() {
		$this->mock_post->post_date_gmt = '2015-12-26 00:00:00';

		$post = new Post( array( 'post' => $this->mock_post ) );

		$this->assertSame( '2015-12-26 00:00:00', $post->publish_date );
	}

	public function test_should_set_WP_Post_publish_date() {
		$post               = new Post;
		$post->publish_date = '2015-12-26 00:00:00';

		$this->assertSame( '2015-12-26 00:00:00', $post->get_underlying_post()->post_date_gmt );
	}

	public function test_should_set_content_from_WP_Post() {
		$this->mock_post->post_content = 'Post content';

		$post = new Post( array( 'post' => $this->mock_post ) );

		$this->assertSame( 'Post content', $post->content );
	}

	public function test_should_set_WP_Post_content() {
		$post          = new Post;
		$post->content = 'Post content';

		$this->assertSame( 'Post content', $post->get_underlying_post()->post_content );
	}

	public function test_should_set_excerpt_from_WP_Post() {
		$this->mock_post->post_excerpt = 'Post excerpt';

		$post = new Post( array( 'post' => $this->mock_post ) );

		$this->assertSame( 'Post excerpt', $post->excerpt );
	}

	public function test_should_set_WP_Post_excerpt() {
		$post          = new Post;
		$post->excerpt = 'Post excerpt';

		$this->assertSame( 'Post excerpt', $post->get_underlying_post()->post_excerpt );
	}

	public function test_should_ignore_random_properties() {
		$post                  = new Post( array() );
		$post->random_property = 'Random property';

		$this->setExpectedException( 'Intraxia\Jaxion\Model\PropertyDoesNotExistException' );

		$post->random_property;
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();
	}
}
