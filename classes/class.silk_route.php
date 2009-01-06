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
 * Class to handle url routes for modules to handle pretty urls.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkRoute extends SilkObject
{
	var $route_string;
	var $defaults;
	
	static private $routes = array();

	function __construct()
	{
		parent::__construct();
	}
	
	static public function load_routes($path = '')
	{
		if ($path == '')
			$path = join_path(ROOT_DIR, 'config', 'routes.php');
		include_once($path);
	}
	
	public function register_route($route_string, $defaults = array())
	{
		$route = new SilkRoute();
		$route->defaults = $defaults;
		$route->route_string = $route_string;
		self::$routes[] = $route;
	}
	
	public static function match_route($uri)
	{
		if( substr($uri, strlen($uri) -1) == "/") $uri = substr($uri, 0, strlen($uri) -1);
		$found = false;
		$matches = array();
		$defaults = array();

		foreach(self::$routes as $one_route)
		{
			$regex = self::create_regex_from_route($one_route->route_string);
			if (preg_match($regex, $uri, $matches))
			{
				$defaults = $one_route->defaults;
				$found = true;
				break;
			}
		}
		
		if ($found)
		{
			$ary = array_unique(array_merge($_GET, $_POST, $defaults, $matches));
			if( strpos( $ary["action"], "?" ) > 0 )
			{
				$ary["action"] = substr( $ary["action"], 0, strpos( $ary["action"], "?"));
			}
			unset($ary[0]);
			return $ary;
		}
		else
		{
			throw new SilkRouteNotMatchedException();
		}
	}
	
	public static function get_routes()
	{
		return self::$routes;
	}
	
	public static function create_regex_from_route($route_string)
	{
		$result = str_replace("/", "\\/", $route_string);
		$result = preg_replace("/:([a-zA-Z_-]+)/", "(?P<$1>.*?)", $result);
		$result = '/^'.$result.'$/';
		return $result;
	}
	
	public static function get_params_from_route($route_string)
	{
		$result = str_replace("/", "\\/", $route_string);
		$matches = array();
		preg_match_all("/:([a-zA-Z_-]+)/", $result, $matches);
		return count($matches) > 1 ? $matches[1] : array();
	}
}

class SilkRouteNotMatchedException extends Exception
{
}

# vim:ts=4 sw=4 noet
?>