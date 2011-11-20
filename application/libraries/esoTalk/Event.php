<?php namespace esoTalk;

class Event {


	public static $attached = array();

	
	public static function attach($event, $callback)
	{
		static::$attached[$event][] = $callback;
	}


	public static function trigger($event, $params = array())
	{
		if (empty(static::$attached[$event]))
		{
			return array();
		}

		$return_values = array();

		foreach (static::$attached[$event] as $callback)
		{
			$return_values[] = call_user_func_array($callback, $params);
		}

		return $return_values;
	}

	public static function __callStatic($method, $parameters)
	{
		if (strpos($method, 'attach_') === 0)
		{
			return static::attach(substr($method, 7), \Arr::get($parameters, 0, array()));
		}

		throw new \BadMethodCallException("Method [$method] is not defined on the Event class.");
	}

}