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

	public $request = null;
	public $response = null;
	
	protected $env = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		EventManager::sendEvent('silk:core:application:startup');

		$this->errors = array();
		$this->variables['routes'] = array();

		//So our shutdown events are called right near the end of the page
		register_shutdown_function(array(&$this, 'shutdown'));
	}

	function shutdown()
	{
		//Make sure this is absolutely the last thing -- gets around SimpleTest and other libs
		register_shutdown_function(array(&$this, 'realShutdown'));
	}

	protected function realShutdown()
	{
		EventManager::sendEvent('silk:core:application:shutdownSoon');
		EventManager::sendEvent('silk:core:application:shutdownNow');
	}

	public function get($name)
	{
		if (!isset($this->variables[$name]))
		{
			return null;
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

	/**
	 * Entry point from the Rack interface. This sets up
	 * the Request and Response objects, processes them
	 * and then sends the requests back to Rack.
	 *
	 * @param Object The rack environment object
	 * @return array The status code, array of headers and array of body strings
	 **/
	function call(&$env)
	{
		$this->env = $env;

		$this->request = new \silk\action\Request($env);
		$this->response = new \silk\action\Response();

		$this->run();

		list($code, $headers, $body) = $this->response->finish();
		return array($code, $headers, $body);
	}

	public static function setup()
	{
		if (is_dir(joinPath(ROOT_DIR, 'vendor', 'extensions')))
			addClassDirectory(joinPath(ROOT_DIR, 'vendor', 'extensions'));

		if (is_dir(joinPath(SILK_LIB_DIR, 'vendor', 'extensions')))
			addClassDirectory(joinPath(SILK_LIB_DIR, 'vendor', 'extensions'));

		//Load up the configuration file
		$config = loadConfig();
		set('config', $config);

		// Ensure we Look in silk pear dir before global pear repository	
		set_include_path(joinPath(SILK_LIB_DIR, 'pear') . PATH_SEPARATOR . get_include_path());

		//Add class path entries
		if (isset($config['class_autoload']))
		{
			foreach ($config['class_autoload'] as $dir)
			{
				addClassDirectory(joinPath(ROOT_DIR, $dir));
			}
		}

		addClassDirectory(joinPath(SILK_LIB_DIR,'vendor','doctrine','lib'));
		addClassDirectory(joinPath(SILK_LIB_DIR,'vendor','doctrine-common','lib'));
		addClassDirectory(joinPath(SILK_LIB_DIR,'vendor','doctrine-dbal','lib'));
		addClassDirectory(joinPath(SILK_LIB_DIR,'vendor','doctrine-mongodb','lib'));
		addClassDirectory(joinPath(SILK_LIB_DIR,'vendor','doctrine-mongodb-odm','lib'));

		//Setup include path for any PEAR stuff in vendor
		addIncludePath(joinPath(SILK_LIB_DIR,'vendor'));

		//Setup session stuff
		//TODO: Use the Rack sessions
		//\SilkSession::setup();

		//Load components
		ComponentManager::load();
	}

	public function run()
	{
		//Kick the profiler so we get a fairly accurate run time
		//Though, this doesn't include the classdir scanning, but
		//it's still pretty close
		//Profiler::get_instance();

		//Set it up so we show the profiler as late as possible
		EventManager::registerEventHandler('silk:core:application:shutdown_now', array(&$this, 'showProfilerReport'));

		self::setup();

		//Process route
		$this->request->handleRequest();
	}

	public static function getExtensionDirectories($directory = '', array $additional_dirs = array())
	{
		$dirs = array();

		$extension_dirs = array_unique($additional_dirs + array(joinPath(SILK_LIB_DIR, 'vendor', 'extensions'), joinPath(ROOT_DIR, 'vendor', 'extensions')));
		foreach ($extension_dirs as $extension_dir)
		{
			if (is_dir($extension_dir))
			{
				foreach (scandir($extension_dir) as $one_dir)
				{
					if ($one_dir != '.' && $one_dir != '..')
					{
						if (is_dir(joinPath($extension_dir, $one_dir, $directory)))
						{
							$dirs[] = joinPath($extension_dir, $one_dir, $directory);
						}
					}
				}
			}
		}

		return $dirs;
	}

	public static function getExtensionFiles($directory = 'lib', array $additional_dirs = array())
	{
		$files = array();
		$dirs = self::getExtensionDirectories($directory, $additional_dirs);
		foreach ($dirs as $dir)
		{
			scanClassesRecursive($dir, $files);
		}
		return $files;
	}
}

# vim:ts=4 sw=4 noet
