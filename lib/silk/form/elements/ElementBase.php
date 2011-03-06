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

namespace silk\form\elements;

use \silk\core\Object;

/**
 * Global object that holds references to various data structures
 * needed by classes/functions.
 */
class ElementBase extends Object
{
	protected $form = null;

	public $name = '';

	public $value = '';

	public $id = '';

	public $class = '';

	public $accesskey = '';

	public $dir = '';

	public $lang = '';

	public $style = '';

	public $tabindex = '';

	public $title = '';

	/**
	 * Constructor
	 */
	public function __construct($form, $name, $params = array())
	{
		parent::__construct();

		$this->form = $form;
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

	public function render()
	{
	}
}

# vim:ts=4 sw=4 noet
