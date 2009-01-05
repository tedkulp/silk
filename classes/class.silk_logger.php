<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008 Ted Kulp
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

include_once(join_path(SILK_LIB_DIR, 'pear', 'log', 'Log.php'));

/**
 * Logger utility.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkLogger extends SilkObject
{
	static private $instances = null;
	
	function __construct()
	{
		parent::__construct();
	}
	
	public static function get_instance($handler = 'file', $name = '')
	{
		if (self::$instances == null)
		{
			self::$instances = array();
		}
		
		$sum = md5($handler . $name);

		if (empty(self::$instances[$sum]))
		{
			self::$instances[$sum] = Log::factory($handler, $name);
		}

		return self::$instances[$sum];
	}
}

# vim:ts=4 sw=4 noet
?>