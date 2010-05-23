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

namespace silk\core;

use \silk\core\Object;

class ComponentManager extends Object
{
	private static $instance = NULL;
	public $components = array();
	public $loaded_apis = array();

	public function __construct()
	{
		parent::__construct();
	}

	public static function get_instance()
	{
		if (self::$instance == NULL)
		{
			self::$instance = new \silk\core\ComponentManager();
		}
		return self::$instance;
	}

	public static function load()
	{
		if (self::find_components())
		{
			$component_dir = join_path(ROOT_DIR, 'components');

			foreach(self::get_instance()->components as $one_component)
			{
				add_class_directory(join_path($component_dir, $one_component, 'models'));
				add_class_directory(join_path($component_dir, $one_component, 'controllers'));
			}
		}
	}

	public static function find_components()
	{
		$result = false;
		$component_dir = join_path(ROOT_DIR, 'components');

		foreach (scandir($component_dir) as $one_file)
		{
			if ($one_file != '.' && $one_file != '..' && $one_file != '.svn')
			{
				if (is_dir(join_path($component_dir, $one_file)))
				{
					self::get_instance()->components[] = $one_file;
					$result = true;
				}
			}
		}

		return $result;
	}

	public static function list_components()
	{
		$components = array();
		$component_dir = join_path(ROOT_DIR, 'components');

		foreach (scandir($component_dir) as $one_file)
		{
			if ($one_file != '.' && $one_file != '..' && $one_file != '.svn')
			{
				if (is_dir(join_path($component_dir, $one_file)))
				{
					$components[$one_file] = self::list_controllers($one_file);
				}
			}
		}

		return $components;
	}
	/**
	 * Dynamically load and return the api object for $component. 
	 * Api Files should be at a location like: components/component_name/class.component_name_api.php
	 * @param $component String Name of the component.	
	 * @return Object The api object for this component.
	 * @author Tim Oxley
	*/
	public static function get_api($component)
	{
		$scm = \silk\core\ComponentManager::get_instance();
		if (!isset($scm->loaded_apis[$component]))
		{
			$path_to_api = join_path(ROOT_DIR, 'components', $component, 'class.' . underscore($component) . '_api.php');
			if (is_file($path_to_api))
			{
				try
				{
					require_once($path_to_api);
					$loaded_apis[$component] = new $component . 'Api';
				}
				catch (Exception $e)
				{
					$scm->loaded_apis[$component] = null;
				}
			}
			else
			{
				$scm->loaded_apis[$component] = null;
			}
		}
		return $scm->loaded_apis[$component];
	}

	public static function list_controllers($component)
	{
		$controllers = array();
		$component_dir = join_path(ROOT_DIR, 'components');

		foreach (scandir(join_path($component_dir, $component, "controllers")) as $one_controller)
		{
			$filename = join_path($component_dir, $component, "controllers", $one_controller);
			if (is_file($filename) && substr($one_controller, 0, 1) != ".")
			{
				$controllers[] = $one_controller;
			}
		}
		return $controllers;
	}
	
}

# vim:ts=4 sw=4 noet
?>
