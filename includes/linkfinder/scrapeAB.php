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
# Get the title page
foreach ($html->find('h1') as $link){
	
//	if (($link->plaintext == '[edit] External links') || ($link->plaintext == '[edit] References') ){
		echo '<br /><br />Innertext: ';
		print_r($link->innertext);
		echo '<br /><br />';
		
		# Get the main content block
		$nextBlock = $link->next_sibling();
		//print_r($nextBlock);	
		
		$htmlCounter = array();
		$counter = 0;
		$sectionCounter = 0;
		$links = array();
		# Walk through the dom and count paragraphs between H2 tags
		foreach ($nextBlock->children() as $bodyChild) {
			echo $bodyChild;
			
			if (($bodyChild->find('span')) && ($bodyChild->tag=='h2')){
				foreach ($bodyChild->find('span') as $span){
					$sectionCounter++;
					$spanText = $span->innertext;
					
					$spanNameArray = explode(' ', $spanText);
					$spanSlug = implode('', $spanNameArray);
					
					echo $spanSlug;
					echo ' < The Span Slug.<br />';
					
					$htmlCounter[$spanSlug]['slug'] = $spanSlug;
					$htmlCounter[$spanSlug]['text'] = $spanText;
					$htmlCounter[$spanSlug]['counter'] = $counter;
					$counter = 0;
					$links = array();
					//$htmlCounter[];
				}
			}
			
			
			if (($bodyChild->tag=='p') && ((count($bodyChild->find('a'))) == 1)){
				echo count($bodyChild->find('a'));
				$counter++;
				echo '<br />' . $counter . '<br />';
				foreach ($bodyChild->find('a') as $childLink){
					echo $link = $childLink->href;
					echo $title = $childLink->title;
					
					$titleArray = explode(' ', $title);
					$titleSlug = implode('', $titleArray);
					$sectionSlug = $htmlCounter[$spanSlug]['slug'];
					
					$htmlCounter[$spanSlug]['links'][$titleSlug]['slug'] = $titleSlug;
					$htmlCounter[$spanSlug]['links'][$titleSlug]['title'] = $title;
					$htmlCounter[$spanSlug]['links'][$titleSlug]['link'] = $link;
					
					$links[$sectionSlug][$titleSlug]['title'] = $title;
					$links[$sectionSlug][$titleSlug]['link'] = $link;
				}
			}			
			
			echo '<hr />';
		}
		
		echo '<pre>';
		print_r($htmlCounter);
		echo '</pre>';
		
		
		# Get the links.
		foreach ($nextBlock->find('h2') as $h2) {
				echo 'H2 Head: ';
				print_r($h2->innertext);
				echo '<br /><br />';	

				if ($h2->next_sibling() != ''){
					//while(count($h2Under->find('span')) != 0){
						$h2Under = $h2->next_sibling();
						echo $h2Under;
						//if (empty($h2Under->find('span'))){
							echo '<br />' . count($h2Under->find('span')) . '< Is this a span';
						//}
						echo $h2Under->next_sibling();
						
						foreach ($h2Under->find('a') as $anInnerLink){
							if($anInnerLink->getAttribute('rel') != 'nofollow'){
								if (($anInnerLink->href != '') && ($anInnerLink->title != '')) {
									$theExternalSiteLink = $anInnerLink->href;
									echo $h2->innertext;
									echo $anInnerLink->title;
									echo ' - ';
									echo $theExternalSiteLink;
									echo '<br /><br /><br />';
									
								}
							}
						
						}
					//}
				}
			
		}
		
		echo '<br /> <strong>Other tests:</strong> <br /><br />';
		
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