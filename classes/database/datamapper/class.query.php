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

#########################################################
# Parts of this code are based/taken from phpDataMapper #
# Homepage: http://phpdatamapper.com/                   #
# Released under the MIT license                        #
#########################################################

namespace silk\database\datamapper;

use \silk\database\Database;

class Query extends \silk\database\Query
{
	protected $_obj = null;
	
	public function __construct(Database $datasource, DataMapper $obj)
	{
		$this->_datasource = $datasource;
		$this->_obj = $obj;
	}
	
	public function execute()
	{
		$ret = $this->_datasource->read($this);
		$classname = get_class($this->_obj);
		$result = array();
		
		if ($ret)
		{
			try
			{
				foreach ($ret as $one_row)
				{
					//Basically give before_load a chance to load that class type if necessary
					$newclassname = $classname;
					if ($this->_obj->get_type_field() != '' && isset($one_row[$this->_obj->get_type_field()]))
					{
						$newclassname = $one_row[$this->_obj->get_type_field()];
					}
			
					$this->_obj->before_load_caller($newclassname, $one_row);

					if (!($newclassname != $classname && class_exists($newclassname)))
					{
						$newclassname = $classname;
					}

					$oneobj = $this->_obj->instantiate_class($newclassname, $one_row);
					$oneobj = $this->_obj->fill_object($one_row, $oneobj);
					$result[] = $oneobj;
				}
			}
			catch (Exception $e)
			{
				//Nothing again
			}
		}
		
		return count($result) == 1 ? $result[0] : $result;
	}
}

# vim:ts=4 sw=4 noet
