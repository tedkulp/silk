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

namespace silk\display;

use \silk\core\Object;

/**
 * Base class for helper classes to extend.
 **/
class HelperBase extends Object
{
	function __construct()
	{
		parent::__construct();
	}
	
	function createSmartyPlugins()
	{
		foreach ($this->getDefinedClassMethods() as $one_method)
		{
			if (starts_with($one_method, 'modifier'))
			{
				$plugin_name = trim(str_replace('modifier', '', $one_method), ' _');
				smarty()->register->modifier($plugin_name, array($this, $one_method));
			}
			else if (starts_with($one_method, 'block'))
			{
				$plugin_name = trim(str_replace('block', '', $one_method), ' _');
				smarty()->register->block($plugin_name, array($this, $one_method));
			}
			else
			{
				smarty()->register->templateFunction($one_method, array($this, $one_method));
			}
			
		}
	}
	
	function getDefinedClassMethods()
	{
		$methods = array();
		$class = new \ReflectionClass($this);
		foreach ($class->getMethods() as $one_method)
		{
			$declaring_class = $one_method->getDeclaringClass()->name;
			if ($declaring_class != '\silk\display\HelperBase' && $declaring_class != 'HelperBase' && $declaring_class != '\silk\core\Object' && $declaring_class != 'Object')
			{
				$methods[] = $one_method->name;
			}
		}
		return $methods;
	}
}

# vim:ts=4 sw=4 noet
