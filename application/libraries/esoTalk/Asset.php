<?php namespace esoTalk;

use Laravel\File;
use Laravel\HTML;
use Laravel\URL;

class Asset extends \Laravel\Asset {
	
	public static function container($container = 'default')
	{
		if ( ! isset(static::$containers[$container]))
		{
			static::$containers[$container] = new Asset_Container($container);
		}

		return static::$containers[$container];
	}

}


class Asset_Container extends \Laravel\Asset_Container {
	
	/**
	 * Get all of the registered assets for a given type / group.
	 *
	 * @param  string  $group
	 * @return string
	 */
	protected function group($group)
	{
		if ( ! isset($this->assets[$group]) or count($this->assets[$group]) == 0) return '';

		$assets = $this->arrange($this->assets[$group]);

		$extension = $group == 'style' ? 'css' : 'js';

		if (count($assets) > 1 and C('esoTalk.aggregate_'.$extension))
		{
			$files = static::aggregate($assets, $extension);
		}
		else
		{
			$files = array();
			foreach ($assets as $data)
			{
				$files[] = '/'.$data['source'];
			}
		}

		$assets = '';

		foreach ($files as $file)
		{
			$assets .= HTML::$group($file);
		}
		
		return $assets;
	}


	public static function aggregate($assets, $extension)
	{
		// Construct an array of filenames, and get the maximum last modifiction time of all the files.
		$filenames = array();
		$last_mod_time = 0;
		foreach ($assets as $data)
		{
			$filenames[] = pathinfo($data['source'], PATHINFO_FILENAME);
			$last_mod_time = max($last_mod_time, File::modified(PUBLIC_PATH.$data['source']));
		}

		// Construct a filename for the aggregation file based on the individual filenames.
		$file = 'assets/'.$extension.'/aggregated/'.implode(',', $filenames).'.'.$extension;

		// If this file doesn't exist, or if it is out of date, generate and write it.
		if ( ! File::exists($file) or File::modified($file) < $last_mod_time)
		{
			$contents = '';

			// Get the contents of each of the files, fixing up image URL paths for CSS files.
			foreach ($assets as $data)
			{
				$content = File::get(PUBLIC_PATH.'/'.$data['source']);

				if ($extension == 'css')
				{
					$content = str_replace('url(', 'url('.URL::to_asset(pathinfo($data['source'], PATHINFO_DIRNAME)).'/', $content);
				}

				$contents .= $content.' ';
			}

			// Minify and write the contents.
			$function = 'minify_'.$extension;
			File::put($file, static::$function($contents));
		}

		return array($file);
	}


	/**
	 * Minify a CSS string by removing comments and whitespace.
	 *
	 * @param string $css The CSS to minify.
	 * @return string The minified result.
	 */
	public static function minify_css($css)
	{
		// Compress whitespace.
		$css = preg_replace('/\s+/', ' ', $css);

		// Remove comments.
		$css = preg_replace('/\/\*.*?\*\//', '', $css);

		return trim($css);
	}


	/**
	 * Minify a JavaScript string using JSMin.
	 *
	 * @param string $js The JavaScript to minify.
	 * @return string The minified result.
	 */
	public static function minify_js($js)
	{
		// require_once PATH_LIBRARY.'/vendor/jsmin.php';
		return \JSMin::minify($js);
	}

}