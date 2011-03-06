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

namespace silk\form;

use \silk\core\Object;

/**
 * Global object that holds references to various data structures
 * needed by classes/functions.
 */
class Form extends Object implements \ArrayAccess
{
	public $name = null;

	public $id = '';

	public $class = '';

	public $acceptCharset = '';

	public $action = '';

	public $enctype = '';

	public $encoding = '';

	public $method = 'POST';

	public $target = '';

	protected $fields = array();

	/**
	 * Constructor
	 */
	public function __construct($name, $params = array())
	{
		parent::__construct();

		$this->name = $name;
		if (!empty($params))
		{
			foreach($params as $key => $value)
			{
				if (isset($this->$key))
					$this->$key = $value;
			}
		}
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
				{
					$this->$name = $arguments[0];
					return $this;
				}
			}
		}
	}

	public function addField($type, $name, array $params = array())
	{
		$potential_class = "\\silk\\form\\elements\\" . $type;
		if (class_exists($potential_class))
		{
			$new_obj = new $potential_class($this, $name, $params);
			$this->fields[$name] = $new_obj;
			return $new_obj;
		}

		return null;
	}

	public function getField($name)
	{
		if (array_key_exists($name, $this->fields))
		{
			return $this->fields[$name];
		}

		return null;
	}

	public function addFieldSet($name, array $params = array())
	{
		return $this->addField('FieldSet', $name, $params);
	}

	public function render()
	{
		$params = $this->compactVariables(array('id', 'name', 'class', 'method', 'action', 'enctype', 'target'));
		$result = $this->createStartTag('form', $params);

		foreach ($this->fields as $name => $one_field)
		{
			$result .= $one_field->render();
		}

		return $result . $this->createEndTag('form');
	}

	public function renderField($name)
	{
		if (array_key_exists($name, $this->fields))
			return $this->fields[$name]->render();

		return '';
	}

	public function createStartTag($name, $params, $self_close = false, $extra_html = '')
	{
		$text = "<{$name}";

		foreach ($params as $key=>$value)
		{
			if ($value != '')
				$text .= " {$key}=\"{$value}\"";
		}

		if ($extra_html != '')
			$text .= " {$extra_html}";

		$text .= ($self_close ? ' />' : '>');

		return $text;
	}

	public function createEndTag($name)
	{
		return "</{$name}>";
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
		if (isset($this->fields[$key]))
			$this->fields[$key] = $value;
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to look up
	 * @return mixed The value of the $obj['field']
	 */
	function offsetGet($key)
	{
		if (isset($this->fields[$key]))
			return $this->fields[$key];
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to unset
	 * @return bool Whether or not it does exist
	 */
	function offsetUnset($key)
	{
		if (isset($this->fields[$key]))
			unset($this->fields[$key]);
	}

	/**
	 * Used for the ArrayAccessor implementation.
	 *
	 * @param string The key to lookup to see if it exists
	 * @return bool Whether or not it does exist
	 **/
	function offsetExists($offset)
	{
		return isset($this->fields[$offset]);
	}
}

# vim:ts=4 sw=4 noet