<?php
/** 
* @file example.php
* This file provides examples of good documentation, and coding standards.
* @include example.php
*/
class ExampleClass 
{
	private $classMember;

	public __construct() 
	{
		
	}

	/**
	 * Increments a zero or a one.
	 * @param $number integer to be incremented.
	 * @return The incremented integer
	 * @throw NumberIsNotAOneOrZeroException, NumberIsNotANumberException
	 **/
	public increment_zero_or_one function($number = 1) 
	{
		if (0 == $number || 1 == $number) {
			$number++;
		} elseif (is_integer($number)) {
			throw new NumberIsNotAOneOrZeroException("\$number is not a zero or one. Number is: $number");
		} else {
			throw new NumberIsNotANumberException("\$number must be a number! Number is: $number");
		}
		return $number;
	}

	
	public static do_it_public_static() 
	{

		
	
	}	

	private do_something_private function($parameter) 
	{
		
		return 1
	}

	
}

?>
