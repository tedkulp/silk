<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008-2011 Ted Kulp
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

namespace silk\model;

use \silk\core\Object;
use \silk\database\Database;
use \Doctrine\Common\Annotations\AnnotationReader;

/**
 * @MappedSuperclass 
 * @HasLifecycleCallbacks
 */
class Model extends Object implements \ArrayAccess
{
	/**
	 * A flag that allows something outside of the object to set a validation
	 * error before save() (and eventually, validate()) are called.  After one
	 * call to validate, any errors are cleared out anyway.
	 */
	public $clear_errors = true;
	public $validation_errors = array();

	static public function load($id)
	{
		$em = Database::getEntityManager();
		$class = get_called_class();
		return $em->find($class, $id);
	}

	static public function migrate()
	{
		if (!Database::isMongoDb())
		{
			$em = Database::getEntityManager();
			$class = get_called_class();
			$class = $em->getClassMetadata($class);
			$classes = array($class);

			$tool = new \Doctrine\ORM\Tools\SchemaTool($em);
			$tool->updateSchema($classes, true);
		}
	}

	public static function getTableName()
	{
		$em = Database::getEntityManager();
		$class = get_called_class();
		$class = $em->getClassMetadata($class);
		if ($class)
			if (Database::isMongoDb())
				return $class->getCollection();
			else
				return $class->getTableName();
		return null;
	}

	public static function dropTable()
	{
		$em = Database::getEntityManager();
		foreach (self::findAll() as $one_item)
		{
			$em->remove($one_item);
		}
		$em->flush();
		return Database::dropTable(self::getTableName(), false);
	}

    /**
     * Finds all entities in the repository.
     *
     * @return array The entities.
     */
    public static function findOne()
    {
		$repo = self::getEntityRepository();
		$query = Database::isMongoDb() ? array('_id' => array('$exists' => true)) : array();
        return $repo->findOneBy($query);
    }

	public static function __callStatic($name, $arguments)
	{
		//If the method doesn't exist and starts with find,
		//then pass it off to the $em
		if (startsWith($name, 'find'))
		{
			$em = Database::getEntityManager();
			if ($em)
			{
				$class = get_called_class();
				$repo = $em->getRepository($class);
				return call_user_func_array(array($repo, $name), $arguments);
			}
		}

		return false;
    }

	public function __call($name, $arguments)
	{
		$matches = array();
		if (preg_match('/^(get|set|add)(.*)$/', $name, $matches))
		{
			$name = lcfirst($matches[2]);
			if ($matches[1] == 'get')
			{
				if (property_exists($this, $name))
					return $this->$name;
			}
			else if ($matches[1] == 'set')
			{
				if (property_exists($this, $name))
					$this->$name = $arguments[0];
			}
			else if ($matches[1] == 'add')
			{
				if (property_exists($this, $name))
				{
					if (is_array($this->$name))
					{
						array_push($this->$name, $arguments[0]);
					}
					else if (is_object($this->$name) && method_exists($this->$name, 'add'))
					{
						$this->$name->add($arguments[0]);
					}
				}
			}
		}
	}

	static public function getQueryBuilder()
	{
		$em = Database::getEntityManager();
		return $em->createQueryBuilder();
	}

	static public function getEntityRepository()
	{
		$em = Database::getEntityManager();
		return $em->getRepository(get_called_class());
	}

	public function hasParameter($name)
	{
		return property_exists($this, $name);
	}

	/**
	 * Callback after object is loaded.  This allows the object to do any
	 * housekeeping, setting up other fields, etc before it's returned.
	 *
	 * @return void
	 */
	protected function afterLoad()
	{
	}

	/**
	 * Wrapper function for after_load.  Only should be 
	 * called by classes that entend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 * @PostLoad
	 */
	public function afterLoadCaller()
	{
		$this->afterLoad();
	}

	/**
	 * Callback sent before the object is validated.  This allows the object to
	 * do any initial cleanup so that validation may pass properly.
	 *
	 * @return void
	 */
	protected function beforeValidation()
	{
	}

	/**
	 * Wrapper function for before_validation.  Only should be 
	 * called by classes that extend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 */
	protected function beforeValidationCaller()
	{
		/*
		foreach ($this->_acts_as_obj as $one_acts_as)
		{
			$one_acts_as->before_validation($this);
		}
		 */
		$this->beforeValidation();
	}

	/**
	 * Callback sent before the object is saved.  This allows the object to
	 * send any events, manipulate any values, etc before the objects is
	 * persisted.
	 **/
	protected function beforeSave()
	{
	}
	
	/**
	 * Wrapper function for before_save.  Only should be 
	 * called by classes that extend the functionality of 
	 * the ORM system.
	 *
	 * @PrePersist
	 * @PreUpdate
	 */
	public function beforeSaveCaller()
	{
		// Handle the autogen timestamps
		if (property_exists($this, 'createDate') && $this->createDate == null)
			$this->createDate = new \DateTime();
		if (property_exists($this, 'modifiedDate'))
			$this->modifiedDate = new \DateTime();

		$this->beforeSave();
	}
	
	/**
	 * Callback sent after the object is saved.
	 **/
	protected function afterSave()
	{
	}
	
	/**
	 * Wrapper function for after_save.  Only should be 
	 * called by classes that entend the functionality of 
	 * the ORM system.
	 *
	 * @PostPersist
	 * @PostUpdate
	 */
	public function afterSaveCaller()
	{
		$this->afterSave();
	}

	/**
	 * Callback sent before the object is deleted from
	 * the database.
	 *
	 * @return void
	 */
	protected function beforeDelete()
	{
	}

	/**
	 * Wrapper function for before_delete.  Only should be 
	 * called by classes that entend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 * @PreRemove
	 */
	public function beforeDeleteCaller()
	{
		/*
		foreach ($this->_acts_as_obj as $one_acts_as)
		{
			$res = $one_acts_as->before_delete($this);
			if( $res === false ) return false;
		}
		*/
		return $this->beforeDelete();
	}

	/**
	 * Callback sent after the object is deleted from
	 * the database.
	 *
	 * @return void
	 */
	protected function afterDelete()
	{
	}

	/**
	 * Wrapper function for after_delete.  Only should be 
	 * called by classes that entend the functionality of 
	 * the ORM system.
	 *
	 * @return void
	 * @PostRemove
	 */
	public function afterDeleteCaller()
	{
		/*
		foreach ($this->_acts_as_obj as $one_acts_as)
		{
			$one_acts_as->after_delete($this);
		}
		*/
		$this->afterDelete();
	}

	/**
	 * Saves the entity from the database.
	 *
	 * @return boolean True if the object was sucesssfully saved
	 */
	public function save($flush_immediately = true)
	{
		$this->beforeValidationCaller();

		if (!$this->isValid())
		{
			return false;
		}

		$em = Database::getEntityManager();
		$em->persist($this);
		if ($flush_immediately)
			$em->flush();

		return true;
	}

	/**
	 * Deletes the entity from the database.
	 *
	 * @return boolean True if the object was sucesssfully deleted
	 */
	public function delete($flush_immediately = true)
	{
		$em = Database::getEntityManager();
		$em->remove($this);
		if ($flush_immediately)
			$em->flush();
	}

	/**
	 * Method to call the validation methods properly.
	 *
	 * @return int The number of validation errors
	 */
	public function isValid()
	{
		//Clear them out first
		if ($this->clear_errors)
			$this->validation_errors = array();

		$this->clear_errors = true;

		//First call the annotated validations
		$this->annotationValidate();

		//Then call the validate method
		$this->validate();

		return (count($this->validation_errors) == 0);
	}

	/**
	 * Looks for annotations in the class parameters
	 * that are marked for validation and checks theem.
	 * Any errors are added to the validation_errors array
	 * in the object.
	 *
	 * @return void
	 **/
	protected function annotationValidate()
	{
		$reader = new AnnotationReader();
		$reader->setAutoloadAnnotations(true);
		$reader->setAnnotationNamespaceAlias('silk\\model\\validations\\', 'Validation');
		$refl_class = new \ReflectionClass($this);
		$class_name = $refl_class->getName();
		foreach ($refl_class->getProperties() as $property)
		{
			if ($property->getDeclaringClass()->getName() == $class_name)
			{
				foreach ($reader->getPropertyAnnotations($property) as $annotation_obj)
				{
					if (is_subclass_of($annotation_obj, '\\silk\\model\\Validation'))
					{
						$prop_name = $property->getName();
						$annotation_obj->setFieldName($prop_name);
						$result = $annotation_obj->isValid($this[$prop_name]);
						if (!$result)
						{
							$this->addValidationError($annotation_obj->getMessage($prop_name));
						}
					}
				}
			}
		}
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

	/**
	 * Method for quickly adding a new validation error to the object.  If this is
	 * called, then it's a safe bet that save() will fail.  This should only be
	 * used for setting validation errors from external sources.
	 *
	 * @param string Message to add to the validation error stack
	 * @return void
	 */
	public function addError($message)
	{
		$this->addValidationError($message);
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
	 */
	protected function addValidationError($message)
	{
		$this->validation_errors[] = $message;
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to set with the given value
	 * @param mixed The value to set for the given key
	 * @return void
	 */
	function offsetSet($key, $value)
	{
		if (property_exists($this, $key))
			$this->$key = $value;
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to look up
	 * @return mixed The value of the $obj['field']
	 */
	function offsetGet($key)
	{
		if (property_exists($this, $key))
			return $this->$key;
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to unset
	 * @return bool Whether or not it does exist
	 */
	function offsetUnset($key)
	{
		if (property_exists($this, $key))
			$this->$key = null;
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to lookup to see if it exists
	 * @return bool Whether or not it does exist
	 **/
	function offsetExists($key)
	{
		return property_exists($this, $key);
	}
}

# vim:ts=4 sw=4 noet
