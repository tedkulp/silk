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

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/silk.api.php');

use \silk\test\TestCase;
use \silk\database\Database;
use \silk\database\datamapper\DataMapper;
use \silk\performance\Cache;
use \SilkDateTime;

class DataMapperTest extends TestCase
{
	public function setUp()
	{
		$this->tearDown();
		
		$test_orm = new TestDataMapperTable();
		$test_orm->migrate();
		
		$test_orm_child = new TestDataMapperTableChild();
		$test_orm_child->migrate();
		
		$pdo = Database::get_instance();
		
		$pdo->execute_sql("INSERT INTO {test_data_mapper_table} (test_field, another_test_field, some_int, some_float, create_date, modified_date) VALUES ('test', 'blah', 5, 5.501, now() - 10, now() - 10)");
		$pdo->execute_sql("INSERT INTO {test_data_mapper_table} (test_field, create_date, modified_date) VALUES ('test2', now(), now())");
		$pdo->execute_sql("INSERT INTO {test_data_mapper_table} (test_field, create_date, modified_date) VALUES ('test3', now(), now())");
		
		$pdo->execute_sql("INSERT INTO {test_data_mapper_table_child} (parent_id, some_other_field, create_date, modified_date) VALUES (1, 'test', now(), now())");

		$pdo->execute_sql("INSERT INTO {has_and_belongs_to_many} (parent_id, child_id, create_date, modified_date) VALUES (1, 1, now(), now())");
		$pdo->execute_sql("INSERT INTO {has_and_belongs_to_many} (parent_id, child_id, create_date, modified_date) VALUES (2, 1, now(), now())");
	}
	
	public function tearDown()
	{
		$pdo = Database::get_instance();
		
		$pdo->drop_table('has_and_belongs_to_many');
		$pdo->drop_table('test_data_mapper_table_child');
		$pdo->drop_table('test_data_mapper_table');
		
		Cache::clear();
	}
	
	public function testGetTableShouldKnowFancyPrefixStuff()
	{
		$test_orm = new TestDataMapperTable();
		
		$this->assertEquals(db_prefix() . 'test_data_mapper_table', $test_orm->get_table());
		$this->assertEquals(db_prefix() . 'test_data_mapper_table.test_field', $test_orm->get_table('test_field'));
	}
	
	public function testFindAllShouldReturnAllRows()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->all()->execute();
		
		$this->assertInternalType('array', $result);
		$this->assertEquals(3, count($result));
	}
	
	public function testFindOneShouldReturnOneRow()
	{
		$test_orm = new TestDataMapperTable();
		
		$result = $test_orm->first()->execute();
		$this->assertInstanceOf('TestDataMapperTable', $result);
		$this->assertEquals(1, count($result));
	}
	
	/*
	public function testFindCountShouldReturnACountDuh()
	{
		$result = cms_orm('test_data_mapper_table')->find_count();
		$this->assertEquals(3, $result);
	}
	*/
	
	public function testDateTimeShouldBeADateTimeObject()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$this->assertNotInstanceOf('SilkDateTime', $result->test_field);
		$this->assertInstanceOf('SilkDateTime', $result->create_date);
		$this->assertInstanceOf('SilkDateTime', $result->modified_date);
	}
	
	public function testOtherFieldsShouldBeString()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$this->assertInternalType('string', $result->test_field);
	}
	
	public function testAutoNumberingShouldWork()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$this->assertEquals(1, $result->id);
	}

	public function testArrayAccessorsShouldWork()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$this->assertEquals(1, $result->id);
		$this->assertEquals(1, $result['id']);
	}

	/*
	public function testDynamicFindersShouldRawk()
	{
		$result = cms_orm('test_data_mapper_table')->find_all_by_test_field('test2');
		$this->assertEquals(1, count($result));
		$result = cms_orm('test_data_mapper_table')->find_all_by_test_field_or_another_test_field('test2', 'blah');
		$this->assertEquals(2, count($result));
		$result = cms_orm('test_data_mapper_table')->find_all_by_test_field_and_another_test_field('test', 'blah');
		$this->assertEquals(1, count($result));
		$result = cms_orm('test_data_mapper_table')->find_all_by_test_field_and_another_test_field('test2', 'blah');
		$this->assertEquals(0, count($result));
		$result = cms_orm('test_data_mapper_table')->find_by_test_field('test2');
		$this->assertEquals(1, count($result));
		$result = cms_orm('test_data_mapper_table')->find_count_by_test_field('test2');
		$this->assertEquals(1, $result);
		$result = cms_orm('test_data_mapper_table')->find_count_by_test_field_or_another_test_field('test2', 'blah');
		$this->assertEquals(2, $result);
		$result = cms_orm('test_data_mapper_table')->find_count_by_test_field_and_another_test_field('test', 'blah');
		$this->assertEquals(1, $result);
		$result = cms_orm('test_data_mapper_table')->find_count_by_test_field_and_another_test_field('test2', 'blah');
		$this->assertEquals(0, $result);
	}
	
	public function testFindByQueryShouldRawkAsWellJustNotQuiteAsHard()
	{
		$result = cms_orm('test_data_mapper_table')->find_all_by_query("SELECT * FROM {test_data_mapper_table} ORDER BY id ASC");
		$this->assertEquals(3, count($result));                                
		$result = cms_orm('test_data_mapper_table')->find_all_by_query("SELECT * FROM {test_data_mapper_table} WHERE test_field = ? ORDER BY id ASC", array('test'));
		$this->assertEquals(1, count($result));
	}
	*/
	
	public function testSaveShouldWorkAndBumpTimestampAndTheDirtyFlagShouldWork()
	{
		#Once without a change
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$old_timestamp = $result->modified_date->timestamp();
		$result->save();
		
		$result = $test_orm->first()->execute();
		$this->assertEquals($old_timestamp, $result->modified_date->timestamp());
		
		#Once with
		$old_timestamp = $result->modified_date->timestamp();
		$result->test_field = 'test10';
		$result->save();
		
		$result = $test_orm->first()->execute();
		$this->assertNotEquals($old_timestamp, $result->modified_date->timestamp());
		$this->assertEquals('test10', $result->test_field);
	}
	
	public function testHasParameterDoesItsThing()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$this->assertTrue($result->has_parameter('test_field'));
		$this->assertTrue($result->has_parameter('another_test_field'));
		$this->assertTrue($result->has_parameter('create_date'));
		$this->assertTrue($result->has_parameter('modified_date'));
		$this->assertFalse($result->has_parameter('i_made_this_up'));
	}
	
	public function testValidatorWillNotAllowSaves()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$result->test_field = '';
		$result->another_test_field = '';
		$this->assertFalse($result->save());
		$result->test_field = 'test';
		$this->assertFalse($result->save());
		$result->another_test_field = 'blah';
		$this->assertTrue($result->save());
	}
	
	public function testNumericalityOfValidatorShouldActuallyWork()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$result->some_int = '';  #We're testing numbers, not empty strings -- do another validation
		$this->assertTrue($result->save());
		$result->some_int = '5';
		$this->assertTrue($result->save());
		$result->some_int = 5;
		$this->assertTrue($result->save());
		$result->some_float = 'sdfsdfsdfsfd';
		$this->assertFalse($result->save());
		$result->some_float = '5.501';
		$this->assertTrue($result->save());
		$result->some_float = 5.501;
		$this->assertTrue($result->save());
	}
	
	public function testHasManyShouldWork()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->load(1);
		
		$this->assertNotNull($result);
		$this->assertEquals('test', $result->children[0]->some_other_field);
	}
	
	public function testBelongsToShouldWorkAsWell()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->load(1);
		
		$this->assertNotNull($result);
		$this->assertEquals(1, count($result->children));
		$this->assertNotNull($result->children[0]->parent);
		$this->assertEquals(1, $result->children[0]->parent->id);
	}

	public function testHasAndBelongsToManyToo()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->load(1);
		
		$this->assertNotNull($result);
		$this->assertEquals(1, count($result->children_through));
		$this->assertNotNull($result->children_through[0]->parent);
		$this->assertEquals(1, $result->children_through[0]->parent->id);

		$test_orm = new TestDataMapperTableChild();
		$result = $test_orm->load(1);
		
		$this->assertNotNull($result);
		$this->assertEquals(2, count($result->parent_through));
		$this->assertNotNull($result->parent_through[0]->children[0]);
		$this->assertEquals(1, $result->parent_through[0]->children[0]->id);
	}
	
	public function testDeleteShouldActuallyDelete()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->load(1);
		
		$this->assertNotNull($result);
		$result->delete();
		$result = $test_orm->all()->execute();
		$this->assertEquals(2, count($result));
	}
	
	public function testLoadCallbacksShouldGetCalled()
	{
		TestDataMapperTable::$static_counter = 0;
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$this->assertNotNull($result);
		$this->assertEquals(1, $result->counter);
		$this->assertEquals(1, TestDataMapperTable::$static_counter);
	}
	
	public function testSaveCallbacksShouldGetCalled()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$this->assertNotNull($result);
		$this->assertEquals(1, $result->counter);
		
		#First no updates -- before gets called, after doesn't
		$result->save();
		$this->assertEquals(2, $result->counter);
		
		#Now with updates -- before and after get called
		$result->test_field = 'test10';
		$result->save();
		$this->assertEquals(5, $result->counter);
	}
	
	public function testDeleteCallbacksShouldGetCalled()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$this->assertNotNull($result);
		$this->assertEquals(1, $result->counter);
		
		$result->delete();
		$this->assertEquals(4, $result->counter);
		
		$result = $test_orm->all()->execute();
		$this->assertEquals(2, count($result));
	}

	public function testBasicActsAsShouldWorkWithBeforeLoad()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();

		$this->assertNotNull($result);
		$this->assertEquals(1, $result->ext_counter);
	}

	public function testBasicActsAsShouldAllowMethodCalls()
	{
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();

		$this->assertNotNull($result);
		$result->test_me();
		$result->test_me();

		//It's 3 because before_load still fires
		$this->assertEquals(3, $result->ext_counter);
	}
}

class TestDataMapperTable extends DataMapper
{
	var $counter = 0;
	var $ext_counter = 0;
	static public $static_counter = 0;
	
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
		'another_test_field' => array(
			'type' => 'string',
			'length' => 255,
		),
		'some_int' => array(
			'type' => 'int',
		),
		'some_float' => array(
			'type' => 'float',
		),
		'version' => array(
			'type' => 'int',
		),
		'create_date' => array(
			'type' => 'create_date',
		),
		'modified_date' => array(
			'type' => 'modified_date',
		),
		'children' => array(
			'type' => 'association',
			'association' => 'has_many',
			'child_object' => 'TestDataMapperTableChild',
			'foreign_key' => 'parent_id',
		),
		'children_through' => array(
			'type' => 'association',
			'association' => 'has_and_belongs_to_many',
			'child_object' => 'TestDataMapperTableChild',
			'child_object_foreign_key' => 'child_id',
			'foreign_key' => 'parent_id',
			'join_table' => 'has_and_belongs_to_many',
		),
		'has_and_belongs_to_many' => array(
			'type' => 'table',
			'fields' => array(
				'parent_id' => array(
					'type' => 'int'
				),
				'child_id' => array(
					'type' => 'int'
				),
				'create_date' => array(
					'type' => 'create_date',
				),
				'modified_date' => array(
					'type' => 'modified_date',
				),
			)
		),
	);

	var $_acts_as = array(
			'ActsAsUnitTest',
	);

	public function validate()
	{
		$this->validate_not_blank('test_field');
		if (strlen($this->another_test_field) == 0)
		{
			$this->add_validation_error('can\'t be blank');
		}
		$this->validate_numericality_of('some_int');
		$this->validate_numericality_of('some_float');
	}
	
	protected function before_load($type, $fields)
	{
		self::$static_counter++;
	}
	
	public function after_load()
	{
		$this->counter++;
	}
	
	public function before_save()
	{
		$this->counter++;
	}
	
	public function after_save()
	{
		$this->counter++;
		$this->counter++;
	}
	
	public function before_delete()
	{
		$this->counter++;
	}
	
	public function after_delete()
	{
		$this->counter++;
		$this->counter++;
	}
}

class TestDataMapperTableChild extends DataMapper
{
	var $_fields = array(
		'id' => array(
			'type' => 'int',
			'primary' => true,
			'serial' => true,
		),
		'parent_id' => array(
			'type' => 'int',
		),
		'some_other_field' => array(
			'type' => 'string',
			'length' => 255,
		),
		'version' => array(
			'type' => 'int',
		),
		'create_date' => array(
			'type' => 'create_date',
		),
		'modified_date' => array(
			'type' => 'modified_date',
		),
		'parent' => array(
			'type' => 'association',
			'association' => 'belongs_to',
			'parent_object' => 'testDataMapperTable',
			'foreign_key' => 'parent_id',
		),
		'parent_through' => array(
			'type' => 'association',
			'association' => 'has_and_belongs_to_many',
			'child_object' => 'TestDataMapperTable',
			'child_object_foreign_key' => 'parent_id',
			'foreign_key' => 'child_id',
			'join_table' => 'has_and_belongs_to_many',
		),
	);
}

class ActsAsUnitTest extends \silk\database\datamapper\acts_as\ActsAs
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function after_load(&$obj)
	{
		$obj->ext_counter++;
	}
	
	public function test_me(&$obj)
	{
		$obj->ext_counter++;
	}
}

# vim:ts=4 sw=4 noet
