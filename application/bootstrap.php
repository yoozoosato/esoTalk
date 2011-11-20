<?php

define('ROOT_PATH', realpath(APP_PATH.'../').'/');
define('ADDON_PATH', ROOT_PATH.'addons/');
define('UPLOAD_PATH', ROOT_PATH.'uploads/');

require LIBRARY_PATH.'esoTalk/helpers.php';

$plugins = IoC::resolve('esoTalk.plugins');

$enabled = array('BBCode');

foreach ($enabled as $plugin)
{
	$plugins->load($plugin);
}

