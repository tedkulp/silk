<?php

class SilkTestTask extends SilkTask {

	/* interchange methods to task can be run from console as well as commandline */

	public function __construct() {
		$this->addOption('tester', array(
				'short_name'  => '-t',
				'long_name'   => '--tests',
				'description' => 'Tests Options',
				'action'      => 'StoreTrue',
				'final'		  => false
		));
		$this->addArgument('testarg');
		return parent::__construct(array(
			'name' => 'Test Task',
			'description' => "A Test Task for Tests and/or Testing",
			'version'     => '0.0.2'
		));
	}

	public function run($argc, $argv) { 
		try {
			$result = $this->parse($argc, $argv);

            echo "Result is \n";
            var_export($result);
            if (isset($result->args['tester']) && $result->args['tester'] == true) {
                echo "\nTest Option is: " . $result->args['test'] . "\n";
            }
		} catch (Exception $exc) {
			$this->displayError($exc->getMessage());
		}
	}
}

?>
