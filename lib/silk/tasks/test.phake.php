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
usage(" --system - Run system tests\n --filter=\"PartialTestName\" - Only run test suites who's name matches the text");
options('', array('system', 'filter='));
task('test', function($app)
{
	try
	{
		if (isset($app['system']))
		{
			echo "\nRunning Silk System tests.\n\n";
			define('SILK_TEST_DIR', joinPath(SILK_LIB_DIR, 'tests'));
		}
		else
		{
			echo "\nRunning Application tests.\n\n";
			define('SILK_TEST_DIR', joinPath(ROOT_DIR, 'tests'));
		}

		$filter = '';
		if (isset($app['filter']))
		{
			$filter = $app['filter'];
		}

		$test_suite = new OurTestSuite(SILK_TEST_DIR, $filter);
	}
	catch (Exception $exc)
	{
		$this->displayError($exc->getMessage());
	}
});

class OurTestSuite extends \silk\test\TestSuite
{
	function __construct($path = '', $filter = '', array $sub_dirs = array('unit', 'functional'))
	{
		parent::__construct();

		$dirs = array_merge(array($path), silk()->getExtensionDirectories('tests'));
		foreach($dirs as $base_path)
		{
			//If there is an init file in the tests dir, run it
			$init_file = joinPath($base_path, 'init.php');
			if (is_file($init_file))
			{
				include($init_file);
			}

			//Now loop through and grab unit tests in this dir
			foreach($sub_dirs as $ext_dir)
			{
				$this->findAndAddTests($base_path, $ext_dir, $filter);
			}
		}

		//$this->run();
		$result = \PHPUnit_TextUI_TestRunner::run($this);
	}

	function findAndAddTests($base_path, $ext_dir, $filter)
	{
		$path = joinPath($base_path, $ext_dir);
		if ($path != '' && is_dir($path))
		{
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
			foreach ($objects as $name => $it)
			{
				if ($it->isFile() && basename($name) != '.' && basename($name) != '..' && endsWith(basename($name), '.php'))
				{
					if (empty($filter) || strpos($it->getPathname(), $filter) !== false)
					{
						echo "adding file: " . $it->getPathname() . "\n";
						$this->addTestFile($it->getPathname());
					}
				}
			}
		}
	}
}

# vim:ts=4 sw=4 noet filetype=php
