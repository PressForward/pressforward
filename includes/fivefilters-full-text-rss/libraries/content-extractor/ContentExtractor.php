<?php
/**
 * Content Extractor
 * 
 * Uses patterns specified in site config files and auto detection (hNews/PHP Readability) 
 * to extract content from HTML files.
 * 
 * @version 0.6
 * @date 2011-05-04
 * @author Keyvan Minoukadeh
 * @copyright 2011 Keyvan Minoukadeh
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPL v3
 */

class ContentExtractor
{
	protected static $tidy_config = array(
				 'clean' => true,
				 'output-xhtml' => true,
				 'logical-emphasis' => true,
				 'show-body-only' => false,
				 'new-blocklevel-tags' => 'article, aside, footer, header, hgroup, menu, nav, section, details, datagrid',
				 'new-inline-tags' => 'new-inline-tags: mark, time, meter, progress',
				 'wrap' => 0,
				 'drop-empty-paras' => true,
				 'drop-proprietary-attributes' => false,
				 'enclose-text' => true,
				 'enclose-block-text' => true,
				 'merge-divs' => true,
				 'merge-spans' => true,
				 'char-encoding' => 'utf8',
				 'hide-comments' => true
				 );
	protected $html;
	protected $config;
	protected $title;
	protected $body;
	protected $success = false;
	public $readability;	
	public $debug = false;

	function __construct($path, $fallback=null) {
		SiteConfig::set_config_path($path, $fallback);	
	}
	
	protected function debug($msg) {
		if ($this->debug) {
			$mem = round(memory_get_usage()/1024, 2);
			$memPeak = round(memory_get_peak_usage()/1024, 2);
			echo '* ',$msg;
			echo ' - mem used: ',$mem," (peak: $memPeak)\n";	
			ob_flush();
			flush();
		}
	}
	
	public function reset() {
		$this->html = null;
		$this->readability = null;
		$this->config = null;
		$this->title = null;
		$this->body = null;
		$this->success = false;
	}
	
	// returns true on success, false on failure
	// $smart_tidy indicates that if tidy is used and no results are produced, we will
	// try again without it. Tidy helps us deal with PHP's patchy HTML parsing most of the time
	// but it has problems of its own which we try to avoid with this option.
	public function process($html, $url, $smart_tidy=true) {
		$this->reset();
		
		// extract host name
		$host = @parse_url($url, PHP_URL_HOST);
		if (!($this->config = SiteConfig::build($host))) {
			// no match, so use defaults
			$this->config = new SiteConfig();
		}
		// store copy of config in our static cache array in case we need to process another URL
		SiteConfig::add_to_cache($host, $this->config);
		
		// use tidy (if it exists)?
		// This fixes problems with some sites which would otherwise
		// trouble DOMDocument's HTML parsing. (Although sometimes it
		// makes matters worse, which is why you can override it in site config files.)
		$tidied = false;
		if ($this->config->tidy && function_exists('tidy_parse_string') && $smart_tidy) {
			$this->debug('Using Tidy');
			$tidy = tidy_parse_string($html, self::$tidy_config, 'UTF8');
			if (tidy_clean_repair($tidy)) {
				$original_html = $html;
				$tidied = true;
				$html = $tidy->value;
			}
			unset($tidy);
		}
		
		// load and parse html
		$this->readability = new Readability($html, $url);		
		
		// we use xpath to find elements in the given HTML document
		// see http://en.wikipedia.org/wiki/XPath_1.0
		$xpath = new DOMXPath($this->readability->dom);

		// strip elements (using xpath expressions)
		foreach ($this->config->strip as $pattern) {
			$elems = @$xpath->query($pattern, $this->readability->dom);
			// check for matches
			if ($elems && $elems->length > 0) {
				$this->debug('Stripping '.$elems->length.' elements (strip)');
				for ($i=$elems->length-1; $i >= 0; $i--) {
					$elems->item($i)->parentNode->removeChild($elems->item($i));
				}
			}
		}
		// strip elements (using id and class attribute values)
		foreach ($this->config->strip_id_or_class as $string) {
			$string = strtr($string, array("'"=>'', '"'=>''));
			$elems = @$xpath->query("//*[contains(@class, '$string') or contains(@id, '$string')]", $this->readability->dom);
			// check for matches
			if ($elems && $elems->length > 0) {
				$this->debug('Stripping '.$elems->length.' elements (strip_id_or_class)');
				for ($i=$elems->length-1; $i >= 0; $i--) {
					$elems->item($i)->parentNode->removeChild($elems->item($i));
				}
			}
		}
		// strip images (using src attribute values)
		foreach ($this->config->strip_image_src as $string) {
			$string = strtr($string, array("'"=>'', '"'=>''));
			$elems = @$xpath->query("//img[contains(@src, '$string')]", $this->readability->dom);
			// check for matches
			if ($elems && $elems->length > 0) {
				$this->debug('Stripping '.$elems->length.' image elements');
				for ($i=$elems->length-1; $i >= 0; $i--) {
					$elems->item($i)->parentNode->removeChild($elems->item($i));
				}
			}
		}
		// strip elements using Readability.com and Instapaper.com ignore class names
		// .entry-unrelated and .instapaper_ignore
		// See https://www.readability.com/publishers/guidelines/#view-plainGuidelines
		// and http://blog.instapaper.com/post/730281947
		$elems = @$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' entry-unrelated ') or contains(concat(' ',normalize-space(@class),' '),' instapaper_ignore ')]", $this->readability->dom);
		// check for matches
		if ($elems && $elems->length > 0) {
			$this->debug('Stripping '.$elems->length.' .entry-unrelated,.instapaper_ignore elements');
			for ($i=$elems->length-1; $i >= 0; $i--) {
				$elems->item($i)->parentNode->removeChild($elems->item($i));
			}
		}
		
		// strip elements that contain style="display: none;"
		$elems = @$xpath->query("//*[contains(@style,'display:none')]", $this->readability->dom);
		// check for matches
		if ($elems && $elems->length > 0) {
			$this->debug('Stripping '.$elems->length.' elements with inline display:none style');
			for ($i=$elems->length-1; $i >= 0; $i--) {
				$elems->item($i)->parentNode->removeChild($elems->item($i));
			}
		}
		
		// try to get title
		foreach ($this->config->title as $pattern) {
			$elems = @$xpath->evaluate($pattern, $this->readability->dom);
			if (is_string($elems)) {
				$this->debug('Title expression evaluated as string');
				$this->title = trim($elems);
				break;
			} elseif ($elems instanceof DOMNodeList && $elems->length > 0) {
				$this->debug('Title matched');
				$this->title = $elems->item(0)->textContent;
				break;
			}
		}
		// try to get body
		foreach ($this->config->body as $pattern) {
			$elems = @$xpath->query($pattern, $this->readability->dom);
			// check for matches
			if ($elems && $elems->length > 0) {
				$this->debug('Body matched');
				if ($elems->length == 1) {				
					$this->body = $elems->item(0);
					// prune (clean up elements that may not be content)
					if ($this->config->prune) {
						$this->debug('Pruning content');
						$this->readability->prepArticle($this->body);
					}
					break;
				} else {
					$this->body = $this->readability->dom->createElement('div');
					$this->debug($elems->length.' body elems found');
					foreach ($elems as $elem) {
						$isDescendant = false;
						foreach ($this->body->childNodes as $parent) {
							if ($this->isDescendant($parent, $elem)) {
								$isDescendant = true;
								break;
							}
						}
						if ($isDescendant) {
							$this->debug('Element is child of another body element, skipping.');
						} else {
							// prune (clean up elements that may not be content)
							if ($this->config->prune) {
								$this->debug('Pruning content');
								$this->readability->prepArticle($elem);
							}
							$this->debug('Element added to body');
							$this->body->appendChild($elem);
						}
					}
				}
			}
		}		
		
		// auto detect?
		$detect_title = $detect_body = false;
		// detect title?
		if (!isset($this->title)) {
			if (empty($this->config->title) || (!empty($this->config->title) && $this->config->autodetect_on_failure)) {
				$detect_title = true;
			}
		}
		// detect body?
		if (!isset($this->body)) {
			if (empty($this->config->body) || (!empty($this->config->body) && $this->config->autodetect_on_failure)) {
				$detect_body = true;
			}
		}
		
		// check for hNews
		if ($detect_title || $detect_body) {
			// check for hentry
			$elems = @$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' hentry ')]", $this->readability->dom);
			if ($elems && $elems->length > 0) {
				$this->debug('hNews: found hentry');
				$hentry = $elems->item(0);
				
				if ($detect_title) {
					// check for entry-title
					$elems = @$xpath->query(".//*[contains(concat(' ',normalize-space(@class),' '),' entry-title ')]", $hentry);
					if ($elems && $elems->length > 0) {
						$this->debug('hNews: found entry-title');
						$this->title = $elems->item(0)->textContent;
						$detect_title = false;
					}
				}
				
				// check for entry-content.
				// according to hAtom spec, if there are multiple elements marked entry-content,
				// we include all of these in the order they appear - see http://microformats.org/wiki/hatom#Entry_Content
				if ($detect_body) {
					$elems = @$xpath->query(".//*[contains(concat(' ',normalize-space(@class),' '),' entry-content ')]", $hentry);
					if ($elems && $elems->length > 0) {
						$this->debug('hNews: found entry-content');
						if ($elems->length == 1) {
							// what if it's empty? (some sites misuse hNews - place their content outside an empty entry-content element)
							$e = $elems->item(0);
							if (($e->tagName == 'img') || (trim($e->textContent) != '')) {
								$this->body = $elems->item(0);
								// prune (clean up elements that may not be content)
								if ($this->config->prune) {
									$this->debug('Pruning content');
									$this->readability->prepArticle($this->body);
								}
								$detect_body = false;
							} else {
								$this->debug('hNews: skipping entry-content - appears not to contain content');
							}
							unset($e);
						} else {
							$this->body = $this->readability->dom->createElement('div');
							$this->debug($elems->length.' entry-content elems found');
							foreach ($elems as $elem) {
								$isDescendant = false;
								foreach ($this->body->childNodes as $parent) {
									if ($this->isDescendant($parent, $elem)) {
										$isDescendant = true;
										break;
									}
								}
								if ($isDescendant) {
									$this->debug('Element is child of another body element, skipping.');
								} else {
									// prune (clean up elements that may not be content)
									if ($this->config->prune) {
										$this->debug('Pruning content');
										$this->readability->prepArticle($elem);
									}								
									$this->debug('Element added to body');									
									$this->body->appendChild($elem);
								}
							}
							$detect_body = false;
						}
					}
				}
			}
		}

		// check for elements marked with instapaper_title
		if ($detect_title) {
			// check for instapaper_title
			$elems = @$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' instapaper_title ')]", $this->readability->dom);
			if ($elems && $elems->length > 0) {
				$this->debug('title found (.instapaper_title)');
				$this->title = $elems->item(0)->textContent;
				$detect_title = false;
			}
		}
		// check for elements marked with instapaper_body
		if ($detect_body) {
			$elems = @$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' instapaper_body ')]", $this->readability->dom);
			if ($elems && $elems->length > 0) {
				$this->debug('body found (.instapaper_body)');
				$this->body = $elems->item(0);
				// prune (clean up elements that may not be content)
				if ($this->config->prune) {
					$this->debug('Pruning content');
					$this->readability->prepArticle($this->body);
				}
				$detect_body = false;
			}
		}

		// still missing title or body, so we detect using Readability
		if ($detect_title || $detect_body) {
			$this->debug('Using Readability');
			// clone body if we're only using Readability for title (otherwise it may interfere with body element)
			if (isset($this->body)) $this->body = $this->body->cloneNode(true);
			$success = $this->readability->init();
		}
		if ($detect_title) {
			$this->debug('Detecting title');
			$this->title = $this->readability->getTitle()->textContent;
		}
		if ($detect_body && $success) {
			$this->debug('Detecting body');
			$this->body = $this->readability->getContent();
			if ($this->body->childNodes->length == 1 && $this->body->firstChild->nodeType === XML_ELEMENT_NODE) {
				$this->body = $this->body->firstChild;
			}
			// prune (clean up elements that may not be content)
			if ($this->config->prune) {
				$this->debug('Pruning content');
				$this->readability->prepArticle($this->body);
			}
		}
		if (isset($this->body)) {
			// remove scripts
			$this->readability->removeScripts($this->body);
			$this->success = true;
		}
		
		// if we've had no success and we've used tidy, there's a chance
		// that tidy has messed up. So let's try again without tidy...
		if (!$this->success && $tidied && $smart_tidy) {
			$this->debug('Trying again without tidy');
			$this->process($original_html, $url, false);
		}

		return $this->success;
	}
	
	private function isDescendant(DOMElement $parent, DOMElement $child) {
		$node = $child->parentNode;
		while ($node != null) {
			if ($node->isSameNode($parent))	return true;
			$node = $node->parentNode;
		}
		return false;
	}

	public function getContent() {
		return $this->body;
	}

	public function getTitle() {
		return $this->title;
	}
	
	public function getSiteConfig() {
		return $this->config;
	}
}
?>