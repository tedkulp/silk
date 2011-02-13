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
class Model extends Object
{
	static public function load($id)
	{
		$em = Database::getEntityManager();
		$class = get_called_class();
		return $em->find($class, $id);
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

	/**
	 * Callback sent before the object is saved.  This allows the object to
	 * send any events, manipulate any values, etc before the objects is
	 * persisted.
	 **/
	protected function beforeSave($event_args)
	{
	}
	
	/**
	 * Wrapper function for before_save.  Only should be 
	 * called by classes that extend the functionality of 
	 * the ORM system.
	 *
	 * @prePersist
	 * @preUpdate
	 */
	protected function beforeSaveCaller($event_args)
	{
		$this->beforeSave($event_args);
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
	 * @postPersist
	 * @postUpdate
	 */
	protected function afterSaveCaller()
	{
		$this->afterSave();
	}
}

# vim:ts=4 sw=4 noet
