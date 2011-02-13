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

namespace silk\display;

/**
 * Extends the Smarty class for singleton handling and setting up multiple
 * plugin locations.
 **/
require_once(joinPath(SILK_LIB_DIR,'vendor','smarty','Smarty.class.php'));

class Smarty extends \Smarty
{
	static private $instance = NULL;
	
	function __construct()
	{
		parent::__construct();
		
		//$this->left_delimiter = '[[';
		//$this->right_delimiter = ']]';
		
		$this->allow_php_tag = true;
		
		$this->template_dir = joinPath(ROOT_DIR, 'tmp', 'templates');
		$this->compile_dir = joinPath(ROOT_DIR, 'tmp', 'templates_c');
		$this->config_dir = joinPath(ROOT_DIR, 'tmp', 'configs');
		$this->cache_dir = joinPath(ROOT_DIR, 'tmp', 'cache');
		$this->plugins_dir = array(joinPath(SILK_LIB_DIR, 'plugins'), joinPath(SILK_LIB_DIR, 'smarty', 'libs', 'plugins'));
		
		foreach ($this->getExtensionPluginDirectories() as $one_dir)
		{
			$this->plugins_dir[] = $one_dir;
		}
		
		$this->cache_plugins = false;
	}
	
	static public function getInstance($have_db = true)
	{
		if (self::$instance == NULL)
		{
			$class_name = config('smarty_class');
			if ($class_name == null)
				$class_name = '\silk\display\Smarty';
			self::$instance = new $class_name($have_db);
		}
		return self::$instance;
	}

	/**
	 * wrapper for include() retaining $this
	 * @return mixed
	 */
	function _include($filename, $once=false, $params=null)
	{
		if ($filename != '')
		{
			if ($once) {
				return include_once($filename);
			} else {
				return include($filename);
			}
		}
	}
	
	public function getExtensionPluginDirectories()
	{
		$dirs = array();
		
		$extension_dir = joinPath(ROOT_DIR, 'extensions');
		if (is_dir($extension_dir))
		{
			foreach (scandir($extension_dir) as $one_dir)
			{
				if ($one_dir != '.' && $one_dir != '..')
				{
					if (is_dir(joinPath($extension_dir, $one_dir, 'plugins')))
					{
						$dirs[] = joinPath($extension_dir, $one_dir, 'plugins');
					}
				}
			}
		}
		
		$plugins = config('smarty_plugins');
		if ($plugins)
		{
			foreach ($plugins as $one_dir)
			{
				$one_dir = joinPath(ROOT_DIR, $one_dir);
				if (is_dir($one_dir))
				{
					$dirs[] = $one_dir;
				}
			}
		}
		
		return $dirs;
	}

	function trigger_error($error_msg, $error_type = E_USER_WARNING)
	{
		var_dump("Smarty error: $error_msg");
	}
}

# vim:ts=4 sw=4 noet
