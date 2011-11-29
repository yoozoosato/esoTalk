<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

/**
 * The user controller handles session/user-altering actions such as logging in and out, signing up, and
 * resetting a password.
 *
 * @package esoTalk
 */
class User_Controller extends Base_Controller {

	public $restful = true;

	/**
	 * Set up filters for actions within this controller.
	 */
	public function __construct()
	{
		parent::__construct();

		// Set up a filter to kick out authorised users.
		// $this->filter('before', 'no_auth')->except('logout');

		$this->filter('before', 'csrf')->on('post');
	}

	/**
	 * Show the login sheet and handle input from the login form.
	 */
	public function get_login()
	{
		$this->layout->body = View::make('user/login');
		Page::body_class('sheetPage');
	}

	public function post_login()
	{
		// If the request is a POST request, attempt to log in using
		// the built-in Laravel authentication system. If we fail, flash
		// a message and redirect back to the login page.
		if (Auth::attempt(Input::get('username'), Input::get('password'), Input::get('remember')))
		{
			// Check if the user has confirmed their email. If they
			// haven't, we'll need to log them out and show a message.
			if ( ! Auth::user()->confirmed_email)
			{
				Auth::logout();

				$this->layout->body = View::of_message(array(
					'title' => 'Error',
					'message' => 'You have not yet confirmed your email address.'
				));

				return;
			}

			// If the login was successful, we either bind a 'success' 
			// flag to the layout for json output on an AJAX request,
			// or we just redirect to the return URI.
			else
			{
				Event::trigger('login_success', $this);

				if (Request::ajax())
				{
					$this->layout->success = true;
				}
				else
				{
					return Redirect::to(Input::get('return'));
				}
			}
		}
		else
		{
			Page::flash_warning('The username/password combination you entered is invalid. Please try again.');
			
			if (Request::ajax())
			{
				$this->layout->success = false;
			}
			else
			{
				return Redirect::to(Input::get('return'));
			}
		}
	}


	/**
	 * Log the user out and redirect.
	 */
	public function get_logout()
	{
		Auth::logout();

		return Redirect::to(Input::get('return'));
	}


/**
 * Show the sign up sheet and handle input from its form.
 *
 * @return void
 */
public function join()
{
	// If we're already logged in, get out of here.
	if (ET::$session->user) $this->redirect(URL(""));

	// If registration is closed, show a message.
	if (!C("esoTalk.registration.open")) {
		$this->renderMessage(T("Registration Closed"), T("message.registrationClosed"));
		return;
	}

	// Set the title and make sure this page isn't indexed.
	$this->title = T("Sign Up");
	$this->addToHead("<meta name='robots' content='noindex, noarchive'/>");

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("user/join");

	if ($form->isPostBack("cancel")) $this->redirect(URL(R("return")));

	// If the form has been submitted, validate it and add the member into the database.
	if ($form->validPostBack("submit")) {

		// Make sure the passwords match. The model will do the rest of the validation.
		if ($form->getValue("password") != $form->getValue("confirm"))
			$form->error("confirm", T("message.passwordsDontMatch"));

		if (!$form->errorCount()) {

			$data = array(
				"username" => $form->getValue("username"),
				"email" => $form->getValue("email"),
				"password" => $form->getValue("password"),
				"account" => ACCOUNT_MEMBER
			);

			if (!C("esoTalk.registration.requireEmailConfirmation")) $data["confirmedEmail"] = true;
			else $data["resetPassword"] = md5(uniqid(rand()));

			// Create the member.
			$model = ET::memberModel();
			$memberId = $model->create($data);

			// If there were validation errors, pass them to the form.
			if ($model->errorCount()) $form->errors($model->errors());

			else {

				// If we require the user to confirm their email, send them an email and show a message.
				if (C("esoTalk.registration.requireEmailConfirmation")) {
					$this->sendConfirmationEmail($data["email"], $data["username"], $memberId.$data["resetPassword"]);
					$this->renderMessage("Success!", T("message.confirmEmail"));
				}

				else {
					ET::$session->login($form->getValue("username"), $form->getValue("password"));
					$this->redirect(URL(""));
				}

				return;

			}

		}

	}

	$this->data("form", $form);
	$this->render("user/join");
}


/**
 * Send an email to a member containing a link which will confirm their email address.
 *
 * @param string $email The email of the member.
 * @param string $username The username of the member.
 * @param string $hash The hash stored in the member's resetPassword field, prefixed with the member's ID.
 * @return void
 */
protected function sendConfirmationEmail($email, $username, $hash)
{
	sendEmail($email,
		sprintf(T("email.confirmEmail.subject"), $username),
		sprintf(T("email.header"), $username).sprintf(T("email.confirmEmail.body"), C("esoTalk.forumTitle"), URL("user/confirm/".$hash, true))
	);
}


/**
 * Confirm a member's email address with the provided hash.
 *
 * @param string $hash The hash stored in the member's resetPassword field, prefixed with the member's ID.
 * @return void
 */
public function confirm($hash = "")
{
	// If email confirmation is not necessary, get out of here.
	if (!C("esoTalk.registration.requireEmailConfirmation")) return;

	// Split the hash into the member ID and hash.
	$memberId = (int)substr($hash, 0, strlen($hash) - 32);
	$hash = substr($hash, -32);

	// See if there is an unconfirmed user with this ID and password hash. If there is, confirm them and log them in.
	$result = ET::SQL()
		->select("1")
		->from("member")
		->where("memberId", $memberId)
		->where("resetPassword", md5($hash))
		->where("confirmedEmail=0")
		->exec();
	if ($result->numRows()) {

		// Mark the member as confirmed.
		ET::memberModel()->updateById($memberId, array(
			"resetPassword" => null,
			"confirmedEmail" => true
		));

		// Log them in and show a message.
		ET::$session->loginWithMemberId($memberId);
		$this->message(T("message.emailConfirmed"), "success");
	}

	// Redirect to the forum index.
	$this->redirect(URL(""));
}


/**
 * Resend an email confirmation email.
 *
 * @param string $username The username of the member to resend to.
 * @return void
 */
public function sendConfirmation($username = "")
{
	// If email confirmation is not necessary, get out of here.
	if (!C("esoTalk.registration.requireEmailConfirmation")) return;

	// Get the requested member.
	$member = reset(ET::memberModel()->get(array("m.username" => $username, "confirmedEmail" => false)));
	if ($member) {
		$this->sendConfirmationEmail($member["email"], $member["username"], $member["memberId"].$member["resetPassword"]);
		$this->renderMessage("Success!", T("message.confirmEmail"));
	}
	else $this->redirect(URL(""));
}


/**
 * Show the forgot password sheet, allowing a member to be sent an email containing a link to reset their
 * password.
 *
 * @return void
 */
public function forgot()
{
	// If the user is logged in, kick them out.
	if (ET::$session->user) $this->redirect(URL(""));

	// Set the title and make sure the page doesn't get indexed.
	$this->title = T("Forgot Password");
	$this->addToHead("<meta name='robots' content='noindex, noarchive'/>");

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("user/forgot");

	// If the cancel button was pressed, return to where the user was before.
	if ($form->isPostBack("cancel")) redirect(URL(R("return")));

	// If they've submitted their email to get a password reset link, email one to them!
	if ($form->validPostBack("submit")) {

		// Find the member with this email.
		$member = reset(ET::memberModel()->get(array("email" => $form->getValue("email"))));
		if (!$member)
			$form->error("email", T("message.emailDoesntExist"));

		else {

			// Update their record in the database with a special password reset hash.
			$hash = md5(uniqid(rand()));
			ET::memberModel()->updateById($member["memberId"], array("resetPassword" => $hash));

			// Send them email containing the link, and redirect to the home page.
			sendEmail($member["email"],
				sprintf(T("email.forgotPassword.subject"), $member["username"]),
				sprintf(T("email.header"), $member["username"]).sprintf(T("email.forgotPassword.body"), C("esoTalk.forumTitle"), URL("user/reset/".$member["memberId"].$hash, true))
			);
			$this->renderMessage("Success!", T("message.passwordEmailSent"));
			return;

		}

	}

	$this->data("form", $form);
	$this->render("user/forgot");
}


/**
 * Show a form allowing the user to reset their password, following on from a link sent to them by the forgot
 * password process.
 *
 * @param string $hashString The hash stored in the member's resetPassword field, prefixed by their ID.
 * @return void
 */
public function reset($hashString = "")
{
	if (empty($hashString)) return;

	// Split the hash into the member ID and hash.
	$memberId = (int)substr($hashString, 0, strlen($hashString) - 32);
	$hash = substr($hashString, -32);

	// Find the member with this password reset token. If it's an invalid token, take them back to the email form.
	$member = reset(ET::memberModel()->get(array("m.memberId" => $memberId, "resetPassword" => md5($hash))));
	if (!$member) return;

	// Construct a form.
	$form = ETFactory::make("form");
	$form->action = URL("user/reset/$hashString");

	// If the change password form has been submitted...
	if ($form->validPostBack("submit")) {

		// Make sure the passwords match. The model will do the rest of the validation.
		if ($form->getValue("password") != $form->getValue("confirm"))
			$form->error("confirm", T("message.passwordsDontMatch"));

		if (!$form->errorCount()) {

			$model = ET::memberModel();
			$model->updateById($memberId, array(
				"password" => $form->getValue("password"),
				"resetPassword" => null
			));

			// If there were validation errors, pass them to the form.
			if ($model->errorCount()) $form->errors($model->errors());

			else {
				$this->message(T("message.passwordChanged"));
				$this->redirect(URL(""));
			}

		}

	}

	$this->data("form", $form);
	$this->render("user/setPassword");
}

}