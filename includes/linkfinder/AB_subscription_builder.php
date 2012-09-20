<?php

require_once('simple_html_dom.php');
$dom = new simple_html_dom;

class ABSubscriptions {

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
	# via http://stackoverflow.com/questions/2668854/sanitizing-strings-to-make-them-url-and-filename-safe
	function sanitize($string, $force_lowercase = true, $anal = false) {
		$strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
					   "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
					   "—", "–", ",", "<", ".", ">", "/", "?");
		$clean = trim(str_replace($strip, "", strip_tags($string)));
		$clean = preg_replace('/\s+/', "-", $clean);
		$clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
		return ($force_lowercase) ?
			(function_exists('mb_strtolower')) ?
				mb_strtolower($clean, 'UTF-8') :
				strtolower($clean) :
			$clean;
	}

	function slugger($string){

		$string = strip_tags($string);
		$stringArray = explode(' ', $string);
		$stringSlug = '';
		foreach ($stringArray as $stringPart){
			$stringSlug .= ucfirst($stringPart);
		}
		$stringSlug = str_replace('&amp;','&', $stringSlug);
		//$charsToElim = array('?','/','\\');
		$stringSlug = $this->sanitize($stringSlug, false, true);
		
		return $stringSlug;
		
	}
	
	function get_spam_sites(){
		
		$spamsites = array('http://www.buy-wellbutrin.com/', 'http://www.mycaal.com/');
		
		return $spamsites;
		
	}

	# to fill the blog property of the array. 
	# PS... How often does this get updated?
	function getLinksFromSection ($sectionURL){		
		set_time_limit(0);
		$html = file_get_html($sectionURL);
		
		$blogs = array();
		$c = 0;
		foreach ($html->find('#bodyContent') as $body){
			foreach ($body->find('a') as $link){
				if (!in_array(($link->href), $this->spamsites())){ 
					if ($link->rel == 'nofollow'){
						$URL = $link->href;
						$title = $link->innertext;
						$slug = $this->slugger($title);
						$blogs[$slug]['slug'] = $slug;
						$blogs[$slug]['url'] = $URL;
						$blogs[$slug]['title'] = htmlspecialchars(strip_tags($title));
					}
				}
				else {
					
				}
			}
		}
		
		return $blogs;
		
	}

	function build_the_ref_array()
	{

		//Random article for testing.
		$html = file_get_html('http://academicblogs.org/index.php/Main_Page');
		
		# Get the title page
		foreach ($html->find('h1') as $link){
			
		//	if (($link->plaintext == '[edit] External links') || ($link->plaintext == '[edit] References') ){
				
				# Get the main content block
				$nextBlock = $link->next_sibling();
				//print_r($nextBlock);	
				
				$htmlCounter = array();
				$counter = 0;
				$sectionCounter = 0;
				$links = array();
				# Walk through the dom and count paragraphs between H2 tags
				foreach ($nextBlock->children() as $bodyChild) {
					
					if (($bodyChild->find('span')) && ($bodyChild->tag=='h2')){
						foreach ($bodyChild->find('span') as $span){
							$sectionCounter++;
							$spanText = $span->innertext;
							
							$spanNameArray = explode(' ', $spanText);
							$spanSlug = '';
							foreach ($spanNameArray as $spanNamePart){
								$spanSlug .= htmlentities(ucfirst($spanNamePart));
							}
							$spanSlug = sanitize($spanSlug, false, true);
							
							$htmlCounter[$spanSlug]['slug'] = $spanSlug;
							$htmlCounter[$spanSlug]['text'] = htmlspecialchars(strip_tags($spanText));
							$htmlCounter[$spanSlug]['counter'] = $counter;
							$counter = 0;
							$links = array();
							//$htmlCounter[];
						}
					}
					
					if (($bodyChild->tag=='p') && ((count($bodyChild->find('a'))) == 1) && ((count($bodyChild->find('a[class=new]'))) == 0)){
						
						$counter++;
						
						foreach ($bodyChild->find('a') as $childLink){
							$link = $childLink->href;
							$title = $childLink->title;
							
							if (!in_array($link, $spamsites)){
								$titleArray = explode(' ', $title);
								$titleSlug = '';
								foreach ($titleArray as $titlePart){
									$titleSlug .= htmlentities(ucfirst($titlePart));
								}
								//$charsToElim = array('?','/','\\');
								$titleSlug = sanitize($titleSlug, false, true);
								
								$link = 'http://academicblogs.org' . $link;
								
								$sectionSlug = $htmlCounter[$spanSlug]['slug'];
								
								$htmlCounter[$spanSlug]['links'][$titleSlug]['slug'] = $titleSlug;
								$htmlCounter[$spanSlug]['links'][$titleSlug]['title'] = htmlspecialchars(strip_tags($title));
								$htmlCounter[$spanSlug]['links'][$titleSlug]['link'] = $link;
								//if ($childLink->){
									$htmlCounter[$spanSlug]['links'][$titleSlug]['blogs'] = $this->getLinksFromSection($link);
								//}
								
								$links[$sectionSlug][$titleSlug]['title'] = $title;
								$links[$sectionSlug][$titleSlug]['link'] = $link;
							} else {
								
								$counter--;
							}
						}
					}			
					
					
				}
				
		}
	
	}

}

?>