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

namespace silk\orm;

use \silk\core\Object;
use \silk\performance\Cache;
use \silk\performance\Profiler;

/**
 * Base class for all things ORM.  All classes that want to be part of
 * the ORM system need to extend this class.  They also need to call the
 * static register_orm_class() method after the class is defined in order
 * to be reigstered for the system (and allow things like find_by_* to 
 * work).
 *
 * @author Ted Kulp
 * @since 1.0
 **/
abstract class ActiveRecord extends ObjectRelationalMapping
{
	function __construct()
	{
		parent::__construct();

		//Setup the predefined fields in the $params array.  Relax: The definitions are cached.
		$fields = $this->get_columns_in_table();
		if (count($fields) > 0) {
			foreach ($fields as $field=>$data) {
				if (array_key_exists($field, $this->field_maps)) $field = $this->field_maps[$field];
				if (!array_key_exists($field, $this->params)) {
					$this->params[$field] = '';
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
	 * @author Ted Kulp
	 **/
	function __get($n)
	{
		if (array_key_exists($n, $this->params))
		{
			if (method_exists($this, 'get_' . $n))
				return call_user_func_array(array($this, 'get_'.$n), array($val));
			else
				return $this->params[$n];
		}
		
		if (orm()->has_association($this, $n))
		{
			return orm()->process_association($this, $n);
		}
	}

	/**
	 * Setter overload method.  Called when an $obj->field = '' and field
	 * does not exist in the object's variable list.
	 *
	 * @param string The field to set
	 * @param mixed The value to set for said field
	 * @return void
	 * @author Ted Kulp
	 **/
	function __set($n, $val)
	{
		if (array_key_exists($n, $this->field_maps)) $n = $this->field_maps[$n];
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
	 * @author Ted Kulp
	 **/
	function __call($function, $arguments)
	{
		$function_converted = underscore($function);
		$drop_first = substr($function_converted, 4); //For the set_ check
		if (array_key_exists($function, $this->field_maps)) $function_converted = $this->field_maps[$function];

		if (starts_with($function, 'find_by_'))
		{
			return $this->find_by_($function, $arguments);
		}
		else if (starts_with($function, 'find_all_by_'))
		{
			return $this->find_all_by_($function, $arguments);
		}
		else if (starts_with($function, 'find_count_by_'))
		{
			return $this->find_count_by_($function, $arguments);
		}
		else if (starts_with($function_converted, 'set_') && $this->has_parameter($drop_first))
		{
			#This handles the SetSomeParam() dynamic function calls
			return $this->__set($drop_first, $arguments[0]);
		}
		else
		{
			//It's possible an acts_as class has this method
			//TODO: Swap this to use base mixins
			$acts_as_list = orm()->get_acts_as($this);
			if (count($acts_as_list) > 0)
			{
				$arguments = array_merge(array(&$this), $arguments);
				foreach ($acts_as_list as $one_acts_as)
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
	
	public static function __callStatic($function, $arguments)
	{
		$manager = ObjectRelationalManager::get_instance();
		$class = $manager(get_called_class());
		if (starts_with($function, 'find_by_'))
		{
			return $class->find_by_($function, $arguments);
		}
		else if (starts_with($function, 'find_all_by_'))
		{
			return $manager(get_class())->find_all_by_($function, $arguments);
		}
		else if (starts_with($function, 'find_count_by_'))
		{
			return $manager(get_class())->find_count_by_($function, $arguments);
		}
	}
	
	/**
	 * Private helper function for processing dynaimc find_by methods.  It essentially does several things...
	 * 1. Split out any "and" or "or" clauses in a dynamic find method
	 * 2. Pops the corresponding arguments off of the array so they don't get processed further
	 * 3. Creates the conditions clause and returns it
	 *
	 * @param string The field (or fields in the case of an "and" or "or" lookup)
	 * @param array Reference to the arguments passed to the method.  The array is modified as necessary.
	 * @return array Conditions clause after processing
	 * @author Ted Kulp
	 **/
	private function split_conditions($field, &$arguments)
	{
		//Figure out if we need to replace the field from the field mappings
		$new_map = array_flip($this->field_maps); //Flip the keys, since this is the reverse operation
		
		$numparams = 1;
		$params = array();
		$fields = preg_split('/(_and_|_or_)/', $field, -1, PREG_SPLIT_DELIM_CAPTURE);
		$conditions = '';
		
		for ($i = 0; $i < count($fields); $i=$i+2)
		{
			$params[] = array_shift($arguments);
			if ($i > 0 && $fields[$i-1] == '_and_')
				$conditions .= ' AND ';
			else if ($i > 0 && $fields[$i-1] == '_or_')
				$conditions .= ' OR ';
			
			//Make sure we're looking it up by what the class thinks the parameter is called,
			//not the database.
			if (array_key_exists($fields[$i], $new_map)) $fields[$i] = $new_map[$fields[$i]];

			$conditions .= $this->get_table($fields[$i]) . ' = ?';
		}
		
		return array('conditions' => array($conditions, $params));
	}
	
	/**
	 * Method for handling the dynamic find_by_* functionality.  It basically figures out
	 * what field is being searched for and creates a query based on that field.
	 *
	 * @param string The name of the function that came into the __call method
	 * @param array The arguments that came into the __call method
	 * @return The results of the find
	 * @author Ted Kulp
	 */
	function find_by_($function, $arguments)
	{
		$field = str_replace('find_by_', '', $function);
		
		$parameters = $this->split_conditions($field, $arguments);
		if (count($arguments) > 0)
		{
			$parameters = array_merge($parameters, $arguments[0]);
		}
		
		return $this->find($parameters);
	}
	
	/**
	 * Method for handling the dynamic find_all_by_* functionality.  It basically figures out
	 * what field is being searched for and creates a query based on that field.
	 *
	 * @param string The name of the function that came into the __call method
	 * @param array The arguments that came into the __call method
	 * @return The results of the find_all
	 * @author Ted Kulp
	 */
	function find_all_by_($function, $arguments)
	{
		$field = str_replace('find_all_by_', '', $function);
		
		$parameters = $this->split_conditions($field, $arguments);
		if (count($arguments) > 0)
		{
			$parameters = array_merge($parameters, $arguments[0]);
		}
		
		return $this->find_all($parameters);
	}
	
	/**
	 * Method for handling the dynamic find_count_by_* functionality.  It basically figures out
	 * what field is being searched for and creates a query based on that field.
	 *
	 * @param string The name of the function that came into the __call method
	 * @param array The arguments that came into the __call method
	 * @return integer The result of the find_count
	 * @author Ted Kulp
	 */
	function find_count_by_($function, $arguments)
	{
		$field = str_replace('find_count_by_', '', $function);
		
		$parameters = $this->split_conditions($field, $arguments);
		if (count($arguments) > 0)
		{
			$parameters = array_merge($parameters, $arguments[0]);
		}
		
		return $this->find_count($parameters);
	}
	
	/**
	 * Figures out the proper name of the table that's persisting this class.
	 *
	 * @param string Field to append to the returned string
	 * @return string Name of the table to use
	 * @author Ted Kulp
	 */
	function get_table($fieldname = '')
	{
		$classname = underscore(get_class($this));
		if (starts_with($classname, 'silk_')) $classname = substr($classname, 4);
		$table = $this->table != '' ? db_prefix() . $this->table : db_prefix() . $classname;
		$table = $table . ($fieldname != '' ? '.' . $fieldname : '');
		return $table;
	}
	
	/**
	 * The generic catch-all find method.  Takes all the given parameters, constructs a query, and calls find_by_query
	 * on it.  It returns the results of find_by_query.
	 *
	 * @param array The list of parameters that we should calculate to constuct the select query
	 * @return mixed The object that is found, or null if none is found in the database.
	 * @author Ted Kulp
	 **/
	function find($arguments = array())
	{
		$obj = ObjectRelationalManager::get_instance()->get_orm_class(get_called_class());
		
		$table = $obj->get_table();
		
		$query = '';
		$queryparams = array();
		
		list($query, $queryparams, $numrows, $offset) = $obj->generate_select_query_and_parameters($table, $arguments, $query, $queryparams);
		
		return $obj->find_by_query($query, $queryparams, $numrows, $offset);
	}
	
	/**
	 * Takes a SQL query related to the class, executes it, and loads the object, if found.
	 * If it's not found, we return null.
	 *
	 * @param string The SELECT query to run
	 * @param array An array of query parameters to replace the ? in the query with
	 * @return mixed The found object, or null if none are found
	 * @author Ted Kulp
	 **/
	public static function find_by_query($query, $queryparams = array())
	{
		$db = db();
		
		$classname = get_called_class();
		$obj = ObjectRelationalManager::get_instance()->get_orm_class($classname);

		try
		{
			$row = $db->GetRow($query, $queryparams);
			
			if($row)
			{
				//Basically give before_load a chance to load that class type if necessary
				$newclassname = $classname;
				if ($obj->type_field != '' && isset($row[$obj->type_field]))
				{
					$newclassname = $row[$obj->type_field];
				}
			
				$obj->before_load_caller($newclassname, $row);
			
				if (!($newclassname != $classname && class_exists($newclassname)))
				{
					$newclassname = $classname;
				}

				$oneobj = new $newclassname;
				$oneobj = $obj->fill_object($row, $oneobj);
				return $oneobj;
			}
		}
		catch (Exception $e)
		{
			//Don't do anything
		}

		return null;
	}
	
	/**
	 * The generic catch-all find_all method.  Takes all the given parameters, constructs a query, and calls find_all_by_query
	 * on it.  It returns the results of find_all_by_query.
	 *
	 * @param array The list of parameters that we should calculate to constuct the select query
	 * @return array An array of objects if found.  If none are found, it will be an empty array.
	 * @author Ted Kulp
	 **/
	public static function find_all($arguments = array())
	{
		$obj = ObjectRelationalManager::get_instance()->get_orm_class(get_called_class());
		
		$table = $obj->get_table();
		
		$query = '';
		$queryparams = array();
		
		list($query, $queryparams, $numrows, $offset) = $obj->generate_select_query_and_parameters($table, $arguments, $query, $queryparams);
		return $obj->find_all_by_query($query, $queryparams, $numrows, $offset);
	}
	
	/**
	 * Takes a SQL query related to the class, executes it, and loads the object(s), if found.
	 * If it's not found, we return an empty array.
	 *
	 * @param string The SELECT query to run
	 * @param array An array of query parameters to replace the ? in the query with
	 * @return mixed The found object(s), or empty array if none are found
	 * @author Ted Kulp
	 **/
	public static function find_all_by_query($query, $queryparams = array(), $numrows = -1, $offset = -1)
	{
		$db = db();
		
		$classname = get_called_class();
		$obj = ObjectRelationalManager::get_instance()->get_orm_class($classname);

		$result = array();
		
		try
		{
			$dbresult = $db->SelectLimit($query, $numrows, $offset, $queryparams);
			
			while ($dbresult && !$dbresult->EOF)
			{
				//Basically give before_load a chance to load that class type if necessary
				$newclassname = $classname;
				if ($obj->type_field != '' && isset($dbresult->fields[$obj->type_field]))
				{
					$newclassname = $dbresult->fields[$obj->type_field];
				}
			
				$obj->before_load_caller($newclassname, $dbresult->fields);
				
				if (!($newclassname != $classname && class_exists($newclassname)))
				{
					$newclassname = $classname;
				}

				$oneobj = new $newclassname;
				$oneobj = $obj->fill_object($dbresult->fields, $oneobj);
				$result[] = $oneobj;
				$dbresult->MoveNext();
			}
		
			if ($dbresult) $dbresult->Close();
		}
		catch (Exception $e)
		{
			//Nothing again
		}
		
		return $result;
	}
	
	/**
	 * Used exactly like find_all, but returns a count instead of the actual objects.
	 *
	 * @param array The parameters used to the construct the SQL query
	 * @return integer The resulting count
	 * @author Ted Kulp
	 **/
	public static function find_count($arguments = array())
	{
		$db = db();
		
		$obj = ObjectRelationalManager::get_instance()->get_orm_class(get_called_class());

		$table = $obj->get_table();
		
		$query = '';
		$queryparams = array();
		
		list($query, $queryparams, $numrows, $offset) = $obj->generate_select_query_and_parameters($table, $arguments, $query, $queryparams, true);
		
		return $db->GetOne($query, $queryparams);
	}
	
	/**
	 * Takes the parameters passed (particularly the id -- works great from a form submit) and loads 
	 * the appropriate object from the database.
	 *
	 * @param array Hash of parameters (make sure one is named id)
	 * @return mixed The found object, or null if none are found
	 * @author Ted Kulp
	 */
	public static function load($hash)
	{
		if (!isset($hash['id']))
			return null;

		return self::find(array('conditions' => array('id = ?', $hash['id'])));
	}

	/**
	 * Saves the ORM'd object back to the database.  First it calls the validation method to make
	 * sure that all validation passes.  If successful, it then determines if this is a new record
	 * or updated record and INSERTs or UPDATEs accordingly.
	 *
	 * Updated records are only saved if they have been changed (dirty flag is set).  If you're doing
	 * any low level changes to the $params array directly, you should set the dirty flag to true
	 * to make sure any changes are saved.
	 *
	 * @return boolean Whether or not the save was successful or not.  If it wasn't, check the validation stack for errors.
	 */
	function save()
	{
		$this->before_validation_caller();

		if ($this->_call_validation())
			return false;

		$this->before_save_caller();

		$db = db();

		$table = $this->get_table();

		$id_field = $this->id_field;
		$id = $this->$id_field;
		
		//Figure out if we need to replace the field from the field mappings
		$new_map = array_flip($this->field_maps); //Flip the keys, since this is the reverse operation
		if (array_key_exists($id_field, $new_map)) $id_field = $new_map[$id_field];
		
		$fields = $this->get_columns_in_table();
		
		$time = $db->DBTimeStamp(time());
		
		//If we have an id, so an update.
		//If not, do an insert.
		if (isset($id) && $id > 0)
		{
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
					if (array_key_exists($localname, $this->field_maps)) $localname = $this->field_maps[$localname];
					if ($onefield == $this->modified_date_field)
					{
						#$queryparams[] = $time;
						$midpart .= "{$table}.{$onefield} = {$time}, ";
						$this->$onefield = time();
					}
					else if ($this->type_field != '' && $this->type_field == $onefield)
					{
						$this->$onefield = get_class($this);
						$queryparams[] = get_class($this);
						$midpart .= "{$table}.{$onefield} = ?, ";
					}
					else if (array_key_exists($localname, $this->params))
					{
						if ($this->params[$localname] instanceof \SilkDateTime)
						{
							$midpart .= "{$table}.{$onefield} = " . $this->params[$localname]->to_sql_string() . ", ";
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
						if (array_key_exists($localname, $this->field_maps)) $localname = $this->field_maps[$localname];
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
					$result = $db->Execute($query, $queryparams) ? true : false;
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
		else
		{
			$new_id = -1;
			
			Profiler::get_instance()->mark('Before Insert');

			if ($this->sequence != '')
			{
				$new_id = $db->GenID(db_prefix() . $this->sequence);
				$this->$id_field = $new_id;
			}

			$query = "INSERT INTO {$table} (";
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
				if (array_key_exists($localname, $this->field_maps)) $localname = $this->field_maps[$localname];
				
				if ($onefield == $this->create_date_field || $onefield == $this->modified_date_field)
				{
					$queryparams[] = trim($time, "'");
					$midpart .= $onefield . ', ';
					$this->$onefield = time();
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
						if ($this->params[$localname] instanceof \SilkDateTime)
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
					if (array_key_exists($localname, $this->field_maps)) $localname = $this->field_maps[$localname];
					if (!in_array($k, $fieldnames) && !in_array($localname, $fieldnames))
					{
						$unsetparams[$k] = $v;
					}
				}
				
				if (!empty($unsetparams))
				{
					$queryparams[] = serialize($unsetparams);
					$midpart .= "extra_params, ";
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
				$result = $db->Execute($query, $queryparams) ? true : false;
			}
			catch (Exception $e)
			{
				$result = false;
			}
			
			if ($result)
			{
				if ($new_id == -1)
				{
					$new_id = $db->Insert_ID();
					$this->$id_field = $new_id;
				}
		
				$this->dirty = false;
				$this->after_save_caller($result);
			}
			
			return $result;
		}
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
		if ($id > -1)
		{
			$method = 'find_by_' . $this->id_field;
			$obj = $this->$method($id);
			if ($obj)
				return $obj->delete();
			return false;
		}
		else
		{
			$table = $this->get_table();
			$id_field = $this->id_field;
		
			$id = $this->$id_field;
		
			$this->before_delete_caller();
		
			//Figure out if we need to replace the field from the field mappings
			$new_map = array_flip($this->field_maps); //Flip the keys, since this is the reverse operation
			if (array_key_exists($id_field, $new_map)) $id_field = $new_map[$id_field];

			$result = db()->Execute("DELETE FROM {$table} WHERE ".$this->get_table($id_field)." = {$id}") ? true : false;
		
			if ($result)
				$this->after_delete_caller();
		
			return $result;
		}
	}
	
	/**
	 * Used to push a hash of keys and values to the object.  This is very helpful
	 * for updating an object based on the fields in a form.
	 *
	 * @param array The hash of keys and values to set in the object
	 */
	function update_parameters($params, $strip_slashes = false)
	{
		//Because a set_ method might rely on other values already being set,
		//we do those last
		$do_sets_last = array();
		
		foreach ($params as $k=>$v)
		{
			//if (array_key_exists($k, $this->params))
			//{
				if ($strip_slashes && is_string($v)) $v = stripslashes($v);

				if (method_exists($this, 'set_' . $k))
				{
					//call_user_func_array(array($this, 'set_'.$k), array($v));
					$do_sets_last[$k] = $v;
				}
				else
				{
					//Just in case there is an override
					$this->params[$k] = $v;
				}
				$this->dirty = true;
			//}
		}
		
		foreach ($do_sets_last as $k=>$v)
		{
			if (method_exists($this, 'set_' . $k))
			{
				call_user_func_array(array($this, 'set_'.$k), array($v));
			}
		}
	}
	
	/**
	 * Returns wether or not the object has a particular parameter.
	 *
	 * @param string Name of the parameter to check for
	 * @return bool If that parameter exists or not
	 */
	public function has_parameter($name)
	{
		return (array_key_exists($name, $this->params));
	}
	
	/**
	 * Fills an object with the fields from the database.
	 *
	 * @param array Reference to the hash for this record that came from the database
	 * @param mixed Reference to the object we should fill
	 * @return The object we filled
	 */
	function fill_object(&$resulthash, &$object)
	{
		$db = db();
		$fields = $this->get_columns_in_table(); //Relax, it's cached
		
		foreach ($resulthash as $k=>$v)
		{
			$datetime = false;
			if (array_key_exists($k, $fields) && strtolower($fields[$k]->type) == 'datetime')
				$datetime = true;

			if (array_key_exists($k, $this->field_maps)) $k = $this->field_maps[$k];

			if ($datetime)
			{
				$object->params[$k] = new \SilkDateTime(db()->UnixTimeStamp($v));
			}
			else if ($k == 'extra_params')
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
			else
			{
				$object->params[$k] = $v;
			}
		}
		
		$object->dirty = false;
		
		$object->after_load_caller();

		return $object;
	}
	
	/**
	 * Generates a select query based on the arguments sent to the find and find_by
	 * methods.
	 * 
	 * @param string Name of the table that should be SELECT'd from
	 * @param array Arguments passed to the find and find_by methods
	 * @param string Reference to the query string that will be modified by this method
	 * @param array Reference to the array of query params passed on to adodb
	 *
	 * @return array An array of $query and $queryparams
	 */
	function generate_select_query_and_parameters($table, $arguments, $query, $queryparams, $count = false)
	{
		$numrows = -1;
		$offset = -1;

		$query = "SELECT {$table}.* FROM {$table}";
		if ($count) $query = "SELECT count(*) as the_count FROM {$table}";
		
		if (array_key_exists('joins', $arguments))
		{
			$query .= " {$arguments['joins']}";
		}

		if (array_key_exists('conditions', $arguments))
		{
			$query .= " WHERE {$arguments['conditions'][0]}";

			//Handle 'conditions' => array('blah = ?', array($value))
			if (isset($arguments['conditions'][1]) && is_array($arguments['conditions'][1]))
			{
				$queryparams = array_merge($queryparams, $arguments['conditions'][1]);
			}

			//Handle 'conditions' => array('blah = ?', $value)
			else if (count($arguments['conditions']) > 1)
			{
				$queryparams = array_merge($queryparams, array_slice($arguments['conditions'], 1));
			}
		}

		if (array_key_exists('order', $arguments))
		{
			$args = $arguments['order'];
			foreach ($this->field_maps as $db=>$obj)
			{
				$args = preg_replace("/(^|[^_0-9A-Za-z\-])".$obj."/i", '$1'.$db, $args);
			}
			$query .= ' ORDER BY ' . $args;
		}
		
		if (array_key_exists('limit', $arguments))
		{
			$offset = $arguments['limit'][0];
			$numrows = $arguments['limit'][1];
		}
		
		return array($query, $queryparams, $numrows, $offset);
	}
	
	/**
	 * Generates an array of column names in the table that perists this class.  This
	 * list is then cached during the life of the request.
	 *
	 * @return array An array of column names
	 */
	function get_columns_in_table()
	{
		return Cache::get_instance()->call(array(&$this, '_get_columns_in_table'), $this->get_table());
	}
	
	function _get_columns_in_table($table)
	{
		$fields = array();
		
		try
		{
			$cols = db()->MetaColumns($table);
			if (is_array($cols) && count(array_keys($cols)) > 0)
			{
				foreach ($cols as $k=>$v)
				{
					$fields[$v->name] = $v;
				}
			}
		}
		catch (Exception $e)
		{

		}
		
		db()->SetFetchMode(ADODB_FETCH_ASSOC); //Data dictionary resets this
		
		return $fields;
	}
	
	/**
	 * Begins a ADODB "smart" transaction.  These are nestable
	 * and further calls to this will be ignored until the 
	 * complete_transaction is called.
	 *
	 * @author Ted Kulp
	 **/
	public function begin_transaction()
	{
		db()->SetTransactionMode("REPEATABLE READ");
		return db()->StartTrans();
	}
	
	/**
	 * Completed an ADODB "smart" transaction.  Depending on 
	 * the errors coming from the various SQL calls while
	 * in the transaction, this will smartly commit or rollback
	 * as necessary.
	 *
	 * @param boolean Set to false if a rollback should occur no matter what
	 * @return boolean Whether or not the commit was successful or rolled back
	 * @author Ted Kulp
	 **/
	public function complete_transaction($auto_complete = true)
	{
		$result = db()->CompleteTrans($auto_complete);
		db()->SetTransactionMode("");
		return $result;
	}
	
	/**
	 * Call this method to making the current transaction fail when
	 * complete_transaction is called.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	public function fail_transaction()
	{
		return db()->FailTrans();
	}
	
	public function __toString()
	{
		$id_field = $this->id_field;
		if (isset($this->$id_field))
			return get_class($this) . '- id:' . $this->$id_field;
		else
			return parent::__toString();
	}
}

# vim:ts=4 sw=4 noet
?>
