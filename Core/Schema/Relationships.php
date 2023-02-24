<?php
/**
 * Relationships data control.
 *
 * @package PressForward
 */

namespace PressForward\Core\Schema;

use Intraxia\Jaxion\Contract\Core\HasActions;

/**
 * Database class for manipulating relationships.
 */
class Relationships implements HasActions {
	/**
	 * Relationships database table name.
	 *
	 * @access public
	 * @var string
	 */
	public $table_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'pf_relationships';
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		$hooks = array(
			array(
				'hook'   => 'admin_init',
				'method' => 'maybe_install_relationship_table',
			),
		);

		return $hooks;
	}

	/**
	 * Checks to see whether the relationship table needs to be installed, and installs if so.
	 *
	 * A regular activation hook won't work correctly given where how
	 * this file is loaded. Might change this in the future.
	 */
	public function maybe_install_relationship_table() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( $this->schema_is_current() ) {
			return;
		}

		$this->install_relationship_table();
	}

	/**
	 * Determines whether the installed version of the DB schema is current.
	 *
	 * @since 4.2
	 *
	 * @return bool
	 */
	protected function schema_is_current() {
		$db_version = get_option( 'pf_relationships_db_version' );

		if ( $db_version && version_compare( $db_version, PF_VERSION, '>=' ) ) {
			return true;
		}

		update_option( 'pf_relationships_db_version', PF_VERSION );
		return false;
	}

	/**
	 * Defines the relationship table schema and runs dbDelta() on it.
	 */
	public static function install_relationship_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql   = array();
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

	/**
	 * Creates a new relationship record.
	 *
	 * @param array $args {
	 *   Array of optional arguments.
	 *   @var int    $user_id           User ID.
	 *   @var int    $item_id           Item ID.
	 *   @var int    $relationship_type Relationship type.
	 *   @var string $value             Value.
	 *   @var bool   $unique            Whether to enforce uniqueness. Default true.
	 * }
	 * @return int|false
	 */
	public function create( $args = array() ) {
		global $wpdb;

		$r = wp_parse_args(
			$args,
			array(
				'user_id'           => 0,
				'item_id'           => 0,
				'relationship_type' => 0,
				'value'             => '',
				'unique'            => true, // Generally you want one entry per user_id+item_id+relationship_type combo.
			)
		);

		if ( $r['unique'] ) {
			$existing = $this->get( $r );
			if ( ! empty( $existing ) ) {
				return false;
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$this->table_name,
			array(
				'user_id'           => $r['user_id'],
				'item_id'           => $r['item_id'],
				'relationship_type' => $r['relationship_type'],
				'value'             => $r['value'],
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
			)
		);

		$this->clean_relationship_cache_incrementor();

		return (int) $wpdb->insert_id;
	}

	/**
	 * Updates a relationship record.
	 *
	 * We assume that only the value ever needs to change.
	 *
	 * Any other params are interpreted as WHERE conditions.
	 *
	 * @param array $args {
	 *   Array of optional arguments.
	 *   @var int    $id                Relationship ID.
	 *   @var int    $user_id           User ID.
	 *   @var int    $item_id           Item ID.
	 *   @var int    $relationship_type Relationship type.
	 *   @var string $value             Value.
	 * }
	 * @return bool
	 */
	public function update( $args = array() ) {
		global $wpdb;

		$r = wp_parse_args(
			$args,
			array(
				'id'                => 0,
				'user_id'           => false,
				'item_id'           => false,
				'relationship_type' => false,
				'value'             => false,
			)
		);

		// If an 'id' is passed, use it. Otherwise build a WHERE.
		$where        = array();
		$where_format = array();
		if ( $r['id'] ) {
			$where['id']    = (int) $r['id'];
			$where_format[] = '%d';
		} else {
			foreach ( $r as $rk => $rv ) {
				if ( in_array( $rk, array( 'id', 'value' ), true ) ) {
					continue;
				}

				if ( false !== $rv ) {
					$where[ $rk ]   = $rv;
					$where_format[] = '%d';
				}
			}
		}

		$updated = false;

		// Sanity: Don't allow for empty $where.
		if ( ! empty( $where ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$updated = $wpdb->update(
				$this->table_name,
				array( 'value' => $r['value'] ),
				$where,
				array( '%s' ),
				$where_format
			);
		}

		$this->clean_relationship_cache_incrementor();

		return (bool) $updated;
	}

	/**
	 * Queries for relationship records.
	 *
	 * @param array $args {
	 *   Array of optional arguments.
	 *   @var int $id                Relationship ID.
	 *   @var int $user_id           User ID.
	 *   @var int $item_id           Item ID.
	 *   @var int $relationship_type Relationship type.
	 * }
	 * @return array
	 */
	public function get( $args = array() ) {
		global $wpdb;

		$r = wp_parse_args(
			$args,
			array(
				'id'                => 0,
				'user_id'           => false,
				'item_id'           => false,
				'relationship_type' => false,
			)
		);

		// Attempt to fetch items from cache. Single items not currently cached.
		$cached    = false;
		$cache_key = false;
		if ( empty( $r['id'] ) ) {
			// For simplicity, each combination of arguments is cached separately.
			$last_changed = wp_cache_get( 'last_changed', 'pf_relationships' );
			if ( ! $last_changed ) {
				$last_changed = microtime();
				wp_cache_set( 'last_changed', $last_changed, 'pf_relationships' );
			}

			$cache_key = md5( wp_json_encode( $r ) ) . '_' . $last_changed;
		}

		if ( $cache_key ) {
			$cached = wp_cache_get( $cache_key, 'pf_relationships' );
		}

		if ( false === $cached ) {
			$sql[] = "SELECT * FROM {$this->table_name}";

			// If an ID is passed, use it. Otherwise build WHERE from params.
			$where = array();
			if ( $r['id'] ) {
				$where[] = $wpdb->prepare( 'id = %d', $r['id'] );
			} else {
				foreach ( $r as $rk => $rv ) {
					if ( in_array( $rk, array( 'user_id', 'item_id', 'relationship_type' ), true ) && false !== $rv ) {
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$where[] = $wpdb->prepare( "{$rk} = %d", $rv );
					}
				}
			}

			if ( ! empty( $where ) ) {
				$sql[] = 'WHERE ' . implode( ' AND ', $where );
			}

			$sql = implode( ' ', $sql );
			if ( $r['user_id'] ) {
				$sql .= ' AND user_id = ' . $r['user_id'];
			}

			// phpcs:ignore WordPress.DB
			$results = $wpdb->get_results( $sql );

			if ( $cache_key ) {
				wp_cache_set( $cache_key, $results, 'pf_relationships' );
			}
		} else {
			$results = $cached;
		}

		return $results;
	}

	/**
	 * Deletes records based on params passed.
	 *
	 * @param array $args See $args array in \PressForward\Core\Schema\Relationships::get().
	 */
	public function delete( $args = array() ) {
		global $wpdb;

		$id = 0;

		if ( ! empty( $args['id'] ) ) {
			$id = $args['id'];
		} else {
			$relationships = $this->get( $args );
			// Assume it's the first one!
			if ( ! empty( $relationships ) ) {
				$id = $relationships[0]->id;
			}
		}

		$deleted = false;
		if ( $id ) {
			// phpcs:ignore WordPress.DB
			$d       = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE id = %d", $id ) );
			$deleted = false !== $d;
		}

		$this->clean_relationship_cache_incrementor();

		return $deleted;
	}

	/**
	 * Invalidates the cache incrementor.
	 */
	protected function clean_relationship_cache_incrementor() {
		wp_cache_delete( 'last_changed', 'pf_relationships' );
	}
}
