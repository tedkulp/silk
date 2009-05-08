<?php

class SilkConsoleTask extends SilkTask implements SilkSingleton {
	
	public $running = false;
	public function __construct() {
			parent::__construct(array(
						'name' => 'Console to the Silk Framework',
						'description' => "Console interface to access various \"tasks\" related to the development and maintenance of applications written using the silk framework.  Tasks are a dynamic system and can be added to and removed at will.  For a list of current tasks use the --list option. To get help for a specific task, use: silk task --help.",
						'version'     => '0.0.1'));
	}

	public static function get_instance()
	{
		static $parser = null;

		if (null == $parser)
		{
			$class_name = get_class();
			$parser = new $class_name;
		}

		return $parser;
	}

	public function run($argc, $argv) { 
		
	//	$parser = new CommandLine
//		self::init();
		// temporary solution so --help & --version options actually quit, 
		// rather than print then start the console.
			
//		if (in_array('--version', $argv) || in_array('--help', $argv)) {
//			$this->parse();	
//			return;	
//		}

		$shell = SilkShell::get_instance();
		// Main program loop.
		while($shell->input()) {
			try {
				if ($shell->parse($argc, $argv) == 0) {

					## we have a full command, execute it
					$shell_retval = eval($shell->getCode());
					if (isset($shell_retval)) {
						echo($shell_retval);
					}
					## cleanup the variable namespace
					unset($shell_retval);
					$shell->resetCode();
				}
			} catch(Exception $shell_exception) {
			//	ob_start();
				print $shell_exception->getMessage()."\n";
				
				echo "\nException on line ".$shell_exception->getLine()."\n";
				echo "In file " . $shell_exception->getFile()."\n";
				print $shell_exception->getTraceAsString();
			//	throw new Exception(ob_get_clean());
				
				$shell->resetCode();
		
				## cleanup the variable namespace
				unset($shell_exception);
			}
		}
	}
	
	public function __clone()
	{
		
	}
	
	public function __wakeup()
	{
		
	}
}

?>
