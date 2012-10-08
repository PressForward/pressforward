<?php

require_once(RSSPF_ROOT . "/lib/simple_html_dom.php");
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
	

	public function build_ab_item_selector() {
	
			if ( false === ( $ABLinksArray = get_transient( 'ab_links_array' ) ) ) {
				$ABSubscriptionBuilder = new AB_subscription_builder;
				$ABLinksArray = $ABSubscriptionBuilder->build_the_ref_array();
				set_transient( 'ab_links_array', $ABLinksArray, 60*60*24*30 );
				//print_r($ABLinksArray);
			}
			$ca = 0;
			$cb = 0;
			$cc = 0;
			
			echo '<select>';
				foreach ($ABLinksArray as $genSubject){
					if ($ca == 0){
						echo '<option disabled="disabled" value="0">----topic----<hr /></option>';
						
					}					
					echo '<option value="' . $genSubject['slug'] . '">' . $genSubject['text'] . ' - ' . $ca . '</option>';
					if ($ca == 0){
						echo '<option disabled="disabled" value="0">--------<hr /></option>';
						$cb = 0;
					}
					$ca++;
					foreach ($genSubject['links'] as $subject){
						//if ($cb == 0){
							echo '<option disabled="disabled" value="0">----section----<hr /></option>';
						//}
						echo '<option value="' . $subject['slug'] . '">&nbsp;&nbsp;&nbsp;' . $subject['title'] . ' - ' . $cb . '</option>';
						
							echo '<option disabled="disabled" value="0">--------<hr /></option>';
						if ($cb == 0){	
							$ca = 0;
							$cc = 0;
						}
						$cb++;
						foreach ($subject['blogs'] as $blogObj){
							
							echo '<option value="' . $blogObj['slug'] . '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $blogObj['title'] . ' - ' . $cc . '</option>';
							if ($cc == 0){
								//echo '<option disabled="disabled" value="0"><hr /></option>';
								
								$cb = 0;
							}
							$cc++;
							
						}
					}

				}
			echo '</select>';	
	}
	
}

?>