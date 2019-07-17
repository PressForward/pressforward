<?php

/**
 * @group OPML
 */
class PF_Tests_OPML_Reader extends PF_UnitTestCase {

	function setUp() {
		$this->reader = new OPML_reader( '' );
		$this->reader->build_from_string(
			'<?xml version="1.0"?>
<opml version="2.0">
	<head>
		<title>Testo</title>
		<expansionState>1,4</expansionState>
		<linkPublicUrl>https://dl.dropbox.com/s/fofo6bmx3tu73hn/blogroll.opml?dl=0</linkPublicUrl>
		<lastCursor>0</lastCursor>
		<dateModified>Fri, 05 Aug 2016 03:31:03 GMT</dateModified>
		</head>
	<body>
		<outline text="On The Media" title="Media Industry" slug="media-industry">
			<outline text="ReadWriteWeb" title="ReadWriteWeb" type="rss" xmlUrl="http://www.readwriteweb.com/rss.xml" htmlUrl="http://readwrite.com"/>
			</outline>
		<outline text="dealbreaker.com" title="dealbreaker.com" type="rss" xmlUrl="http://dealbreaker.com"/>
		<outline text="WPMU" title="WPMU" slug="wpmu">
			<outline text="WPMU Tutorials" title="WPMU Tutorials" type="rss" xmlUrl="http://wpmututorials.com/feed/" htmlUrl="http://wpmututorials.com"/>
			</outline>
		</body>
	</opml>'
		);
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
