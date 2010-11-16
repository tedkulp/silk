<?php

use \silk\test\TestSuite;

class SilkTestTask extends SilkTask {

	/* interchange methods to task can be run from console as well as commandline */
	
	public function __construct()
	{
		$this->addOption('system', array(
			'long_name' => '--system',
			'description' => 'Runs the Silk Framework tests',
			'action' => 'StoreTrue',
			'final' => false
			)
		);
		
		$this->addArgument('args', array(
			'multiple' => true,
			'optional' => true,
			)
		);
		
		return parent::__construct(array(
			'name' => 'Test Task',
			'description' => "A Test Task for Tests and/or Testing",
			'version' => '0.0.2'
			)
		);
	}

	public function run($argc, $argv)
	{ 
		try
		{
			$result = $this->parse($argc, $argv);

			//var_dump($result);
			
			if ($result->options['system'] == true)
			{
				echo "\nRunning Silk System tests.\n\n";
				$test_suite = new OurSystemTestSuite();
			}
			else
			{
				echo "Not Implemented Yet\n";
			}
		}
		catch (Exception $exc)
		{
			$this->displayError($exc->getMessage());
		}
	}
}

class OurSystemTestSuite extends TestSuite
{
	function __construct()
	{
		parent::__construct();
		$this->collect(join_path(SILK_LIB_DIR, 'test'), new SimplePatternCollector('/php$/'));
		$this->collect(join_path(SILK_LIB_DIR, 'test', 'orm'), new SimplePatternCollector('/php$/'));
	}
}

# vim:ts=4 sw=4 noet