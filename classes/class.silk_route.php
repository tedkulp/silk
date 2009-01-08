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
        echo "<br />Regex: $regex<br />";
        echo "_GET<pre>"; var_dump($_GET); echo "</pre>_POST<pre>"; var_dump($_POST); echo "</pre>";
       	echo "Matches<pre>"; var_dump($matches); echo "</pre>Defaults<pre>"; var_dump($defaults); echo "</pre>";
        $found = true;
        break;
      }
    }

    if ($found)
    {
      $ary = array_unique(array_merge($_GET, $_POST, $defaults, $matches));
      echo "This is the result of array_unique(array_merge()) of the above 4 var_dumps<br />ary:<pre>"; var_dump($ary); echo "</pre>";
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

  public function build_default_routes()
  {
		$components = SilkComponentManager::list_components();
		foreach($components as $component=>$controllers)
		{
//			echo "Component: $component<br />";
			foreach($controllers as $one_controller)
			{
				$class_name = str_replace("class.", "", str_replace(".php", "", str_replace("_controller", "", $one_controller)));
//				echo "-----> class: $class_name<br />";
				if(count($controllers) > 1)
				{
					$route = "/$component/$class_name/:action";
					$params = array("component" => $component, "controller" => $one_controller);
				} elseif(count($controllers) == 1 && $component == $class_name)
				{
					$route = "/$component/:action";
					$params = array("component" => $component, "controller" => $class_name);
				}
				echo "Registering: $route<br />";
//				echo "<pre>"; var_dump($params); echo "</pre>";
				SilkRoute::register_route($route, $params);
			}
		}
  }
}

class SilkRouteNotMatchedException extends Exception
{
}

# vim:ts=4 sw=4 noet
?>