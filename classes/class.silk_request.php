<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008 Ted Kulp
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

/**
 * Static methods for handling web requests.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkRequest extends SilkObject
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Sets up various things important for incoming requests
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	public static function setup()
	{
		#magic_quotes_runtime is a nuisance...  turn it off before it messes something up
		set_magic_quotes_runtime(false);
		
		# sanitize $_GET
		array_walk_recursive($_GET, array('SilkRequest', 'sanitize_get_var'));
		
		self::strip_slashes_from_globals();
		
		#Fix for IIS (and others) to make sure REQUEST_URI is filled in
		if (!isset($_SERVER['REQUEST_URI']))
		{
		    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
		    if(isset($_SERVER['QUERY_STRING']))
		    {
		        $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
		    }
		}
	}
	
	public static function handle_request()
	{
		self::setup();
		
		SilkRoute::load_routes();

		$params = array();
		try
		{
			$params = SilkRoute::match_route(SilkRequest::get_requested_page());
			$class_name = camelize($params['controller'] . '_controller');
			if (class_exists($class_name))
			{
				$controller = new $class_name;
			}
			else
			{
				throw new SilkControllerNotFoundException();
			}
			echo $controller->run_action($params['action'], $params);
		}
		catch (SilkRouteNotMatchedException $ex)
		{
			die("route not found");
		}
		catch (SilkControllerNotFoundException $ex)
		{
			die("controller not found");
		}
		catch (SilkViewNotFoundException $ex)
		{
			die("template not found");
		}
	}
	
	/**
	 * Removes possible javascript from a string
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	public static function sanitize_get_var(&$value, $key)
	{
		$value = eregi_replace('\<\/?script[^\>]*\>', '', $value);
	}
	
	/**
	 * Determines the uri that was requested
	 *
	 * @return string The uri of the page requested
	 * @author Ted Kulp
	 * @since 1.0
	 */
	public static function get_requested_uri($strip_query_string = false)
	{
		$result = '';

		if (isset($_SERVER['HTTP_HOST']))
		{
			$default_ports = array('https' => 443, 'http' => 80);
			$prefix = (!empty($_SERVER['HTTPS']) ? 'https' : 'http');
			$result .= $prefix . (($_SERVER['SERVER_PORT']!=$default_ports[$prefix]) ? ':'.$_SERVER['SERVER_PORT'] : '');
			$result .= '://' . $_SERVER['HTTP_HOST'];
		}
		
		if (isset($_SERVER['REQUEST_URI']))
		{
			$result .= $_SERVER['REQUEST_URI'];
		}
		else if (isset($_SERVER['SCRIPT_NAME']))
		{
			$result .= $_SERVER['SCRIPT_NAME'];
		}
		
		if( strpos( $result, "?" ) > 0 )
		{
			$result = substr( $result, 0, strpos( $result, "?"));
		}
		
		return $result;
	}
	
	public static function get_request_filename()
	{
		/*
		if (isset($_SERVER['PATH_TRANSLATED']))
		{
		     return str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']);
		}
		else if (isset($_ENV['PATH_TRANSLATED']))
		{
		     return str_replace('\\\\', '\\', $_ENV['PATH_TRANSLATED']);
		}
		else
		{*/
			return $_SERVER['SCRIPT_FILENAME'];
		//}
	}
	
	public static function get_calculated_url_base($whole_url = false)
	{
		$cur_url_dir = dirname($_SERVER['SCRIPT_NAME']);
		$cur_file_dir = dirname(self::get_request_filename());

		//Get the difference in number of characters between the root
		//and the requested file
		$len = strlen($cur_file_dir) - strlen(ROOT_DIR);
		
		//Now substract that # from the currently requested uri
		$result = substr($cur_url_dir, 0, strlen($cur_url_dir) - $len);
		
		if ($whole_url)
		{
			//Ok, we want the whole url of the base -- time for some magic
			//Grab the requested uri
			$requested_uri = self::get_requested_uri();
			
			//Figure out where in the string our calculated base is
			$pos = strpos($requested_uri, $result);
			if ($pos)
			{
				//If it exists, substr out the whole thing
				$result = substr($requested_uri, 0, $pos + strlen($result));
			}
		}

		return $result;
	}
	
	/**
	 * Calculate the total path of the requested page, suitable for sending off to the
	 * route processor.  Domain, subdir and script (if not using mod_rewrite) are
	 * calculated and stripped off.
	 *
	 * @return string The total path of the request page (e.g. /controller/action/id)
	 * @author Ted Kulp
	 * @since 1.0
	 **/
	public static function get_requested_page()
	{
		if (isset($_SERVER['PATH_INFO']))
		{
			return $_SERVER['PATH_INFO'];
		}
		else
		{
			$result = str_replace(self::get_calculated_url_base(true), '', self::get_requested_uri());
			if (starts_with($result, '/index.php'))
			{
				$result = substr($result, strlen('/index.php'));
			}
			return $result;
		}
	}

	/**
	 * Strips the slashes from all incoming superglobals,
	 * if necessary.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	public static function strip_slashes_from_globals()
	{
		if (get_magic_quotes_gpc())
		{
		    $_GET = self::stripslashes_deep($_GET);
		    $_POST = self::stripslashes_deep($_POST);
		    $_REQUEST = self::stripslashes_deep($_REQUEST);
		    $_COOKIE = self::stripslashes_deep($_COOKIE);
		    $_SESSION = self::stripslashes_deep($_SESSION);
		}
	}
	
	function stripslashes_deep($value)
	{
		if (is_array($value))
		{
			$value = array_map(array('SilkRequest', 'stripslashes_deep'), $value);
		}
		elseif (!empty($value) && is_string($value))
		{
			$value = stripslashes($value);
		}
		return $value;
	}
	
	/**
	 * Sanitize input to prevent against XSS and other nasty stuff.
	 * Taken from cakephp (http://cakephp.org)
	 * Licensed under the MIT License
	 *
	 * @return string The cleansed string
	 **/
	public static function clean_value($val)
	{
		if ($val == "")
		{
			return $val;
		}
		//Replace odd spaces with safe ones
		$val = str_replace(" ", " ", $val);
		$val = str_replace(chr(0xCA), "", $val);
		//Encode any HTML to entities (including \n --> <br />)
		$val = self::clean_html($val);
		//Double-check special chars and remove carriage returns
		//For increased SQL security
		$val = preg_replace("/\\\$/", "$", $val);
		$val = preg_replace("/\r/", "", $val);
		$val = str_replace("!", "!", $val);
		$val = str_replace("'", "'", $val);
		//Allow unicode (?)
		$val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val);
		//Add slashes for SQL
		//$val = $this->sql($val);
		//Swap user-inputted backslashes (?)
		$val = preg_replace("/\\\(?!&amp;#|\?#)/", "\\", $val);
		return $val;
	}
	
	/**
	 * Method to sanitize incoming html.
	 * Take from cakephp (http://cakephp.org)
	 * Licensed under the MIT License
	 *
	 * @return string The cleansed string
	 **/
	public static function clean_html($string, $remove = false)
	{
		if ($remove)
		{
			$string = strip_tags($string);
		}
		else
		{
			$patterns = array("/\&/", "/%/", "/</", "/>/", '/"/', "/'/", "/\(/", "/\)/", "/\+/", "/-/");
			$replacements = array("&amp;", "&#37;", "&lt;", "&gt;", "&quot;", "&#39;", "&#40;", "&#41;", "&#43;", "&#45;");
			$string = preg_replace($patterns, $replacements, $string);
		}
		return $string;
	}
	
	public static function has($name, $session = false)
	{
		if ($session)
			$_ARR = array_merge($_SESSION, $_REQUEST);
		else
			$_ARR = $_REQUEST;
		return array_key_exists($name, $_ARR);
	}
	
	public static function get($name, $clean = true, $session = false)
	{
		$value = '';
		if (array_key_exists($name, $_REQUEST))
			$value = $_REQUEST[$name];
		if ( ($session) && (array_key_exists($name, $_SESSION)))
			$value = $_SESSION[$name];
		if ($clean)
			$value = self::clean_value($value);
		return $value;
	}
	
	public static function get_cookie($name)
	{
		if (array_key_exists($name, $_COOKIE))
			return self::clean_value($_COOKIE[$name]);
		return '';
	}
	
	public static function set_cookie($name, $value, $expire = null)
	{
		setcookie($name, $value, $expire);
	}
}

class SilkControllerNotFoundException extends Exception
{
	
}

class SilkViewNotFoundException extends Exception
{
	
}

# vim:ts=4 sw=4 noet
?>