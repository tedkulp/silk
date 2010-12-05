#!/usr/bin/env php
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


if (!isset($argv))
	die('This is a command line tool');

//Find silk.api.php
//First look in lib dir
$api_file = '';
if (file_exists(dirname(__FILE__) . '/lib/silk/silk.api.php'))
{
	$api_file = dirname(__FILE__) . '/lib/silk/silk.api.php';
	define('ROOT_DIR', dirname(__FILE__));
}
else if (file_exists(dirname(__FILE__) . '/silk.api.php')) //We're in the main dir
{
	$api_file = dirname(__FILE__) . '/silk.api.php';
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
		}

		if (isset($_SERVER['PWD']))
		{
			define('ROOT_DIR', $_SERVER['PWD']);
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

\silk\core\Bootstrap::get_instance()->setup();

$cli = new SilkCli();
$cli->run($argc, $argv);

# vim:ts=4 sw=4 noet
