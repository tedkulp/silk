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

/**
 * Extends the Smarty class for singleton handling and setting up multiple
 * plugin locations.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
require_once(join_path(SILK_LIB_DIR,'smarty','Smarty.class.php'));

class SilkSmarty extends Smarty
{
	static private $instance = NULL;
	
	function __construct()
	{
		parent::__construct();
		
		$this->left_delimiter = '[[';
		$this->right_delimiter = ']]';
		
		$this->assign('lbkt', '[');
		$this->assign('rbkt', ']');
		
		$this->template_dir = join_path(ROOT_DIR, 'tmp', 'templates');
		$this->compile_dir = join_path(ROOT_DIR, 'tmp', 'templates_c');
		$this->config_dir = join_path(ROOT_DIR, 'tmp', 'configs');
		$this->cache_dir = join_path(ROOT_DIR, 'tmp', 'cache');
		$this->plugins_dir = array(join_path(SILK_LIB_DIR, 'plugins'), join_path(SILK_LIB_DIR, 'smarty', 'plugins'));
		
		foreach ($this->get_extension_plugin_directories() as $one_dir)
		{
			$this->plugins_dir[] = $one_dir;
		}
		
		$this->cache_plugins = false;
	}
	
	static public function get_instance($have_db = true)
	{
		if (self::$instance == NULL)
		{
			$class_name = config('smarty_class');
			if ($class_name == null)
				$class_name = 'SilkSmarty';
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
	
	public function get_extension_plugin_directories()
	{
		$dirs = array();
		
		$extension_dir = join_path(ROOT_DIR, 'extensions');
		if (is_dir($extension_dir))
		{
			foreach (scandir($extension_dir) as $one_dir)
			{
				if ($one_dir != '.' && $one_dir != '..')
				{
					if (is_dir(join_path($extension_dir, $one_dir, 'plugins')))
					{
						$dirs[] = join_path($extension_dir, $one_dir, 'plugins');
					}
				}
			}
		}
		
		foreach (config('smarty_plugins') as $one_dir)
		{
			$one_dir = join_path(ROOT_DIR, $one_dir);
			if (is_dir($one_dir))
			{
				$dirs[] = $one_dir;
			}
		}
		
		return $dirs;
	}

	function trigger_error($error_msg, $error_type = E_USER_WARNING)
	{   
		var_dump("Smarty error: $error_msg");
	}
}

?>
