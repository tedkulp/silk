<?php

/**
	A placeholder just in case.
*/
abstract class SilkTask extends SilkObject {

	/**
		Main routine for Task. Called automatically when task is called, eg silk.php taskname.
	*/
	public abstract function run($args, $flags, $options); 

	public function help() {
		echo $this->usage() . "\n\n";
		echo $this->description() . "\n";	
	}
	

	public function description() {
			$output = <<<EOF
Task: taskname

Description:
Description of task.

Commands:
command1 - silk.php taskname command1
Description of command 1

command2 - silk.php taskname command2
Description of command 1

EOF;
	}
	public function usage() {
		return <<<EOF
Usage: taskname [-e|-g][optional_param] required
EOF;
	} 
	


}

?>
