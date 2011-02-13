<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
//
// Copyright (c) 2008-2011 Ted Kulp
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

namespace silk\action;

use \silk\core\Object;
use \silk\action\Route;
use \silk\action\Response;

/**
 * Static methods for handling web requests.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class Request extends \Rack\Request
{

	/**
	 * Sets up various things important for incoming requests
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	public function setup()
	{
		# sanitize $_GET
		array_walk_recursive($_GET, array(get_called_class(), 'sanitize_get_var'));

		#Fix for IIS (and others) to make sure REQUEST_URI is filled in
		if (!isset($this->env['REQUEST_URI']))
		{
		    $this->env['REQUEST_URI'] = $this->env['SCRIPT_NAME'];
		    if(isset($this->env['QUERY_STRING']))
		    {
		        $this->env['REQUEST_URI'] .= '?'.$this->env['QUERY_STRING'];
		    }
		}
	}

	public function handleRequest()
	{
		//$this->setup();
		
		Route::loadRoutes();

		$params = array();
		try
		{
			list($params, $callback) = Route::matchRoute($this->getRequestedPage());
			if ($callback !== null)
			{
				echo call_user_func_array($callback, array($params, $this->getRequestedPage()));
			}
			else
			{
				$class_name = camelize($params['controller'] . '_controller');
				if (class_exists($class_name))
				{
					$controller = new $class_name;
				}
				else
				{
					throw new \silk\action\ControllerNotFoundException($class_name);
				}

				//Do it to it
				$controller->runAction($params['action'], $params);
			}
		}
		//TODO: Do some kind of 404/500 error page handling here through Response
		// The unhandled exceptions give better debugging info
		catch (\silk\action\RouteNotMatchedException $ex)
		{
			response()->sendError404($ex);
		}
		catch (\silk\action\ControllerNotFoundException $ex)
		{
			response()->sendError404($ex);
		}
		catch (\silk\action\ViewNotFoundException $ex)
		{
			response()->sendError404($ex);
		}
		catch (\SilkAccessException $ex)
		{
			response()->sendError500($ex);
		}
	}
	
	/**
	 * Removes possible javascript from a string
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	public static function sanitizeGetVar(&$value, $key)
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
	public function getRequestedUri($strip_query_string = false)
	{
		$result = '';

		if (isset($this->env['HTTP_HOST']))
		{
			$default_ports = array('https' => 443, 'http' => 80);
			$prefix = (!empty($this->env['HTTPS']) ? 'https' : 'http');
			$result .= $prefix . (($this->env['SERVER_PORT']!=$default_ports[$prefix]) ? ':'.$this->env['SERVER_PORT'] : '');
			$result .= '://' . $this->env['HTTP_HOST'];
		}

		if (isset($this->env['REQUEST_URI']))
		{
			$result .= $this->env['REQUEST_URI'];
		}
		else if (isset($this->env['SCRIPT_NAME']))
		{
			$result .= $this->env['SCRIPT_NAME'];
		}

		if( strpos( $result, "?" ) > 0 )
		{
			$result = substr( $result, 0, strpos( $result, "?"));
		}

		return $result;
	}

	public function getRequestFilename()
	{
		/*
		if (isset($this->env['PATH_TRANSLATED']))
		{
		     return str_replace('\\\\', '\\', $this->env['PATH_TRANSLATED']);
		}
		else if (isset($_ENV['PATH_TRANSLATED']))
		{
		     return str_replace('\\\\', '\\', $_ENV['PATH_TRANSLATED']);
		}
		else
		{*/
			return $this->env['SCRIPT_FILENAME'];
		//}
	}
	
	public function getCalculatedUrlBase($whole_url = false, $add_index_php = false)
	{
		$cur_url_dir = dirname($this->env['SCRIPT_NAME']);
		$cur_file_dir = dirname($this->getRequestFilename());

		$has_index_php = false;
		if (isset($this->env['REQUEST_URI']) && strpos($this->env['REQUEST_URI'], "index.php") !== false)
		{
			$has_index_php = true;
		}

		//Get the difference in number of characters between the root
		//and the requested file
		$len = strlen($cur_file_dir) - strlen(ROOT_DIR);

		//Now substract that # from the currently requested uri
		$result = substr($cur_url_dir, 0, strlen($cur_url_dir) - $len);

		if ($whole_url)
		{
			//Ok, we want the whole url of the base -- time for some magic
			//Grab the requested uri
			$requested_uri = $this->getRequestedUri();

			//Figure out where in the string our calculated base is
			if ($requested_uri != '')
			{
				$pos = strpos($requested_uri, $result, 7);
				if ($pos)
				{
					//If it exists, substr out the whole thing
					$result = substr($requested_uri, 0, $pos + strlen($result));
				}
			}
		}
		
		if ($add_index_php && $has_index_php)
			$result = $result . '/index.php';
		
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
	public function getRequestedPage()
	{
		$result = str_replace($this->getCalculatedUrlBase(true), '', $this->getRequestedUri());
		if (startsWith($result, '/index.php'))
		{
			$result = substr($result, strlen('/index.php'));
		}
		else if (startsWith($result, 'index.php'))
		{
			$result = substr($result, strlen('index.php'));
		}
		
		if ($result == '')
			$result = '/';
		
		return $result;
	}

	/**
	 * Sanitize input to prevent against XSS and other nasty stuff.
	 * Taken from cakephp (http://cakephp.org)
	 * Licensed under the MIT License
	 *
	 * @return string The cleansed string
	 **/
	public static function cleanValue($val)
	{
		if ($val == "")
		{
			return $val;
		}
		//Replace odd spaces with safe ones
		$val = str_replace(" ", " ", $val);
		$val = str_replace(chr(0xCA), "", $val);
		//Encode any HTML to entities (including \n --> <br />)
		$val = self::cleanHtml($val);
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
	public static function cleanHtml($string, $remove = false)
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
			$value = self::cleanValue($value);
		return $value;
	}

	public static function getCookie($name)
	{
		if (array_key_exists($name, $_COOKIE))
			return self::cleanValue($_COOKIE[$name]);
		return '';
	}

	public static function setCookie($name, $value, $expire = null)
	{
		setcookie($name, $value, $expire);
	}
}

class ControllerNotFoundException extends \Exception
{

}

class ViewNotFoundException extends \Exception
{

}

# vim:ts=4 sw=4 noet
