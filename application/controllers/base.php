<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * A base controller for the esoTalk application.
 * 
 * Extend this controller and you get a whole bunch of stuff for free, including:
 * - JS/CSS aggregation (via $this->add_js_file() and $this->add_css_file())
 * - esoTalk-style messages (via $this->flash_message())
 * - Breadcrumb navigation functions ($this->push_navigation())
 * - 
 */
class Base_Controller extends Controller {

	public $layout = 'layout';

	public function __construct()
	{
		// If this request is being made via XMLHttpRequest, then we need to
		// format the output as a json document. We will use the 'json'
		// layout, and apply an 'after' filter to change the response's
		// Content-Type to application/json.
		if (Request::ajax())
		{
			$this->layout = 'json';

			$this->filter('after', 'json_response');
		}
	}


	// /**
	//  * When $this->pushNavigation() is called, we store the navigation ID in here so that we can use it when we
	//  * later call ET::$session->getNavigation().
	//  * @var string
	//  */
	// private $navigationId = false;




	// /**
	//  * Add a message to be displayed on the page. The messages will also be stored in the session so that if the
	//  * controller redirects instead of rendering, they will be displayed on the next response.
	//  *
	//  * @param string $message The message text.
	//  * @param mixed $options An array of options. Possible keys include:
	//  * 		id: a unique ID for the message. If specified, this message will overwrite any previous messages with
	//  * 			the same ID.
	//  * 		className: the CSS class to apply to the message.
	//  * 		callback: a JavaScript function to run when the message is dismissed.
	//  * 		If $options is a string, it will be used as the className.
	//  * @return void
	//  */
	// public function message($message, $options = "")
	// {
	// 	if (!is_array($options)) $options = array("className" => $options);
	// 	$options["message"] = $message;
	// 	if (!empty($options["id"])) $this->messages[$options["id"]] = $options;
	// 	else $this->messages[] = $options;
	// 	ET::$session->store("messages", $this->messages);
	// }


	// /**
	//  * Add an array of messages to be displayed on the page. This is the same as looping through an array and
	//  * calling message() for each item.
	//  *
	//  * @param array $messages An array of messages. Any non-numeric keys will be used as the ID for their message.
	//  * @param mixed $options An array of options; see message() for a full description. These options will be used
	//  * 		for all of the messages.
	//  * @return void
	//  */
	// public function messages($messages, $options = "")
	// {
	// 	if (!is_array($options)) $options = array("className" => $options);
	// 	foreach ($messages as $id => $message) {
	// 		$options["id"] = !is_numeric($id) ? $id : null;
	// 		$this->message(T("message.$message", $message), $options);
	// 	}
	// }


	// /**
	//  * Given an array of notifications, add messages to the controller to display the notifications in the
	//  * messages area.
	//  *
	//  * @param array $notifications An array of notifications, typically from ETActivityModel::getNotifications(-1).
	//  * @return void
	//  */
	// public function notificationMessages($notifications)
	// {
	// 	foreach ($notifications as $notification) {

	// 		// If we've already shown this notification as a message before, don't show it again.
	// 		if ($notification["time"] <= ET::$session->preference("notificationCheckTime")) continue;

	// 		$avatar = avatar($notification["fromMemberId"], $notification["avatarFormat"], "thumb");
	// 		$this->message("<a href='".$notification["link"]."' class='messageLink'><span class='action'>".$avatar.$notification["body"]."</span></a>", "popup autoDismiss hasSprite");
	// 	}

	// 	// Update the user's "notificationCheckTime" preference so these notifications won't be shown again.
	// 	ET::$session->setPreferences(array("notificationCheckTime" => time()));
	// }


	// /**
	//  * Common initialization for all controllers, called on every page load. This will add basic user links to
	//  * the "user" menu, and add core JS files and language definitions.
	//  *
	//  * If this is overridden, parent::init() should be called to maintain consistency between controllers.
	//  *
	//  * @return void
	//  */
	// public function init()
	// {
	// 	// Check for updates to the esoTalk software, but only if we're the root admin and we haven't checked in
	// 	// a while.
	// 	if (ET::$session->userId == C("esoTalk.rootAdmin") and C("esoTalk.admin.lastUpdateCheckTime") + C("esoTalk.updateCheckInterval") < time())
	// 		ET::upgradeModel()->checkForUpdates();

	// 	if ($this->responseType === RESPONSE_TYPE_DEFAULT) {

	// 		// If the user IS NOT logged in, add the 'login' and 'sign up' links to the bar.
	// 		if (!ET::$session->user) {
	// 			$this->addToMenu("user", "login", "<a href='".URL("user/login?return=".urlencode($this->selfURL))."' class='link-login'>".T("Log In")."</a>");
	// 			$this->addToMenu("user", "join", "<a href='".URL("user/join?return=".urlencode($this->selfURL))."' class='link-join'>".T("Sign Up")."</a>");
	// 		}

	// 		// If the user IS logged in, we want to display their name and appropriate links.
	// 		else {
	// 			$this->addToMenu("user", "user", "<a href='".URL("member/me")."'>".avatar(ET::$session->userId, ET::$session->user["avatarFormat"], "thumb").name(ET::$session->user["username"])."</a>");

	// 			// Fetch all unread notifications so we have a count for the notifications button.
	// 			$notifications = ET::activityModel()->getNotifications(-1);
	// 			$count = count($notifications);
	// 			$this->addToMenu("user", "notifications", "<a href='".URL("settings/notifications")."' id='notifications' class='popupButton ".($count ? "new" : "")."'><span>$count</span></a>");

	// 			// Show messages with these notifications.
	// 			$this->notificationMessages($notifications);

	// 			$this->addToMenu("user", "settings", "<a href='".URL("settings")."' class='link-settings'>".T("Settings")."</a>");

	// 			if (ET::$session->isAdmin())
	// 				$this->addToMenu("user", "administration", "<a href='".URL("admin")."' class='link-administration'>".T("Administration")."</a>");

	// 			$this->addToMenu("user", "logout", "<a href='".URL("user/logout")."' class='link-logout'>".T("Log Out")."</a>");
	// 		}

	// 		// Get the number of members currently online and add it as a statistic.
	// 		$online = ET::SQL()
	// 			->select("COUNT(*)")
	// 			->from("member")
	// 			->where("UNIX_TIMESTAMP()-:seconds<lastActionTime")
	// 			->bind(":seconds", C("esoTalk.userOnlineExpire"))
	// 			->exec()
	// 			->result();
	// 		$stat = Ts("statistic.online", "statistic.online.plural", number_format($online));
	// 		$stat = "<a href='".URL("members/online")."' class='link-membersOnline'>$stat</a>";
	// 		$this->addToMenu("statistics", "statistic-online", $stat);

	// 		$this->addToMenu("meta", "copyright", "<a href='http://esotalk.com/'>Powered by esoTalk".(ET::$session->isAdmin() ? " ".ESOTALK_VERSION : "")."</a>");

	// 		// Set up some default JavaScript files and language definitions.
	// 		$this->addJSFile("js/lib/jquery.js", true);
	// 		$this->addJSFile("js/lib/jquery.misc.js", true);
	// 		$this->addJSFile("js/lib/jquery.history.js", true);
	// 		$this->addJSFile("js/lib/jquery.scrollTo.js", true);
	// 		$this->addJSFile("js/global.js", true);
	// 		$this->addJSLanguage("message.ajaxRequestPending", "message.ajaxDisconnected", "Loading...", "Notifications");
	// 		$this->addJSVar("notificationCheckInterval", C("esoTalk.notificationCheckInterval"));

	// 		// If config/custom.css contains something, add it to be included in the page.
	// 		if (file_exists($file = PATH_CONFIG."/custom.css") and filesize($file) > 0) $this->addCSSFile("config/custom.css", true);

	// 	}

	// 	$this->trigger("init");
	// }


	// /**
	//  * Push an item onto the top of the navigation (breadcrumb) stack.
	//  *
	//  * This is simply a layer on top of ETSession::pushNavigation() which stores the navigation ID. Later in the
	//  * controller's life, the navigation ID is used to create a "back" button with ETSession::getNavigation().
	//  *
	//  * @see ETSession::pushNavigation()
	//  * @param string $id The navigation ID.
	//  * @param string $type The type of page this is.
	//  * @param string $url The URL to this page.
	//  * @return void
	//  */
	// public function pushNavigation($id, $type, $url)
	// {
	// 	$this->navigationId = $id;
	// 	ET::$session->pushNavigation($id, $type, $url);
	// }





	// 			$data["head"] = $this->head();
	// 			$data["pageTitle"] = ($this->title ? $this->title." - " : "").C("esoTalk.forumTitle");

	// 			// Add the forum title, or logo if the forum has one.
	// 			$logo = C("esoTalk.forumLogo");
	// 			$title = C("esoTalk.forumTitle");
	// 			if ($logo) $size = getimagesize($logo);
	// 			$data["forumTitle"] = $logo ? "<img src='".URL($logo)."' {$size[3]} alt='$title'/>" : $title;

	// 			// Add the details for the "back" button.
	// 			$data["backButton"] = ET::$session->getNavigation($this->navigationId);

	// 			// Get common menu items.
	// 			foreach ($this->menus as $menu => $items)
	// 				$data[$menu."MenuItems"] = $items->getContents();

	// 			// Add the body class.
	// 			$data["bodyClass"] = $this->bodyClass;

	// 			// Get messages.
	// 			$data["messages"] = $this->getMessages();







}