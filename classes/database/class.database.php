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

//////////////////////////////////////////////////////////
// Parts of this code are based/taken from phpDataMapper //
// Homepage: http://phpdatamapper.com/                   //
// Released under the MIT license                        //
//////////////////////////////////////////////////////////

namespace silk\database;

use \silk\database\Query;
use \silk\core\EventManager;

abstract class Database extends \PDO
{
	protected $query_log = array();
	protected $_database_name = '';
	protected $_dsn = '';
	protected $_prefix = '';
	
	static private $instance = NULL;

	public static function get_instance($dsn = null, $debug = false)
	{
		if (self::$instance == NULL)
		{
			self::connect($dsn, $debug);
			EventManager::send_event('silk:database:connection:opened');
			EventManager::register_event_handler('silk:core:application:shutdown_now', array(get_called_class(), 'close'), true);
		}
		return self::$instance;
	}
	
	public static function close()
	{
		if (self::$instance !== null)
		{
			self::$instance = null;
			EventManager::send_event('silk:database:connection:closed');
		}
	}
	
	public static function get_prefix()
	{
		$prefix = self::get_instance()->_prefix;
		return $prefix;
	}
	
	public static function connect($dsn = null, $debug = false, $prefix = null)
	{
		if ($dsn == null)
		{
			$config = get('config');
			
			//Setup the database connection
			//if (!isset($config['database']['dsn']))
				//throw new SilkDatabaseException("No database information found in the configuration file.");
			
			$dsn = $config['database']['pdo_dsn'];
			$debug = $config['debug'];
			$prefix = $config['database']['prefix'];
			$attrs = array(\PDO::ATTR_PERSISTENT => true);
		}

		try
		{
			$class = '\silk\database\adapter\Mysql';
			$attrs = $attrs + $class::get_connection_attributes(); //Add driver specific attributes
			self::$instance = new $class($dsn, $config['database']['username'], $config['database']['password'], $attrs);
			self::$instance->_dsn = $dsn;
			self::$instance->_prefix = $prefix;
		}
		catch (\PDOException $e)
		{
			echo 'Connection failed: ' . $e->getMessage();
		}

		return self::$instance;
	}

	/*
	function __construct($dsn, $username, $password, $driver_options = array())
	{
		parent::__construct($dsn, $username, $password, $driver_options);
	}
	*/
	
	/**
	 * Returns an instnace of the Database (or one of it's 
	 * children) singleton.  Most people can generally use db() 
	 * instead of this, but they both do the same thing.
	 *
	 * @return Database The singleton Database instance
	 **/
	/*
	static public function get_instance()
	{
		if (self::$instance == NULL)
		{
			$config = get('config');
			
			$conn_string = $config['database']['dsn'];
			$attrs = array(\PDO::ATTR_PERSISTENT => true);
			
			try
			{
				$class = '\silk\database\adapter\Mysql';
				$attrs = $attrs + $class::get_connection_attributes(); //Add driver specific attributes
				self::$instance = new $class($conn_string, $config['db_username'], $config['db_password'], $attrs);
				self::$instance->_database_name = $config['db_name'];
			}
			catch (\PDOException $e)
			{
				echo 'Connection failed: ' . $e->getMessage();
			}
		}
		return self::$instance;
	}
	*/
	
	public function query($query, $add_prefix = true)
	{
		$query = $add_prefix ? $this->add_prefix_to_query($query) : $query;
		return parent::query($query);
	}
	
	public function prepare($query, $driver_options = array())
	{
		$query = $this->add_prefix_to_query($query);
		return parent::prepare($query, $driver_options);
	}
	
	public function execute_sql($query, $input_parameters = array(), $driver_options = array())
	{
		$this->log_query($query, $input_parameters);
		$handle = $this->prepare($query, $driver_options);
		if ($handle)
		{
			return $handle->execute($input_parameters);
		}
		return false;
	}

	public function execute($query, $input_parameters = array(), $driver_options = array())
	{
		return $this->execute_sql($query, $input_parameters, $driver_options);
	}

	public function fetch_all($query, $input_parameters = array(), $driver_options = array())
	{
		$this->log_query($query, $input_parameters);
		$handle = $this->prepare($query, $driver_options);
		if ($handle)
		{
			if ($handle->execute($input_parameters))
			{
				return $handle->fetchAll();
			}
		}
		return false;
	}
	
	public function fetchAll($query, $input_parameters = array(), $driver_options = array())
	{
		$this->log_query($query, $input_parameters);
		return $this->fetch_all($query, $input_parameters, $driver_options);
	}
	
	public function fetch_column($query, $input_parameters = array(), $column_num = 0, $driver_options = array())
	{
		$this->log_query($query, $input_parameters);
		$handle = $this->prepare($query, $driver_options);
		if ($handle)
		{
			if ($handle->execute($input_parameters))
			{
				$result = array();
				while ($one_col = $handle->fetchColumn($column_num))
				{
					$result[] = $one_col;
				}
				return $result;
			}
		}
		return false;
	}
	
	public function get_one($query, $input_parameters = array(), $driver_options = array())
	{
		$this->log_query($query, $input_parameters);
		$handle = $this->prepare($query, $driver_options);
		if ($handle)
		{
			if ($handle->execute($input_parameters))
			{
				return $handle->fetchColumn();
			}
		}
		return false;
	}
	
	function last_insert_id($name = NULL)
	{
		return $this->lastInsertId($name);
	}
	
	function timestamp($time = null)
	{
		if (!$time)
			$time = time();
		
		return date($this->format_datetime, $time);
	}
	
	public function add_prefix_to_query($query)
	{
		return strtr($query, array('{' => self::get_prefix(), '}' => ''));
	}
	
	public function log_query($sql, $data = null)
	{
		$this->query_log[] = array('query' => $sql, 'data' => $data);
	}
	
	public function select($fields = "*")
	{
		$query = new Query($this);
		$query->select($fields);
		return $query;
	}
	
	public function read(Query $query)
	{
		$conditions = $this->statement_conditions($query->conditions);
		$binds = $this->statement_binds($query->params());
		$order = array();
		if ($query->order)
		{
			foreach($query->order as $oField => $oSort)
			{
				$order[] = $oField . " " . $oSort;
			}
		}

		$sql = "
			SELECT " . $this->statement_fields($query->fields) . "
			FROM " . $query->table . "
			" . ($conditions ? 'WHERE ' . $conditions : '') . "
			" . ($query->group ? 'GROUP BY ' . implode(', ', $query->group) : '') . "
			" . ($order ? 'ORDER BY ' . implode(', ', $order) : '') . "
			" . ($query->limit ? 'LIMIT ' . $query->limit : '') . " " . ($query->limit && $query->limit_offset ? 'OFFSET ' . $query->limit_offset: '') . "
			";

		// Unset any NULL values in binds (compared as "IS NULL" and "IS NOT NULL" in SQL instead)
		if($binds && count($binds) > 0)
		{
			foreach($binds as $field => $value)
			{
				if (null === $value)
				{
					unset($binds[$field]);
				}
			}
		}

		// Add query to log
		$this->log_query($sql, $binds);

		// Prepare update query
		$handle = $this->prepare($sql);
		if ($handle)
		{
			if ($handle->execute($binds))
			{
				return $handle->fetchAll();
			}
		}
		
		return false;
	}
	
	public function statement_conditions(array $conditions = array())
	{
		if (count($conditions) == 0)
		{
			return;
		}

		$sqlStatement = "";
		$defaultColOperators = array(0 => '', 1 => '=');
		$ci = 0;
		$loopOnce = false;
		foreach ($conditions as $condition)
		{
			if (is_array($condition) && isset($condition['conditions']))
			{
				$subConditions = $condition['conditions'];
			}
			else
			{
				$subConditions = $conditions;
				$loopOnce = true;
			}
			$sqlWhere = array();
			foreach ($subConditions as $column => $value)
			{
				// Column name with comparison operator
				$colData = explode(' ', $column);
				if ( count( $colData ) > 2 )
				{
					$operator = array_pop( $colData );
					$colData = array( join(' ', $colData), $operator );
				}
				$col = $colData[0];

				// Array of values, assume IN clause
				if(is_array($value))
				{
					$sqlWhere[] = $col . " IN('" . implode("', '", $value) . "')";
					// NULL value
				}
				elseif (is_null($value))
				{
					$sqlWhere[] = $col . " IS NULL";

					// Standard string value
				}
				else
				{
					$colComparison = isset($colData[1]) ? $colData[1] : '=';
					$columnSql = $col . ' ' . $colComparison;

					// Add to binds array and add to WHERE clause
					$colParam = preg_replace('/\W+/', '_', $col) . $ci;
					$sqlWhere[] = $columnSql . " :" . $colParam . "";
				}

				// Increment ensures column name distinction
				$ci++;
			}
			
			if ( $sqlStatement != "" )
			{
				$sqlStatement .= " " . (isset($condition['setType']) ? $condition['setType'] : 'AND') . " ";
			}
			$sqlStatement .= join(" " . (isset($condition['type']) ? $condition['type'] : 'AND') . " ", $sqlWhere );

			if($loopOnce)
			{
				break;
			}
		}

		return $sqlStatement;
	}
	
	public function statement_binds(array $conditions = array())
	{
		if(count($conditions) == 0)
		{
			return;
		}

		$binds = array();
		$ci = 0;
		$loopOnce = false;
		foreach ($conditions as $condition)
		{
			if (is_array($condition) && isset($condition['conditions']))
			{
				$subConditions = $condition['conditions'];
			}
			else
			{
				$subConditions = $conditions;
				$loopOnce = true;
			}
			
			foreach ($subConditions as $column => $value)
			{
				// Can't bind array of values
				if(!is_array($value) && !is_object($value))
				{
					// Column name with comparison operator
					$colData = explode(' ', $column);
					if ( count( $colData ) > 2 )
					{
						$operator = array_pop( $colData );
						$colData = array( join(' ', $colData), $operator );
					}
					$col = $colData[0];
					$colParam = preg_replace('/\W+/', '_', $col) . $ci;

					// Add to binds array and add to WHERE clause
					$binds[$colParam] = $value;
				}

				// Increment ensures column name distinction
				$ci++;
			}
			
			if($loopOnce)
			{
				break;
			}
		}
		return $binds;
	}
	
	public function statement_fields(array $fields = array())
	{
		return count($fields) > 0 ? implode(', ', $fields) : "*";
	}

	public function check_sub_table($fields)
	{
		foreach ($fields as $k => $v)
		{
			if ($v['type'] == 'table' && isset($v['fields']) && is_array($v['fields']))
			{
				//No noes, recursion
				$this->migrate($k, $v['fields']);
			}
		}
	}
	
	public function migrate($table, array $fields, $check_sub_table = true)
	{
		// Get current fields for table
		$tableExists = false;
		$tableColumns = $this->getColumnsForTable(db_prefix() . $table);

		if($tableColumns)
		{
			$tableExists = true;
		}
		
		if($tableExists)
		{
			// Update table
			$this->update_table($table, $fields, false);
		}
		else
		{
			// Create table
			$this->create_table($table, $fields, false);
		}

		if ($check_sub_table)
			$this->check_sub_table($fields);
	}
	
	public function create_table($table, $fields = array(), $check_sub_table = true)
	{
		// Prepare fields and get syntax for each
		$columns_syntax = array();
		foreach($fields as $field_name => $field_info)
		{
			$ret = $this->migrateSyntaxFieldCreate($field_name, $field_info);
			if ($ret)
				$columns_syntax[$field_name] = $ret;
		}

		// Get syntax for table with fields/columns
		$sql = $this->migrateSyntaxTableCreate(db_prefix() . $table, $fields, $columns_syntax);

		// Add query to log
		$this->log_query($sql);

		$this->query($sql, false);

		if ($check_sub_table)
			$this->check_sub_table($fields);
		
		return true;
	}
	
	public function update_table($table, array $formattedFields, $check_sub_table = true)
	{
		/*
			STEPS:
			* Use fields to get column syntax
			* Use column syntax array to get table syntax
			* Run SQL
		*/

		// Prepare fields and get syntax for each
		$tableColumns = $this->getColumnsForTable(db_prefix() . $table);
		$updateFormattedFields = array();
		foreach($tableColumns as $fieldName => $columnInfo) {
			if(isset($formattedFields[$fieldName])) {
				// TODO: Need to do a more exact comparison and make this non-mysql specific
				if ( 
						$this->_fieldTypeMap[$formattedFields[$fieldName]['type']] != $columnInfo['DATA_TYPE'] ||
						$formattedFields[$fieldName]['default'] !== $columnInfo['COLUMN_DEFAULT']
					) {
					$updateFormattedFields[$fieldName] = $formattedFields[$fieldName];
				}

				unset($formattedFields[$fieldName]);
			}
		}

		$columnsSyntax = array();
		// Update fields whose options have changed
		foreach($updateFormattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldUpdate($fieldName, $fieldInfo, false);
		}
		// Add fields that are missing from current ones
		foreach($formattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldUpdate($fieldName, $fieldInfo, true);
		}

		// Get syntax for table with fields/columns
		if ( !empty($columnsSyntax) ) {
			$sql = $this->migrateSyntaxTableUpdate(db_prefix() . $table, $formattedFields, $columnsSyntax);

			// Add query to log
			$this->log_query($sql);

			// Run SQL
			$this->query($sql, false);
		}

		if ($check_sub_table)
			$this->check_sub_table($fields);

		return true;
	}

	public function drop_table($table)
	{
		$sql = "DROP TABLE " . db_prefix() . $table;

		// Add query to log
		$this->log_query($sql);

		return $this->query($sql, false);
	}
	
	public function truncate_table($table)
	{
		$sql = "TRUNCATE TABLE " . db_prefix() . $table;

		// Add query to log
		$this->log_query($sql);

		return $this->query($sql, false);
	}

}

# vim:ts=4 sw=4 noet
