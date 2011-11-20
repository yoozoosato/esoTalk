<?php

class My_User_Controller extends User_Controller {
	
	public function action_login()
	{
		echo 'bccode takes over!';
		//parent::action_login();

		return View::make($this->plugin->view('testview'), array('test' => 'THIS IS DATA'));
	}

}