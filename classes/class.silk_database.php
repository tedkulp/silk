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

//Database related defines
define('ADODB_OUTP', 'adodb_outp');

/**
 * Singleton class to represent a connection to the database.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkDatabase extends SilkObject
{
	static private $instance = NULL;
	static private $prefix = NULL;

	static public function get_instance($dsn = '', $debug = false)
	{
		if (self::$instance == NULL)
		{
			SilkDatabase::connect($dsn, $debug);
		}
		return self::$instance;
	}
	
	static public function close()
	{
		if (self::$instance !== null)
		{
			if (self::$instance->IsConnected())
			{
				self::$instance->Close();
			}
		}
	}
	
	static public function get_prefix()
	{
		if (self::$prefix === null)
		{
			self::$prefix = 'silk_';
		}
		return self::$prefix;
	}
	
	static function connect($dsn, $debug = false, $die = true, $prefix = null, $make_global = true)
	{
		/*
		$gCms = silk();
		$persistent = false;
		
		if ($dbms == '')
		{
			$config = cms_config();
			$dbms = $config['dbms'];
			$hostname = $config['db_hostname'];
			$username = $config['db_username'];
			$password = $config['db_password'];
			$dbname = $config['db_name'];
			$debug = $config['debug'];
			$persistent = $config['persistent_db_conn'];
		}
		*/
		
		if ($prefix !== null)
		{
			self::$prefix = $prefix;
		}
		
		$dbinstance = null;

		$_GLOBALS['ADODB_CACHE_DIR'] = join_path(ROOT_DIR,'tmp','cache');

		require_once(join_path(SILK_LIB_DIR,'adodb','adodb-exceptions.inc.php'));
        require_once(join_path(SILK_LIB_DIR,'adodb','adodb.inc.php'));

		try
		{
			$dbinstance = ADONewConnection($dsn);
			$dbinstance->fnExecute = 'count_execs';
			$dbinstance->fnCacheExecute = 'count_cached_execs';
		}
		catch (exception $e)
		{
			if ($die)
			{
				echo "<strong>Database Connection Failed</strong><br />";
				echo "Error: {$dbinstance->_errorMsg}<br />";
				echo "Function Performed: {$e->fn}<br />";
				echo "Host/DB: {$e->host}/{$e->database}<br />";
				die();
			}
			else
			{
				return null;
			}
		}

		$dbinstance->SetFetchMode(ADODB_FETCH_ASSOC);
		$dbinstance->debug = $debug;
		
	    if ($dbms == 'sqlite')
	    {
			$dbinstance->Execute("PRAGMA short_column_names = 1;");
	        sqlite_create_function($dbinstance->_connectionID,'now','time',0);
	    }
	
		if ($make_global)
		{
			self::$instance = $dbinstance;
		}
		
		//Initialize the CMS_DB_PREFIX define
		self::get_prefix();

		return $dbinstance;
	}
	
	public static function query_count()
	{
		if (method_exists(self::$instance, 'query_count'))
		{
			return self::$instance->query_count();
		}
		else
		{
			global $EXECS;
			global $CACHED;
			return $EXECS + $CACHED;
		}
	}
	
	public static function create_table($table, $fields)
	{	
		$dbdict = NewDataDictionary(self::get_instance());
		$taboptarray = array('mysql' => 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

		$sqlarray = $dbdict->CreateTableSQL(self::get_prefix().$table, $fields, $taboptarray);
		if (count($sqlarray))
		{
			//$sqlarray[0] .= "\n/*!40100 DEFAULT CHARACTER SET UTF8 */";
		}
		$dbdict->ExecuteSQLArray($sqlarray);
	}
	
	public static function create_index($table, $name, $field)
	{	
		$dbdict = NewDataDictionary(self::get_instance());

		$sqlarray = $dbdict->CreateIndexSQL($name, self::get_prefix().$table, $field);
		$dbdict->ExecuteSQLArray($sqlarray);
	}
	
	public static function drop_table($table)
	{
		$dbdict = NewDataDictionary(self::get_instance());

		$sqlarray = $dbdict->DropTableSQL(self::get_prefix().$table);
		$dbdict->ExecuteSQLArray($sqlarray);
	}
}

function adodb_outp($msg, $newline = true)
{
	if ($newline)
		$msg .= "<br>\n";

	$msg = str_replace('<hr />', '', $msg);

	SilkProfiler::get_instance()->mark($msg);
}

//TODO: Clean me up.  Globals?  Yuck!
function count_execs($db, $sql, $inputarray)
{
	//SilkProfiler::get_instance()->mark($sql);

	global $EXECS;

	if (!is_array($inputarray))
		$EXECS++;
	else if (is_array(reset($inputarray)))
		$EXECS += sizeof($inputarray);
	else
		$EXECS++;

	$null = null;
	return $null;
}

//TODO: You too, slacker!
function count_cached_execs($db, $secs2cache, $sql, $inputarray)
{
	SilkProfiler::get_instance()->mark('CACHED:' . $sql);

	global $CACHED; $CACHED++;
}

# vim:ts=4 sw=4 noet
?>