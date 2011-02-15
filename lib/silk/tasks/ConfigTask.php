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

namespace silk\tasks;

use \silk\cli\Task;

class ConfigTask extends Task
{
	public $needs_db = false;

	public function __construct()
	{
		$this->addArgument('environment_name');

        return parent::__construct(array(
            'name' => 'config',
            'description' => "Command for quickly changing config files for various environments.",
            'version'     => '0.0.1'
        ));
    }
	
	public function run($argc, $argv)
	{
		$env = '';
		$result = $this->parse($argc, $argv);

		if ($result->args['environment_name'] != '')
		{
			$env = $result->args['environment_name'];
			$config_dir = joinPath(ROOT_DIR, 'config');
			if (is_writable($config_dir))
			{
				if (is_readable(joinPath($config_dir, $env)))
				{
					$files = scandir(joinPath($config_dir, $env));
					foreach ($files as $one_file)
					{
						if ($one_file != '.' && $one_file != '..')
						{
							//if (!in_array('q', $flags))
								echo "Copying '" . joinPath($config_dir, $env, $one_file) . "' to '" . joinPath($config_dir, $one_file) . "'\n";

							if (!copy(joinPath($config_dir, $env, $one_file), joinPath($config_dir, $one_file)))
							{
								echo "Error copying file: " . joinPath($config_dir, $env, $one_file) . ".  Aborting.\n";
								return 4;
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
		else
		{
			echo "No environment name given\n";
			return 1;
		}
		
		return 0;
	}
}

# vim:ts=4 sw=4 noet
