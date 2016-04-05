<?php
namespace PressForward\Core\Schema;
/**
 * Classes and functions for dealing with feed items
 */

/**
 * Database class for manipulating feed items
 */
class Relationships {


	public function __construct() {
        // Maybe install custom table for relationships
        add_action( 'admin_init', array( $this, 'maybe_install_relationship_table' ) );
    }


	/**
	 * Checks to see whether the relationship table needs to be installed, and installs if so
	 *
	 * A regular activation hook won't work correctly given where how
	 * this file is loaded. Might change this in the future
	 */
	public function maybe_install_relationship_table() {
		if ( ! is_super_admin() ) {
			return;
		}

		global $wpdb;
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->prefix . 'pf_relationships' ) );

		if ( ! $table_exists ) {
			$this->install_relationship_table();
		}
	}

	/**
	 * Defines the relationship table schema and runs dbDelta() on it
	 */
	public static function install_relationship_table() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = array();
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pf_relationships (
						id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					user_id bigint(20) NOT NULL,
					item_id bigint(20) NOT NULL,
				relationship_type smallint(5) NOT NULL,
				value varchar(255),

				KEY user_id (user_id),
				KEY item_id (item_id),
				KEY relationship_type (relationship_type)
			)";

		dbDelta( $sql );
	}

}
