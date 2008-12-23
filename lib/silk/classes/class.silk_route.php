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
	
	public function register_route($route_string, $defaults = array())
	{
		$route = new SilkRoute();
		$route->defaults = $defaults;
		$route->route_string = $route_string;
		self::$routes[] = $route;
	}
	
	public static function match_route($uri)
	{
		$found = false;
		$matches = array();
		$defaults = array();

		foreach(self::$routes as $one_route)
		{
			$regex = self::create_regex_from_route($one_route->route_string);
//			echo "regex: $regex<br />";
//			echo "uri: $uri<br />";
//			echo "one_route->route_string: $one_route->route_string<br />";
//			echo "<br />";
			if (preg_match($regex, $uri, $matches))
			{
				$defaults = $one_route->defaults;
				$found = true;
				var_dump($matches);
				break;
			}
		}
		
		if ($found)
		{
			$ary = array_unique(array_merge($_GET, $defaults, $matches));
			if( strpos( $ary["action"], "?" ) > 0 ) {
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
	
	public static function create_regex_from_route($route_string)
	{
		$result = str_replace("/", "\\/", $route_string);
		$result = preg_replace("/:([a-zA-Z_-]+)/", "(?P<$1>.*?)", $result);
		$result = '/^'.$result.'$/';
		return $result;
	}
}

class SilkRouteNotMatchedException extends Exception
{
}

# vim:ts=4 sw=4 noet
?>