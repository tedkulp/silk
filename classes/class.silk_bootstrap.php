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
 * Methods for starting up a web application.
 *
 * @since 1.0
 * @author Ted Kulp
 **/
class SilkBootstrap extends SilkObject
{
	static private $instance = NULL;

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Returns an instance of the SilkBookstrap singleton.
	 *
	 * @return SilkBookstrap The singleton SilkBookstrap instance
	 * @author Ted Kulp
	 **/
	static public function get_instance()
	{
		if (self::$instance == NULL)
		{
			self::$instance = new SilkBootstrap();
		}
		return self::$instance;
	}

	public static function setup()
	{
		//Load up the configuration file
		$config = load_config();
		set('config', $config);
		
		// Ensure we Look in silk pear dir before global pear repository	
		
		set_include_path(join_path(SILK_LIB_DIR, 'pear') . PATH_SEPARATOR . get_include_path());
		
		//Add class path entries
		if (isset($config['class_autoload']))
		{
			foreach ($config['class_autoload'] as $dir)
			{
				add_class_directory(join_path(ROOT_DIR, $dir));
			}
		}
		
		foreach (self::get_extension_class_directories() as $one_dir)
		{
			add_class_directory($one_dir);
		}
		
		//Setup session stuff
		SilkSession::setup();
		
		//Load components
		SilkComponentManager::load();
	}
	
	//Deprecated -- it's lazy load now
	public function setup_database()
	{
	}

	public function run()
	{
		//Kick the profiler so we get a fairly accurate run time
		//Though, this doesn't include the classdir scanning, but
		//it's still pretty close
		SilkProfiler::get_instance();
		
		self::setup();
		
		//Process route
		SilkRequest::handle_request();

		$config = silk()->get('config');
		if ($config['debug'])
		{
			echo SilkProfiler::get_instance()->report();
		}
	}
	
	public static function get_extension_class_directories()
	{
		$dirs = array();
		
		$extension_dir = join_path(ROOT_DIR, 'extensions');
		if (is_dir($extension_dir))
		{
			foreach (scandir($extension_dir) as $one_dir)
			{
				if ($one_dir != '.' && $one_dir != '..')
				{
					if (is_dir(join_path($extension_dir, $one_dir, 'classes')))
					{
						$dirs[] = join_path($extension_dir, $one_dir, 'classes');
					}
				}
			}
		}
		
		return $dirs;
	}
}

# vim:ts=4 sw=4 noet
?>
