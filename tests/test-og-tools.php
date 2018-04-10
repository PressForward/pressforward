<?php

class PF_Tests_OG_Tools extends PF_UnitTestCase {
	public function test_image_check() {
		$img = 'https://i.guim.co.uk/img/media/6f0c2682de6a8a9e0ca2c4c0863d997925d754df/0_271_6016_3608/master/6016.jpg?w=1200&amp;h=630&amp;q=55&amp;auto=format&amp;usm=12&amp;fit=crop&amp;crop=faces%2Centropy&amp;bm=normal&amp;ba=bottom%2Cleft&amp;blend64=aHR0cHM6Ly91cGxvYWRzLmd1aW0uY28udWsvMjAxOC8wMS8zMS9mYWNlYm9va19kZWZhdWx0LnBuZw&amp;s=1c7af81a5e8020881399c756a895a48e';
		$this->assertSame( pressforward('schema.feed_item')->resolve_image_type($img), 'jpg' );
		$this->assertFalse( pressforward('schema.feed_item')->resolve_image_type('http://whatever.com/not_an_img') );
		$this->assertSame( pressforward('schema.feed_item')->resolve_image_type('http://notadomain.com/img.jpg'), 'jpg' );
	}
}
