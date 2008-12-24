<?php

class SilkControllerBase extends SilkObject
{
	function run_action($action_name, $params = array())
	{
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
			return $this->render_default_template($action_name, $params);
		}
	}
	
	function render_default_template($action_name, $params = array())
	{
		$default_template_dir = str_replace('_controller', '', underscore(get_class($this)));
		$path_to_default_template = join_path(ROOT_DIR, 'app', 'views', $default_template_dir, $action_name . '.tpl');
		return smarty()->fetch("file:{$path_to_default_template}");
	}
	
	function set($name, $value)
	{
		smarty()->assign($name, $value);
	}
	
	function set_by_ref($name, &$value)
	{
		smarty()->assign_by_ref($name, $value);
	}
}

?>