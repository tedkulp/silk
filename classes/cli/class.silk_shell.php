<?php

require_once SILK_LIB_DIR .'/pear/php_shell/Shell.php';

class SilkShell extends PHP_Shell implements SilkSingleton {

	public function __construct()
	{
		parent::__construct();
	}
	
	public static function get_instance()
	{
		static $__shell = null;
		if (null == $__shell)
		{
			$class_name = get_class();
			$__shell = new $class_name;

			$cmd = PHP_Shell_Commands::getInstance();
			$cmd->registerCommand('#^silk\s?(.*)?#', $__shell, 'cmdSilk', 'silk', 'Runs silk commands. Try silk --help.');

			@ob_end_clean();
			error_reporting(E_ALL);
			set_time_limit(0);
				
			// Welcome Message	
			$f = "Silk Console Interface\nVersion %s%s\n>> use '?' to open the inline help\n";
			
			printf($f,
				$__shell->getVersion(),
				$__shell->hasReadline() ? ', with readline() support' : '');
			unset($f);
		}

		return $__shell;
	}

	/**
	 * Silk cli callback. Forwards commands from commandline to the SilkCli. 
	 **/
	public function cmdSilk($params = null)
	{
		//SilkCli 'Singleton'
		static $silkCli = null;
		if (null == $silkCli)
		{
			$silkCli = SilkCli::get_instance();
		}
		$argc = 0;
		$argv = array();

		// Build argv from the words provided on the console commandline.
		if (isset($params))
		{
			$argv = explode(' ', $params);
			$argc = count($argv);
		}

		$silkCli->run($argc, $argv);
	}

	public function __clone()
	{
		
	}
	
	public function __wakeup()
	{
		
	}
} 

?>
