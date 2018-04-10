<?php

require_once(__DIR__ . '/../vendor/autoload.php');

$matcher = new GenderEngine\GenderEngine();

var_dump($matcher->test('bob'));
var_dump($matcher->getPreviousMatchConfidence());

echo '<br />';

var_dump($matcher->test('kyle'));
var_dump($matcher->getPreviousMatchConfidence());

echo '<br />';

var_dump($matcher->test('zacary'));
var_dump($matcher->getPreviousMatchConfidence());

echo '<br />';

var_dump($matcher->test('sallie'));
var_dump($matcher->getPreviousMatchConfidence());

echo '<br />';

var_dump($matcher->test('frankinstien'));
var_dump($matcher->getPreviousMatchConfidence());

echo '<br />';

var_dump($matcher->test('shaqleen'));
var_dump($matcher->getPreviousMatchConfidence());

echo '<br />';
