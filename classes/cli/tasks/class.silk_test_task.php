<?php

class SilkTestTask extends SilkTask {
	public function run($args, $flags, $options) { 
		echo 'returning...';
		//return 1;
	//	die('dying');
		echo "shouldn't appear";
	}
}

?>
