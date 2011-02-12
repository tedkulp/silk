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

namespace silk\core;

/**
 * Base class for all Silk classes
 **/
abstract class Object
{
	public $mixins = array();
	
	/**
	 * Base constructor.  Doesn't really do anything, but
	 * gives methods extending CmsObject something to call.
	 *
	 * @author Ted Kulp
	 **/
	public function __construct()
	{
		//echo 'instantiate - ', $this->__toString(), '<br />';
	}
	
	public function mixin($class_name)
	{
		if (class_exists($class_name) && !array_key_exists($class_name, $this->mixins))
		{
			$this->mixins[$class_name] = new $class_name($this);
		}
	}
	
	function __call($function, $arguments)
	{
		if (count($this->mixins))
		{
			foreach ($this->mixins as $class_name => $obj)
			{
				if (method_exists($obj, $function))
				{
					return call_user_func_array(array($obj, $function), $arguments);
				}
			}
		}
		
		return false;
	}

	/**
	 * Returns the name of a class using get_class with the namespaces stripped.
	 * This will not work inside a class scope as get_class() a workaround for
	 * that is using get_class_name(get_class());
	 *
	 * @param  object|string  $object  Object or Class Name to retrieve name
	 * @return  string  Name of class with namespaces stripped
	 */
	Function getClassName($object = null)
	{
		if (!is_object($object) && !is_string($object)) {
			return false;
		}
		
		$class = explode('\\', (is_string($object) ? $object : get_class($object)));
		return $class[count($class) - 1];
	}

	/**
	 * Base toString override.
	 *
	 * @return string The name of the class
	 **/
	public function __toString()
	{
		return "Object(".get_class($this).")";
	}
}

# vim:ts=4 sw=4 noet
