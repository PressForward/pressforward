<?php

$file="http://www.google.com/reader/public/subscriptions/user%2F10862070116690190079%2Fbundle%2FWriting%2BTech%20Bundle";

if (fopen ($file, "r")) {
	$opml_data = simplexml_load_file($file);

}

//Site data
$a = array();
//Feed URI
$b = array();
$c = 0;
foreach ($opml_data->body->outline->outline as $data){
	$a[] = reset($data);
}
foreach ($a as $outline) {
	$b[] = $outline['xmlUrl'];
}


?>