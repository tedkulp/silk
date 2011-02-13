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

namespace silk\core;

use \silk\core\Object;

class ComponentManager extends Singleton
{
	public $components = array();
	public $loaded_apis = array();

	public function __construct()
	{
		parent::__construct();
		$this->components = array(); 
	}

	public static function load()
	{
		if (self::findComponents())
		{
			$component_dir = joinPath(ROOT_DIR, 'components');

			$obj = self::getInstance();
			$components = $obj->components;
			if (!empty($components))
			{
				foreach($obj->components as $one_component)
				{
					addClassDirectory(joinPath($component_dir, $one_component, 'models'));
					addClassDirectory(joinPath($component_dir, $one_component, 'controllers'));
				}
			}
		}
	}

	public static function findComponents()
	{
		$result = false;
		$component_dir = joinPath(ROOT_DIR, 'components');

		if (is_dir($component_dir))
		{
			foreach (scandir($component_dir) as $one_file)
			{
				if ($one_file != '.' && $one_file != '..' && $one_file != '.svn')
				{
					if (is_dir(joinPath($component_dir, $one_file)))
					{
						$obj = self::getInstance();
						$obj->components[] = $one_file;
						$result = true;
					}
				}
			}
		}

		return $result;
	}

	public static function listComponents()
	{
		$components = array();
		$component_dir = joinPath(ROOT_DIR, 'components');

		if (is_dir($component_dir))
		{
			foreach (scandir($component_dir) as $one_file)
			{
				if ($one_file != '.' && $one_file != '..' && $one_file != '.svn')
				{
					if (is_dir(joinPath($component_dir, $one_file)))
					{
						$components[$one_file] = ComponentManager::listControllers($one_file);
					}
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
	*/
	public static function getApi($component)
	{
		$scm = \silk\core\ComponentManager::getInstance();
		if (!isset($scm->loaded_apis[$component]))
		{
			$path_to_api = joinPath(ROOT_DIR, 'components', $component, 'class.' . underscore($component) . '_api.php');
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

	public static function listControllers($component)
	{
		$controllers = array();
		$component_dir = joinPath(ROOT_DIR, 'components');
		if (!is_dir($component_dir))
			return $controllers;
		
		$controller_dir = joinPath($component_dir, $component, "controllers");
		if (!is_dir($controller_dir))
			return $controllers;

		foreach (scandir($controller_dir) as $one_controller)
		{
			$filename = joinPath($component_dir, $component, "controllers", $one_controller);
			if (is_file($filename) && substr($one_controller, 0, 1) != ".")
			{
				$controllers[] = $one_controller;
			}
		}
		return $controllers;
	}
	
}

# vim:ts=4 sw=4 noet
