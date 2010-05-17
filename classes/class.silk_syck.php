<?php

require_once(join_path(SILK_LIB_DIR, 'syck', 'Yaml.php'));
require_once(join_path(SILK_LIB_DIR, 'syck', 'Yaml', 'Dumper.php'));
require_once(join_path(SILK_LIB_DIR, 'syck', 'Yaml', 'Exception.php'));
require_once(join_path(SILK_LIB_DIR, 'syck', 'Yaml', 'Loader.php'));
require_once(join_path(SILK_LIB_DIR, 'syck', 'Yaml', 'Node.php'));

class SilkSyck extends \silk\core\Object {
	function __construct()
	{
		parent::__construct();
	}
	
	function loadFile($filename) {
		return Horde_Yaml::loadFile($filename);
	}
	
	function dump($ary) {
		return Horde_Yaml::dump($ary);
	}

}
?>