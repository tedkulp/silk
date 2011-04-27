<?php
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

use \rack\Rack;

//Find silk.api.php
//First look in lib dir
$api_file = '';
if (strpos('@php_bin@', '@php_bin') === 0)  // not a pear install
{
	if (file_exists(dirname(dirname(__FILE__)) . '/lib/silk/silk.api.php'))
	{
		$api_file = dirname(dirname(__FILE__)) . '/lib/silk/silk.api.php';
		define('ROOT_DIR', dirname(dirname(__FILE__)));
	}
	else if (file_exists(dirname(dirname(__FILE__)) . '/silk.api.php')) //We're in the main dir
	{
		$api_file = dirname(dirname(__FILE__)) . '/silk.api.php';
		define('ROOT_DIR', dirname(dirname(__FILE__)));
	}
}
else //PEAR, baby!
{
	$api_file = "@pear_directory@/silk/silk.api.php";
	if (isset($_SERVER['PWD']))
	{
		define('ROOT_DIR', $_SERVER['PWD']);
	}
	else
	{
		define('ROOT_DIR', "@pear_directory@/silk");
	}
}

if (!empty($api_file))
{
	include_once($api_file);
}
else
{
	echo "Can't find silk libraries.  Exiting.";
	exit(1);
}

$rack_dir = joinPath(SILK_LIB_DIR, 'vendor', 'rack', 'lib');
include_once(joinPath($rack_dir, 'Rack.php'));

Rack::add("\\rack\\middleware\\MethodOverride", $rack_dir . '/rack/middleware/MethodOverride.php');
Rack::add("\\rack\\middleware\\HeadRequest", $rack_dir . '/rack/middleware/HeadRequest.php');
Rack::add("\\silk\\action\\middleware\\ErrorPageHandler");
Rack::add("\\rack\\middleware\\ExecTime", $rack_dir . '/rack/middleware/ExecTime.php');
Rack::add("\\silk\\core\\Application", null, \silk\core\Application::getInstance());

Rack::run();

# vim:ts=4 sw=4 noet
