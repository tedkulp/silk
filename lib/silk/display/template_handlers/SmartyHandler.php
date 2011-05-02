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

namespace silk\display\template_handlers;

use \silk\core\Object;

class SmartyHandler extends Object implements TemplateHandlerInterface
{
	protected $controller = null;
	protected $helper = null;
	protected $smarty_data = null;

	public function setController(&$controller)
	{
		$this->controller = $controller;
	}

	public function setHelper(&$helper)
	{
		$this->helper = $helper;
		$this->createSmartyPlugins();
	}

	public function partialCallback($params, $template)
	{
		$txt = '';

		if (isset($params['name']) && !empty($params['name']))
		{
			$txt = $this->controller->renderPartial($params['name'], $params);
			if (!empty($txt) && isset($params['js']) && $params['js'])
				$txt = json_encode($txt);
		}

		return $txt;
	}

	function createSmartyPlugins()
	{
		try
		{
			smarty()->registerPlugin('function', 'partial', array($this, 'partialCallback'));
		}
		catch (\Exception $e)
		{
		}
		if ($this->helper != null)
		{
			foreach ($this->helper->getDefinedClassMethods() as $one_method)
			{
				if (startsWith($one_method, 'modifier'))
				{
					$plugin_name = trim(str_replace('modifier', '', $one_method), ' _');
					try
					{
						smarty()->registerPlugin('modifier', $plugin_name, array($this->helper, $one_method));
					}
					catch (\Exception $e)
					{
					}
				}
				else if (startsWith($one_method, 'block'))
				{
					$plugin_name = trim(str_replace('block', '', $one_method), ' _');
					try
					{
						smarty()->registerPlugin('block', $plugin_name, array($this->helper, $one_method));
					}
					catch (\Exception $e)
					{
					}
				}
				else if (startsWith($one_method, 'function'))
				{
					$plugin_name = trim(str_replace('function', '', $one_method), ' _');
					try
					{
						smarty()->registerPlugin('function', $plugin_name, array($this->helper, $one_method));
					}
					catch (\Exception $e)
					{
					}
				}
			}
		}
	}
	
	public function setVariables($variables)
	{
		//Create fresh Smarty data object, so we can
		//keep the variables in one scope instead of
		//polluting global (or smarty object) space.
		$smarty_data = smarty()->createData();
		foreach ($variables as $k => $v)
		{
			$smarty_data->assign($k, $v);
		}
		$this->smarty_data = $smarty_data;
	}

	public function processTemplateFromFile($filename)
	{
		$this->smarty_data->assignByRef('controller', $this->controller);

		if ($this->helper != null)
			$this->smarty_data->assignByRef('helper', $this->helper);

		//Display the template using our smarty_data object
		//to seed the variables that will be passed along
		return smarty()->fetch("file:" . $filename, $this->smarty_data);
	}
}

# vim:ts=4 sw=4 noet
