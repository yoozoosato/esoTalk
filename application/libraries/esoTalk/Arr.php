<?php namespace esoTalk;

class Arr extends \Laravel\Arr {
	
	/**
	 * Insert value(s) into an array, mostly an array_splice alias
	 * WARNING: original array is edited by reference, only boolean success is returned
	 *
	 * @param   array        the original array (by reference)
	 * @param   array|mixed  the value(s) to insert, if you want to insert an array it needs to be in an array itself
	 * @param   int          the numeric position at which to insert, negative to count from the end backwards
	 * @return  bool         false when array shorter then $pos, otherwise true
	 */
	public static function insert(array &$original, $value, $pos)
	{
		if (count($original) < abs($pos))
		{
			\Error::notice('Position larger than number of elements in array in which to insert.');
			return false;
		}

		array_splice($original, $pos, 0, $value);
		return true;
	}

	/**
	 * Insert value(s) into an array after a specific key
	 * WARNING: original array is edited by reference, only boolean success is returned
	 *
	 * @param   array        the original array (by reference)
	 * @param   array|mixed  the value(s) to insert, if you want to insert an array it needs to be in an array itself
	 * @param   string|int   the key after which to insert
	 * @return  bool         false when key isn't found in the array, otherwise true
	 */
	public static function insert_after_key(array &$original, $value, $key)
	{
		$pos = array_search($key, array_keys($original));
		if ($pos === false)
		{
			\Error::notice('Unknown key after which to insert the new value into the array.');
			return false;
		}

		return static::insert($original, $value, $pos + 1);
	}

	/**
	 * Insert value(s) into an array after a specific value (first found in array)
	 *
	 * @param   array        the original array (by reference)
	 * @param   array|mixed  the value(s) to insert, if you want to insert an array it needs to be in an array itself
	 * @param   string|int   the value after which to insert
	 * @return  bool         false when value isn't found in the array, otherwise true
	 */
	public static function insert_after_value(array &$original, $value, $search)
	{
		$key = array_search($search, $original);
		if ($key === false)
		{
			\Error::notice('Unknown value after which to insert the new value into the array.');
			return false;
		}

		return static::insert_after_key($original, $value, $key);
	}

}