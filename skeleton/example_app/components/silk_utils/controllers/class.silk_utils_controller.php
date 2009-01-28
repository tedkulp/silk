<?php

class SilkUtilsController extends SilkControllerBase {
	
	function show_vars() {
		$this->mydump("Class Dirs", $GLOBALS["class_dirs"]);
		$this->mydump("Routes", SilkRoute::get_routes());
	}
	
	function mydump($myname, $var) {
		echo "Name: $myname<pre>";
		var_dump($var);
		echo "</pre>";
	}
	
	function index() {
		echo "Here is the index function<br />";
	}
}
?>