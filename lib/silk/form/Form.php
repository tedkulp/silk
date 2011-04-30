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

define('SILK_FORM_VAR', '_silk_form_name');

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

	public $remote = false;

	protected $fields = array();

	protected $buttons = array();

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
				$key = lcfirst(camelize(str_replace('-', '_', $key)));
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

	public function addField($type, $name, array $params = array(), $ary_to_add = 'fields')
	{
		$potential_class = "\\silk\\form\\elements\\" . $type;
		if (class_exists($potential_class))
		{
			$name = $this->convertArrayToName($name);

			$new_obj = new $potential_class($this, $name, $params);
			$this->{$ary_to_add}[$name] = $new_obj;
			return $new_obj;
		}

		return null;
	}

	public function getField($name)
	{
		$name = $this->convertArrayToName($name);

		if (array_key_exists($name, $this->fields))
		{
			$field = $this->fields[$name];
			if ($field)
				return $field;
		}

		foreach ($this->fields as $one_field)
		{
			if ($one_field instanceof Form)
			{
				$field = $one_field->getField($name);
				if ($field)
					return $field;
			}
		}

		return null;
	}

	public function addFieldSet($name, array $params = array())
	{
		return $this->addField('FieldSet', $name, $params);
	}

	public function addButton($name, array $params = array(), $type = 'Button')
	{
		if (!isset($params['value']))
			$params['value'] = $name;
	
		return $this->addField($type, $name, $params, 'buttons');
	}

	public function addImageButton($name, array $params = array(), $type = 'ImageButton')
	{
		return $this->addButton($name, $params, $type);
	}

	public function setValues(array $params = array())
	{
		foreach ($params as $key => $value)
		{
			$field = $this->getField($key);
			if ($field)
				$field->setValue($value);
		}
	}

	public function render()
	{
		$result = $this->renderStart();

		foreach ($this->fields as $name => $one_field)
		{
			$result .= $one_field->render();
		}

		foreach ($this->buttons as $name => $one_field)
		{
			$result .= $one_field->render();
		}

		$result .= $this->renderEnd();

		return $result;
	}

	public function renderStart()
	{
		$params = $this->compactVariables(array('id', 'name', 'class', 'method', 'action', 'enctype', 'target', 'remote'));

		$tmp = '';

		if (!in_array(strtoupper($params['method']), array('GET', 'POST')))
		{
			$tmp .= $this->createStartTag('input', array('name' => '_method', 'value' => strtoupper($params['method']), 'type' => 'hidden'));
			$params['method'] = 'POST';
		}

		$result = $this->createStartTag('form', $params);
		$result .= $this->createStartTag('input', array('name' => SILK_FORM_VAR, 'value' => $this->name, 'type' => 'hidden'));
		$result .= $tmp;

		return  $result;
	}

	public function renderEnd()
	{
		return $this->createEndTag('form');
	}

	public function renderField($name)
	{
		$field = $this->getField($name);
		if ($field != null)
			return $field->render();

		return '';
	}

	public function isPosted()
	{
		if (isset(silk()->request[SILK_FORM_VAR]))
		{
			$this->fillFields();
			return true;
		}

		return false;
	}

	public function getClickedButton()
	{
		$params = silk()->request->post();
		foreach ($this->buttons as $one_field)
		{
			if (isset($params[$one_field->getName()]))
			{
				return $one_field->getName();
			}
		}
	}

	protected function fillFields()
	{
		$params = silk()->request->post();
		foreach ($this->fields as $one_field)
		{
			if ($one_field instanceof Form)
			{
				$one_field->fillFields();
			}
			else
			{
				if (isset($params[$one_field->getName()]))
				{
					$one_field->setValue($params[$one_field->getName()]);
				}
			}
		}
	}

	public function compactVariables(array $names = array())
	{
		$result = parent::compactVariables($names);
		if (isset($result['remote']) && $result['remote'])
		{
			$result['data-remote'] = 'true';
			unset($result['remote']);
		}
		return $result;
	}

	public function convertArrayToName($name)
	{
		if (!is_array($name))
			return $name;

		$count = 0;
		$result = '';
		foreach ($name as $one_name)
		{
			if ($count > 0)
				$result .= "[{$one_name}]";
			else
				$result .= $one_name;
			$count++;
		}
		return $result;
	}

	public function createStartTag($name, $params, $self_close = false, $extra_html = '')
	{
		$text = "<{$name}";

		foreach ($params as $key=>$value)
		{
			if ($value != '')
			{
				$key = str_replace('_', '-', underscore($key));
				$text .= " {$key}=\"{$value}\"";
			}
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
