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
 * Base class for helper classes to extend.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkHelperBase extends SilkObject
{
	function __construct()
	{
		parent::__construct();
	}
	
	function create_smarty_plugins()
	{
		foreach ($this->get_defined_class_methods() as $one_method)
		{
			if (starts_with($one_method, 'modifier'))
			{
				$plugin_name = trim(str_replace('modifier', '', $one_method), ' _');
				smarty()->register_modifier($plugin_name, array($this, $one_method));
			}
			else if (starts_with($one_method, 'block'))
			{
				$plugin_name = trim(str_replace('block', '', $one_method), ' _');
				smarty()->register_block($plugin_name, array($this, $one_method));
			}
			else
			{
				smarty()->register_function($one_method, array($this, $one_method));
			}
			
		}
	}
	
	function get_defined_class_methods()
	{
		$methods = array();
		$class = new ReflectionClass($this);
		foreach ($class->getMethods() as $one_method)
		{
			$declaring_class = $one_method->getDeclaringClass()->name;
			if ($declaring_class != 'SilkHelperBase' && $declaring_class != 'SilkObject')
			{
				$methods[] = $one_method->name;
			}
		}
		return $methods;
	}
}

# vim:ts=4 sw=4 noet
?>