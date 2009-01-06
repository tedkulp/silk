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
 * Static methods for handling web responses.
 * 
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkResponse extends SilkObject
{
	function __construct()
	{
		parent::__construct();
	}

	function get_encoding()
	{
		return 'UTF-8';
	}

	/**
	 * Redirects a user to given url.
	 *
	 * @param $to The url to redirect to
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	public static function redirect($to)
	{
		$_SERVER['PHP_SELF'] = null;

		$config = array();
		/*
		try
		{
			$config = cms_config();
		}
		catch (Exception $e)
		{
		}
		*/

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

		if (headers_sent() && !(isset($config) && $config['debug'] == true))
		{
			// use javascript instead
			echo '<script type="text/javascript">
				<!--
				location.replace("'.$to.'");
			// -->
			</script>
				<noscript>
				<meta http-equiv="Refresh" content="0;URL='.$to.'">
				</noscript>';
			exit;
		}
		else
		{
			if (isset($config) && $config['debug'] == true)
			{
				echo "Debug is on.  Redirecting disabled...  Please click this link to continue.<br />";
				echo "<a href=\"".$to."\">".$to."</a><br />";

				echo '<pre>';
				echo CmsProfiler::get_instance()->report();
				echo '</pre>';

				exit();
			}
			else
			{
				header("Location: $to");
				exit();
			}
		}
	}

	/**
	 * Given a page ID or an alias, redirect to it
	 */
	public static function redirect_to_alias($alias)
	{
		$node = CmsPageTree::get_node_by_alias($alias);
		$content = $node->get_content();
		if (isset($content))
		{
			if ($content->get_url() != '')
			{
				redirect($content->get_url());
			}
		}
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
	 * @return string
	 * @author Ted Kulp
	 **/
	public static function create_url($params = array())
	{
		$new_url = '';
		foreach(SilkRoute::get_routes() as $one_route)
		{
			$route_params = SilkRoute::get_params_from_route($one_route->route_string);
			$diff = array_diff($route_params, array_keys($params));
			if (!count($diff))
			{
				//This is the first route that should work ok for the given parameters
				//Even if it's short, we can add the rest on via the query string
				$new_url = $one_route->route_string;
				$similar = array_intersect($route_params, array_keys($params));
				foreach ($similar as $one_param)
				{
					$new_url = str_replace(":{$one_param}", $params[$one_param], $new_url);
					unset($params[$one_param]);
				}
				break;
			}
		}
		if (count($params))
		{
			$new_url = $new_url . '?' . http_build_query($params, '', '&amp;');
		}
		return $new_url;
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
		while (@ob_end_clean());
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
		<html><head>
			<title>404 Not Found</title>
			</head><body>
			<h1>Not Found</h1>
			<p>The requested URL was not found on this server.</p>
			</body></html>';
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

}

# vim:ts=4 sw=4 noet
?>