<?php
namespace PressForward\Interfaces;
interface Items
{
    public function insert_post( $post, $error = false, $item_id = false );
    public function update_post( $post, $error = false );
	public function delete_post( $postid, $force_delete = false );
	public function get_post(  $post = null, $output = OBJECT, $filter = 'raw' );
	public function is_error( $post );
}
