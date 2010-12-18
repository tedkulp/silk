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

class SilkCreateTask extends SilkTask
{
    public function __construct()
    {
	   $this->AddOption('version', array(
		  'long_name'   => '--version',
		  'description' => "Runs some stuff.",
		  'action'      => 'StoreString',
		  'final' => true)
	   );

	   $this->AddOption('skeleton', array(
		  'long_name'   => '--skeleton',
		  'description' => "Specifies the name or directory of the skeleton to use.",
		  'action'      => 'StoreString')
	   );

	   $this->addArgument('create_type');
	   $this->addArgument('additional_args', array(
		  'multiple' => true,
		  'optional' => true)
	   );

	   return parent::__construct(array(
		  'name' => 'create',
		  'description' => "Various methods for handling Silk Framework configuration.",
		  'version'     => '0.0.1')
	   );
    }

    public function run($argc, $argv)
    {
	   $result = $this->parse($argc, $argv);

	   if (isset($result->args['create_type']))
	   {
		  $create_type = $result->args['create_type'];
		  //TODO: find real way to make this extensible
		  $method_name = "{$create_type}_command";
		  if (method_exists($this, $method_name))
		  {
			 $this->$method_name($result->args['additional_args'], $result->options);
		  }
	   }
    }

    public function app_command($args, $options)
    {
	   if (empty($args) || count($args) != 1)
	   {
		  echo "\nThis command requires exactly one argument for the application name. Exiting!\n\n";
		  exit(1);
	   }

	   //Is skeleton given?  If so, figure out if it exists
	   $skeleton = join_path(SILK_LIB_DIR, 'skeleton', 'default');
	   if ($options['skeleton'] != null)
	   {
		  //First see if it's a real directory
		  if (@is_dir(join_path($_SERVER['PWD'], $options['skeleton'])))
		  {
			 $skeleton = join_path($_SERVER['PWD'], $options['skeleton']);
		  }
		  else if (@is_dir(join_path(SILK_LIB_DIR, 'skeleton', $options['skeleton']))) //Maybe a name in the skeletons directory
		  {
			 $skeleton = join_path(SILK_LIB_DIR, 'skeleton', $options['skeleton']);
		  }
		  else
		  {
			 echo "\nGiven skeleton is not a valid directory or name in " . join_path(SILK_LIB_DIR, 'skeleton') . ". Exiting!\n\n";
			 exit(1);
		  }
	   }

	   //Create the directory.  Make sure we have access to do so before going any further
	   $dir_name = $_SERVER['PWD'] . DIRECTORY_SEPARATOR . $args[0];
	   if (!@mkdir($dir_name))
	   {
		  echo "\nCould not create directory named '" . $args[0] . "' because of permissions or it already exists. Exiting!\n\n";
		  exit(1);
	   }

	   //Ok, we have a source and a target
	   //Here is where we create the skeleton
	   $this->recurse_copy($skeleton, $dir_name);

	   //Fix permissions on tmp directories if they exist
	   if (is_dir(join_path($dir_name, 'tmp', 'templates_c')))
	   {
		 @chmod(join_path($dir_name, 'tmp', 'templates_c'), 0777);
	   }
	   if (is_dir(join_path($dir_name, 'tmp', 'cache')))
	   {
		 @chmod(join_path($dir_name, 'tmp', 'cache'), 0777);
	   }

	   //Create the test directories
	   @mkdir(join_path($dir_name, 'test'));
	   @mkdir(join_path($dir_name, 'test', 'fixtures'));
	   @mkdir(join_path($dir_name, 'test', 'functional'));
	   @mkdir(join_path($dir_name, 'test', 'unit'));

	   //TODO: I'm sure there's more, but this is a start
    }

    //Recursively copy files from directory to directory
    //Taken from: http://www.php.net/manual/en/function.copy.php#91010
    public function recurse_copy($src, $dst)
    {
	   $dir = opendir($src);
	   @mkdir($dst);
	   while(false !== ( $file = readdir($dir)))
	   {
		  if (( $file != '.' ) && ( $file != '..' ))
		  {
			 if (is_dir($src . '/' . $file))
			 {
				$this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
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

# vim:ts=5 sw=4 noet
