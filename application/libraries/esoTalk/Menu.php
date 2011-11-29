<?php namespace esoTalk;

// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * The ETMenu class provides a way to collect menu items and then render them as list items in a menu.
 *
 * @package esoTalk
 */
class Menu {

	/**
	 * All of the instantiated menu containers.
	 *
	 * @var array
	 */
	protected static $containers = array();

	/**
	 * Get a menu container instance.
	 *
	 * @param  string            $container
	 * @return Menu_Container
	 */
	public static function container($container)
	{
		if ( ! isset(static::$containers[$container]))
		{
			static::$containers[$container] = new Menu_Container($container);
		}

		return static::$containers[$container];
	}

	/**
	 * Magic Method for calling methods on menu containers.
	 */
	public static function __callStatic($method, $parameters)
	{
		if (strpos($method, '_to') !== false)
		{
			list($method, $container) = explode('_to', $method);
			$container = ltrim($container, '_');
		}
		elseif (strpos($method, 'get_') !== false)
		{
			list($method, $container) = explode('_', $method);
		}

		$container = $container ?: array_shift($parameters);

		return call_user_func_array(array(static::container($container), $method), $parameters);
	}

}


class Menu_Container {
	
	/**
	 * A list of menu items.
	 * 
	 * @var array
	 */
	protected $items = array();

	/**
	 * A list of menu item keys to highlight.
	 * 
	 * @var array
	 */
	protected $highlight = array();

	/**
	 * Add an item to this menu container.
	 *
	 * @param  string $id       The name of the menu item.
	 * @param  string $html     The HTML content of the menu item.
	 * @param  mixed  $position The position to put the menu item, relative to other menu items.
	 * @return void
	 */
	public function add($key, $value, $position = false)
	{
		Arr::insert_with_key($this->items, $key, $value, $position);
	}

	/**
	 * Add a separator item to this menu collection.
	 *
	 * @param mixed $position The position to put the menu item, relative to other menu items.
	 * @see addToArrayString
	 * @return void
	 */
	public function add_separator($position = false)
	{
		Arr::insert($this->items, 'separator', $position);
	}

	/**
	 * Highlight a particular menu item.
	 *
	 * @param string $id The name of the menu item to highlight.
	 * @return void
	 */
	public function highlight($key)
	{
		$this->highlight[] = $key;
	}

	/**
	 * Get the contents of the menu as a string of <li> elements.
	 *
	 * @return string The HTML contents of the menu.
	 */
	public function get()
	{
		$return = '';

		foreach ($this->items as $k => $v)
		{
			if ($v == 'separator')
			{
				$return .= '<li class="sep"></li>'.PHP_EOL;
			}
			else
			{
				$return .= '<li class="item-'.$k.(in_array($k, $this->highlight) ? ' selected' : '').'">'.$v.'</li>'.PHP_EOL;
			}
		}

		return $return;
	}

	/**
	 * Get the number of menu items collected in this menu.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->items);
	}

}