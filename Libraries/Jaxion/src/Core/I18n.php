<?php
namespace Intraxia\Jaxion\Core;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\I18n as I18nContract;

/**
 * Class I18n
 *
 * @package    Intraxia\Jaxion
 * @subpackage Core
 */
class I18n implements I18nContract, HasActions {
	/**
	 * Plugin basename
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Plugin path.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * I18n constructor.
	 *
	 * @param string $basename Plugin basename.
	 * @param string $path     Plugin path.
	 */
	public function __construct( $basename, $path ) {
		$this->basename = $basename;
		$this->path = $path;
	}

	/**
	 * {@inheritdoc}
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			$this->basename,
			false,
			basename( $this->path ) . '/languages/'
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'init',
				'method' => 'load_plugin_textdomain',
			),
		);
	}
}
