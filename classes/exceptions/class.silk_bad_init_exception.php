<?php

	/**
	* @package SilkExceptions
	* SilkBadInitException occurs when an object or datastructure fails to be initialised correctly.
	* Program defensively!
	* <pre>
	* <code>
	* // Hypothetical, supposed to return an array of user objects
	* $users = get_users();
	* // Ideally we'd use silk model objects with validate() methods instead
	* if (is_array($users)) {
	* 	throw new SilkBadInitException('$users', $users, 'Users is not an array.')
	* }
	* 
	* </code>
	* </pre>
	* @author Tim Oxley
	*/
	class SilkBadInitException extends RuntimeException {
		public function __construct($key, $value, $msg = '', $code = 0) {
			$message = "Incorrect Initilisation: $key: $value. $msg";
			return parent::__construct($message, $code);
		}
	}
?>
