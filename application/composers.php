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
		Asset::container('global')->add('proto', 'addons/skins/Proto/assets/styles.css', 'base');

		Asset::container('global')->add('jquery', 'assets/js/lib/jquery.js');
		Asset::container('global')->add('jquery.misc', 'assets/js/lib/jquery.misc.js', 'jquery');
		Asset::container('global')->add('jquery.history', 'assets/js/lib/jquery.history.js', 'jquery');
		Asset::container('global')->add('jquery.scrollTo', 'assets/js/lib/jquery.scrollTo.js', 'jquery');
		Asset::container('global')->add('main', 'assets/js/global.js', 'jquery');

		if (Auth::guest())
		{
			Menu::add_to_user('login', HTML::link_to_login('Log In', null, array('class' => 'link-login')));

			Menu::add_to_user('join', HTML::link_to_join('Sign Up', null, array('class' => 'link-join')));
		}
		else
		{
			Menu::add_to_user('user', '<a href="'.URL::to_member('me').'">'.Auth::user()->avatar().Auth::user()->name().'</a>');

			Menu::add_to_user('settings', HTML::link_to_settings('Settings'));

			if (Auth::user()->is_admin())
			{
				Menu::add_to_user('administration', HTML::link_to_admin('Administration'));
			}

			Menu::add_to_user('logout', HTML::link('user/logout', 'Log Out', null, array('class' => 'link-logout')));
		}

		// 	// Fetch all unread notifications so we have a count for the notifications button.
		// 	$notifications = ET::activityModel()->getNotifications(-1);
		// 	$count = count($notifications);
		// 	$this->addToMenu("user", "notifications", "<a href='".URL("settings/notifications")."' id='notifications' class='popupButton ".($count ? "new" : "")."'><span>$count</span></a>");

		// 	// Show messages with these notifications.
		// 	$this->notificationMessages($notifications);


		// // Get the number of members currently online and add it as a statistic.
		// $online = ET::SQL()
		// 	->select("COUNT(*)")
		// 	->from("member")
		// 	->where("UNIX_TIMESTAMP()-:seconds<lastActionTime")
		// 	->bind(":seconds", C("esoTalk.userOnlineExpire"))
		// 	->exec()
		// 	->result();
		// $stat = Ts("statistic.online", "statistic.online.plural", number_format($online));
		// $stat = "<a href='".URL("members/online")."' class='link-membersOnline'>$stat</a>";
		// $this->addToMenu("statistics", "statistic-online", $stat);

		Menu::add_to_meta('copyright', '<a href="http://esotalk.com/">Powered by esoTalk</a>');
	})

);