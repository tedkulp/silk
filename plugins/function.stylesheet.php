<?php
	function smarty_function_stylesheet($params, &$smarty)
	{
		if(!isset($params["file"])) {
			$params["file"] = "layouts/default.css";
		}
		return '<link rel="stylesheet" type="text/css" href="' . join_url(\silk\action\Request::get_calculated_url_base(true), $params['file']) . '" />';
	}
?>