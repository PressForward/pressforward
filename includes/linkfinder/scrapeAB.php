<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<?php
//http://simplehtmldom.sourceforge.net/
//http://simplehtmldom.sourceforge.net/manual.htm#section_traverse
//http://simplehtmldom.sourceforge.net/manual_api.htm
//error_reporting(E_ALL);
mb_language('uni');
mb_internal_encoding('UTF-8');
require_once('simple_html_dom.php');
$dom = new simple_html_dom;

function getTitle($str){
    //$str = file_get_contents($Url);
    if(strlen($str)>1){
        preg_match("/\<title\>(.*)\<\/title\>/",$str,$title);
        return $title[1];
    }
}

function customError($errno, $errstr)
{
  return 'Nothing found';

}


//Random article for testing.
$html = file_get_html('http://academicblogs.org/index.php/Main_Page');
echo 'Blog Categories: <br />'; 
foreach ($html->find('h1') as $link){
	
//	if (($link->plaintext == '[edit] External links') || ($link->plaintext == '[edit] References') ){
		echo '<br /><br />Innertext: ';
		print_r($link->innertext);
		echo '<br /><br />';

		$nextBlock = $link->next_sibling();
		//print_r($nextBlock);	
		
		foreach ($nextBlock->find('h2') as $h2) {
				echo 'H2 Head: ';
				print_r($h2->innertext);
				echo '<br /><br />';	
		}
		foreach ($nextBlock->find('a') as $innerLink){
		if($innerLink->getAttribute('rel') != 'nofollow'){
			if (($innerLink->href != '') && ($innerLink->title != '')) {
				$theExternalSite = $innerLink->href;
				//if (!file_get_html($theExternalSite)){
					//echo 'Page no longer exists.';
				//} else {
				//	$exHtml = file_get_html($theExternalSite);
					
				//}
				//echo getTitle($innerLink->href);
				//if($exHtml->find('head')->find('title')){
				//	echo $exHtml->find('head')->find('title')->plaintext;
				//} elseif ($exHtml->head->meta->title == 'name') {
				//	echo 'Name';
				//}
				//if (getTitle($theExternalSite))
				set_error_handler("customError");
				echo $innerLink->title;
				restore_error_handler();
				echo ' - ';
				echo $theExternalSite;
				echo '<br />';
			}
			//echo $link->plaintext;
			//echo ' |- ';
			//echo $link->next_sibling();
		}
		}
//	}

}

//$contentHtml = file_get_html('http://academicblogs.org/index.php/Main_Page');
//set_error_handler("customError");
//$content = $contentHtml->find('.hentry');
//echo $content[0]->innertext;

?>