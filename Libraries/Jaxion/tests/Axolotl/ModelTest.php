<?php
namespace Intraxia\Jaxion\Test\Axolotl;

use Intraxia\Jaxion\Axolotl\Collection;
use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Test\Stub\PostAndMetaModel;
use Intraxia\Jaxion\Test\Stub\TableModel;
use Intraxia\Jaxion\Test\Stub\ModelWithHiddenAttrs;
use Intraxia\Jaxion\Test\Stub\ModelWithNoHiddenVisibleAttrs;
use Mockery;
use WP_Post;

/**
 * @group model
 */
class ModelTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		parent::setUp();
		Mockery::mock( 'overload:WP_Post' );
		Mockery::mock( 'overload:WP_REST_Response' );
	}

	public function test_should_construct_with_attributes() {
		$args = array(
			'text' => 'Some text',
			'children' => null,
			'url' => null,
		);

		$model = new PostAndMetaModel( $args );

		$this->assertSame( $args, $model->get_table_attributes() );
		$this->assertSame( $args['text'], $model->get_attribute( 'text' ) );
		$this->assertSame( $args['text'], $model->text );
	}

	public function test_should_construct_with_post() {
		$object = $this->create_post();

		$model = new PostAndMetaModel( array( Model::OBJECT_KEY => $object ) );

		$this->assertSame( $object, $model->get_underlying_wp_object() );
		$this->assertSame( $object->ID, $model->ID );
		$this->assertSame( $object->ID, $model->get_attribute( 'ID' ) );
		$this->assertSame( $object->post_title, $model->title );
		$this->assertSame( $object->post_title, $model->get_attribute( 'title' ) );
	}

	public function test_should_fill_to_attributes() {
		$model = new PostAndMetaModel();

		$model->text = 'Text1';

		$this->assertSame( 'Text1', $model->text );
		$this->assertSame( 'Text1', $model->get_attribute( 'text' ) );

		$model->set_attribute( 'text', 'Text2' );

		$this->assertSame( 'Text2', $model->text );
		$this->assertSame( 'Text2', $model->get_attribute( 'text' ) );

		$attributes = $model->get_table_attributes();

		$this->assertSame( 'Text2', $attributes['text'] );
	}

	public function test_should_fill_to_post() {
		$model = new PostAndMetaModel();

		$model->title = 'Title1';

		$this->assertSame( 'Title1', $model->title );
		$this->assertSame( 'Title1', $model->get_attribute( 'title' ) );

		$model->set_attribute( 'title', 'Title2' );

		$this->assertSame( 'Title2', $model->title );
		$this->assertSame( 'Title2', $model->get_attribute( 'title' ) );

		$post = $model->get_underlying_wp_object();

		$this->assertSame( 'Title2', $post->post_title );
	}

	public function test_table_model_should_not_have_post() {
		$model = new TableModel;

		$this->assertFalse( $model->get_underlying_wp_object() );
	}

	public function test_should_not_fill_guarded_when_guarded() {
		$model = new PostAndMetaModel;

		$this->setExpectedException( 'Intraxia\Jaxion\Axolotl\GuardedPropertyException' );

		$model->set_attribute( 'ID', 1 );
	}

	public function test_should_fill_guarded_when_unguarded() {
		$model = new PostAndMetaModel;

		$model->unguard();

		$model->set_attribute( 'ID', 2 );

		$this->assertSame( 2, $model->get_attribute( 'ID' ) );

		$model->reguard();

		$this->setExpectedException( 'Intraxia\Jaxion\Axolotl\GuardedPropertyException' );

		$model->set_attribute( 'ID', 3 );
	}

	public function test_should_compute_attribute() {
		$model = new PostAndMetaModel( $this->create_args() );

		$this->assertSame( 'example.com/Title', $model->get_attribute( 'url' ) );
	}

	public function test_should_set_default_post_type() {
		$model = new PostAndMetaModel;

		$this->assertSame( 'custom', $model->get_underlying_wp_object()->post_type );
	}

	public function test_should_return_defined_attributes() {
		$keys = array( 'title', 'text', 'children', 'ID', 'url' );

		$model = new PostAndMetaModel;

		$this->assertSame( $keys, $model->get_attribute_keys() );
		$this->assertSame( $keys, $model->get_attribute_keys() ); // Test memoizing

		$model = new ModelWithHiddenAttrs;

		$this->assertSame( $keys, $model->get_attribute_keys() );
		$this->assertSame( $keys, $model->get_attribute_keys() ); // Test memoizing

		$model = new ModelWithNoHiddenVisibleAttrs;

		$this->assertSame( $keys, $model->get_attribute_keys() );
		$this->assertSame( $keys, $model->get_attribute_keys() ); // Test memoizing
	}

	public function test_should_retrieve_table_keys() {
		$keys = array( 'text', 'children' );

		$model = new PostAndMetaModel;

		$this->assertSame( $keys, $model->get_table_keys() );
		$this->assertSame( $keys, $model->get_table_keys() ); // Test memoizing
	}

	public function test_should_retrieve_post_keys() {
		$keys = array( 'title', 'ID' );

		$model = new PostAndMetaModel;

		$this->assertSame( $keys, $model->get_wp_object_keys() );
		$this->assertSame( $keys, $model->get_wp_object_keys() ); // Test memoizing
	}

	public function test_should_retrieve_computed_keys() {
		$keys = array( 'url' );

		$model = new PostAndMetaModel;

		$this->assertSame( $keys, $model->get_computed_keys() );
		$this->assertSame( $keys, $model->get_computed_keys() ); // Test memoizing
	}

	public function test_should_serialize_visible_attributes() {
		$model = new PostAndMetaModel( $args = $this->create_args() );

		$keys = array( 'title', 'text', 'url' );

		$this->assertSame( $keys, array_keys( $arr = $model->serialize() ) );

		foreach ( $keys as $key ) {
			$this->assertSame( $model->get_attribute( $key ), $arr[ $key ] );
		}
	}

	public function test_should_serialize_without_hidden_attributes() {
		$model = new ModelWithHiddenAttrs( $args = $this->create_args() );

		$keys = array( 'title', 'text', 'url' );

		$this->assertSame( $keys, array_keys( $arr = $model->serialize() ) );

		foreach ( $keys as $key ) {
			$this->assertSame( $model->get_attribute( $key ), $arr[ $key ] );
		}
	}

	public function test_should_serialize_from_defined_attributes() {
		$model = new ModelWithNoHiddenVisibleAttrs( $args = $this->create_args() );

		$keys = array( 'title', 'text', 'children', 'ID', 'url' );

		$this->assertSame( $keys, array_keys( $arr = $model->serialize() ) );

		foreach ( $keys as $key ) {
			if ('children' === $key) {
				$this->assertSame( $model->get_attribute( $key )->serialize(), $arr[ $key ] );
			} else {
				$this->assertSame( $model->get_attribute( $key ), $arr[ $key ] );
			}
		}
	}

	public function test_should_copy_attributes_to_original() {
		$args = $this->create_args();

		$model = new PostAndMetaModel( $args );

		$model->sync_original();

		$original   = $model->get_original_table_attributes();
		$attributes = $model->get_table_attributes();

		$this->assertSame( $original['text'], $attributes['text'] );
		$this->assertNotSame( $model->get_original_underlying_wp_object(), $model->get_underlying_wp_object() );
	}

	public function test_should_return_changed_table_attributes_from_original() {
		$model = new PostAndMetaModel( $this->create_args() );
		$model->sync_original();

		$model->set_attribute( 'text', 'New text' );

		$original   = $model->get_original_table_attributes();
		$attributes = $model->get_table_attributes();

		$this->assertNotSame( $original['text'], $attributes['text'] );
		$this->assertSame( array(
			'text'     => 'New text',
			'children' => $original['children'],
		), $model->get_changed_table_attributes() );
	}

	public function test_should_return_changed_object_attributes_from_original() {
		$model = new PostAndMetaModel( $this->create_args() );
		$model->sync_original();

		$model->unguard();
		$model->set_attribute( 'ID', 2 );
		$model->reguard();

		$original   = $model->get_original_underlying_wp_object();
		$attributes = $model->get_underlying_wp_object();

		$this->assertNotSame( $original, $attributes );
		$this->assertSame(
			array( 'ID' => 2 ),
			$model->get_changed_wp_object_attributes()
		);
	}

	public function test_should_clear_fillable_model_attributes() {
		$args = $this->create_args();

		$model = new PostAndMetaModel( $args );

		$model->clear();

		$this->assertNull( $model->get_attribute( 'text' ) );
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();
	}

	/**
	 * @return WP_Post
	 */
	protected function create_post() {
		$post             = new WP_Post;
		$post->ID         = 1;
		$post->post_title = 'Title';

		return $post;
	}

	/**
	 * @return array
	 */
	protected function create_args() {
		$args = array(
			'text' => 'Some text',
			'children' => new Collection( 'string' ),
			Model::OBJECT_KEY => $this->create_post(),
		);

		return $args;
	}
}
