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
 * Base class for all Silk classes
 *
 * @author Ted Kulp
 * @since 1.0
 **/
abstract class SilkObject
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
	 * Base toString override.
	 *
	 * @return string The name of the class
	 * @author Ted Kulp
	 **/
	public function __toString()
	{
		return "Object(".get_class($this).")";
	}
}
?>
