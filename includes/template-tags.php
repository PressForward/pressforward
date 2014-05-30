<?php 

function get_the_source_title(){
	$st = pf_retrieve_meta(get_the_ID(), 'source_title');
	return $st;
}

function the_source_title(){
	echo get_the_source_title();
}

function get_the_original_post_date(){
	$opd = pf_retrieve_meta(get_the_ID(), 'item_date');
	return $opd;
}

function the_original_post_date(){
	echo get_the_original_post_date();
}

function get_the_item_author(){
	$ia = pf_retrieve_meta(get_the_ID(), 'item_author');
	return $ia;
}

function the_item_author(){
	echo get_the_item_author();
}	

function get_the_item_link(){
	$m = pf_retrieve_meta(get_the_ID(), 'item_link');
	return $m;
}

function the_item_link(){
	echo get_the_item_link();
}	

function get_the_item_feat_image(){
	$m = pf_retrieve_meta(get_the_ID(), 'item_feat_img');
	return $m;
}

function the_item_feat_image(){
	echo get_the_item_feat_image();
}	

function get_the_item_tags(){
	$m = pf_retrieve_meta(get_the_ID(), 'item_tags');
	return $m;
}

function the_item_tags(){
	echo get_the_item_tags();
}	

function get_the_repeats(){
	$m = pf_retrieve_meta(get_the_ID(), 'source_repeat');
	return $m;
}

function the_item_repeats(){
	echo get_the_repeats();
}	

function get_the_nomination_count(){
	$m = pf_retrieve_meta(get_the_ID(), 'nomination_count');
	return $m;
}

function the_nomination_count(){
	echo get_the_item_tags();
}	

function get_the_nominators(){
	$m = pf_retrieve_meta(get_the_ID(), 'nominator_array', false, false);
	return $m;
}

function the_nominators(){
	if (is_array(get_the_nominators())){
		$nomers = implode(", " , get_the_nominators());
		echo $nomers;
	} else {
		echo get_the_nominators();
	}
}	

function get_the_word_count(){
	$m = pf_retrieve_meta(get_the_ID(), 'pf_feed_item_word_count');
	return $m;
}

function the_word_count(){
	echo get_the_word_count();
}	