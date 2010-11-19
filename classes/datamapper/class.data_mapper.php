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

namespace silk\datamapper;

use \silk\core\Object;
use \silk\database\Database;
use \silk\performance\Cache;
use \SilkDateTime;
use \silk\performance\Profiler;

abstract class DataMapper extends Object implements \ArrayAccess
{
	protected $_table_name = '';
	protected $_id_field = 'id';
	protected $_type_field = 'type';
	protected $_extra_params_field = 'extra_params';
	protected $_create_date_field = 'create_date';
	protected $_modified_date_field = 'modified_date';
	
	protected $_fields = array();
	protected $_acts_as = array();
	protected $_acts_as_obj = array();
	protected $_associations = null; //For caching
	protected $dirty = false;
	
	/**
	 * A flag that allows something outside of the object to set a validation
	 * error before save() (and eventually, validate()) are called.  After one
	 * call to validate, any errors are cleared out anyway.
	 */
	var $clear_errors = true;
	
	public $validation_errors = array();
	
	public $params = array();
	
	function __construct()
	{
		parent::__construct();

		//Run setup
		$this->setup(true);

		//Fill in and run the setup methods for any acts_as classes
		//attached
		if (is_array($this->_acts_as))
		{
			foreach ($this->_acts_as as $one_class)
			{
				if (!array_key_exists($one_class, $this->_acts_as_obj) && class_exists($one_class))
				{
					$the_class = new $one_class();
					$the_class->setup($this);
					$this->_acts_as_obj[$one_class] = $the_class;
				}
			}
		}
	}
	
	/**
	 * Getter overload method.  Called when an $obj->field and field
	 * does not exist in the object's variable list.
	 *
	 * @param string The field to look up
	 * @return mixed The value for that field, if it exists
	 **/
	function __get($n)
	{
		if (array_key_exists($n, $this->params))
		{
			if (method_exists($this, 'get_' . $n))
			{
				return call_user_func_array(array($this, 'get_'.$n), array());
			}
			else
			{
				return $this->params[$n];
			}
		}
		
		$assoc = $this->get_associations();
		
		if (array_key_exists($n, $assoc))
		{
			if ($assoc[$n] != null)
				return $assoc[$n]->get_data();
			else
				return $this->fill_association($n)->get_data();
		}
	}
	
	/**
	 * Setter overload method.  Called when an $obj->field = '' and field
	 * does not exist in the object's variable list.
	 *
	 * @param string The field to set
	 * @param mixed The value to set for said field
	 * @return void
	 **/
	function __set($n, $val)
	{
		//if (array_key_exists($n, $this->field_maps)) $n = $this->field_maps[$n];
		if (method_exists($this, 'set_' . $n))
			call_user_func_array(array($this, 'set_'.$n), array($val));
		else
			$this->params[$n] = $val;
		$this->dirty = true;
	}
	
	/**
	 * Caller overload method.  Called when an $obj->method() is called and
	 * that method does not exist.
	 *
	 * @param string The name of the method called
	 * @param array The parameters sent along with that method call
	 * @return mixed The result of the method call
	 **/
	function __call($function, $arguments)
	{
		$function_converted = underscore($function);
		$drop_first = substr($function_converted, 4); //For the set_ check
		//if (array_key_exists($function, $this->field_maps)) $function_converted = $this->field_maps[$function];

		if (starts_with($function_converted, 'set_') && $this->has_parameter($drop_first))
		{
			#This handles the SetSomeParam() dynamic function calls
			return $this->__set($drop_first, $arguments[0]);
		}
		else
		{
			//It's possible an acts_as class has this method
			$acts_as_list = $this->_acts_as_obj;
			if (count($acts_as_list) > 0)
			{
				$arguments = array_merge(array(&$this), $arguments);
				foreach ($acts_as_list as $k => $one_acts_as)
				{
					if (method_exists($one_acts_as, $function))
					{
						return call_user_func_array(array($one_acts_as, $function), $arguments);
					}
				}
			}

			#This handles the SomeParam() dynamic function calls
			return $this->__get($function_converted);
		}
	}
	
	public function get_id_field()
	{
		return $this->_id_field;
	}
	
	public function get_type_field()
	{
		return $this->_type_field;
	}
	
	public function get_associations()
	{
		if ($this->_associations !== null)
			return $this->_associations;
		
		$this->_associations = array();
		foreach ($this->_fields as $k => $v)
		{
			if ($v['type'] == 'association' && isset($v['association']))
			{
				$this->_associations[$k] = null;
			}
		}
		
		return $this->_associations;
	}
	
	public function fill_association($name)
	{
		$assoc = $this->get_associations();
		
		if (array_key_exists($name, $assoc))
		{
			if ($assoc[$name] != null) //Already filled in
			{
				return $assoc[$name];
			}
			else
			{
				$class_name = '\\silk\\datamapper\\association\\' . camelize($this->_fields[$name]['association']);
				$this->_associations[$name] = new $class_name($this, $this->_fields[$name]);
				return $this->_associations[$name];
			}
		}
	}
	
	/**
	 * Method for setting up various pieces of the object.  This is mainly used to setup 
	 * associations on objects that will be used in sessions so that they get properly setup 
	 * again after the object comes out of serialization.
	 *
	 * @param bool Whether or not this is the first time this was called from the constructor
	 * @return void
	 **/
	protected function setup($first_time = false)
	{
	}
	
	/**
	 * Returns wether or not the object has a particular parameter.
	 *
	 * @param string Name of the parameter to check for
	 * @return bool If that parameter exists or not
	 */
	public function has_parameter($name)
	{
		return (array_key_exists($name, $this->_fields));
	}
	
	/**
	 * Callback that instantiates the class.  Allows an ORM'd object to 
	 * override the default logic that the "type" field gives.
	 *
	 * @param string $type The type (class) that is going to be created
	 * @param array $row The row from the database that will be filled
	 * @return mixed A new object of the desired subclass. Defaults to $type.
	 * @author Ted Kulp
	 */
	public function instantiate_class($type, $row)
	{
		return new $type;
	}
	
	/**
	 * Virtual function that is called before a save operation can be
	 * completed.  Allows the object to make sure that all the necessary
	 * fields are filled in, they're in the proper range, etc.
	 *
	 * @return void
	 */
	public function validate()
	{
	}
	
	public function migrate()
	{
		$pdo = Database::get_instance();
		$pdo->migrate($this->get_table('', false), $this->_fields);
	}
	
	/**
	 * Figures out the proper name of the table that's persisting this class.
	 *
	 * @param string Field to append to the returned string
	 * @return string Name of the table to use
	 */
	public function get_table($fieldname = '', $add_prefix = true)
	{
		$classname = underscore(get_class($this));
		if (starts_with($classname, 'cms_')) $classname = substr($classname, 4);
		$table = $this->table != '' ? $this->table : $classname;
		if ($add_prefix) $table = db_prefix() . $table;
		$table = $table . ($fieldname != '' ? '.' . $fieldname : '');
		return $table;
	}
	
	/**
	 * Takes the parameters passed (particularly the id -- works great from a form submit) and loads 
	 * the appropriate object from the database.  If the value is a single number, it will load the
	 * object with that id.  If a type is given and the id doesn't exist, then a new object of the
	 * given type will be returned.
	 *
	 * @param array Hash of parameters (make sure one is named id) or one id
	 * @param string If set to a class name, type of class to return if id isn't given.
	 * @return mixed The found object, or null if none are found
	 */
	public function load($hash_or_id, $type = null)
	{
		if (is_array($hash_or_id))
		{
			if (!isset($hash_or_id[$this->_id_field]))
			{
				if ($type != null)
				{
					return new $type;
				}
				else if (isset($hash_or_id['type']))
				{
					return new $hash_or_id['type'];
				}
				else
				{
					return $this;
				}
			}
		}
		else
		{
			//Just make a hash out of it for simplicity sake
			$tmp = $hash_or_id;
			$hash_or_id = array();
			$hash_or_id[$this->_id_field] = $tmp;
		}
		
		return $this->first(array($this->_id_field => $hash_or_id[$this->_id_field]), true);
	}
	
	public function all($conditions = array(), $execute = false)
	{
		$query = $this->select()->from($this->get_table());
		return $execute ? $query->execute() : $query;
	}
	
	public function first($conditions = array(), $execute = false)
	{
		$query = $this->all($conditions)->limit(1);
		return $execute ? $query->execute() : $query;
	}
	
	public function select($fields = "*")
	{
		$db = Database::get_instance();
		$query = new Query($db, $this); //Specific query object so execute does the right thing
		$query->select($fields);
		return $query;
	}
	
	/**
	 * Fills an object with the fields from the database.
	 *
	 * @param array Reference to the hash for this record that came from the database
	 * @param mixed Reference to the object we should fill
	 * @return The object we filled
	 */
	public function fill_object(&$resulthash, &$object)
	{
		$db = Database::get_instance();
		$fields = $this->_fields;

		foreach ($resulthash as $k=>$v)
		{
			$datetime = false;
			if (array_key_exists($k, $fields) && in_array(strtolower($fields[$k]['type']), array('datetime', 'create_date', 'modified_date')))
				$datetime = true;

			//if (array_key_exists($k, $this->field_maps)) $k = $this->field_maps[$k];

			if ($datetime)
			{
				$object->params[$k] = new SilkDateTime(date('c', strtotime($v)));
			}
			else if ($k === $this->_extra_params_field)
			{
				if ($v != null && $v != '')
				{
					$ary = unserialize($v);
					if (is_array($ary))
					{
						foreach ($ary as $k2=>$v2)
						{
							$object->params[$k2] = $v2;
						}
					}
				}
			}
			else
			{
				$object->params[$k] = $v;
			}
		}
		
		$object->dirty = false;
		
		$object->after_load_caller();

		return $object;
	}
	
	function save()
	{
		//CmsCache::get_instance('orm')->clean();
		//cms_db()->CacheFlush();
		
		$this->before_validation_caller();

		if ($this->check_not_valid())
		{
			return false;
		}

		$this->before_save_caller();

		$id_field = $this->_id_field;
		$id = $this->$id_field;
		
		
		//If we have an id, so an update.
		//If not, do an insert.
		if (isset($id) && $id > 0)
		{
			return $this->save_update();
		}
		else
		{
			return $this->save_new();
		}
	}
	
	protected function save_update()
	{
		$db = Database::get_instance();
		$table = $this->get_table();
		$time = $db->timestamp();
		
		$fields = $this->_fields;
		$id_field = $this->_id_field;
		$id = $this->$id_field;
		
		Profiler::get_instance()->mark('Before Update');
		if ($this->dirty)
		{
			Profiler::get_instance()->mark('Dirty Bit True');
			$query = "UPDATE {$table} SET ";
			$midpart = '';
			$queryparams = array();
			$unsetparams = array();
			$fieldnames = array();
			$has_extra_params = false;

			foreach($fields as $onefield=>$obj)
			{
				$fieldnames[] = $onefield;
				
				if ($onefield == 'extra_params')
				{
					$has_extra_params = true;
					continue;
				}
				
				$localname = $onefield;
				//if (array_key_exists($localname, $this->field_maps)) $localname = $this->field_maps[$localname];
				if ($onefield == $this->_modified_date_field)
				{
					$queryparams[] = $time;
					$midpart .= "{$table}.{$onefield} = ?, ";
					$this->$onefield = new SilkDateTime();
				}
				else if ($this->type_field != '' && $this->type_field == $onefield)
				{
					$this->$onefield = get_class($this);
					$queryparams[] = get_class($this);
					$midpart .= "{$table}.{$onefield} = ?, ";
				}
				else if (array_key_exists($localname, $this->params))
				{
					if ($this->params[$localname] instanceof SilkDateTime)
					{
						$queryparams[] = $this->params[$localname]->to_sql_string();
						$midpart .= "{$table}.{$onefield} = ?, ";
					}
					else
					{
						$queryparams[] = $this->params[$localname];
						$midpart .= "{$table}.{$onefield} = ?, ";
					}
				}
			}
			
			if ($has_extra_params)
			{
				foreach($this->params as $k=>$v)
				{
					$localname = $k;
					//if (array_key_exists($localname, $this->field_maps)) $localname = $this->field_maps[$localname];
					if (!in_array($k, $fieldnames) && !in_array($localname, $fieldnames))
					{
						$unsetparams[$k] = $v;
					}
				}
				
				if (!empty($unsetparams))
				{
					$queryparams[] = serialize($unsetparams);
					$midpart .= "{$table}.extra_params = ?, ";
				}
			}

			if ($midpart != '')
			{	
				$midpart = substr($midpart, 0, -2);
				$query .= $midpart . " WHERE {$table}.{$id_field} = ?";
				$queryparams[] = $id;
			}

			try
			{
				$result = $db->execute($query, $queryparams);
				$result = $result ? true : false;
			}
			catch (Exception $e)
			{
				$result = false;
			}

			if ($result)
			{
				$this->dirty = false;
				Profiler::get_instance()->mark('Dirty Bit Reset');
			}
			
			$this->after_save_caller($result);
			
			return $result;
		}
		
		return true;
	}
	
	protected function save_new()
	{
		$db = Database::get_instance();
		$table = $this->get_table();
		$time = $db->timestamp();
		
		$fields = $this->_fields;
		$id_field = $this->_id_field;
		
		$new_id = -1;
		
		Profiler::get_instance()->mark('Before Insert');

		/*
		if ($this->sequence != '')
		{
			$new_id = $db->GenID(db_prefix() . $this->sequence);
			$this->$id_field = $new_id;
		}
		*/

		$query = "INSERT INTO {$table} (";
		$midpart = '';
		$queryparams = array();
		$unsetparams = array();
		$fieldnames = array();
		$has_extra_params = false;
		
		foreach($fields as $onefield=>$obj)
		{
			$fieldnames[] = $onefield;
			
			if ($onefield == $this->_extra_params_field)
			{
				$has_extra_params = true;
				continue;
			}
			
			$localname = $onefield;
			//if (array_key_exists($localname, $this->field_maps)) $localname = $this->field_maps[$localname];
			
			if ($onefield == $this->_create_date_field || $onefield == $this->_modified_date_field)
			{
				$queryparams[] = trim($time, "'");
				$midpart .= $onefield . ', ';
				$this->$onefield = new SilkDateTime();
			}
			else if ($this->type_field != '' && $this->type_field == $onefield)
			{
				$queryparams[] = get_class($this);
				$midpart .= $onefield . ', ';
				$this->$onefield = get_class($this);
			}
			else if (array_key_exists($localname, $this->params))
			{
				if (!($new_id == -1 && $localname == $this->id_field))
				{
					if ($this->params[$localname] instanceof SilkDateTime)
						$queryparams[] = trim($this->params[$localname]->to_sql_string(), "'");
					else
						$queryparams[] = $this->params[$localname];
					$midpart .= $onefield . ', ';
				}
			}
		}
		
		if ($has_extra_params)
		{
			foreach($this->params as $k=>$v)
			{
				$localname = $k;
				//if (array_key_exists($localname, $this->field_maps)) $localname = $this->field_maps[$localname];
				if (!in_array($k, $fieldnames) && !in_array($localname, $fieldnames))
				{
					$unsetparams[$k] = $v;
				}
			}
			
			if (!empty($unsetparams))
			{
				$queryparams[] = serialize($unsetparams);
				$midpart .= $this->_extra_params_field . ", ";
			}
		}
		
		if ($midpart != '')
		{
			$midpart = substr($midpart, 0, -2);
			$query .= $midpart . ') VALUES (';
			$query .= implode(',', array_fill(0, count($queryparams), '?'));
			$query .= ')';
		}
		
		try
		{
			$result = $db->execute($query, $queryparams) ? true : false;
		}
		catch (Exception $e)
		{
			$result = false;
		}
		
		if ($result)
		{
			if ($new_id == -1)
			{
				$new_id = $db->last_insert_id();
				$this->$id_field = $new_id;
			}
	
			$this->dirty = false;
			$this->after_save_caller($result);
		}
		
		return $result;
	}
	
	/**
	 * Deletes a record from the table that persists this class.  If no id is given, then
	 * it deletes the object given.  If an id is given, then it deletes that one from the
	 * database directly.  Keep in mind that deleting a object from the database directly
	 * while having one in memory simultaniously could cause issues.
	 *
	 * @param integer The id to delete.  If none, then deletes the object called on.
	 *
	 * @return boolean Boolean based on whether or not the delete was successful.
	 */
	function delete($id = -1)
	{
		//CmsCache::get_instance('orm')->clean();
		//cms_db()->CacheFlush();
		
		if ($id > -1)
		{
			$obj = $this->load($id);
			if ($obj)
				return $obj->delete();
			return false;
		}
		else
		{
			$table = $this->get_table();
			$id_field = $this->_id_field;
			$id = $this->$id_field;
		
			$can_delete = $this->before_delete_caller();
			if ($can_delete === false) return false;

			$pdo = Database::get_instance();
			$result = $pdo->query("DELETE FROM {$table} WHERE ".$this->get_table($id_field)." = {$id}") ? true : false;
		
			if ($result)
				$this->after_delete_caller();
		
			return $result;
		}
	}
	
	/**
	 * Callback to call before the class is instantiated and the fields
	 * are set.  Keep in mind the scopes of before_load and after_load.
	 * before_load is called on the orm object, so keep it's implementation
	 * sort of "static" in nature.  after_load is called on the instantiated
	 * object.
	 *
	 * @param string Name of the class that we're going to instantiate
	 * @param array Hash of the fields that will be inserted into the object
	 * @return void
	 * @author Ted Kulp
	 */
	protected function before_load($type, $fields)
	{
	}
	
	/**
	 * Wrapper function for before_load.  Only should be 
	 * called by classes that entend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 * @author Ted Kulp
	 */
	public function before_load_caller($type, $fields)
	{
		foreach ($this->_acts_as_obj as $one_acts_as)
		{
			$one_acts_as->before_load($type, $fields);
		}
		$this->before_load($type, $fields);
	}
	
	/**
	 * Callback after object is loaded.  This allows the object to do any
	 * housekeeping, setting up other fields, etc before it's returned.
	 *
	 * @return void
	 * @author Ted Kulp
	 */
	protected function after_load()
	{
	}
	
	/**
	 * Wrapper function for after_load.  Only should be 
	 * called by classes that entend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 * @author Ted Kulp
	 */
	public function after_load_caller()
	{
		foreach ($this->_acts_as_obj as $one_acts_as)
		{
			$one_acts_as->after_load($this);
		}
		$this->after_load();
	}
	
	/**
	 * Callback sent before the object is validated.  This allows the object to
	 * do any initial cleanup so that validation may pass properly.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	protected function before_validation()
	{
	}
	
	/**
	 * Wrapper function for before_validation.  Only should be 
	 * called by classes that extend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 * @author Ted Kulp
	 */
	protected function before_validation_caller()
	{
		foreach ($this->_acts_as_obj as $one_acts_as)
		{
			$one_acts_as->before_validation($this);
		}
		$this->before_validation();
	}
	
	/**
	 * Callback sent before the object is saved.  This allows the object to
	 * send any events, manipulate any values, etc before the objects is
	 * persisted.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	protected function before_save()
	{
	}
	
	/**
	 * Wrapper function for before_save.  Only should be 
	 * called by classes that extend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 * @author Ted Kulp
	 */
	protected function before_save_caller()
	{
		foreach ($this->_acts_as_obj as $one_acts_as)
		{
			$one_acts_as->before_save($this);
		}
		$this->before_save();
	}
	
	/**
	 * Callback sent after the object is saved.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	protected function after_save(&$result)
	{
	}
	
	/**
	 * Wrapper function for after_save.  Only should be 
	 * called by classes that entend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 * @author Ted Kulp
	 */
	protected function after_save_caller(&$result)
	{
		foreach ($this->_acts_as_obj as $one_acts_as)
		{
			$one_acts_as->after_save($this, $result);
		}
		$this->after_save($result);
	}
	
	/**
	 * Callback sent before the object is deleted from
	 * the database.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	protected function before_delete()
	{
	}
	
	/**
	 * Wrapper function for before_delete.  Only should be 
	 * called by classes that entend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 * @author Ted Kulp
	 */
	protected function before_delete_caller()
	{
		foreach ($this->_acts_as_obj as $one_acts_as)
		{
			$res = $one_acts_as->before_delete($this);
			if( !$res ) return false;
		}
		return $this->before_delete();
	}
	
	/**
	 * Callback sent after the object is deleted from
	 * the database.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	protected function after_delete()
	{
	}
	
	/**
	 * Wrapper function for after_delete.  Only should be 
	 * called by classes that entend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 * @author Ted Kulp
	 */
	protected function after_delete_caller()
	{
		foreach ($this->_acts_as_obj as $one_acts_as)
		{
			$one_acts_as->after_delete($this);
		}
		$this->after_delete();
	}
	
	/**
	 * Method to call the validation methods properly.
	 *
	 * @return int The number of validation errors
	 * @author Ted Kulp
	 **/
	public function check_not_valid()
	{	
		//Clear them out first
		if ($this->clear_errors)
			$this->validation_errors = array();
			
		$this->clear_errors = true;
		
		//Call the validate method
		$this->validate();
		
		return (count($this->validation_errors) > 0);
	}
	
	/**
	 * Method for quickly adding a new validation error to the object.  If this is
	 * called, then it's a safe bet that save() will fail.  This should only be
	 * used for setting validation errors from external sources.
	 *
	 * @param string Message to add to the validation error stack
	 * @return void
	 * @author Ted Kulp
	 */
	public function add_error($message)
	{
		$this->add_validation_error($message);
		$this->clear_errors = false;
	}
	
	/**
	 * Method for quickly adding a new validation error to the object.  If this is
	 * called, then it's a safe bet that save() will fail.  This should only be
	 * used for setting validation errors from the object itself, as it doesn't
	 * set the clear_errors flag.
	 *
	 * @param string Message to add to the validation error stack
	 * @return void
	 * @author Ted Kulp
	 */
	protected function add_validation_error($message)
	{
		$this->validation_errors[] = $message;
	}
	
	/**
	 * Validation method to see if a parameter has been filled in.  This should
	 * be called from an object's validate() method on each field that needs to be
	 * filled in before it can be saved.
	 *
	 * @param string Name of the field to check
	 * @param string If given, this is the message that will be set in the object if the method didn't succed.
	 * @return void
	 * @author Ted Kulp
	 */
	function validate_not_blank($field, $message = '')
	{
		if ($this->$field == null || $this->$field == '')
		{
			if ($message == '')
				$message = $field . ' must not be blank';

			$this->add_validation_error($message);
		}
	}
	
	/**
	 * Validation method to see if a parameter has been filled in.  This should
	 * be called from an object's validate() method on each field that needs to be
	 * filled in before it can be saved.
	 *
	 * @param string Name of the field to check
	 * @param string If given, this is the message that will be set in the object if the method didn't succed.
	 * @return void
	 * @author Ted Kulp
	 */
	function validate_numericality_of($field, $message = '')
	{
		if (!($this->$field == null || $this->field != ''))
		{
			if ((string)$this->$field != (string)intval($this->$field) && (string)$this->$field != (string)floatval($this->$field))
			{
				if ($message == '')
					$message = $field . ' must be a number';

				$this->add_validation_error($message);
			}
		}
	}
	
	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to set with the given value
	 * @param mixed The value to set for the given key
	 * @return void
	 **/
	function offsetSet($key, $value)
	{
		//if (array_key_exists($key, $this->field_maps)) $key = $this->field_maps[$key];
		$this->params[$key] = $value;
		$this->dirty = true;
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to look up
	 * @return mixed The value of the $obj['field']
	 **/
	function offsetGet($key)
	{
		if (array_key_exists($key, $this->params))
		{
			return $this->params[$key];
		}
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to unset
	 * @return bool Whether or not it does exist
	 **/
	function offsetUnset($key)
	{
		if (array_key_exists($key, $this->params))
		{
			unset($this->params[$key]);
		}
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to lookup to see if it exists
	 * @return bool Whether or not it does exist
	 **/
	function offsetExists($offset)
	{
		return array_key_exists($offset, $this->params);
	}
	
	public function __toString()
	{
		if (isset($this->_id_field))
			return get_class($this) . '- id:' . $this->_id_field;
		else
			return parent::__toString();
	}
}

# vim:ts=4 sw=4 noet
