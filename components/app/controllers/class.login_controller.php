<?php

class LoginController extends SilkControllerBase
{
	function index($params)
	{
		$user_session = new SilkUserSession($params['login']);
		if ($user_session->login())
		{
			if (isset($_SESSION['redirect_to']))
			{
				$url = $_SESSION['redirect_to'];
				unset($_SESSION['redirect_to']);
				redirect($url);
			}
		}
		$this->set_by_ref('user_session', $user_session);
	}
	
	function logout($params)
	{
		SilkUserSession::logout();
		SilkResponse::redirect_to_action(array('component' => 'blog', 'action' => 'index', 'controller' => 'login'));
	}
}

# vim:ts=4 sw=4 noet
?>