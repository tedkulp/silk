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
 * Methods for starting up a web application.
 *
 * @package Silk
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
	 * Returns an instnace of the SilkBookstrap singleton.
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
	
	public function run()
	{
		//Load up the configuration file
		if (is_file(join_path(ROOT_DIR, 'config', 'setup.yml')))
			$config = SilkYaml::load(join_path(ROOT_DIR, 'config', 'setup.yml'));
		else
			die("Config file not found!");
			
		//Add class path entries
//		echo "<pre>"; var_dump($config); echo "</pre>";
		if (isset($config['class_autoload']))
		{
			foreach ($config['class_autoload'] as $dir)
			{
				add_class_directory(join_path(ROOT_DIR, $dir));
			}
		}
		
		//Setup the database connection
		if (!isset($config['database']['dsn']))
			die("No database information found in the configuration file");
		
		if (null == SilkDatabase::connect($config['database']['dsn'], $config['debug'], true, $config['database']['prefix']))
			die("Could not connect to the database");
		
		//Process route
		SilkRequest::handle_request();
		
		if ($config['debug'])
		{
			echo SilkProfiler::get_instance()->report();
		}

	}
}

# vim:ts=4 sw=4 noet
?>