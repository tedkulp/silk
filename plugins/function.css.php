<?php
	function smarty_function_css($params, &$smarty)
	{
		if(!isset($params["css"])) {
			$params["css"] = "default";
		}
		$css_file = join_path(ROOT_DIR,"layouts",$params["css"].".css");
		if(file_exists($css_file)) {
			return file_get_contents($css_file);
		}
	}

?>