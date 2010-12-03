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

namespace silk\database\orm;

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
abstract class ObjectRelationalMapping extends Object implements \ArrayAccess
{
	/**
	 * The ORM version number.  This basically is a number that
	 * should be incremented when an object has a major change.
	 *
	 * Adding or removing fields from the table doesn't really
	 * constitute a change.  It's more like changes to a table
	 * name, enabling or disabling a sequence or changing the
	 * name of an id field.  Since some areas of CMSMS store
	 * serialized versions of objects, there could be a
	 * discrepency between the current version and the version
	 * that was just unserialized.  We can use the version number
	 * to test to see if it's the same.
	 *
	 * If not set, then it defaults to 1.  If you never really
	 * change the object, then you shouldn't need to modify it.
	 */
	var $orm_version = 1;

	/**
	 * Used to define any default settings for this object.  Not
	 * all fields need to be defined, as they'll come out of the
	 * database field names anyway.
	 */
	var $params = array();

	/**
	 * Used to define a variation between a database field and
	 * what the property name should be.  Takes a hash of
	 * 'database field name' => 'property name'
	 */
	var $field_maps = array();
	
	/**
	 * Used to define a different table name for this object if it
	 * doesn't match the predetermined name based on the object's class
	 * name.  The prefix in config.php will be applied automatically.
	 */
	var $table = '';
	
	/**
	 * Used to define an alternate field for the id.  This basically says
	 * whether or not we insert or update.
	 */
	var $id_field = 'id';
	
	/**
	 * Used to define a sequence to use for creating a new id to use.  If 
	 * blank, then the auto increment for the database is used for the id.
	 */
	var $sequence = '';
	
	/**
	 * Used to store validation error messages if a save does not go as
	 * expected.
	 */
	var $validation_errors = array();
	
	/**
	 * Used to store any association relationships.
	 **/
	var $associations = array();
	
	/**
	 * Used to define which field holds the record create date.
	 */
	var $create_date_field = 'create_date';
	
	/**
	 * Used to define which field holds the record modified date.
	 */
	var $modified_date_field = 'modified_date';
	
	/**
	 * Used to only update objects (not insert) that have changed
	 * any of their properties.  This means you should be using properites
	 * ($obj->some_field or $obj->SetSomeField()) so that the dirty bit
	 * gets flipped properly.
	 */
	var $dirty = false;
	
	/**
	 * A flag that allows something outside of the object to set a validation
	 * error before save() (and eventually, validate()) are called.  After one
	 * call to validate, any errors are cleared out anyway.
	 */
	var $clear_errors = true;
	
	/**
	 * Used in situations where we're doing a bit of polymorphism.  The type 
	 * field will store the name of the class that this object currently is.
	 * Then, when it's loaded, we will automatically instantiate that type of 
	 * object again and not just go by the name of the class that called the 
	 * find.  If you want this functionality to not exist, make this variable
	 * blank.
	 */
	var $type_field = 'type';
	
	function __construct()
	{
		parent::__construct();
		
		$this->setup(true);

		//Run the setup methods for any acts_as classes attached
		foreach (orm()->get_acts_as($this) as $one_acts_as)
		{
			$one_acts_as->setup($this);
		}
	}
	
	public function __wakeup()
	{
		$this->setup();
	}
	
	/**
	 * Method for setting up various pieces of the object.  This is mainly used to setup 
	 * associations on objects that will be used in sessions so that they get properly setup 
	 * again after the object comes out of serialization.
	 *
	 * @param bool Whether or not this is the first time this was called from the constructor
	 * @return void
	 * @author Ted Kulp
	 **/
	public function setup($first_time = false)
	{
	}

	/**
	 * Used to create a has_many association.  This should be called in the constructor of
	 * the data object.  Any associations are lazy loaded on the first call to them and are
	 * cached for the life of the object.
	 *
	 * @param string The name of the association.  It will then be called via 
	 *        $obj->assication_name.  Make sure it's not the same name as a 
	 *        parameter, or it will never get called.
	 * @param string The name of the class on the other end of the association.  This should
	 *        be the name that would be used when calling from the orm (cmsms()->child_class_name).
	 * @param string The name of the field in the association class that contains the matching id to 
	 *        this object.
	 * @param array Extra parameters that should be sent to the query.  Allows for things like order by, joins,
	 *        etc when the association is queried.
	 * @return void
	 * @author Ted Kulp
	 **/
	protected function create_has_many_association($association_name, $child_class_name, $child_field, $extra_params = array())
	{
		orm()->create_has_many_association($this, $association_name, $child_class_name, $child_field, $extra_params);
	}
	
	/**
	 * Used to create a has_one association.  This should be called in the constructor of
	 * the data object.  Any associations are lazy loaded on the first call to them and are
	 * cached for the life of the object.
	 *
	 * @param string The name of the association.  It will then be called via 
	 *        $obj->assication_name.  Make sure it's not the same name as a 
	 *        parameter, or it will never get called.
	 * @param string The name of the class on the other end of the association.  This should
	 *        be the name that would be used when calling from the orm (cmsms()->child_class_name).
	 * @param string The name of the field in the association class that contains the matching id to 
	 *        this object.
	 * @param array Extra parameters that should be sent to the query.  Allows for things like order by, joins,
	 *        etc when the association is queried.
	 * @return void
	 * @author Ted Kulp
	 **/
	protected function create_has_one_association($association_name, $child_class_name, $child_field, $extra_params = array())
	{
		orm()->create_has_one_association($this, $association_name, $child_class_name, $child_field, $extra_params);
	}
	
	/**
	 * Used to create a belongs_to association.  This should be called in the constructor of
	 * the data object.  Any associations are lazy loaded on the first call to them and are
	 * cached for the life of the object.
	 *
	 * @param string The name of the association.  It will then be called via 
	 *        $obj->assication_name.  Make sure it's not the same name as a 
	 *        parameter, or it will never get called.
	 * @param string The name of the class on the other end of the association.  This should
	 *        be the name that would be used when calling from the orm (cmsms()->belongs_to_class_name).
	 * @param string The name of the field in the this class that contains the matching id to 
	 *        the given belongs_to_class_name.
	 * @param array Extra parameters that should be sent to the query.  Allows for things like order by, joins,
	 *        etc when the association is queried.
	 * @return void
	 * @author Ted Kulp
	 **/
	protected function create_belongs_to_association($association_name, $belongs_to_class_name, $child_field, $extra_params = array())
	{
		orm()->create_belongs_to_association($this, $association_name, $belongs_to_class_name, $child_field, $extra_params);
	}
	
	/**
	 * Used to create a belongs_to association.  This should be called in the constructor of
	 * the data object.  Any associations are lazy loaded on the first call to them and are
	 * cached for the life of the object.
	 *
	 * @param string The name of the association.  It will then be called via 
	 *        $obj->assication_name.  Make sure it's not the same name as a 
	 *        parameter, or it will never get called.
	 * @param string The name of the class on the other end of the association.  This should
	 *        be the name that would be used when calling from the orm (cmsms()->belongs_to_class_name).
	 * @param string The name of the field in the this class that contains the matching id to 
	 *        the given belongs_to_class_name.
	 * @param array Extra parameters that should be sent to the query.  Allows for things like order by, joins,
	 *        etc when the association is queried.
	 * @return void
	 * @author Ted Kulp
	 **/
	protected function create_has_and_belongs_to_many_association($association_name, $child_class, $join_table, $join_other_id_field, $join_this_id_field, $extra_params = array())
	{
		orm()->create_has_and_belongs_to_many_association($this, $association_name, $child_class, $join_table, $join_other_id_field, $join_this_id_field, $extra_params);
	}
	
	/**
	 * Used to see if an association has been cached on the object yet.
	 *
	 * @return boolean Whether or not the association has been cached
	 * @author Ted Kulp
	 **/
	public function has_association($name)
	{
		return array_key_exists($name, $this->associations);
	}
	
	/**
	 * Get the association that has been previously cached on this object.
	 *
	 * @return mixed The array or object that was cached
	 * @author Ted Kulp
	 **/
	public function get_association($name)
	{
		return $this->associations[$name];
	}
	
	/**
	 * Set the object or array to cache so we don't need a call to the database
	 * if it's used again.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	public function set_association($name, $value)
	{
		$this->associations[$name] = $value;
	}
	
	protected function assign_acts_as($name)
	{
		orm()->create_acts_as($this, $name);
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to set with the given value
	 * @param mixed The value to set for the given key
	 * @return void
	 * @author Ted Kulp
	 **/
	function offsetSet($key, $value)
	{
		if (array_key_exists($key, $this->field_maps)) $key = $this->field_maps[$key];
		$this->params[$key] = $value;
		$this->dirty = true;
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to look up
	 * @return mixed The value of the $obj['field']
	 * @author Ted Kulp
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
	 * @author Ted Kulp
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
	 * @author Ted Kulp
	 **/
	function offsetExists($offset)
	{
		return array_key_exists($offset, $this->params);
	}
	
	/**
	 * "Static" method to register this class with the orm system.  This must be called
	 * right after an ORM class has been defined.
	 *
	 * @param $classname Name of the class to register with the ORM system
	 * @return void
	 * @author Ted Kulp
	 */
	static function register_orm_class($classname)
	{
		global $gSilk;
		$ormclasses =& $gSilk->orm;
		
		$name = underscore($classname);
		$ormclasses[$name] = new $classname;
		
		return FALSE;
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
	}
	
	/**
	 * Used to push a hash of keys and values to the object.  This is very helpful
	 * for updating an object based on the fields in a form.
	 *
	 * @param array The hash of keys and values to set in the object
	 */
	function update_parameters($params, $strip_slashes = false)
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
	protected function before_load_caller($type, $fields)
	{
		foreach (orm()->get_acts_as($this) as $one_acts_as)
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
	protected function after_load_caller()
	{
		foreach (orm()->get_acts_as($this) as $one_acts_as)
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
		foreach (orm()->get_acts_as($this) as $one_acts_as)
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
		foreach (orm()->get_acts_as($this) as $one_acts_as)
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
		foreach (orm()->get_acts_as($this) as $one_acts_as)
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
		foreach (orm()->get_acts_as($this) as $one_acts_as)
		{
			$one_acts_as->before_delete($this);
		}
		$this->before_delete();
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
		foreach (orm()->get_acts_as($this) as $one_acts_as)
		{
			$one_acts_as->after_delete($this);
		}
		$this->after_delete();
	}
	
	/**
	 * Virtual function that is called before a save operation can be
	 * completed.  Allows the object to make sure that all the necessary
	 * fields are filled in, they're in the proper range, etc.
	 *
	 * @return void
	 * @author Ted Kulp
	 */
	public function validate()
	{
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
	 * Method to call the validation methods properly.
	 *
	 * @return int The number of validation errors
	 * @author Ted Kulp
	 **/
	public function _call_validation()
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
	 * Begins a ADODB "smart" transaction.  These are nestable
	 * and further calls to this will be ignored until the 
	 * complete_transaction is called.
	 *
	 * @author Ted Kulp
	 **/
	public function begin_transaction()
	{
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
