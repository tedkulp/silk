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
				die("Could not create db directory.  Please create it manually or fix permissions.\n");
			}
		}
		
		if (!is_dir(join_path(ROOT_DIR, 'db', 'migrate')))
		{
			if (!mkdir(join_path(ROOT_DIR, 'db', 'migrate')))
			{
				die("Could not create db/migrate directory.  Please create it manually or fix permissions.\n");
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
		else if ($args[0] == 'run')
		{
			$files = scandir($path_to_migrations);
			$migration_files = array();
			
			foreach ($files as $one_file)
			{
				$matches = array();
				if (preg_match('/^([0-9]{14}).*?\.php$/', $one_file, $matches))
				{
					$migration_files[$matches[1]] = $one_file;
				}
			}
			
			$current_migration = $this->get_migration_version();
			$target_migration = array_pop(array_keys($migration_files));
			
			if (isset($options['version']))
			{
				if (in_array($options['version'], array_keys($migration_files)) || $options['version'] == '0')
				{
					$target_migration = $options['version'];
				}
			}
			
			echo "Current version: {$current_migration}\n";
			echo "Target version: {$target_migration}\n";
			
			if ($current_migration == $target_migration)
			{
				die("Database up to date.  Nothing to do\n");
			}
			else if ($current_migration < $target_migration)
			{
				foreach ($migration_files as $ver=>$filename)
				{
					if ($ver > $current_migration && $ver <= $target_migration)
					{
						//Run this
						echo "Running migration: {$ver}\n";
						$this->run_migration(join_path($path_to_migrations, $filename), $ver, $ver);
					}
				}
			}
			else if ($current_migration > $target_migration)
			{
				$ary = array_reverse(array_keys($migration_files));
				$count = 0;
				foreach ($ary as $ver)
				{
					if ($ver <= $current_migration && $ver > $target_migration)
					{
						//Run this
						echo "Running migration: {$ver}\n";
						$prev_ver = '0';
						if (isset($ary[$count + 1]))
							$prev_ver = $ary[$count + 1];
						$this->run_migration(join_path($path_to_migrations, $migration_files[$ver]), $ver, $prev_ver, 'down');
					}
					$count++;
				}
			}
		}
	}
	
	public function help()
	{
		return <<<EOF
Task: migration

Description:
Migrations are a system for allowing for incremental changes to a database.  It works
great in a development environment where databases change little by little as code development
continues on.  The system basically allows a user to generate a migration file.  In the up()
function, they define the database commands necessary to upgrade the database to the current
environment.  In the down function, you specify the opposite commands, in case the database
needs to roll back to an ealier version.

Commands:
generate - silk.php migration generate ["Optional Description"]
Generates a new migration file.  The first section of the filename will be a timestamp for
the current date and time.

run - silk.php migration run [--version=20090201123456]
Get the database migrated.  The system will look at it's current state, then
migrate the database forward or backward in order based on the given version.
If no version is given, we use the version of the latest migration file.

EOF;
	}
	
	public function run_migration($filename, $ver, $new_ver, $dir = 'up')
	{
		$old_func = $GLOBALS['ADODB_OUTP'];
		$GLOBALS['ADODB_OUTP'] = 'migration_db_echo';
		srand();
		srand(time() + rand());
		$classname = 'cls_mgr_' . rand();
		$file = str_replace('?>', '', str_replace('<?php', '', file_get_contents($filename)));
		eval("class {$classname} { " . $file . " }");
		$dbdict = NewDataDictionary(db());
		$cls = new $classname;
		try
		{
			$cls->$dir($dbdict, db_prefix());
			$this->update_migration_version($new_ver);
		}
		catch (ADODB_Exception $ex)
		{
			die($ex->msg . "\n");
		}
		$GLOBALS['ADODB_OUTP'] = $old_func;
	}
	
	public function generate_template()
	{
		return <<<EOF
<?php
	function up(\$dict, \$db_prefix)
	{
		//SilkDatabase::create_table('test_table', 'id I, field C(255)');
		//SilkDatabase::add_column('test_table', 'new_field C(50)');
		//SilkDatabase::drop_column('test_table', 'field');
	}
	
	function down(\$dict, \$db_prefix)
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
		$result = '0';
		try
		{
			$result = db()->GetOne("SELECT version from {$db_prefix}migration_version");
		}
		catch (ADODB_Exception $ex)
		{
			if (!$result)
			{
				SilkDatabase::create_table('migration_version', 'version C(15)');
				db()->Execute("INSERT INTO {$db_prefix}migration_version (version) VALUES ('0')");
				$result = '0';
			}
		}
		return str_pad($result, 14);
	}
	
	public function update_migration_version($num)
	{
		$db_prefix = db_prefix();
		db()->Execute("UPDATE {$db_prefix}migration_version SET version=?", array($num));
	}
}

if (!function_exists('migration_db_echo'))
{
	function migration_db_echo($msg, $newline = true)
	{
		$msg = str_replace("\t", "", str_replace("\r", "", str_replace("\n", "", $msg)));
		$msg = preg_replace("/ {2,}/", ' ', $msg);
		echo trim($msg) . "\n";
	}
}

# vim:ts=4 sw=4 noet
?>