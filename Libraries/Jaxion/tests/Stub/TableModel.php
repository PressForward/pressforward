<?php
namespace Intraxia\Jaxion\Test\Stub;

use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable;

class TableModel extends Model implements UsesCustomTable{
	protected $fillable = array(
		'title',
		'text',
	);

	protected $guarded = array(
		'ID',
	);

	protected $visible = array(
		'title',
		'text',
		'url',
	);

	public static function get_table_name() {
		return 'custom';
	}

	/**
	 * Get the attribute used as the primary key.
	 *
	 * @return string
	 */
	public static function get_primary_key() {
		return 'ID';
	}
}
