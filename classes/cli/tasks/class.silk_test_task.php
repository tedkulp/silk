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

class SilkTestTask extends SilkTask
{
	public $has_help = false;
	
	public function run($args, $flags, $options)
	{
		//Chop off php if it's there
		if ($_SERVER['argv'] == 'php')
			array_shift($_SERVER['argv']);
		
		//Chop off silk.php
		array_shift($_SERVER['argv']);
		
		//No tests given, so let's grab all of them, starting with
		//silk's
		if (count($args) == 0)
		{
			$files = array();
			$this->recursive_file_listing(join_path(SILK_LIB_DIR, 'test'), $files);
			
			//Now the app dir -- only grabbing test. files -- maybe this
			//needs a little more definition
			$this->recursive_file_listing(join_path(ROOT_DIR, 'app'), $files);
			
			foreach($files as $one_file)
				$_SERVER['argv'][] = $one_file;
		}
		
		require 'PHPUnit/TextUI/Command.php';
		define('PHPUnit_MAIN_METHOD', 'PHPUnit_TextUI_Command::main');
		PHPUnit_TextUI_Command::main(FALSE);
	}
	
	function recursive_file_listing($dir = '.', &$files)
	{
		if (file_exists($dir))
		{
			foreach(new DirectoryIterator($dir) as $file)
			{
				if (!$file->isDot() && $file->getFilename() != '.svn')
				{
					if ($file->isDir())
					{
						$newdir = $file->getPathname();
						$this->recursive_file_listing($newdir, $files);
					}
					else
					{
						if (starts_with(basename($file->getPathname()), 'test.'))
						{
							$files[] = $file->getPathname();
						}
					}
				}
			}
		}
		return $files;
	}
}

# vim:ts=4 sw=4 noet
?>
