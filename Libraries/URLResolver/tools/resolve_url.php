#!/usr/bin/env php
<?php require_once('../URLResolver.php');
/*******************************************************************************
* Copyright (c) 2011-2014 by Matt Wright
* https://github.com/mattwright/URLResolver.php
*
* Licensed under The MIT License
* See URLResolver.php for full license text
*******************************************************************************/

if (count($argv) != 2 || !preg_match('/^https?:\/\//i', $argv[1])) {
	print "You must supply a URL:\n  ./resolve_url.php http://goo.gl/0GMP1\n";
	exit;
}

$resolver = new URLResolver();
$resolver->isDebugMode(true);
$resolver->setUserAgent('Mozilla/5.0 (compatible; URLResolver.php/1.0; +https://github.com/mattwright/URLResolver.php)');
$resolver->resolveURL($argv[1]);
