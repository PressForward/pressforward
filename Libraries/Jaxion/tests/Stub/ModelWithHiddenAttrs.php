<?php
namespace Intraxia\Jaxion\Test\Stub;

class ModelWithHiddenAttrs extends PostAndMetaModel {
	protected $visible = array();

	protected $hidden = array(
		'ID',
		'children',
	);
}
