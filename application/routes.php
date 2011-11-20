<?php

$routes = array();

foreach (Event::trigger('routes') as $new_routes)
{
	if (is_array($new_routes))
	{
		$routes = array_merge($routes, $new_routes);
	}
}

return $routes + array(

	/*
	|--------------------------------------------------------------------------
	| Application Routes
	|--------------------------------------------------------------------------
	|
	| Simply tell Laravel the HTTP verbs and URIs it should respond to. It's a
	| piece of cake to create beautiful applications using the elegant RESTful
	| routing available in Laravel.
	|
	| Let's respond to a simple GET request to http://example.com/hello:
	|
	|		'GET /hello' => function()
	|		{
	|			return 'Hello World!';
	|		}
	|
	| You can even respond to more than one URI:
	|
	|		'GET /hello, GET /world' => function()
	|		{
	|			return 'Hello World!';
	|		}
	|
	| It's easy to allow URI wildcards using (:num) or (:any):
	|
	|		'GET /hello/(:any)' => function($name)
	|		{
	|			return "Welcome, $name.";
	|		}
	|
	*/

	'GET /' => 'conversations@index',

	'GET /user/login' => array('name' => 'login', 'user@login'),

	'GET /(:num)/(:any?)' => array('name' => 'conversation'),

);