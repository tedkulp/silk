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
	protected $_fixtures = array();
	
	function __construct($label = false)
	{
		parent::__construct($label);
		//$this->_addons = new CmsTestCaseAddons($this);
	}

	public function setUp()
	{
		$this->runFixtureMethod('setup');

		if (method_exists($this, 'before_test'))
		{
			$this->before_test();
		}
	}
	public function tearDown()
	{
		$this->runFixtureMethod('teardown');

		if (method_exists($this, 'after_test'))
		{
			$this->after_test();
		}
	}

	public function runFixtureMethod($name = 'setup')
	{
		if (isset($this->_fixtures) && is_array($this->_fixtures))
		{
			foreach($this->_fixtures as $one_fixture)
			{
				//Loads us up a fixture
				//(if we can)
				
				if (SILK_TEST_DIR)
				{
					$filename = join_path(SILK_TEST_DIR, 'fixtures', 'fixture.' . $one_fixture . '.php');
					if (is_file($filename))
					{
						@include_once($filename);
						$class_name = camelize($one_fixture) . 'Fixture';
						$fixture_class = new $class_name;
						if ($fixture_class)
							$fixture_class->{$name}();
					}
				}
			}
		}
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

# vim:ts4 sw=4 noet
