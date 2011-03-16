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
use \silk\core\ComponentManager;

/**
 * Class to handle url routes for modules to handle pretty urls.
 **/
class Route extends Object
{
	var $route_string;
	var $defaults;
	var $callback = null;

	static private $routes = array();

	function __construct()
	{
		parent::__construct();
	}

	public function registerRoute($route_string, $defaults = array())
	{
		if ($route_string == '*')
			$route_string = '.*';
		$route = new Route();
		$route->defaults = $defaults;
		$route->route_string = $route_string;
		$route->callback = null;
		self::$routes[] = $route;
	}
	
	public function registerRouteCallback($route_string, $method, $defaults = array())
	{
		if ($route_string == '*')
			$route_string = '.*';
		$route = new Route();
		$route->defaults = $defaults;
		$route->route_string = $route_string;
		$route->callback = $method;
		self::$routes[] = $route;
	}
	
	public function registerSplitRoute($params) {
		
		$component = isset($params["component"]) ? $params["component"] : "";
		$controllers = isset($params["controllers"]) ? $params["controllers"] : array($component);
		$action = isset($params["action"]) ? $params["action"] : "index";
		$extra = isset($params["extra"]) ? $params["extra"] : array();
		
		if(!$component || count($controllers) == 0) { return; }
		// untested		
//		foreach($extra as $key => $value) {
//			$route = "/$component/:controller/:action";
//			\silk\action\Route::register_route($route, $defaults);
//		}
		
		if( count($controllers) > 1) {
			
			if(empty($action)) {
				$defaults = array( "component" => $component );
			} else {
				$defaults = array( "component" => $component, "action" => $action );
			}
			
			foreach($controllers as $one_controller) {
				$defaults["controller"] = $one_controller;

				$route = "/$component/$one_controller";
				self::registerRoute($route, $defaults);

				$route = "/$component/$one_controller/:action";
				self::registerRoute($route, array_diff($defaults, array("action" => $action)));
				
				if( $component != $one_controller ) {
					
					unset($defaults["component"]);
					
					$route = "/$one_controller";
					self::registerRoute($route, $defaults);

					$route = "/$one_controller/:action";
					self::registerRoute($route, array_diff($defaults, array("action" => $action)));
				}
			}
		} else {
			$defaults = array( "controller" => $controllers[0], "action" => $action );
			
			$route = "/$controllers[0]";
			self::registerRoute($route, $defaults);			

			$route = "/$controllers[0]/:action";
			self::registerRoute($route, array_diff($defaults, array("action" => $action)));
			
		}
	}

	public static function matchRoute($uri, $route_shortening = true)
	{
		if( strlen($uri) > 1 && substr($uri, strlen($uri) -1) == "/")
			$uri = substr($uri, 0, strlen($uri) -1);
		$uri = str_replace("/index.php", "", $uri); 

		$found = false;
		$matches = array();
		$defaults = array();
		$callback = null;

		foreach(self::$routes as $one_route)
		{
			$regex = self::createRegexFromRoute($one_route->route_string);
			if (preg_match($regex, $uri, $matches))
			{
				$defaults = $one_route->defaults;
				$callback = $one_route->callback;
				$found = true;
				break;
			}
		}
		if ($found)
		{
			$ary = array_merge($_GET, $_POST, $defaults, $matches);
			if (strpos($ary["action"], "?") > 0)
			{
				$ary["action"] = substr( $ary["action"], 0, strpos( $ary["action"], "?"));
			}
			unset($ary[0]);
			return array($ary, $callback);
		}
		else
		{
			throw new RouteNotMatchedException($uri);
		}
	}
	
	/**
	 * Rebuild the route to match the same number of element in the $uri
	 */
	public static function rebuildRoute($route, $uri) {
		$uri_words = explode("/", $uri);
		$max = count($uri_words) - 1;
		
		//rebuild the route to match the same number of elements as the $uri
		$route_words = explode("/", $route);
		$count = 0;
		$new_route = "";
		
		foreach( $route_words as $route_piece ) {
			if( !empty($route_piece)) {
				$new_route .= "/" . $route_piece;
				$count++;
				if( $count >= $max ) break;
			}
		}
		return $new_route;
	}

	public static function getRoutes()
	{
		return self::$routes;
	}

	public static function clearRoutes()
	{
		self::$routes = array();
	}

	public static function createRegexFromRoute($route_string)
	{
		$result = str_replace("/", "\\/", $route_string);
		$result = preg_replace("/:([a-zA-Z_-]+)/", "(?P<$1>.+?)", $result);
		$result = '/^'.$result.'$/';
		return $result;
	}

	public static function getParamsFromRoute($route_string)
	{
		$result = str_replace("/", "\\/", $route_string);
		$total_matches = array();
		$matches = array();
		preg_match_all("/:([a-zA-Z_-]+)/", $result, $matches);
		if (count($matches) > 1)
			$total_matches = array_merge($total_matches, $matches[1]);
		preg_match_all("/\(?P<([a-zA-Z_-]+)>/", $result, $matches);
		if (count($matches) > 1)
			$total_matches = array_merge($total_matches, $matches[1]);
		return $total_matches;
	}

	public static function buildControllerRoutesFromComponent($component)
	{
		$class_names = array();
		foreach (ComponentManager::listControllers($component) as $one_controller)
		{
			$class_names[] = underscore(str_replace('Controller', '', basename($one_controller, '.php')));
		}
		self::registerSplitRoute(array("component" => $component, "controllers" => $class_names));
	}

	/**
	 * Automatically build routes for components.  This basically makes a
	 * /:component/:controller/:action route for each component, or a
	 * /:component/:action route if there is only one controller.
	 *
	 * @return void
	 * @author Greg Froese
	 **/
	public static function buildDefaultComponentRoutes()
	{
		$components = ComponentManager::listComponents();
		$route = array();

		foreach($components as $component=>$controllers)
		{
			self::buildControllerRoutesFromComponent($component);
		}

		$route["/:component/:controller/:action/"] = array();
		$route["/:controller/:action/:id"] = array();
		$route["/:controller/:action"] = array();
		$route["/:controller"] = array("action" => "index");

		foreach ($route as $route_string => $params)
		{
			self::registerRoute($route_string, $params);
		}
	}
}

class RouteNotMatchedException extends \Exception
{
}

# vim:ts=4 sw=4 noet
