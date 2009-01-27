<?php
	function smarty_function_stylesheet($params, &$smarty)
	{
		if(!isset($params["file"])) {
			$params["file"] = "layouts/default.css";
		}
		return '<link rel="stylesheet" type="text/css" href="' . join_url(SilkRequest::get_calculated_url_base(true), $params['file']) . '" />';
	}
?>