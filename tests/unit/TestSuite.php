<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008-2011 Ted Kulp
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

class TestSuiteTest extends TestCase
{
	var $_fixtures = array('TestSuite');

	public function testRun()
	{
		$this->assertEquals(true, 1==1);
		$this->assertNotEquals(false, 1==1);
	}

	public function testFixture()
	{
		if (\silk\database\Database::isMongoDb())
		{
			$test_suite = TestModelOdm::load(1);
			$this->assertNotNull($test_suite);
			$this->assertEquals('Test Field', $test_suite['testField']);
			$this->assertInstanceOf('DateTime', $test_suite['createDate']);
			$this->assertInstanceOf('DateTime', $test_suite['modifiedDate']);

			$test_suite = TestModelOdm::load(2);
			$this->assertNotNull($test_suite);
			$this->assertEquals('Test Field Again', $test_suite['testField']);
			$this->assertInstanceOf('DateTime', $test_suite['createDate']);
			$this->assertInstanceOf('DateTime', $test_suite['modifiedDate']);
		}
		else
		{
			$test_suite = TestModel::load(1);
			$this->assertNotNull($test_suite);
			$this->assertEquals('Test Field', $test_suite['testField']);
			$this->assertInstanceOf('DateTime', $test_suite['createDate']);
			$this->assertInstanceOf('DateTime', $test_suite['modifiedDate']);

			$test_suite = TestModel::load(2);
			$this->assertNotNull($test_suite);
			$this->assertEquals('Test Field Again', $test_suite['testField']);
			$this->assertInstanceOf('DateTime', $test_suite['createDate']);
			$this->assertInstanceOf('DateTime', $test_suite['modifiedDate']);
		}
	}
}

/**
 * @Entity
 * @Table(name="test_data_mapper_table")
 */
class TestModel extends \silk\model\Model
{
	/**
	 * @Id @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id;

	/**
	 * @Column
	 * @Validation:NotEmpty
	 */
	protected $testField;

	/**
	 * @Column(type="datetime")
	 */
	protected $createDate;

	/**
	 * @Column(type="datetime")
	 */
	protected $modifiedDate;
}

/**
 * @Document(collection="test_data_mapper_table")
 */
class TestModelOdm extends \silk\model\Model
{
	/**
	 * @Id(strategy="NONE")
	 */
	protected $id;

	/**
	 * @Field
	 * @Validation:NotEmpty
	 */
	protected $testField;

	/**
	 * @Field(type="date")
	 */
	protected $createDate;

	/**
	 * @Field(type="date")
	 */
	protected $modifiedDate;
}

# vim:ts=4 sw=4 noet
