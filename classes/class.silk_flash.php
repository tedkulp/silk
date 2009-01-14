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

class SilkFlash extends SilkObject
{
	static private $instance = NULL;
	private $stores = array();

	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Returns an instnace of the SilkFlash singleton.
	 *
	 * @return SilkFlash The singleton SilkFlash instance
	 * @author Ted Kulp
	 **/
	static public function get_instance()
	{
		if (self::$instance == NULL)
		{
			self::$instance = new SilkFlash();
		}
		return self::$instance;
	}
	
	function get($name)
	{
		$val = '';
		if (isset($_SESSION['silk_flash_store'][$name]))
		{
			$val = $_SESSION['silk_flash_store'][$name];
			unset($_SESSION['silk_flash_store'][$name]);
		}
		return $val;
	}
	
	function __get($name)
	{
		$this->get($name);
	}
	
	function set($name, $val)
	{
		$_SESSION['silk_flash_store'][$name] = $val;
	}
	
	function __set($name, $val)
	{
		$this->set($name, $val);
	}
}

# vim:ts=4 sw=4 noet
?>