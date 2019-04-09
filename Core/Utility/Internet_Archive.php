<?php
/**
 * Created by IntelliJ IDEA.
 * User: yoann
 * Date: 2019-04-09
 * Time: 10:08
 */

namespace PressForward\Core\Utility;


class Internet_Archive {
	function __construct() {

	}

	/**
	 * @param $url The URL of a page we want to be saved on archive.org
	 *
	 * @return bool|string TRUE if successful, FALSE if not
	 */
	function send_request_to_archive_dot_org( $url ) {
		pf_log("Send to archive.org!");
		$ch = curl_init( 'https://web.archive.org/save/' . $url );
		$result = curl_exec($ch);

		return $result;
	}
}
