<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
#CMS - CMS Made Simple
#(c)2004-2010 by Ted Kulp (ted@cmsmadesimple.org)
#This project's homepage is: http://cmsmadesimple.org
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

namespace silk\test;

require_once 'PHPUnit/Autoload.php';

class TestCase extends \PHPUnit_Framework_TestCase
{
	private $_addons = null;
	
	function __construct($label = false)
	{
		parent::__construct($label);
		//$this->_addons = new CmsTestCaseAddons($this);
	}

	public function testNothing()
	{
		//Make phpunit happy...
		//We don't need to actually test anything since it's
		//a parent class.
		$this->assertEquals(1, 1);
	}
	
	/*
	function __call($function_name, $args)
	{
		if (method_exists($this->_addons, $function_name))
		{
			return call_user_func_array(array($this->_addons, $function_name), $args);
		}
		return false;
	}
	*/
}

# vim:ts=4 sw=4 noet
