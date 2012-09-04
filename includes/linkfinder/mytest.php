<?php
//http://simplehtmldom.sourceforge.net/
//http://simplehtmldom.sourceforge.net/manual.htm#section_traverse
//http://simplehtmldom.sourceforge.net/manual_api.htm
//error_reporting(E_ALL);
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
  echo "Page no longer exists.";

}


//Random article for testing.
$html = file_get_html('http://en.wikipedia.org/wiki/Saving_Babies');
echo 'References: <br />'; 
foreach ($html->find('h2') as $link){
	
	if (($link->plaintext == '[edit] External links') || ($link->plaintext == '[edit] References') ){
		
		$nextBlock = $link->next_sibling();
		foreach ($nextBlock->find('a') as $innerLink){
		if($innerLink->getAttribute('rel') == 'nofollow'){
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
			echo getTitle(file_get_html($theExternalSite));
			restore_error_handler();
			echo ' - ';
			echo $theExternalSite;
			echo '<br />';
			//echo $link->plaintext;
			//echo ' |- ';
			//echo $link->next_sibling();
		}
		}
	}

}

?>