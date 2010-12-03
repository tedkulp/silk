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

namespace silk\database\datamapper\association;

use \silk\core\Object;

class HasMany extends Object implements \Countable, \IteratorAggregate, \ArrayAccess
{
	private $_obj = null;
	private $_child_class = '';
	private $_foreign_key = '';
	private $_data = array();

	function __construct($obj, $field_definition)
	{
		parent::__construct();
		
		$this->_obj = $obj;
		$this->_child_class = $field_definition['child_object'];
		$this->_foreign_key = $field_definition['foreign_key'];
		
		$this->fill_data();
	}
	
	function get_data()
	{
		return $this;
	}
	
	private function fill_data()
	{
		if ($this->_child_class != '' && $this->_foreign_key != '')
		{
			$class = new $this->_child_class;
			if ($this->_obj->{$this->_obj->get_id_field()} > -1)
			{
				/*
				$queryattrs = $this->extra_params;
				$conditions = "{$this->_foreign_key} = ?";
				$params = array($obj->{$obj->get_id_field()});
			
				if (array_key_exists('conditions', $this->extra_params))
				{
					$conditions = "({$conditions}) AND ({$this->extra_params['conditions'][0]})";
					if (count($this->extra_params['conditions']) > 1)
					{
						$params = array_merge($params, array_slice($this->extra_params['conditions'], 1));
					}
				}
				$queryattrs['conditions'] = array_merge(array($conditions), $params);
				*/
				
				$conditions = array($this->_foreign_key => $this->_obj->{$this->_obj->get_id_field()});
				$this->_data = $class->all($conditions)->execute();
				
				//If we got a single, we still need an array for in here
				if (!is_array($this->_data))
					$this->_data = array($this->_data);
			}
		}
	}
	
	public function count()
	{
		// Load related records for current row
		return count($this->_data);
	}

	public function getIterator()
	{
		// Load related records for current row
		return $this->_data ? $this->_data : array();
	}
	
	public function offsetExists($key)
	{
		return isset($this->_data[$key]);
	}

	public function offsetGet($key)
	{
		return $this->_data[$key];
	}

	public function offsetSet($key, $value)
	{
		if ($key === null)
		{
			return $this->_data[] = $value;
		}
		else
		{
			return $this->_data[$key] = $value;
		}
	}

	public function offsetUnset($key)
	{
		unset($this->_data[$key]);
	}
}

# vim:ts=4 sw=4 noet
