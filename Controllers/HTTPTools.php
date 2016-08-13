<?php
namespace PressForward\Controllers;

use Intraxia\Jaxion\Contract\Core\HasActions;
use PressForward\Interfaces\System;
use URLResolver;
/**
 * Readability stuff
 */

class HTTPTools implements HasActions {

	function __construct( URLResolver $resolver, System $system, Metas $meta ) {
        $this->url_resolver = $resolver;
		$this->system = $system;
		$this->meta = $meta;
	}

	public function action_hooks() {
		return array(
			array(
				'hook' => 'wp_head',
				'method' => 'pf_aggregation_forwarder',
			),
		);
	}

	public function resolve_source_url($url){
		$url = $this->resolve_a_url($url);
		$url_array = parse_url($url);
		if (empty($url_array['host'])){
			return;
		}
		$source_url = 'http://' . $url_array['host'];
		return $source_url;
	}

	public function resolve_full_url($url){
		$url = $this->resolve_a_url($url);
		return $url;
	}

	/**
	* Examine a URL and resolve it as needed.
	*
	* @since 3.4.5
	*
	* @param string $url A web address URI.
	* @return bool True value for a submitted URL that matches an aggregation service.
	*
	*/
	public function resolve_a_url($url){
		$url_array = parse_url($url);
		if (empty($url_array['host'])){
			return;
		} else {
			$check = $this->url_is_aggregation_service($url);
			if ($check){
				$url = $this->url_resolver->resolveURL($url)->getURL();
			}
		}

		return $url;

	}


	/**
	* Return an array of known aggregation services.
	*
	* @since 3.4.5
	*
	* @return array An array of URLs with aggregation URL host parts.
	*
	*/
	public function aggregation_services(){
		return array(
						'Google'  			=>	'google.com',
						'Tweeted Times'		=>  'tweetedtimes.com'
					);
	}

	/**
	* Check a URL for an aggregation service's forward and return true or false.
	*
	* @since 3.4.5
	*
	* @param string $url A web address URI.
	* @return bool True value for a submitted URL that matches an aggregation service.
	*
	*/
	public function url_is_aggregation_service($url){
		$check = false;
		$services = $this->aggregation_services();
		foreach ($services as $service){
			$pos = strpos($url, $service);
			if(!empty($pos)){
				$check = true;
			}
		}
		return $check;
	}

	function pf_aggregation_forwarder(){
		if(1 == $this->system->get_option('pf_link_to_source',0)){
			//http://webmaster.iu.edu/tools-and-guides/maintenance/redirect-meta-refresh.phtml ?
			$linked = $this->meta->get_post_pf_meta(get_the_ID(), 'item_link', true);
			//Need syndicate tag here.
			if (is_single() && ('' != $linked)){
				?>
				 <script type="text/javascript">console.log('You are being redirected to the source item.');</script>
				<META HTTP-EQUIV="refresh" CONTENT="10;URL=<?php echo $this->meta->get_post_pf_meta(get_the_ID(), 'item_link', true); ?>">
				<?php

			}
		}
	}

}
