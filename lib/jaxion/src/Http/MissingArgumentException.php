<?php
namespace Intraxia\Jaxion\Http;

use RuntimeException;

/**
 * Class MissingArgumentException
 *
 * Thrown when attempting to an add an endpoint without all the required arguments.
 *
 * @package Intraxia\Jaxion
 * @subpackage Http
 */
class MissingArgumentException extends RuntimeException
{
}
