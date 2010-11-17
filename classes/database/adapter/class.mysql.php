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

namespace silk\database\adapter;

use \silk\database\Database;
use \silk\database\Query;

class Mysql extends Database
{
	protected $format_date = "Y-m-d";
	protected $format_time = " H:i:s";
	protected $format_datetime = "Y-m-d H:i:s";

	// Driver-Specific settings
	protected $_engine = 'InnoDB';
	protected $_charset = 'utf8';
	protected $_collate = 'utf8_unicode_ci';

	protected $_fieldTypeMap = array(
		'string' => array('adapter_type' => 'varchar', 'length' => 255),
		'text' => array('adapter_type' => 'text'),
		'int' => array('adapter_type' => 'int'),
		'integer' => array('adapter_type' => 'int'),
		'bool' => array('adapter_type' => 'tinyint', 'length' => 1),
		'boolean' => array('adapter_type' => 'tinyint', 'length' => 1),
		'float' => array('adapter_type' => 'float'),
		'double' => array('adapter_type' => 'double'),
		'date' => array('adapter_type' => 'date'),
		'datetime' => array('adapter_type' => 'datetime'),
		'create_date' => array('adapter_type' => 'datetime'),
		'modified_date' => array('adapter_type' => 'datetime'),
		'time' => array('adapter_type' => 'time'),
	);
	
	function __construct($dsn, $username, $password, $driver_options = array())
	{
		parent::__construct($dsn, $username, $password, $driver_options);
		if (preg_match('/dbname=([A-Za-z0-9_-]+)/', $dsn, $matches))
		{
			if (isset($matches[1]))
				$this->_database_name = $matches[1];
		}
	}
	
	static public function get_connection_attributes()
	{
		return array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'");
	}
	
	/**
	 * Get columns for current table
	 *
	 * @param String $table Table name
	 * @return Array
	 */
	public function getColumnsForTable($table)
	{
		$tableColumns = array();
		$tblCols = $this->query("SELECT * FROM information_schema.columns WHERE table_schema = '" . $this->_database_name . "' AND table_name = '" . $table . "'");

		if($tblCols)
		{
			while($columnData = $tblCols->fetch(\PDO::FETCH_ASSOC))
			{
				$tableColumns[$columnData['COLUMN_NAME']] = $columnData;
			}
			return $tableColumns;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Syntax for each column in CREATE TABLE command
	 *
	 * @param string $fieldName Field name
	 * @param array $fieldInfo Array of field settings
	 * @return string SQL syntax
	 */
	public function migrateSyntaxFieldCreate($fieldName, array $fieldInfo)
	{
		// Ensure field type exists
		if(!isset($this->_fieldTypeMap[$fieldInfo['type']])) {
			//throw new phpDataMapper_Exception("Field type '" . $fieldInfo['type'] . "' not supported");
			//var_dump("Field type '" . $fieldInfo['type'] . "' not supported");
			return null;
		}

		$fieldInfo = array_merge($fieldInfo, $this->_fieldTypeMap[$fieldInfo['type']]);

		$syntax = "`" . $fieldName . "` " . $fieldInfo['adapter_type'];
		// Column type and length
		$syntax .= isset($fieldInfo['length']) ? '(' . $fieldInfo['length'] . ')' : '';
		// Unsigned
		$syntax .= isset($fieldInfo['unsigned']) ? ' unsigned' : '';
		// Collate
		$syntax .= ($fieldInfo['type'] == 'string' || $fieldInfo['type'] == 'text') ? ' COLLATE ' . $this->_collate : '';
		// Nullable
		$isNullable = true;
		if((isset($fieldInfo['required']) && $fieldInfo['required']) || !isset($fieldInfo['null']) || !$fieldInfo['null']) {
			$syntax .= ' NOT NULL';
			$isNullable = false;
		}
		// Default value
		if((!isset($fieldInfo['default']) || $fieldInfo['default'] === null) && $isNullable) {
			$syntax .= " DEFAULT NULL";
		} elseif(isset($fieldInfo['default']) && $fieldInfo['default'] !== null) {
			$default = $fieldInfo['default'];
			// If it's a boolean and $default is boolean then it should be 1 or 0
			if ( is_bool($default) && $fieldInfo['type'] == "boolean" ) {
				$default = $default ? 1 : 0;
			}
			$syntax .= " DEFAULT '" . $default . "'";
		}
		// Extra
		$syntax .= (isset($fieldInfo['primary']) && $fieldInfo['primary'] && isset($fieldInfo['serial']) && $fieldInfo['serial']) ? ' AUTO_INCREMENT' : '';
		return $syntax;
	}


	/**
	 * Syntax for CREATE TABLE with given fields and column syntax
	 *
	 * @param string $table Table name
	 * @param array $formattedFields Array of fields with all settings
	 * @param array $columnsSyntax Array of SQL syntax of columns produced by 'migrateSyntaxFieldCreate' function
	 * @return string SQL syntax
	 */
	public function migrateSyntaxTableCreate($table, array $formattedFields, array $columnsSyntax)
	{
		$syntax = "CREATE TABLE IF NOT EXISTS `" . $table . "` (\n";
		// Columns
		$syntax .= implode(",\n", $columnsSyntax);

		// Keys...
		$ki = 0;
		$usedKeyNames = array();
		foreach($formattedFields as $fieldName => $fieldInfo) {
			// Determine key field name (can't use same key name twice, so we have to append a number)
			$fieldKeyName = $fieldName;
			while(in_array($fieldKeyName, $usedKeyNames)) {
				$fieldKeyName = $fieldName . '_' . $ki;
			}
			// Key type
			if(isset($fieldInfo['primary'])) {
				$syntax .= "\n, PRIMARY KEY(`" . $fieldName . "`)";
			}
			if(isset($fieldInfo['unique'])) {
				$syntax .= "\n, UNIQUE KEY `" . $fieldKeyName . "` (`" . $fieldName . "`)";
				$usedKeyNames[] = $fieldKeyName;
			}
			if(isset($fieldInfo['index'])) {
				$syntax .= "\n, KEY `" . $fieldKeyName . "` (`" . $fieldName . "`)";
				$usedKeyNames[] = $fieldKeyName;
			}
		}

		// Extra
		$syntax .= "\n) ENGINE=" . $this->_engine . " DEFAULT CHARSET=" . $this->_charset . " COLLATE=" . $this->_collate . ";";

		return $syntax;
	}


	/**
	 * Syntax for each column in CREATE TABLE command
	 *
	 * @param string $fieldName Field name
	 * @param array $fieldInfo Array of field settings
	 * @return string SQL syntax
	 */
	public function migrateSyntaxFieldUpdate($fieldName, array $fieldInfo, $add = false)
	{
		return ( $add ? "ADD COLUMN " : "MODIFY " ) . $this->migrateSyntaxFieldCreate($fieldName, $fieldInfo);
	}


	/**
	 * Syntax for ALTER TABLE with given fields and column syntax
	 *
	 * @param string $table Table name
	 * @param array $formattedFields Array of fields with all settings
	 * @param array $columnsSyntax Array of SQL syntax of columns produced by 'migrateSyntaxFieldUpdate' function
	 * @return string SQL syntax
	 */
	public function migrateSyntaxTableUpdate($table, array $formattedFields, array $columnsSyntax)
	{
		/*
			ALTER TABLE `posts`
			CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
			CHANGE `status` `status` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'draft'
		*/
		$syntax = "ALTER TABLE `" . $table . "` \n";
		// Columns
		$syntax .= implode(",\n", $columnsSyntax);


		// Keys...
		$ki = 0;
		$usedKeyNames = array();
		foreach($formattedFields as $fieldName => $fieldInfo) {
			// Determine key field name (can't use same key name twice, so we  have to append a number)
			$fieldKeyName = $fieldName;
			while(in_array($fieldKeyName, $usedKeyNames)) {
				$fieldKeyName = $fieldName . '_' . $ki;
			}
			// Key type
			if($fieldInfo['primary']) {
				$syntax .= ",\n PRIMARY KEY(`" . $fieldName . "`)";
			}
			if($fieldInfo['unique']) {
				$syntax .= ",\n UNIQUE KEY `" . $fieldKeyName . "` (`" . $fieldName . "`)";
				$usedKeyNames[] = $fieldKeyName;
				 // Example: ALTER TABLE `posts` ADD UNIQUE (`url`)
			}
			if($fieldInfo['index']) {
				$syntax .= ",\n KEY `" . $fieldKeyName . "` (`" . $fieldName . "`)";
				$usedKeyNames[] = $fieldKeyName;
			}
		}

		// Extra
		$syntax .= ";";
		return $syntax;
	}
}

# vim:ts=4 sw=4 noet
