<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008-2010 Ted Kulp
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

namespace silk\auth;

use \silk\core\Object;
use \silk\action\Request;

/**
 * Class to hold static methods for various aspects of the admin panel's 
 * inner working and security.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class UserSession extends Object
{
	private $params = array();
	static private $algorithm = 'md5';
	private $openid_param = 'openid';
	public $validation_errors = array();
	static private $current_user = null;

	function __construct($params = array())
	{
		parent::__construct();
		$this->params = $params;
		self::get_current_user_from_session();
	}

	function login()
	{
		if (isset($_REQUEST['openid_mode'])) //Coming back from an openid redirect -- just hit $_REQUEST directly
		{
			$consumer = $this->get_consumer();
			$response = $consumer->complete(Request::get_requested_uri(true));
			$msg = '';
			if ($response->status == Auth_OpenID_CANCEL)
			{
				// This means the authentication was cancelled.
				$this->validation_errors[] = 'Verification cancelled.';
		    }
			else if ($response->status == Auth_OpenID_FAILURE)
			{
				// Authentication failed; display the error message.
				$this->validation_errors[] = "OpenID authentication failed: " . $response->message;
		    }
			else if ($response->status == Auth_OpenID_SUCCESS)
			{
				$esc_identity = htmlentities($response->getDisplayIdentifier());
				
				$user = orm('user')->find_by_openid($esc_identity);
				if ($user != null)
				{
					self::$current_user = $user;
					$_SESSION['silk_user'] = $user;
					return true;
				}
				else
				{
					$this->validation_errors[] = "No user associated to this login";
				}
			}
		}
		else if ($this->params != null && is_array($this->params))
		{
			if ($this->params['username'] != '' && $this->params['password'] != '') //Username/password entered
			{
				$user = \silk\auth\User::find_by_username( $this->params['username'] );
				if ($user != null)
				{
					//Add salt
					if ($user->password == $this->encode_password($this->params['password']))
					{
						self::$current_user = $user;
						$_SESSION['silk_user'] = $user;
						return true;
					}
				}
				$this->validation_errors[] = 'Username or password incorrect.';
			}
			else if (isset($this->params['openid'])) //New openid entered
			{
				$consumer = $this->get_consumer();
				$auth_request = $consumer->begin($this->params['openid']);

				if ($auth_request)
				{
					if ($auth_request->shouldSendRedirect())
					{
						$redirect_url = $auth_request->redirectURL(Request::get_calculated_url_base(true), Request::get_requested_uri(true));
						redirect($redirect_url);
					}
				}
			}
		}
		return false;
	}
	
	static public function logout()
	{
		self::get_current_user_from_session();
		unset($_SESSION['silk_user']);
		self::$current_user = null;
	}
	
	static public function is_logged_in()
	{
		self::get_current_user_from_session();
		return self::$current_user != null;
	}
	
	static public function get_current_user()
	{
		self::get_current_user_from_session();
		return self::$current_user;
	}
	
	static private function get_current_user_from_session()
	{
		if( isset($_SESSION['silk_user']))
		{
			self::$current_user = $_SESSION['silk_user'];
		}
	}
	
	static public function include_openid()
	{
		$path = join_path(SILK_LIB_DIR, 'openid');
		silk()->add_include_path($path);
		include_once(join_path('Auth', 'OpenID', 'Consumer.php'));
		include_once(join_path('Auth', 'OpenID', 'FileStore.php'));
		silk()->remove_include_path($path);
	}
	
	static public function get_consumer()
	{
		self::include_openid();

		$store = new \Auth_OpenID_FileStore(join_path(ROOT_DIR, 'tmp', 'cache'));
		$consumer = new \Auth_OpenID_Consumer($store);
		return $consumer;
	}
	
	static public function encode_password($password)
	{
		return hash(self::$algorithm, $password);
	}
	
	static public function get_anonymous_user()
	{
		return new AnonymousUser();
	}
}

# vim:ts=4 sw=4 noet
?>
