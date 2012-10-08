<?php

$file="http://www.google.com/reader/public/subscriptions/user%2F10862070116690190079%2Fbundle%2FWriting%2BTech%20Bundle";

if (fopen ($file, "r")) {
	$opml_data = simplexml_load_file($file);

}

//print_r( '<pre><code>' );
//print_r($opml_data);
//print_r( '</code></pre>' );

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

print_r( '<pre><code>' );
print_r($b);
print_r( '</code></pre>' );

?>