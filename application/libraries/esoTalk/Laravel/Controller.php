<?php namespace esoTalk\Laravel;

use Laravel\IoC;

abstract class Controller extends \Laravel\Routing\Controller {

	public static function resolve($controller)
	{
		// If the controller is registered in the IoC container, we will resolve
		// it out of the container. Using constructor injection on controllers
		// via the container allows more flexible and testable applications.
		if (IoC::registered('controllers.'.$controller))
		{
			$controller = IoC::resolve('controllers.'.$controller);
		}
		else
		{
			$controller = str_replace(' ', '_', ucwords(str_replace('.', ' ', $controller))).'_Controller';

			$controller = new $controller;
		}

		// If the controller has specified a layout to be used when rendering
		// views, we will instantiate the layout instance and set it to the
		// layout property, replacing the string layout name.
		if ( ! is_null($controller->layout))
		{
			$controller->layout = View::make($controller->layout);
		}

		return $controller;
	}

}