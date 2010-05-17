<?php

namespace silk\exception;

class InvalidKeyException extends \Exception
{
	// Redefine the exception so message isn't optional
	public function __construct($message = null, $code = 0)
	{
		if ($message != null)
			$message = "Invalid Key: " . $message;

		parent::__construct($message, $code);
	}
}

?>