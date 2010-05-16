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

/**
 * Class to handle the management of ORM object instances and loading.
 *
 * @author Ted Kulp
 * @since 1.0
 */
class SilkObjectRelationalManager extends SilkObject
{
	static private $instance = NULL;

	var $classes = array();
	
	var $assoc = array();
	
	var $acts_as = array();

	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Returns an instance of the SilkObjectRelationalManager singleton.  Most 
	 * people can generally use orm() instead of this, but they 
	 * both do the same thing.
	 *
	 * @return SilkObjectRelationalManager The singleton SilkObjectRelationalManager instance
	 * @author Ted Kulp
	 **/
	static public function get_instance()
	{
		if (self::$instance == NULL)
		{
			self::$instance = new SilkObjectRelationalManager();
		}
		return self::$instance;
	}
	
	function __invoke($name)
	{
		return $this->get_orm_class($name);
	}
	
	/**
	 * Getter overload method.
	 *
	 * @param string The field to look up
	 * @return mixed The value for that field, if it exists
	 * @author Ted Kulp
	 **/
	function __get($name)
	{
		return $this->get_orm_class($name);
	}
	
	/**
	 * Retrieves an instance of an ORM'd object for doing
	 * find_by_* methods.
	 *
	 * @return mixed The object with the given name, or null if it's not registered
	 * @author Ted Kulp
	 **/
	function get_orm_class($name, $try_prefix = true)
	{
		if (isset($this->classes[$name]))
			return $this->classes[$name];
		elseif (isset($this->classes[underscore($name)]))
			return $this->classes[underscore($name)];
		else
		{
			// Let's try to load the thing dynamically
			$name = camelize($name);
			if (class_exists($name))
			{
				$this->classes[underscore($name)] = new $name;
				return $this->classes[underscore($name)];	
			}
			else
			{
				if ($try_prefix)
				{
					return $this->get_orm_class('silk_' . $name, false);
				}
				else
				{
					var_dump('Class not found! -- ' . $name);
					return null;
				}
			}
		}
	}
	
	function has_association(&$obj, $association_name)
	{
		return $obj != null && isset($this->assoc[get_class($obj)][$association_name]);
	}
	
	function process_association(&$obj, $association_name)
	{
		if ($this->has_association($obj, $association_name))
		{
			return $this->assoc[get_class($obj)][$association_name]->get_data($obj);
		}
	}
	
	function create_has_many_association(&$obj, $association_name, $child_class_name, $child_field, $extra_params = array())
	{
		if (!isset($this->assoc[get_class($obj)][$association_name]))
		{
			$association = new SilkHasManyAssociation($association_name);
			$association->child_class = $child_class_name;
			$association->child_field = $child_field;
			$association->extra_params = $extra_params;
			$this->assoc[get_class($obj)][$association_name] = $association;
		}
	}
	
	function create_has_one_association(&$obj, $association_name, $child_class_name, $child_field, $extra_params = array())
	{
		if (!isset($this->assoc[get_class($obj)][$association_name]))
		{
			$association = new SilkHasOneAssociation($association_name);
			$association->child_class = $child_class_name;
			$association->child_field = $child_field;
			$association->extra_params = $extra_params;
			$this->assoc[get_class($obj)][$association_name] = $association;
		}
	}
	
	function create_belongs_to_association(&$obj, $association_name, $belongs_to_class_name, $child_field, $extra_params = array())
	{
		if (!isset($this->assoc[get_class($obj)][$association_name]))
		{
			$association = new SilkBelongsToAssociation($association_name);
			$association->belongs_to_class_name = $belongs_to_class_name;
			$association->child_field = $child_field;
			$association->extra_params = $extra_params;
			$this->assoc[get_class($obj)][$association_name] = $association;
		}
	}
	
	function create_has_and_belongs_to_many_association(&$obj, $association_name, $child_class, $join_table, $join_other_id_field, $join_this_id_field, $extra_params = array())
	{
		if (!isset($this->assoc[get_class($obj)][$association_name]))
		{
			$association = new SilkHasAndBelongsToManyAssociation($association_name);
			$association->child_class = $child_class;
			$association->join_table = db_prefix().$join_table;
			$association->join_other_id_field = $join_other_id_field;
			$association->join_this_id_field = $join_this_id_field;
			$association->extra_params = $extra_params;
			$this->assoc[get_class($obj)][$association_name] = $association;
		}
	}
	
	function create_acts_as(&$obj, $name)
	{
		if (!isset($this->acts_as[get_class($obj)][$name]))
		{
			$class_name = 'SilkActsAs' . camelize($name);
			$acts_as_obj = new $class_name;
			if ($acts_as_obj != null)
			{
				$this->acts_as[get_class($obj)][$name] = $acts_as_obj;
			}
		}
	}
	
	function get_acts_as(&$obj)
	{
		if (isset($this->acts_as[get_class($obj)]))
		{
			return $this->acts_as[get_class($obj)];
		}
		return array();
	}
}

# vim:ts=4 sw=4 noet
?>