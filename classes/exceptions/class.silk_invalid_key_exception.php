<?php

    /**
	 * @package SilkExceptions
	 * Occurs when an array key is not valid in the current context 
	 * @author Ted Kulp
     */
	class SilkInvalidKeyException extends DomainException {
		public function __construct($key, $code = 0) {
			$message = "Invalid Key: $key ";
			parent::__construct($message, $code);
		}
	}
?>
