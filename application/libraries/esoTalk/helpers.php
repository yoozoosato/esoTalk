<?php

/**
 * Retrieve a language line.
 *
 * @param  string  $key
 * @param  array   $replacements
 * @param  string  $language
 * @return string
 */
function T($key, $replacements = array(), $default = null)
{
	if (is_null($default) and ! is_array($replacements))
	{
		$default = $replacements;
		$replacements = array();
	}
	
	return __($key, $replacements)->get(null, $default);
}


function C($key, $default = null)
{
	return Laravel\Config::get($key, $default);
}