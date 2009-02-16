<?php

require_once SILK_LIB_DIR .'/pear/php_shell/Shell.php';

class SilkShell extends PHP_Shell {

	public function __construct() {
		parent::__construct();
				
        $cmd = PHP_Shell_Commands::getInstance();

        $cmd->registerCommand('#^silk\s?(.*)?#', $this, 'cmdSilk', 'silk', 'Runs silk commands. Try silk --help.');
	}

	public function cmdSilk($params = null) {
		$argc = 0;
		$argv = array();
		if (isset($params[0])) {
			$argv = explode(' ', $params[0]);
			$argc = count($argv);
		}
		$cli = new SilkCli($argc, $argv);
		$cli->run();
	}
		
} 

?>
