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

/**
 * Static methods for handling web responses.
 * 
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkResponse extends \silk\core\Object
{
	static private $instance = NULL;
	
	protected $body = array();
	protected $headers = array();
	protected $status = '200';
	protected $version = '1.1';

	protected $_statuses = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Time-out',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Large',
		415 => 'Unsupported Media Type',
		416 => 'Requested range not satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time-out'
	);
	
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Returns an instnace of the SilkResponse singleton.
	 *
	 * @return SilkResponse The singleton SilkResponse instance
	 * @author Ted Kulp
	 **/
	static public function get_instance()
	{
		if (self::$instance == NULL)
		{
			self::$instance = new SilkResponse();
		}
		return self::$instance;
	}
	
	function get_encoding()
	{
		return 'UTF-8';
	}
	
	function set_status_code($code = '200')
	{
		$this->status = $code;
	}
	
	function add_header($name, $value)
	{
		if ($name == '')
			$this->headers[] = $value;
		else
			$this->headers[$name] = $value;
	}
	
	function clear_headers()
	{
		$this->headers = array();
	}
	
	function body($str = '')
	{
		$this->body[] = $str;
	}
	
	function clear_body()
	{
		$this->body = array();
	}
	
	function render()
	{
		$this->headers[] = "HTTP/{$this->version} {$this->status} {$this->_statuses[(int)$this->status]}";
		$this->headers['Status'] = "{$this->status} {$this->_statuses[(int)$this->status]}";
		
		$this->send_headers();
		
		$body = join("\r\n", (array)$this->body);
		
		if (!in_debug() &&
			extension_loaded('zlib') &&
			$this->status == '200'
		)
		{
			$str = @ob_gzhandler($body, 5);
			if ($str !== false)
			{
				$body = $str;
			}
		}
		
		$split_ary = str_split($body, 8192);

		foreach ($split_ary as $one_item)
		{
			echo $one_item;
		}
	}
	
	function send_headers()
	{
		foreach ($this->headers as $k => $v)
		{
			if (is_int($k))
			{
				header($v, true);
			}
			else
			{
				header("{$k}: {$v}", true);
			}
		}
	}

	/**
	 * Redirects the browser to the given url.
	 *
	 * @param string The url to redirect to
	 * @return void
	 * @author Ted Kulp
	 **/
	public static function redirect($to)
	{
		$_SERVER['PHP_SELF'] = null;

		$config = load_config();
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
		
		$response = SilkResponse::get_instance();

		if (headers_sent() && !(isset($config) && $config['debug'] == true))
		{
			// use javascript instead
			$response->clear_body();
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
				$response->clear_body();
				$response->body("Debug is on.  Redirecting disabled...  Please click this link to continue.<br />");
				$response->body("<a href=\"".$to."\">".$to."</a><br />");

				$response->body('<pre>');
				$response->body(CmsProfiler::get_instance()->report());
				$response->body('</pre>');

				$response->render();
				exit();
			}
			else
			{
				$response->set_status_code('302');
				$response->add_header('Location', $to);
				$response->clear_body();
				$response->render();
				exit();
			}
		}
	}

	/**
	 * Given a has of key/value pairs, generates and redirects to a URL
	 * for this application.  Takes the same parameters as
	 * SilkResponse::create_url.
	 *
	 * @param array List of parameters used to create the url
	 * @return void
	 * @author Ted Kulp
	 **/
	public static function redirect_to_action($params = array())
	{
		SilkResponse::redirect(SilkResponse::create_url($params));
	}
	
	/**
	 * Given a hash of key/value pairs, generate a URL for this application.
	 * It will try and select the best URL for the situation by first going
	 * through all the routes and seeing which is the best match.  Then, any
	 * remaining parameters are put into the querystring.
	 *
	 * Given the following and assuming the default route list:
	 * @code
	 * create_url(array('controller' => 'user', 'action' => 'list', 'some_param' => '1'))
	 * @endcode
	 *
	 * Should generate:
	 * @code
	 * /user/list?some_param=1
	 * @endcode
	 *
	 * @param array List of parameters used to create the url
	 * @return string
	 * @author Ted Kulp
	 **/
	public static function create_url($params = array())
	{
		$new_url = '';
		foreach(\silk\action\Route::get_routes() as $one_route)
		{
			$route_params = \silk\action\Route::get_params_from_route($one_route->route_string);
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
			return SilkRequest::get_calculated_url_base(true, true) . $new_url;
		else
			return trim(SilkRequest::get_calculated_url_base(true, true), '/') . $new_url;
	}

	/**
	 * Shows a very close approximation of an Apache generated 404 error.
	 * It also sends the actual header along as well, so that generic
	 * browser error pages (like what IE does) will be displayed.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	function send_error_404()
	{
		$this->set_status_code('404');
		$this->body('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body></html>');
		$this->render();
		exit();
	}

	/**
	 * Converts a string into a valid DOM id.
	 *
	 * @param string The string to be converted
	 * @return string The converted string
	 * @author Ted Kulp
	 **/
	public static function make_dom_id($text)
	{
		return trim(preg_replace("/[^a-z0-9_\-]+/", '_', strtolower($text)), ' _');
	}
	
	/**
	 * Converts a string into a string suitable for use as
	 * a uri.
	 *
	 * @param string $text String to convert
	 * @return string The converted string
	 * @author Ted Kulp
	 */
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
?>