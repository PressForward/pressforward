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
  return false;

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

$contentHtml = file_get_html('');
set_error_handler("customError");

//Case 1 - .hentry http://oha2012.thatcamp.org/
//Case 2 - .entry-content  http://www.freshandnew.org/2012/08/museum-datasets-un-comprehensive-ness-data-mining/
//Case 3 - .article-body p (for each p) https://chronicle.com/article/Historians-Ask-Public-to-Help/134054
//Case 3 - article http://oha2012.thatcamp.org/
//Case 4 - section http://www.wordsinspace.net/urban-media-archaeology/2012-fall/?page_id=9
//Case 5 - #content http://www.wordsinspace.net/urban-media-archaeology/2012-fall/?page_id=9
//Case 6 - .page-content http://www.wordsinspace.net/urban-media-archaeology/2012-fall/?page_id=9
//Last case - OG Description


?>