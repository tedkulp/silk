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

use \Rack\Rack;
use \silk\core\RackApp;

//Find silk.api.php
//First look in lib dir
$api_file = '';
$rack_file = '';
if (file_exists(dirname(__FILE__) . '/lib/silk/silk.api.php'))
{
	$api_file = dirname(__FILE__) . '/lib/silk/silk.api.php';
	$rack_file = dirname(__FILE__) . '/lib/silk/rack/lib/rack.php';
	define('ROOT_DIR', dirname(__FILE__));
}
else if (file_exists(dirname(__FILE__) . '/silk.api.php')) //We're in the main dir
{
	$api_file = dirname(__FILE__) . '/silk.api.php';
	$rack_file = dirname(__FILE__) . '/rack/lib/rack.php';
	define('ROOT_DIR', dirname(__FILE__));
}
else //PEAR?
{
	$output = '';
	$ret_code = null;
	$cmd = exec('pear config-get php_dir', $output, $ret_code);
	if ($ret_code == 0 && !empty($cmd))
	{
		$potential_path = $cmd . '/silk/silk.api.php';
		if (file_exists($potential_path))
		{
			$api_file = $potential_path;
			$rack_file = $cms . '/silk/rack/lib/rack.php';
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

if (!empty($api_file))
{
	include_once($api_file);
}
else
{
	fwrite(STDERR, "Can't find silk libraries.  Exiting.\n");
	exit(1);
}

include_once($rack_file);

Rack::add("\silk\core\RackApp");

Rack::run();

# vim:ts=4 sw=4 noet
