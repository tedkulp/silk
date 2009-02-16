<?php

class SilkConsoleTask extends SilkTask {
	
	private static $__shell = NULL;

/* 	public static function get___shell() {	
		if (self::$__shell == NULL) {
			self::$__shell = new SilkApplication();
		}

		return self::$__shell;
	}
*/	
	private static function init() {
		if (self::$__shell == NULL) {
			self::$__shell = new SilkShell();
		}
		@ob_end_clean();
		error_reporting(E_ALL);
		set_time_limit(0);
			
		// Welcome Message	
		$f = <<<EOF
PHP-Barebone-Shell - Version %s%s
(c) 2006, Jan Kneschke <jan@kneschke.de>

>> use '?' to open the inline help

EOF;
		
		printf($f,
			self::$__shell->getVersion(),
			self::$__shell->hasReadline() ? ', with readline() support' : '');
		unset($f);
	}

	public function run($args, $flags, $options) { 
		self::init();

		if (! is_a(self::$__shell, 'SilkShell')) {
			throw new UnexpectedValueException("self::\$__shell not properly initialised. " .self::$__shell);
		}

		$__shell = self::$__shell;
		// Main program loop.
		while($__shell->input()) {
			try {
				if ($__shell->parse() == 0) {
					## we have a full command, execute it
		
					$__shell_retval = eval($__shell->getCode());
					if (isset($__shell_retval)) {
						echo($__shell_retval);
					}
					## cleanup the variable namespace
					unset($__shell_retval);
					$__shell->resetCode();
				}
			} catch(Exception $__shell_exception) {
				print $__shell_exception->getTraceAsString();
				
				$__shell->resetCode();
		
				## cleanup the variable namespace
				unset($__shell_exception);
			}
		}
	}
}

?>
