<?php

require_once(RSSPF_ROOT . "/includes/linkfinder/AB_subscription_builder.php");

class RSSPF_AB_Subscribe extends RSSPF_Module {

	/////////////////////////////
	// PARENT OVERRIDE METHODS //
	/////////////////////////////

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::start();
	}
	

	function add_to_feeder(){
		echo $this->build_ab_item_selector();
	}
	
	public function build_ab_item_selector() {
	    //echo 'test1';
			//if ( false === ( $ABLinksArray = get_transient( 'ab_links_array' ) ) ) {
				$ABSubscriptionBuilder = new AB_subscription_builder;
				$ABLinksArray = $ABSubscriptionBuilder->build_the_ref_array();
				//set_transient( 'ab_links_array', $ABLinksArray, 60*60*24*30 );
				//print_r($ABLinksArray);
			//}
			
			$ca = 0;
			$cb = 0;
			$cc = 0;
			
			if ($ABLinksArray == false){
				return "Academic Blogs not found";
			}
			
			$ab_items_selector = '<select>';
			
				foreach ($ABLinksArray as $genSubject){
					if ($ca == 0){
						$ab_items_selector .= '<option disabled="disabled" value="0">----topic----<hr /></option>';
						
					}					
					$ab_items_selector .= '<option value="' . $genSubject['slug'] . '">' . $genSubject['text'] . ' - ' . $ca . '</option>';
					if ($ca == 0){
						$ab_items_selector .= '<option disabled="disabled" value="0">--------<hr /></option>';
						$cb = 0;
					}
					$ca++;
					foreach ($genSubject['links'] as $subject){
						//if ($cb == 0){
							$ab_items_selector .= '<option disabled="disabled" value="0">----section----<hr /></option>';
						//}
						$ab_items_selector .= '<option value="' . $subject['slug'] . '">&nbsp;&nbsp;&nbsp;' . $subject['title'] . ' - ' . $cb . '</option>';
						
							$ab_items_selector .= '<option disabled="disabled" value="0">--------<hr /></option>';
						if ($cb == 0){	
							$ca = 0;
							$cc = 0;
						}
						$cb++;
						foreach ($subject['blogs'] as $blogObj){
							
							$ab_items_selector .= '<option value="' . $blogObj['slug'] . '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $blogObj['title'] . ' - ' . $cc . '</option>';
							if ($cc == 0){
								//$ab_items_selector .= '<option disabled="disabled" value="0"><hr /></option>';
								
								$cb = 0;
							}
							$cc++;
							
						}
					}

				}
			$ab_items_selector .= '</select>';	
			
			return $ab_items_selector;
	}
	
}

?>