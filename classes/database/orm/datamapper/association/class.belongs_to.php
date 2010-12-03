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

namespace silk\database\orm\datamapper\association;

use \silk\core\Object;

class BelongsTo extends Object
{
	private $_obj = null;
	private $_parent_class = '';
	private $_foreign_key = '';
	private $_data = null;

	function __construct($obj, $field_definition)
	{
		parent::__construct();
		
		$this->_obj = $obj;
		$this->_parent_class = $field_definition['parent_object'];
		$this->_foreign_key = $field_definition['foreign_key'];
		
		$this->fill_data();
	}
	
	function get_data()
	{
		return $this->_data;
	}
	
	private function fill_data()
	{
		if ($this->_parent_class != '' && $this->_foreign_key != '')
		{
			$class = new $this->_parent_class;
			if ($this->_obj->{$this->_foreign_key} > -1)
			{
				//$belongs_to = call_user_func_array(array($class, 'find_by_id'), $obj->{$this->child_field});
				//$belongs_to = $class->find_by_id($obj->{$this->child_field});
				//$obj->set_association($this->association_name, $belongs_to);
				
				$conditions = array($this->_foreign_key => $this->_obj->{$this->_obj->get_id_field()});
				$this->_data = $class->first($conditions)->execute();
			}
		}
		
	}
}

# vim:ts=4 sw=4 noet
