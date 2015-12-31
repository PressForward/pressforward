<?php
interface System_Items
{
    public function insert_post( $post, $error );
    public function update_post( $post, $error );
	public function delete_post( $postid, $force_delete );
	public function get_post(  $post = null, $output = OBJECT, $filter = 'raw' );
	public function is_error( $post );
}
