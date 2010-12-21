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

require_once(dirname(dirname(dirname(__FILE__))) . '/silk.api.php');

use \silk\test\TestCase;
use \silk\database\datamapper\DataMapper;

class TestSuiteTest extends TestCase
{
	var $_fixtures = array('test_suite');

	public function testRun()
	{
		$this->assertEquals(true, 1==1);
		$this->assertNotEquals(false, 1==1);
	}

	public function testFixture()
	{
		$model = new TestModel();
		$test_suite = $model->first(array('id' => 1), true);
		$this->assertNotNull($test_suite);
		$this->assertEquals('Test Field', $test_suite->test_field);
		$this->assertInstanceOf('SilkDateTime', $test_suite->create_date);
		$this->assertInstanceOf('SilkDateTime', $test_suite->modified_date);

		$test_suite = $model->first(array('id' => 2), true);
		$this->assertNotNull($test_suite);
		$this->assertEquals('Test Field Again', $test_suite->test_field);
		$this->assertInstanceOf('SilkDateTime', $test_suite->create_date);
		$this->assertInstanceOf('SilkDateTime', $test_suite->modified_date);
	}
}

class TestModel extends DataMapper
{
	var $_fields = array(
		'id' => array(
			'type' => 'int',
			'primary' => true,
			'serial' => true,
		),
		'test_field' => array(
			'type' => 'string',
			'length' => 255,
			'required' => true,
		),
		'create_date' => array(
			'type' => 'create_date',
		),
		'modified_date' => array(
			'type' => 'modified_date',
		),
	);
}

# vim:ts=4 sw=4 noet
