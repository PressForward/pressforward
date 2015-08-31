<?php

/**
 * @group OPML
 */
class PF_Tests_OPML_Reader extends PF_UnitTestCase {

	function setUp() {
		$this->reader = new OPML_reader( 'https://dl.dropbox.com/s/fofo6bmx3tu73hn/blogroll.opml?dl=0' );
	}

	function test_load_opml_file() {
		$this->assertInstanceOf( 'SimpleXMLElement', $this->reader->opml_file );
		$this->assertInternalType( 'string', $this->reader->file_url );
	}

	function test_gets_opml_object() {
		$opml = $this->reader->get_OPML_obj();

		$this->assertInstanceOf( 'OPML_Object', $opml );
	}
}
