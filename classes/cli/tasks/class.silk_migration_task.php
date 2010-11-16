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

class SilkMigrationTask extends SilkTask
{
	public function __construct()
	{
		$this->AddOption('version', array(
			'long_name'   => '--version',
			'description' => "Runs some stuff.",
			'action'      => 'StoreString',
			'final' => true)
		);
		
		$this->addArgument('args', array(
			'multiple' => true)
		);
		
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
		
		$path_to_migrations = join_path(ROOT_DIR, 'db', 'migrate');
		if ((isset($args) && 0 == count($args)) || 
		(isset($args[0]) && '' == trim($args[0]))) {
			echo "Arguments required. \n\n";
			echo $this->usage() . "\n\n";
			echo "Try: migration --help for more details.\n";
		
			return 0;
		}
	
		if (!is_dir(join_path(ROOT_DIR, 'db')))
		{
			if (!mkdir(join_path(ROOT_DIR, 'db')))
			{
				echo "Could not create db directory.  Please create it manually or fix permissions.\n";
				return 1;
			}
		}
		
		if (!is_dir(join_path(ROOT_DIR, 'db', 'migrate')))
		{
			if (!mkdir(join_path(ROOT_DIR, 'db', 'migrate')))
			{
				echo "Could not create db/migrate directory.  Please create it manually or fix permissions.\n";
				return 2;
			}
		}
			
		if (isset($args[0]) && $args[0] == 'generate')
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
					echo "Not a valid description.  Can only contain the characters (A-Z, a-z, 0-9, -, _ and space).\n";
					return 3;
				}
			}
			
			$filename = join_path($path_to_migrations, $this->generate_timestamp() . '_' . $description . '.php');
			
			$contents = file_put_contents($filename, $this->generate_template());
			if ($contents)
			{
				echo "Migration file: {$filename} created.\n";
			}
			else
			{
				echo "Error creating migration file: {$filename}\n";
				return 4;
			}
		}
		else if (isset($args[0]) && $args[0] == 'run')
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
			
			if (isset($result->options['version']))
			{
				if (in_array($result->options['version'], array_keys($migration_files)) || $result->options['version'] == '0')
				{
					$target_migration = $result->options['version'];
				}
			}
			
			echo "Current version: {$current_migration}\n";
			echo "Target version: {$target_migration}\n";
			
			if ($current_migration == $target_migration)
			{
				echo "Database up to date. Nothing to do\n";
				return 5;
			}
			else if ($current_migration < $target_migration)
			{
				foreach ($migration_files as $ver=>$filename)
				{
					if ($ver > $current_migration && $ver <= $target_migration)
					{
						//Run this
						echo "Running migration: {$ver}\n";
						if (0 != $this->run_migration(join_path($path_to_migrations, $filename), $ver, $ver)) {
							// return on error
							return 7;
						}
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
						if(0 != $this->run_migration(join_path($path_to_migrations, $migration_files[$ver]), $ver, $prev_ver, 'down')) {
							// return on error
							return 8;
						}
					}
					$count++;
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
Usage: migration (generate | run)
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
			echo($ex->msg . "\n");
			return 1;
		}
		$GLOBALS['ADODB_OUTP'] = $old_func;
		return 0;
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
