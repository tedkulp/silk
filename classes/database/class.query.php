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

use \silk\database\Database;
use \silk\core\Object;

class Query extends Object implements \Countable, \IteratorAggregate
{
	protected $_datasource = null;
	
	public $fields = array();
	public $table;
	public $conditions = array();
	public $joins = array();
	public $order = array();
	public $group = array();
	public $limit;
	public $limit_offset;
	
	public function __construct(Database $datasource)
	{
		$this->_datasource = $datasource;
	}

	public function select($fields = '*')
	{
		$this->fields = (is_string($fields) ? explode(',', $fields) : $fields);
		return $this;
	}

	public function from($table)
	{
		$this->table = $table;
		return $this;
	}
	
	public function where(array $conditions = array(), $type = "AND", $set_type = "AND")
	{
		$where = array();
		$where['conditions'] = $conditions;
		$where['type'] = $type;
		$where['setType'] = $set_type;

		$this->conditions[] = $where;
		return $this;
	}
	public function or_where(array $conditions = array(), $type = "AND")
	{
		return $this->where($conditions, $type, "OR");
	}
	
	public function and_where(array $conditions = array(), $type = "AND")
	{
		return $this->where($conditions, $type, "AND");
	}

	public function join($fields = array())
	{
		if (is_array($fields))
		{
			foreach ($fields as $field => $on)
			{
				// Ignore it if it's not a key/value pair
				if (!is_numeric($field))
				{
					$this->joins[$field] = $on;
				}
			}
		}

		return $this;
	}
	
	public function order($fields = array())
	{
		$default_sort = "ASC";
		
		if (is_array($fields))
		{
			foreach ($fields as $field=>$sort)
			{
				// Numeric index - field as array entry, not key/value pair
				if (is_numeric($field))
				{
					$field = $sort;
					$sort = $default_sort;
				}

				$this->order[$field] = strtoupper($sort);
			}
		}
		else
		{
			$this->order[$fields] = $default_sort;
		}
		return $this;
	}
	
	public function group(array $fields = array())
	{
		foreach($fields as $field)
		{
			$this->group[] = $field;
		}
		return $this;
	}
	
	public function limit($limit = 20, $offset = null)
	{
		$this->limit = $limit;
		$this->limit_offset = $offset;
		return $this;
	}

	public function execute()
	{
		return $this->_datasource->read($this);
	}
	
	public function first()
	{
		$result = $this->limit(1)->execute();
		return ($result !== false && !empty($result)) ? $result[0] : false;
	}
	
	public function params()
	{
		$params = array();
		foreach($this->conditions as $i=>$data)
		{
			if(isset($data['conditions']) && is_array($data['conditions']))
			{
				foreach($data['conditions'] as $field => $value)
				{
					$params[$field] = $value;
				}
			}
		}
		return $params;
	}
	
	public function count()
	{
		// Execute query and return count
		$result = $this->execute();
		return ($result !== false) ? count($result) : 0;
	}
	
	public function getIterator()
	{
		// Execute query and return result set for iteration
		$result = $this->execute();
		return ($result !== false) ? new \ArrayIterator($result) : array();
	}

}

# vim:ts=4 sw=4 noet
