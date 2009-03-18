<?php

	/**
	* @package SilkExceptions
	* SilkFileNotFoundException occurs when a file cannot be found.
	* @author Tim Oxley
	*/
	class SilkFileNotFoundException extends RuntimeException {
		public function __construct($filename, $code = 0) {
			$message = "File not found: $filename ";
			return parent::__construct($message, $code);
		}
	}
?>
