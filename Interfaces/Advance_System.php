<?php
namespace PressForward\Interfaces;
interface Advance_System
{
	public function last_step_state();
	public function last_step_post_type();
    public function transition( $old_post, $new_post );
    public function transition_post_image( $old_post, $new_post );
	public function prep_bookmarklet( $post_id );
	public function to_last_step( $post = array() );
	public function to_nomination(  $post = array() );
	public function get_pf_type_by_id( $item_id, $post_type );
}
