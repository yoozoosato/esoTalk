<?php namespace esoTalk;

/**
 * Class for managing packages (plugins, skins, languages.)
 */
class Packages {

	public $directory = '';

	public $type = '';

	public $packages = array();

	public function __construct($directory, $type)
	{
		$this->directory = $directory;
		$this->type = $type;
	}

	/**
	 * Load the package with the given name.
	 */
	public function load($name)
	{
		$directory = $this->directory.$name.'/';
		include $directory.$this->type.'.php';
		$class = ucfirst($this->type).'_'.$name;
		$this->packages[$name] = new $class($directory);
	}

	/**
	 * Get a list of all packages and their information.
	 */
	public static function info()
	{
		$dir = new DirectoryIterator($this->directory);
	}

}