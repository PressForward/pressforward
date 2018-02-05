<?php
namespace Intraxia\Jaxion\Contract\Axolotl;

interface Serializes {
	/**
	 * Serializes the model's public data into an array.
	 *
	 * @return array
	 */
	public function serialize();
}
