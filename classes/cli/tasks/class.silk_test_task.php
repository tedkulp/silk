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

			if ($result->options['system'] == true)
			{
				echo "\nRunning Silk System tests.\n\n";
				$test_suite = new OurSystemTestSuite();
			}
			else
			{
				echo "Not Implemented Yet\n";

				//Hack to make sure the TestSuite below
				//doesn't get used
				$context = SimpleTest::getContext();
				$context->setTest("ignore me");
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

		$pattern = '/test\..*php$/';
		$path = join_path(SILK_LIB_DIR, 'test');

		$dirs = array($path);

		$count = 0;
		$it = new RecursiveDirectoryIterator($path);
		while ($it->valid())
		{
			if (!$it->isDot() && $it->isDir())
			{
				if (!in_array($it->getPathname(), $dirs))
				{
					$dirs[] = $it->getPathname();
				}
			}
			$it->next();
			$count++;
		}

		foreach ($dirs as $one_dir)
		{
			echo "adding path: " . $one_dir . "\n";
			$this->collect($one_dir, new SimplePatternCollector($pattern));
		}
	}
}

# vim:ts=4 sw=4 noet
