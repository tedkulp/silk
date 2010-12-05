<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008-2010 Ted Kulp
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

use \silk\auth\UserSession;

/**
 * Wraps the DateTime class in PHP 5.2+.  It allows us to have a consistent
 * object for handling dates and is especially useful when handing dates over
 * to the ORM system.  Includes methods for manipulating the stored date and
 * time.
 *
 * @since 1.0
 * @author Ted Kulp
 **/
class SilkDateTime extends \silk\core\Object
{
	private $datetime = null;

	function __construct($curtime = 'now')
	{
		if (is_int($curtime))
			$curtime = strftime('%x %X', $curtime);

		try
		{
			$this->datetime = date_create($curtime);
		}
		catch (Exception $e)
		{
			$this->datetime = date_create('now');
		}
	}
	
	function __toString()
	{
		return $this->to_format_string();
	}
	
	function modify($modify)
	{
		date_modify($this->datetime, $modify);
	}
	
	function format($format)
	{
		try
		{
			return @date_format($this->datetime, $format);
		}
		catch (Exception $e)
		{
			return '';
		}
	}
	
	function strftime($format)
	{
		try
		{
			return @strftime($format, $this->datetime->format('U'));
		}
		catch (Exception $e)
		{
			return '';
		}
	}
	
	function timestamp()
	{
		return $this->format('U');
	}

	/**
	 * Returns a formatted string based on the individual user's settings.
	 * If no one is logged in, then a default based on locale is used.
	 *
	 * @return String The formatted datetime string
	 * @author Ted Kulp
	 **/
	function to_format_string($format = '%x %X')
	{
		return $this->strftime($format);
	}
	
	/**
	 * Returns a formating string based on the current database connection.
	 *
	 * @return String the formatted datetime string
	 * @author Ted Kulp
	 */
	function to_sql_string()
	{
		return db()->timeStamp($this->timestamp());
	}
}

# vim:ts=4 sw=4 noet
