<?php

/**
 * @package SilkExceptions
 * Some code is simply not suitable for a production environment. Debugging statements, 
 * This exception should be thrown when unsafe code is used in anything but a debugging
 * environment. 
 */
	class SilkNotProductionSafeException extends RuntimeException {
		public function __construct($function, $code = 0) {
			$message = $function . "() should not be used in a production environment"; 
			return parent::__construct($message, $code);
		}
	}

?>
