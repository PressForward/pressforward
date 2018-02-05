<?php
namespace Intraxia\Jaxion\Core;

use MyCLabs\Enum\Enum;

/**
 * Class ConfigType
 *
 * Enumeration of Configuaration types.
 *
 * @package    Intraxia\Jaxion
 * @subpackage Core
 */
class ConfigType extends Enum {
	const PLUGIN = 'plugin';
	const THEME = 'theme';
	const MU_PLUGIN = 'mu-plugin';
}
