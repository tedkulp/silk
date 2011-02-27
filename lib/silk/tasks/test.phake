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

desc('Command for running phpunit');
task('test', function($app)
{
	try
	{
		if (isset($app['system']) && $app['system'] == 'true')
		{
			echo "\nRunning Silk System tests.\n\n";
			define('SILK_TEST_DIR', joinPath(SILK_LIB_DIR, 'tests'));
		}
		else
		{
			echo "\nRunning Application tests.\n\n";
			define('SILK_TEST_DIR', joinPath(ROOT_DIR, 'tests'));
		}
		$test_suite = new OurTestSuite(SILK_TEST_DIR);
	}
	catch (Exception $exc)
	{
		$this->displayError($exc->getMessage());
	}
});

class OurTestSuite extends \silk\test\TestSuite
{
	function __construct($path = '')
	{
		parent::__construct();

		if ($path != '' && is_dir($path))
		{
			$dirs = array($path);

			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
			foreach ($objects as $name => $it)
			{
				if ($it->isFile() && basename($name) != '.' && basename($name) != '..' && endsWith(basename($name), '.php'))
				{
					echo "adding file: " . $it->getPathname() . "\n";
					$this->addTestFile($it->getPathname());
				}
			}

			//$this->run();
			$result = \PHPUnit_TextUI_TestRunner::run($this);
		}
	}
}

# vim:ts=4 sw=4 noet filetype=php
