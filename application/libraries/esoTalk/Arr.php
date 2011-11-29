<?php namespace esoTalk;

class Arr extends \Laravel\Arr {
	
	public static function insert(&$array, $value, $pos = false)
	{
		if ($pos === false)
		{
			$array[] = $value;
		}
		else
		{
			if ( ! is_int($pos))
			{
				$pos = array_search($pos, array_keys($array), true) + 1;
			}

			array_splice($array, $pos, 0, array($value));
		}

		return true;
	}

	public static function insert_with_key(&$array, $key, $value, $pos = false)
	{
		$keys = array_keys($array);
		$values = array_values($array);

		if ($pos === false)
		{
			$pos = count($keys);
		}
		elseif ( ! is_int($pos))
		{
			$pos = array_search($pos, $keys, true) + 1;
		}

		static::insert($keys, $key, $pos);
		static::insert($values, $value, $pos);

		$array = array_combine($keys, $values);

		return true;
	}

}