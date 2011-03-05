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

use \Rack\Rack;

//Find silk.api.php
//First look in lib dir
$api_file = '';
$rack_file = '';
if (file_exists(dirname(__FILE__) . '/lib/silk/silk.api.php'))
{
	$api_file = dirname(__FILE__) . '/lib/silk/silk.api.php';
	$rack_dir = dirname(__FILE__) . '/lib/silk/vendor/rack/lib';
	define('ROOT_DIR', dirname(__FILE__));
}
else if (file_exists(dirname(__FILE__) . '/silk.api.php')) //We're in the main dir
{
	$api_file = dirname(__FILE__) . '/silk.api.php';
	$rack_dir = dirname(__FILE__) . '/vendor/rack/lib';
	define('ROOT_DIR', dirname(__FILE__));
}
else //PEAR?
{
	if (include_once("PEAR/Config.php"))
	{
		$config = PEAR_Config::singleton('', '');
		$cmd = $config->get('php_dir');
		if ($cmd && !empty($cmd))
		{
			$potential_path = $cmd . '/silk/silk.api.php';
			if (file_exists($potential_path))
			{
				$api_file = $potential_path;
				$rack_dir = $cmd . '/silk/vendor/rack/lib';
			}

			if (isset($_SERVER['PWD']))
			{
				define('ROOT_DIR', $_SERVER['PWD']);
			}
			else
			{
				define('ROOT_DIR', dirname(__FILE__));
			}
		}
	}
}

if (!empty($api_file))
{
	include_once($api_file);
}
else
{
	fwrite(STDERR, "Can't find silk libraries.  Exiting.\n");
	exit(1);
}

include_once($rack_dir . '/rack.php');

Rack::add("\Rack\Middleware\ExecTime", $rack_dir . '/rack/middleware/exec_time.php');
Rack::add("\silk\core\Application", null, \silk\core\Application::getInstance());

Rack::run();

# vim:ts=4 sw=4 noet
