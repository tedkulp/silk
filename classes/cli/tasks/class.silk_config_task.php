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

class SilkConfigTask extends SilkTask
{
	public $needs_db = false;
	
	public function run($args, $flags, $options)
	{
		if (isset($args[0]) && ($args[0] == 'env' || $args[0] == 'environment'))
		{
			$env = '';
			if (isset($args[1]) && $args[1] != '')
			{
				$config_dir = join_path(ROOT_DIR, 'config');
				if (is_writable($config_dir))
				{
					if (is_readable(join_path($config_dir, $args[1])))
					{
						$files = scandir(join_path($config_dir, $args[1]));
						foreach ($files as $one_file)
						{
							if ($one_file != '.' && $one_file != '..')
							{
								if (!in_array('q', $flags))
									echo "Copying '" . join_path($config_dir, $args[1], $one_file) . "' to '" . join_path($config_dir, $one_file) . "'\n";
								if (!copy(join_path($config_dir, $args[1], $one_file), join_path($config_dir, $one_file)))
								{
									echo "Error copying file: " . join_path($config_dir, $args[1], $one_file) . ".  Aborting.\n";
									echo 4;
								}
							}
						}
					}
					else
					{
						echo "Not a valid environment name\n";
						return 3;
					}
				}
				else
				{
					echo "No permissions to write to the config directory.  Please correct before continuing. \n";
					return 2;
				}
			}
		}
		else
		{
			if (isset($args[0]))
				echo "'{$args[0]}' is not a valid config argument.  Exiting.\n";
			else
				echo "No argument given.  Exiting.\n";
			return 1;
		}
		
		return 0;
	}
	
		public function description()
		{
			return <<<EOF
Various methods for handling configuration files in the silk framework.

-env(ironment) 'environment_name'
	Copies all files from config/environment_name to the config directory.
	Allows for an application to quick switch between multiple 
	environments. By default, we have 'development', 'test', and 'production',
	but any valid unix directory can be used.

EOF;
	}

	public function usage() {
		return <<<EOF
Usage: config [-q] env
EOF;
	}
}

# vim:ts=4 sw=4 noet
?>