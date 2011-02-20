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

/**
 * @MappedSuperclass 
 * @HasLifecycleCallbacks
 */
class Model extends Object implements \ArrayAccess
{
	static public function load($id)
	{
		$em = Database::getEntityManager();
		$class = get_called_class();
		return $em->find($class, $id);
	}

	static public function migrate()
	{
		$em = Database::getEntityManager();
		$class = get_called_class();
		$class = $em->getClassMetadata($class);
		$classes = array($class);

		$tool = new \Doctrine\ORM\Tools\SchemaTool($em);
		$tool->updateSchema($classes, true);
	}

    /**
     * Finds all entities in the repository.
     *
     * @return array The entities.
     */
    public static function findOne()
    {
		$repo = self::getEntityRepository();
        return $repo->findOneBy(array());
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
		if (preg_match('/^(get|set)(.*)$/', $name, $matches))
		{
			$name = lcfirst($matches[2]);
			if ($matches[1] == 'get')
			{
				if (isset($this->$name))
					return $this->$name;
			}
			else if ($matches[1] == 'set')
			{
				if (isset($this->$name))
					$this->$name = $arguments[1];
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
		if (isset($this->$name))
			return true;
		return false;
	}

    /* @PostLoad */
    public function doStuffOnPostLoad()
    {
        $this->value = 'changed from postLoad callback!';
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
		if (isset($this->createDate) && $this->createDate == null)
			$this->createDate = new \DateTime();
		if (isset($this->modifiedDate))
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
	function save($flush_immediately = true)
	{
		$em = Database::getEntityManager();
		$em->persist($this);
		if ($flush_immediately)
			$em->flush();
	}

	/**
	 * Deletes the entity from the database.
	 *
	 * @return boolean True if the object was sucesssfully deleted
	 */
	function delete($flush_immediately = true)
	{
		$em = Database::getEntityManager();
		$em->remove($this);
		if ($flush_immediately)
			$em->flush();
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
		if (isset($this->$key))
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
		if (isset($this->$key))
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
		if (isset($this->$key))
			$this->$key = null;
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to lookup to see if it exists
	 * @return bool Whether or not it does exist
	 **/
	function offsetExists($offset)
	{
		return isset($this->$offset);
	}
}

# vim:ts=4 sw=4 noet
