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

desc('Command for creating new apps');
options('', array('skeleton='));
task('create', function($app)
{
	//Recursively copy files from directory to directory
	//Taken from: http://www.php.net/manual/en/function.copy.php#91010
	if (!function_exists('recurseCopy'))
	{
		function recurseCopy($src, $dst)
		{
			$dir = opendir($src);
			@mkdir($dst);
			while(false !== ( $file = readdir($dir)))
			{
				if (( $file != '.' ) && ( $file != '..' ))
				{
					if (is_dir($src . '/' . $file))
					{
						recurseCopy($src . '/' . $file,$dst . '/' . $file);
					}
					else
					{
						copy($src . '/' . $file,$dst . '/' . $file);
					}
				}
			}
			closedir($dir);
		}
	}


	$app_name = $app->cmd_args['arg0'];

	//Is skeleton given?  If so, figure out if it exists
	$skeleton = joinPath(SILK_LIB_DIR, 'skeleton', 'default');
	if (isset($app['skeleton']) && !empty($app['skeleton']))
	{
		//First see if it's a real directory
		if (@is_dir($app['skeleton']))
		{
			$skeleton = $app['skeleton'];
		}
		else if (@is_dir(joinPath($_SERVER['PWD'], $app['skeleton']))) //Maybe a subdirectory of PWD?
		{
			$skeleton = joinPath($_SERVER['PWD'], $app['skeleton']);
		}
		else if (@is_dir(joinPath(SILK_LIB_DIR, 'skeleton', $app['skeleton']))) //Maybe a name in the skeletons directory?
		{
			$skeleton = joinPath(SILK_LIB_DIR, 'skeleton', $app['skeleton']);
		}
		else
		{
			echo "\nGiven skeleton is not a valid directory or name in " . joinPath(SILK_LIB_DIR, 'skeleton') . ". Exiting!\n\n";
			exit(1);
		}
	}

	echo "\nCreating application {$app_name} using skeleton in {$skeleton}.\n";

	//Create the directory.  Make sure we have access to do so before going any further
	$dir_name = $_SERVER['PWD'] . DIRECTORY_SEPARATOR . $app_name;
	if (!@mkdir($dir_name))
	{
		echo "\nCould not create directory named '{$app_name}' because of permissions or it already exists. Exiting!\n\n";
		exit(1);
	}

	//Ok, we have a source and a target
	//Here is where we create the skeleton
	recurseCopy($skeleton, $dir_name);

	//Fix permissions on tmp directories if they exist
	if (is_dir(joinPath($dir_name, 'tmp', 'templates_c')))
	{
		@chmod(joinPath($dir_name, 'tmp', 'templates_c'), 0777);
	}
	if (is_dir(joinPath($dir_name, 'tmp', 'cache')))
	{
		@chmod(joinPath($dir_name, 'tmp', 'cache'), 0777);
	}

	//Create the app directories
	//(if they don't exist from the skeleton)
	@mkdir(joinPath($dir_name, 'app'));
	@mkdir(joinPath($dir_name, 'app', 'models'));
	@mkdir(joinPath($dir_name, 'app', 'controllers'));
	@mkdir(joinPath($dir_name, 'app', 'views'));
	@mkdir(joinPath($dir_name, 'app', 'helpers'));
	@mkdir(joinPath($dir_name, 'app', 'plugins'));
	
	//Create the test directories
	//(if they don't exist from the skeleton)
	@mkdir(joinPath($dir_name, 'test'));
	@mkdir(joinPath($dir_name, 'test', 'fixtures'));
	@mkdir(joinPath($dir_name, 'test', 'functional'));
	@mkdir(joinPath($dir_name, 'test', 'unit'));

	//Then grab the latest index.php and copy that in
	copy(joinPath(SILK_LIB_DIR, 'index.php'), joinPath($dir_name, 'public', 'index.php'));
});



# vi$app:ts=4 sw=4 noet filetype=php
