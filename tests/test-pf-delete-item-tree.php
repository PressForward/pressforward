<?php

class PF_Tests_PfDeleteItemTree extends PF_UnitTestCase {
	public function tearDown() {
		// Remove all uploads.
		$this->remove_added_uploads();
		parent::tearDown();
	}

	public function test_should_return_false_for_bad_post_id() {
		$this->assertFalse( pf_delete_item_tree( 12345 ) );
	}

	public function test_should_return_false_for_post_in_wrong_post_type() {
		$p = $this->factory->post->create();
		$this->assertFalse( pf_delete_item_tree( $p ) );
	}

	public function test_should_queue_nomination_for_deletion() {
		$n = $this->factory->post->create( array( 'post_type' => 'nomination' ) );
		$this->assertEqualSets( array( $n ), pf_delete_item_tree( $n ) );
	}

	public function test_should_return_false_for_already_queued_item() {
		$p = $this->factory->feed_item->create();
		update_option( 'pf_delete_queue', array( $p ) );
		$this->assertFalse( pf_delete_item_tree( $p ) );
	}

	public function test_deleting_feeditem_should_queue_attachments() {
		$fi = $this->factory->feed_item->create();
		$a = $this->make_attachment( $fi );

		$expected = array( $fi, $a );
		$this->assertEqualSets( $expected, pf_delete_item_tree( $fi ) );
		$this->assertEqualSets( $expected, get_option( 'pf_delete_queue' ) );
	}

	public function test_deleting_feed_should_queue_feed_items_and_attachments() {
		$f = $this->factory->feed->create();
		$fi1 = $this->factory->feed_item->create( array( 'post_parent' => $f ) );
		$fi2 = $this->factory->feed_item->create( array( 'post_parent' => $f ) );
		$a = $this->make_attachment( $fi1 );

		$expected = array( $f, $fi1, $fi2, $a );
		$this->assertEqualSets( $expected, pf_delete_item_tree( $f ) );
		$this->assertEqualSets( $expected, get_option( 'pf_delete_queue' ) );
	}

	public function test_deleting_opml_should_should_queue_child_feeds_and_feed_items_and_atachments() {
		$o = $this->factory->feed->create();
		$f1 = $this->factory->feed->create( array( 'post_parent' => $o ) );
		$f2 = $this->factory->feed->create( array( 'post_parent' => $o ) );
		$fi1 = $this->factory->feed_item->create( array( 'post_parent' => $f1 ) );
		$fi2 = $this->factory->feed_item->create( array( 'post_parent' => $f1 ) );
		$a = $this->make_attachment( $fi1 );

		$expected = array( $o, $f1, $f2, $fi1, $fi2, $a );
		$this->assertEqualSets( $expected, pf_delete_item_tree( $o ) );
		$this->assertEqualSets( $expected, get_option( 'pf_delete_queue' ) );
	}

	public function test_queued_items_should_not_appear_in_any_queries() {
		$posts = $this->factory->feed->create_many( 3 );

		update_option( 'pf_delete_queue', array( $posts[1] ) );

		$q = new WP_Query( array(
			'post_type' => pressforward()->pf_feeds->post_type,
			'post_status' => 'any',
			'numberposts' => -1,
			'fields' => 'ids',
		) );

		$this->assertNotContains( $posts[1], $q->posts );
	}

	public function test_batch_delete_should_delete_empty_terms() {
		$f1 = $this->factory->feed->create();
		$f2 = $this->factory->feed->create();

		$tax = pressforward()->pf_feeds->tag_taxonomy;

		$t1 = $this->factory->term->create( array( 'taxonomy' => $tax ) );
		$t2 = $this->factory->term->create( array( 'taxonomy' => $tax ) );

		wp_set_object_terms( $f1, array( $t1 ), $tax );
		wp_set_object_terms( $f2, array( $t1, $t2 ), $tax );

		$nonce = 123;
		update_option( 'pf_delete_queue_nonce', $nonce );
		$_GET['pf_process_delete_queue'] = $nonce;

		update_option( 'pf_delete_queue', array( $f2 ) );

		pf_process_delete_queue();

		$terms = get_terms( $tax, array( 'hide_empty' => false ) );

		$this->assertEqualSets( array( $t1 ), wp_list_pluck( $terms, 'term_id' ) );
	}

	protected function make_attachment( $parent_post_id = 0, $upload = false ) {
		if ( false !== $upload ) {
			$filename = DIR_TESTDATA.'/images/test-image.jpg';
			$contents = file_get_contents($filename);
			$upload = wp_upload_bits(basename($filename), null, $contents);
		}

		$type = '';
		if ( !empty($upload['type']) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ($mime)
				$type = $mime['type'];
		}

		$attachment = array(
			'post_title' => basename( $upload['file'] ),
			'post_content' => '',
			'post_type' => 'attachment',
			'post_parent' => $parent_post_id,
			'post_mime_type' => $type,
			'guid' => $upload[ 'url' ],
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $parent_post_id );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		return $this->ids[] = $id;

	}
}
