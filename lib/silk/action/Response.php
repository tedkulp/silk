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

use \silk\performance\Profiler;
use \silk\core\Object;
use \silk\action\Route;

/**
 * Methods for handling responses back to the client
 **/
class Response extends \Rack\Response
{
	public function __construct($status = 200, $headers = array(), $body = array())
	{
		parent::__construct($status, $headers, $body);
	}
	
	/**
	 * Returns the current encoding. Defaults to 'UTF-8'.
	 *
	 * @return string The current encoding
	 **/
	function getEncoding()
	{
		return 'UTF-8';
	}
	
	/**
	 * Sets the numeric status code for this response. Defaults to 200.
	 *
	 * @param int $code The numeric statuc code to set
	 * @return void
	 **/
	function setStatusCode($code = 200)
	{
		$this->status = $code;
	}
	
	/**
	 * Adds a header that should be returned with this response.
	 *
	 * @param string $name The name of the header to send (e.g. 'Response-type')
	 * @param string $value The value to send
	 * @return void
	 **/
	function addHeader($name, $value)
	{
		if ($name == '')
			$this->headers[] = $value;
		else
			$this->headers[$name] = $value;
	}
	
	/**
	 * Clear all the headers in the response
	 *
	 * @return void
	 **/
	function clearHeaders()
	{
		$this->headers = array();
	}

	/**
	 * Sets the body (content) of the request. Multiple calls
	 * to this method will append text to the existing content.
	 *
	 * @param string $val The content to append to the request
	 * @return string The given content -- because that's what Rack does
	 **/
	function body($val)
	{
		return $this->write($val);
	}

	/**
	 * Clears the body (content) from the request.
	 *
	 * @return void
	 **/
	function clearBody()
	{
		$this->body = array();
	}
	
	/**
	 * Redirects the browser to the given url.
	 *
	 * @param string $to The url to redirect to
	 * @return void
	 **/
	public static function sendRedirect($to)
	{
		$_SERVER['PHP_SELF'] = null;

		$config = loadConfig();
		if( !isset($config['debug']) ) $config['debug'] = false; 


		$schema = $_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http';
		$host = strlen($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME'];

		$components = parse_url($to);
		if(count($components) > 0)
		{
			$to =  (isset($components['scheme']) && starts_with($components['scheme'], 'http') ? $components['scheme'] : $schema) . '://';
			$to .= isset($components['host']) ? $components['host'] : $host;
			$to .= isset($components['port']) ? ':' . $components['port'] : '';
			if(isset($components['path']))
			{
				if(in_array(substr($components['path'],0,1),array('\\','/')))//Path is absolute, just append.
				{
					$to .= $components['path'];
				}
				//Path is relative, append current directory first.
				else if (isset($_SERVER['PHP_SELF']) && !is_null($_SERVER['PHP_SELF'])) //Apache
				{
					$to .= (strlen(dirname($_SERVER['PHP_SELF'])) > 1 ?  dirname($_SERVER['PHP_SELF']).'/' : '/') . $components['path'];
				}
				else if (isset($_SERVER['REQUEST_URI']) && !is_null($_SERVER['REQUEST_URI'])) //Lighttpd
				{
					if (endswith($_SERVER['REQUEST_URI'], '/'))
						$to .= (strlen($_SERVER['REQUEST_URI']) > 1 ? $_SERVER['REQUEST_URI'] : '/') . $components['path'];
					else
						$to .= (strlen(dirname($_SERVER['REQUEST_URI'])) > 1 ? dirname($_SERVER['REQUEST_URI']).'/' : '/') . $components['path'];
				}
			}
			$to .= isset($components['query']) ? '?' . $components['query'] : '';
			$to .= isset($components['fragment']) ? '#' . $components['fragment'] : '';
		}
		else
		{
			$to = $schema."://".$host."/".$to;
		}
		
		$response = response();

		if (headers_sent() && !(isset($config) && $config['debug'] == true))
		{
			// use javascript instead
			$response->clearBody();
			$response->body('<script type="text/javascript">
				<!--
					location.replace("'.$to.'");
				// -->
				</script>
				<noscript>
					<meta http-equiv="Refresh" content="0;URL='.$to.'">
				</noscript>');
			$response->render();
			exit;
		}
		else
		{
			if (isset($config) && $config['debug'] == true)
			{
				$response->clearBody();
				$response->body("Debug is on.  Redirecting disabled...  Please click this link to continue.<br />");
				$response->body("<a href=\"".$to."\">".$to."</a><br />");

				$response->body('<pre>');
				$response->body(Profiler::getInstance()->report());
				$response->body('</pre>');

				$response->render();
				exit();
			}
			else
			{
				$response->setStatusCode('302');
				$response->addHeader('Location', $to);
				$response->clearBody();
				$response->render();
				exit();
			}
		}
	}

	/**
	 * Given a has of key/value pairs, generates and redirects to a URL
	 * for this application.  Takes the same parameters as
	 * Response::create_url.
	 *
	 * @param array $params List of parameters used to create the url
	 * @return void
	 **/
	public static function redirectToAction($params = array())
	{
		self::sendRedirect(self::createUrl($params));
	}
	
	/**
	 * Given a hash of key/value pairs, generate a URL for this application.
	 * It will try and select the best URL for the situation by first going
	 * through all the routes and seeing which is the best match.  Then, any
	 * remaining parameters are put into the querystring.
	 *
	 * Given the following and assuming the default route list:
	 * <code>
	 * create_url(array('controller' => 'user', 'action' => 'list', 'some_param' => '1'))
	 * </code>
	 *
	 * Should generate:
	 * <code>
	 * /user/list?some_param=1
	 * </code>
	 *
	 * @param array $params List of parameters used to create the url
	 * @return string The completed url
	 **/
	public static function createUrl($params = array())
	{
		$new_url = '';
		$params = array_merge(array(
			'controller' => silk()->get('current_controller'),
			'component' => silk()->get('current_component'),
			'action' => silk()->get('current_action')
		), $params);
		foreach(Route::getRoutes() as $one_route)
		{
			$route_params = Route::getParamsFromRoute($one_route->route_string);
			$diff = array_diff($route_params, array_keys($params));
			if (!count($diff))
			{
				//This is the first route that should work ok for the given parameters
				//Even if it's short, we can add the rest on via the query string
				
				//However, we need to make sure that any default paramters in this route
				//match what was sent
				$exit = false;
				foreach ($one_route->defaults as $k=>$v)
				{
					//It's opposite day, people.
					//If the key exists in the default and it doesn't
					//match what was sent, then we move on.
					if (isset($params[$k]) && $params[$k] != $v)
					{
						$exit = true;
						continue;
					}
					unset($params[$k]);
				}
				if ($exit)
					continue;
				
				$new_url = $one_route->route_string;
				$similar = array_intersect($route_params, array_keys($params));
				foreach ($similar as $one_param)
				{
					$old_url = $new_url;
					$new_url = str_replace(":{$one_param}", $params[$one_param], $new_url);
					if ($old_url == $new_url)
					{
						$regex = '/\(\?P\<' . $one_param . '\>.+?\)/';
						$new_url = preg_replace($regex, $params[$one_param], $new_url);
					}
					unset($params[$one_param]);
				}
				break;
			}
		}
		if (count($params))
		{
			$new_url = $new_url . '?' . http_build_query($params, '', '&amp;');
		}
		
		if (starts_with($new_url, '?'))
			return Request::getCalculatedUrlBase(true, true) . $new_url;
		else
			return trim(Request::getCalculatedUrlBase(true, true), '/') . $new_url;
	}

	/**
	 * Shows a very close approximation of an Apache generated 404 error.
	 * It also sends the actual header along as well, so that generic
	 * browser error pages (like what IE does) will be displayed.
	 *
	 * @return void
	 **/
	function sendError404($message = '')
	{
		$this->setStatusCode('404');
		$this->write('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>');
		if (inDebug() && $message != '')
		{
			$this->write('<p>' . $message . '</p>');
		}
		$this->write('</body></html>');
	}
	
	/**
	 * Shows a very close approximation of an Apache generated 404 error.
	 * It also sends the actual header along as well, so that generic
	 * browser error pages (like what IE does) will be displayed.
	 *
	 * @return void
	 **/
	function sendError500($message = '')
	{
		$this->setStatusCode('500');
		$this->write('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>500 Internal Server Error</title>
</head><body>
<h1>Internal Server Error</h1>
<p>The Web server (running the Web Site) encountered an unexpected condition that prevented it from fulfilling the request by the client (e.g. your Web browser or our CheckUpDown robot) for access to the requested URL.</p>');
		if (inDebug() && $message != '')
		{
			$this->write('<p>' . $message . '</p>');
		}
		$this->write('</body></html>');
	}

	/**
	 * Converts a string into a valid DOM id.
	 *
	 * @param string $text The string to be converted
	 * @return string The converted string
	 **/
	public static function makeDomId($text)
	{
		return trim(preg_replace("/[^a-z0-9_\-]+/", '_', strtolower($text)), ' _');
	}
	
	/**
	 * Converts a string into a string suitable for use as
	 * a uri.
	 *
	 * @param string $text String to convert
	 * @param boolean $tolower If the result should be all lowercase (defaults to false)
	 * @return string The converted string
	 **/
	public static function slugify($text, $tolower = false)
	{
		// lowercase only on empty aliases
		if ($tolower == true)
		{
			$text = strtolower($text);
		}

		$text = preg_replace("/[^\w-]+/", "-", $text);
		$text = trim($text, '-');

		return $text;
	}

}

# vim:ts=4 sw=4 noet
