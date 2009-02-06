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

class SilkMigrationTask extends SilkObject
{
	public function run($args, $flags, $options)
	{
		$path_to_migrations = join_path(ROOT_DIR, 'db', 'migrate');
		
		if (!is_dir(join_path(ROOT_DIR, 'db')))
		{
			if (!mkdir(join_path(ROOT_DIR, 'db')))
			{
				die("Could not create db directory.  Please create it manually for fix permissions.\n");
			}
		}
		
		if (!is_dir(join_path(ROOT_DIR, 'db', 'migrate')))
		{
			if (!mkdir(join_path(ROOT_DIR, 'db', 'migrate')))
			{
				die("Could not create db/migrate directory.  Please create it manually for fix permissions.\n");
			}
		}
		
		if ($args[0] == 'generate')
		{
			$description = 'migration';
			if (count($args) > 1)
			{
				if (preg_match('/^[A-Za-z0-9\-][A-Za-z0-9\-_ ]+$/', $args[1]))
				{
					$description = underscore(str_replace(' ', '_', $args[1]));
				}
				else
				{
					die("Not a valid description.  Can only contain the characters (A-Z, a-z, 0-9, -, _ and space).\n");
				}
			}
			
			$filename = join_path($path_to_migrations, $this->generate_timestamp() . '_' . $description . '.php');
			
			$result = file_put_contents($filename, $this->generate_template());
			if ($result)
			{
				echo "Migration file: {$filename} created.\n";
			}
			else
			{
				die("Error creating migration file: {$filename}\n");
			}
		}
	}
	
	public function help()
	{
		return <<<EOF
Do some stuff here!!!
EOF;
	}
	
	public function generate_template()
	{
		return <<<EOF
<?php
	function up(&\$dict, \$db_prefix, \$taboptarray)
	{
		//Modify the data dictionary with changes
		//to the database using ADODB's data dictionary
		//methods.
		//See: http://phplens.com/lens/adodb/docs-datadict.htm
		
		//ex.
		//\$dict->CreateTableSQL(\$db_prefix.'test_table', 'id I, field C(255)', \$taboptarray);
		//\$dict->AddColumnSQL(\$db_prefix.'test_table', 'new_field C(50)');
		//\$dict->DropColumnSQL(\$db_prefix.'test_table', 'field');
		//etc.
	}
	
	function down(&\$dict, \$db_prefix, \$taboptarray)
	{
		
	}
?>
EOF;
	}
	
	public function generate_timestamp()
	{
		return gmstrftime('%Y%m%d%H%M%S');
	}
	
	public function get_migration_version()
	{
		$db_prefix = db_prefix();
		$result = 0;
		try
		{
			$result = db()->GetOne("SELECT version from {$db_prefix}migration_version");
		}
		catch (ADODB_Exception $ex)
		{
			if (!$result)
			{
				SilkDatabase::create_table('migration_version', 'version I(11)');
				db()->Execute("INSERT INTO {$db_prefix}migration_version (version) VALUES (0)");
				$result = 0;
			}
		}
		return $result;
	}
}

# vim:ts=4 sw=4 noet
?>