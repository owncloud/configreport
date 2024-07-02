<?php
$config = new OC\CodingStandard\Config();
$config
	->setUsingCache(true)
	->setIndent('    ')
	->getFinder()
	->exclude('templates')
	->exclude('vendor')
	->exclude('vendor-bin')
	->in(__DIR__);
return $config;