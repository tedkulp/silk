<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008 Ted Kulp
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
 * Base class for controller classes to extend.
 *
 * @author Ted Kulp
 * @package Silk
 * @since 1.0
 **/
class SilkControllerBase extends SilkObject
{
	/**
	 * The main method for running an action method in the controller, calling
	 * the view and displaying any rendered results.  If an action method returns
	 * text, then that will be returned.  If nothing is returned, then an attempt to
	 * pull a default view/template will be attempted.
	 *
	 * @param string The name of the action to display the view of
	 * @param array An array of parameters to send to the template.  This generally come 
	 *        from the route processor.
	 * @return string The rendered result
	 * @author Ted Kulp
	 **/
	function run_action($action_name, $params = array())
	{
		$this->before_filter();

		$this->set('params', $params);
		$value = null;
		
		//See if a method exists in the controller that matches the action
		if (method_exists($this, $action_name))
		{
			$value = call_user_func_array(array($this, $action_name), array($params));
		}

		//If nothing is returned (or there is no method in the controller), then we try the 
		//default template and render that
		if ($value === null)
		{
			$value = $this->render_default_template($action_name, $params);
		}
		
		//Now put the value inside a layout, if necessary
		$this->set('title', underscore(get_class($this)) . ' - ' . $action_name);
		$this->set('content', $value);
		$value = $this->render_layout($value);
		
		$this->after_filter();
		
		return $value;
	}
	
	/**
	 * Fetches the default template for an action of this controller.  It calculates
	 * the file location, loads it into smarty and returns the results.
	 *
	 * @param string The name of the action to display the view of
	 * @param array An array of parameters to send to the template.  This generally come 
	 *        from the route processor.
	 * @return The rendered result
	 * @author Ted Kulp
	 **/
	function render_default_template($action_name, $params = array())
	{
		$default_template_dir = str_replace('_controller', '', underscore(get_class($this)));
		$path_to_default_template = join_path(ROOT_DIR, 'app', 'views', $default_template_dir, $action_name . '.tpl');
		if (is_file($path_to_default_template))
		{
			return smarty()->fetch("file:{$path_to_default_template}");
		}
		else
		{
			throw new SilkViewNotFoundException();
		}
	}
	
	function render_layout()
	{
		$path_to_default_template = join_path(ROOT_DIR, 'app', 'views', 'layouts', 'default.tpl');
		if (is_file($path_to_default_template))
		{
			return smarty()->fetch("file:{$path_to_default_template}");
		}
		else
		{
			return $value;
		}
	}
	
	/**
	 * Sets a value in the smarty instnace for use in the template
	 * for display or logic.
	 *
	 * @param string The name of the variable to set.
	 * @param mixed The value to set for that named variable
	 * @return void
	 * @author Ted Kulp
	 **/
	function set($name, $value)
	{
		smarty()->assign($name, $value);
	}
	
	/**
	 * Sets a value in the smarty instnace for use in the template
	 * for display or logic.  Works exaclty like set, except that
	 * is stores the value by reference in case it's necessary
	 * to modify the value directly in the view.
	 *
	 * @param string The name of the variable to set.
	 * @param mixed The value to set for that named variable
	 * @return void
	 * @author Ted Kulp
	 **/
	function set_by_ref($name, &$value)
	{
		smarty()->assign_by_ref($name, $value);
	}
	
	/**
	 * Callback function to run before calling the "action" method of the
	 * controller.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	function before_filter()
	{
		
	}
	
	/**
	 * Callback function to run after calling the "action" method of the
	 * controller.
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	function after_filter()
	{
		
	}
}

# vim:ts=4 sw=4 noet
?>