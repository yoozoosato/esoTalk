<?php

$composers = array();

foreach (Event::trigger('composers') as $new_composers)
{
	if (is_array($new_composers))
	{
		$composers = array_merge($composers, $new_composers);
	}
}

return $composers + array(

	/*
	|--------------------------------------------------------------------------
	| View Names & Composers
	|--------------------------------------------------------------------------
	|
	| Named views give you beautiful syntax when working with your views.
	|
	| Here's how to define a named view:
	|
	|		'home.index' => array('name' => 'home')
	|
	| Now, you can create an instance of that view using the very expressive
	| View::of dynamic method. Take a look at this example:
	|
	|		return View::of_home();
	|
	| View composers provide a convenient way to add common elements to a view
	| each time it is created. For example, you may wish to bind a header and
	| footer partial each time the view is created.
	|
	| The composer will receive an instance of the view being created, and is
	| free to modify the view however you wish. Here is how to define one:
	|
	|		'home.index' => function($view)
	|		{
	|			//
	|		}
	|
	| Of course, you may define a view name and a composer for a single view:
	|
	|		'home.index' => array('name' => 'home', function($view)
	|		{
	|			//
	|		})	
	|
	*/

	'layout' => array('name' => 'layout', function($view)
	{		
		Asset::container('global')->add('base', 'assets/css/base.css');
		Asset::container('global')->add('jquery', 'assets/js/lib/jquery.js');
		Asset::container('global')->add('main', 'assets/js/global.js', 'jquery');

		Asset::container('global')->add('proto', 'addons/skins/Proto/assets/styles.css', 'base');
	})

);