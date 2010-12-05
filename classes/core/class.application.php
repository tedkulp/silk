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

use \silk\performance\Cache;
use \silk\display\Smarty;
use \silk\database\Database;

/**
 * Global object that holds references to various data structures
 * needed by classes/functions.
 *
 * @author Ted Kulp
 * @since 1.0
 */
class Application extends Singleton
{
	/**
	 * Variables object - various objects and strings needing to be passed 
	 */
	public $variables;

	/**
	 * Internal error array - So functions/modules can store up debug info and spit it all out at once
	 */
	public $errors;
	
	public $params = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		EventManager::send_event('silk:core:application:startup');
		
		$this->errors = array();
		$this->variables['routes'] = array();
		
		//So our shutdown events are called right near the end of the page
		register_shutdown_function(array(&$this, 'shutdown'));
	}
	
	function shutdown()
	{
		//Make sure this is absolutely the last thing -- gets around SimpleTest and other libs
		register_shutdown_function(array(&$this, 'real_shutdown'));
	}
		
	protected function real_shutdown()
	{
		EventManager::send_event('silk:core:application:shutdown_soon');
		EventManager::send_event('silk:core:application:shutdown_now');
	}

	public function get($name)
	{
		if (!isset($this->variables[$name])) {
			throw new \InvalidArgumentException("Cannot get($name), $name is not set.");
		}
		return $this->variables[$name];
	}
	
	public function set($name, $value)
	{
		$this->variables[$name] = $value;
	}
	
	/**
	 * Getter overload method.  Called when an $obj->field and field
	 * does not exist in the object's variable list.  In this case,
	 * it will get a db or smarty instance (for backwards 
	 * compatibility), or call get on the given field name.
	 *
	 * @param string The field to look up
	 * @return mixed The value for that field, if it exists
	 * @author Ted Kulp
	 **/
	public function __get($name)
	{
		if ($name == 'db')
			return Database::get_instance();
		else if ($name == 'smarty')
			return Smarty::get_instance();
		else
			return $this->get($name);
	}
	
    /**
     * Setter overload method.  Called when an $obj->field and field
     * does not exist in the object's variable list.  In this case,
     * it will get a db or smarty instance (for backwards 
     * compatibility), or call get on the given field name.
     *
     * @param string The field to set
     * @param string The value to set it to
     * @author Ted Kulp
     **/
	public function __set($name, $value)
	{
		if ($name != 'db' && $name != 'smarty')
			$this->set($name, $value);
	}
	
	public function __isset($name)
	{
		return isset($this->variables[$name]);
	}

	public function __unset($name)
	{
		unset($this->variables[$name]);
	}
	
	public function add_include_path($path)
	{
		foreach (func_get_args() AS $path)
		{
			if (!file_exists($path) OR (file_exists($path) && filetype($path) !== 'dir'))
			{
				//trigger_error("Include path '{$path}' not exists", E_USER_WARNING);
				continue;
			}

			$paths = explode(PATH_SEPARATOR, get_include_path());

			if (array_search($path, $paths) === false)
				array_push($paths, $path);

			set_include_path(implode(PATH_SEPARATOR, $paths));
		}
	}

	public function remove_include_path($path)
	{
		foreach (func_get_args() AS $path)
		{
			$paths = explode(PATH_SEPARATOR, get_include_path());

			if (($k = array_search($path, $paths)) !== false)
				unset($paths[$k]);
			else
				continue;

			if (!count($paths))
			{
				//trigger_error("Include path '{$path}' can not be removed because it is the only", E_USER_NOTICE);
				continue;
			}

			set_include_path(implode(PATH_SEPARATOR, $paths));
		}
	}
}

# vim:ts=4 sw=4 noet
