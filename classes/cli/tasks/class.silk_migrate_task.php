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

use \silk\database\Database;

class SilkMigrateTask extends SilkTask
{
	public function __construct()
	{
		/*
		$this->AddOption('version', array(
			'long_name'   => '--version',
			'description' => "Runs some stuff.",
			'action'      => 'StoreString',
			'final' => true)
		);
		
		$this->addArgument('args', array(
			'multiple' => true)
		);
		*/
		
		return parent::__construct(array(
			'name' => 'config',
			'description' => "Various methods for handling Silk Framework configuration.",
			'version'     => '0.0.1')
		);
	}
	
	public function run($argc, $argv)
	{
		$result = $this->parse($argc, $argv);
		$args = $result->args['args'];

		$classes = config('auto_migrate');

		if (is_array($classes))
		{
			foreach ($classes as $class_name => $file_name)
			{
				if (require_once($file_name))
				{
					$class = new $class_name;
					if ($class)
					{
						echo "Running migraiton on class: {$class_name}\n";
						$class->migrate();
					}
				}
			}
		}

		return 0;
	}
	
	public function description()
	{
		return <<<EOF
Migrations allow for ordered, incremental changes to a database. Main usage is within a 
development environment where databases change little-by-little as code development continues.
This command can generate new migration files, and perform the required migrations.

Within a migration file, the up() function defines the database commands required to upgrade the database
to the current environment. The down() function, specifies the opposite commands, to roll back to the ealier version.

TODO: Provide link to example of migration in silk.

 -generate ['Optional Description']
	Generates a new migration file. The first section of the filename will be a timestamp for
	the current date and time.

 -run [--version=20090201123456]
	Migrate database to latest migration, or to a particular version specified by --version. Examines the current database state, then
	migrates the database forward or backward, as required.

EOF;
	}
	
	public function usage() {
		return <<<EOF
Usage: migrate
EOF;
	}
}

# vim:ts=4 sw=4 noet
