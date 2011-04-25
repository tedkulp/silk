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

namespace silk\action;

use \silk\core\Object;
use \silk\action\Response;

/**
 * Base class for controller classes to extend.
 **/
class Controller extends Object
{
	/**
	 * Whether or not a layout should be rendered at all
	 * If false, just the text from the action will be returned.
	 *
	 * @var boolean
	 */
	protected $show_layout = true;
	
	/**
	 * The name of the layout to use.  If empty, the global
	 * layout (layouts/default.tpl) will be used.
	 *
	 * @var string
	 */
	protected $layout_name = '';
	
	/**
	 * If you need to use a callback in order to generate the
	 * layout, it should be set here.
	 *
	 * @var callback
	 */
	protected $layout_callback = null;
	
	/**
	 * The name of the current action
	 *
	 * @var string
	 */
	protected $current_action = '';
	
	/**
	 * The type of the current request (GET, POST, etc.)
	 *
	 * @var string
	 */
	protected $request_method = '';
	
	/**
	 * An array of the params passed to run_action that were
	 * parsed from the $_REQUEST, route and route defaults.
	 *
	 * @var string
	 */
	protected $params = array();

	protected $variables = array();

	public function __construct()
	{
		parent::__construct();
	}
	
	protected $status = '200';
	
	function setStatusCode($code = '200')
	{
		$this->status = $code;
	}
	
	protected $headers = array();
	
	function addHeader($name, $value)
	{
		if ($name == '')
			$this->headers[] = $value;
		else
			$this->headers[$name] = $value;
	}
	
	protected $clear_headers = false;
	
	function clearHeaders()
	{
		$this->clear_headers = true;
		$this->headers = array();
	}

	public static function listControllers()
	{
		$controllers = array();

		$controller_dir = joinPath(ROOT_DIR, 'app', 'controllers');
		if (!is_dir($controller_dir))
			return $controllers;

		foreach (scandir($controller_dir) as $one_controller)
		{
			$filename = joinPath(ROOT_DIR, 'app', "controllers", $one_controller);
			if (is_file($filename) && substr($one_controller, 0, 1) != ".")
			{
				$controllers[] = $one_controller;
			}
		}
		return $controllers;
	}

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
	 **/
    public function runAction($action_name, $params = array())
	{
		$request = request();

		$this->current_action = $action_name;
		$this->request_method = $request->requestMethod();

		//Throw some variables into the application for URL helpers
		silk()->set('current_action', $this->current_action);
		silk()->set('current_controller', str_replace("Controller", "", get_class($this)));
		silk()->set('current_request_method', $this->requestMethod);
	
		if (isset($_REQUEST['is_silk_ajax']))
			$this->show_layout = false;
		else if (isset($params['show_layout']) && !$params['show_layout'])
			$this->show_layout = false;

		// Load api methods
		
		//Add the plugins directory for the app to smarty, if it
		//exists
		$plugin_dir = joinPath($this->getAppDirectory(), 'plugins');
		if (file_exists($plugin_dir)) 
		{
			if (!in_array($plugin_dir, smarty()->plugins_dir))
			{
				smarty()->plugins_dir[] = $plugin_dir;
			}
		}
		
		$this->params = $params;
		$this->set('params', $params);
		$this->setByRef('controller_obj', $this);

		//See if we should be loading the helper class
		if (file_exists($this->getHelperFullPath()))
		{
			include_once($this->getHelperFullPath());
			$name = $this->getHelperClassName();
			$helper = new $name;
			$helper->createSmartyPlugins();
		}

		$this->beforeFilter();
		
		$value = null;

		//See if a method exists in the controller that matches the action
		if (method_exists($this, $action_name))
		{
			@ob_start();
			$value = call_user_func_array(array($this, $action_name), array($params));
			$value .= trim(@ob_get_contents());
			@ob_end_clean();
		}

		//If nothing is returned (or there is no method in the controller), then we try the
		//default template and render that
		if ($value === null || empty($value))
		{
			$value = $this->renderTemplate($action_name, $params);
		}

		//Now put the value inside a layout, if necessary
		if ($this->show_layout)
		{
			$this->set('title', underscore(get_class($this)) . ' - ' . $action_name);
			$this->set('content', $value);
			$value = $this->renderLayout($value);
		}

		$this->afterFilter();
		
		$response = response();
		$response->setStatusCode($this->status);
		
		if ($this->clear_headers)
			$response->clear_headers();

		foreach ($this->headers as $k => $v)
		{
			$response->addHeader($k, $v);
		}

		$response->write($value);
	}

	/**
	 * Fetches the default template for an action of this controller.  It calculates
	 * the file location, loads it into smarty and returns the results.
	 *
	 * @param string The name of the action to display the view of
	 * @param array An array of parameters to send to the template.  This generally come
	 *        from the route processor.
	 * @return string
	 **/
    public function renderTemplate($action_name, $params = array())
	{
		if (config('template_handlers') != null)
		{
			foreach (config('template_handlers') as $k=>$v)
			{
				if (isset($v['extensions']))
				{
					foreach ($v['extensions'] as $k2=>$extension)
					{
						$path_to_template = joinPath($this->getTemplateDirectory(), underscore($action_name) . '.' . $extension);
						if (is_file($path_to_template))
						{
							//We have a hit, let's create the template handler
							if (class_exists($v['handler_class']) &&
								in_array('silk\display\template_handlers\TemplateHandlerInterface', class_implements($v['handler_class'])))
							{
								$handler = new $v['handler_class'];
								if ($handler)
								{
									//Inject dependencies
									$handler->setController($this);
									$handler->setVariables($this->variables);

									//And we're off!
									return $handler->processTemplateFromFile($path_to_template);
								}
							}
						}
					}
				}
			}
		}

		throw new \silk\action\ViewNotFoundException('Template file for action \'' . $action_name . '\' does not exist');
	}
	
	/**
	 * Render another template inside of this controller's view directory.  Generally
	 * used in situations where form and other reuse is needed between actions.  It's
	 * also very handy when programming ajax actions.
	 *
	 * @param string $template_name The name of the template to render
	 * @return string
	 */
    public function renderPartial($template_name, $params = array())
	{
		$path_to_template = joinPath($this->getTemplateDirectory(), $template_name);
		if (is_file($path_to_template))
		{
			foreach ($params as $k=>$v)
			{
				smarty()->assign($k, $v);
			}
			return smarty()->fetch("file:{$path_to_template}");
		}
		
		return '';
	}

	/**
	 * If a layout is set on the controller, this will render the layout directly
	 * and return it.  Smarty parameters for the layout must be set before-hand.
	 * If no layout is found or being used, this will return the original $value
	 * string.
	 *
	 * @param string $value Content to be displayed if no layout is found.
	 * @return void The rendered output, or the original $value if no layout is to be used.
	 */
    public function renderLayout($value)
	{
		if ($this->layout_callback != null)
		{
			return call_user_func_array($this->layout_callback, array($this->current_action, $this->params, $this));
		}
		else
		{
			if (config('template_handlers') != null)
			{
				foreach (config('template_handlers') as $k=>$v)
				{
					if (isset($v['extensions']))
					{
						foreach ($v['extensions'] as $k2=>$extension)
						{
							$path_to_template = joinPath($this->getLayoutDirectory(), 'default.' . $extension);
							if ($this->layout_name != '')
							{
								$path_to_template = joinPath($this->getLayoutDirectory(), $this->layout_name . '.' . $extension);
							}
							if (is_file($path_to_template))
							{
								//We have a hit, let's create the template handler
								if (class_exists($v['handler_class']) &&
									in_array('silk\display\template_handlers\TemplateHandlerInterface', class_implements($v['handler_class'])))
								{
									$handler = new $v['handler_class'];
									if ($handler)
									{
										//Inject dependencies
										$handler->setController($this);
										$handler->setVariables($this->variables);

										//And we're off!
										return $handler->processTemplateFromFile($path_to_template);
									}
								}
							}
						}
					}
				}
			}
		}

		return $value;
	}
	
    public function getTemplateDirectory()
	{
		$default_template_dir = str_replace('_controller', '', underscore(get_class($this)));
		return joinPath($this->getAppDirectory(), 'views', $default_template_dir);
	}

	/**
	 * Returns the directory where this controller lives
	 *
	 * @return string
	 */
    public function getControllerDirectory()
	{
		$ref = new \ReflectionClass($this);
		return dirname($ref->getFilename());
	}

	/**
	 * Returns the directory where the app's layout templates live
	 *
	 * @return string
	 */
    public function getLayoutDirectory()
	{
		return joinPath($this->getAppDirectory(), 'layouts');
	}
	
	/**
	 * Returns the directory where this controller's helper lives
	 *
	 * @return string
	 */
    public function getHelperDirectory()
	{
		return joinPath($this->getAppDirectory(), 'helpers');
	}
	
	/**
	 * Returns the class name of this controller's helper class
	 *
	 * @return string
	 */
    public function getHelperClassName()
	{
		return str_replace('Controller', 'Helper', get_class($this));
	}
	
	/**
	 * Returns the filename of this controller's helper class
	 *
	 * @return string
	 */
    public function getHelperFilename()
	{
		$ref = new \ReflectionClass($this);
		return str_replace('controller', 'helper', basename($ref->getFilename()));
	}
	
	/**
	 * Returns the full path where the helper class
	 *
	 * @return string The filename of the helper class
	 */
    public function getHelperFullPath()
	{
		return joinPath($this->getHelperDirectory(), $this->getHelperFilename());
	}

	/**
	 * Returns the directory of the app where this
	 * controller lives.
	 *
	 * @return string
	 */
	public function getAppDirectory()
	{
		return dirname($this->getControllerDirectory());
	}

	/**
	 * Sets a value in the smarty instnace for use in the template
	 * for display or logic.
	 *
	 * @param string The name of the variable to set.
	 * @param mixed The value to set for that named variable
	 * @return void
	 **/
	public function set($name, $value)
	{
		$this->variables[$name] = $value;
		//smarty()->assign($name, $value);
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
	 **/
	public function setByRef($name, &$value)
	{
		$this->variables[$name] = $value;
		//smarty()->assignByRef($name, $value);
	}

	/**
	 * Callback function to run before calling the "action" method of the
	 * controller.
	 *
	 * @return void
	 **/
	public function beforeFilter()
	{

	}

	/**
	 * Callback function to run after calling the "action" method of the
	 * controller.
	 *
	 * @return void
	 **/
	public function afterFilter()
	{

	}

	public function __get($name)
	{
		/*
		if ($name == 'flash')
		{
			return \SilkFlash::get_instance()->get('std');
		}
		 */
		return false;
	}
	
	public function __set($name, $val)
	{
		/*
		if ($name == 'flash')
		{
			\SilkFlash::get_instance()->set('std', $val);
			return true;
		}
		 */
		return false;
	}

	public function flash($store = 'std')
	{
		return \SilkFlash::getInstance()->get($store);
	}
	
	public function setFlash($store = 'std', $val)
	{
		return \SilkFlash::getInstance()->set($store, $val);
	}
	
	/**
	 * Check to see if the current request is a GET request
	 *
	 * @return boolean Whether or not this is a GET request
	 */
	public function isGet()
	{
		return ($this->request_method == 'GET');
	}
	
	/**
	 * Check to see if the current request is a POST request
	 *
	 * @return boolean Whether or not this is a POST request
	 */
	public function isPost()
	{
		return ($this->request_method == 'POST');
	}
	
	/**
	 * Check to see if the current request is a PUT request
	 *
	 * @return boolean Whether or not this is a PUT request
	 */
	public function isPut()
	{
		return ($this->request_method == 'PUT');
	}
	
	/**
	 * Check to see if the current request is a DELETE request
	 *
	 * @return boolean Whether or not this is a DELETE request
	 */
	public function isDelete()
	{
		return ($this->request_method == 'DELETE');
	}
	
	/**
	 * Allow you to quickly take a boolean and check it against the current
	 * action.  If it's still false, then you can either have it call a
	 * callback function of some sort, or it will throw a SilkAccessException.
	 *
	 * @param boolean $boolean The original check value
	 * @param array $action_filter A hash of filters.  Currently accepts "only" and
	 *                             "except", with their values being an array of action
	 *                             names.
	 * @param function $fail_callback An optional callback to call if access is still false
	 *                                after filtering.  If a string, will call this method 
	 *                                on the current controller.  If an array, this will be
	 *                                passed directly as a callback.
	 * @return boolean Whether or not is access check is successful
	 */
	public function checkAccess($boolean, $action_filter = array(), $fail_callback = null)
	{
		$access = $boolean;
		
		//If we have an only key, then we check against that and 
		//automatically set to true if it's not in the list
		if (array_key_exists('only', $action_filter))
		{
			if (is_string($action_filter['only']))
			{
				$action_filter['only'] = array($action_filter['only']);
			}
			
			if (!in_array($this->current_action, $action_filter['only']))
			{
				$access = true;
			}
		}
		
		//If we have an except key then we check against that and
		//automatically set to true if it IS in the list
		if (array_key_exists('except', $action_filter))
		{
			if (is_string($action_filter['except']))
			{
				$action_filter['except'] = array($action_filter['except']);
			}
			
			if (in_array($this->current_action, $action_filter['except']))
			{
				$access = true;
			}
		}
		
		if (!$access)
		{
			//If access is still false and we have a callback, call it
			if ($fail_callback !== null)
			{
				//If the callback is a string, convert it to $this->callback
				//instead
				if (is_string($fail_callback))
					$fail_callback = array($this, $fail_callback);
				
				call_user_func_array($fail_callback, array());
			}
			else
			{
				$ex = new SilkAccessException();
				$ex->controller = $this->params['controller'];
				$ex->action = $this->current_action;
				throw $ex;
			}
		}
		
		return $access;
	}
}

class SilkAccessException extends \Exception
{
	var $controller = '';
	var $action = '';
	
	public function __toString()
	{
		return __CLASS__ . " -- controller: {$this->controller} -- action: {$this->action}";
	}
}

# vim:ts=4 sw=4 noet
