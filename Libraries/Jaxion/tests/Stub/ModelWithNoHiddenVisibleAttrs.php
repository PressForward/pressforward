<?php
namespace Intraxia\Jaxion\Test\Stub;

class ModelWithNoHiddenVisibleAttrs extends PostAndMetaModel {
	protected $hidden = array();

	protected $visible = array();
}
