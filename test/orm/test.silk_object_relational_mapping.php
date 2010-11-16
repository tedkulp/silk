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

use \silk\performance\Cache;
use \silk\test\TestCase;
use \silk\orm\ActiveRecord;

class OrmTest extends TestCase
{
	public function setUp()
	{
		$this->tearDown();
		SilkDatabase::create_table('test_orm_table', "
			id I KEY AUTO,
			test_field C(255),
			another_test_field C(255),
			some_int I,
			some_float F,
			version I,
			create_date T,
			modified_date T
		");
		db()->Execute("INSERT INTO {test_orm_table} (test_field, another_test_field, some_int, some_float, create_date, modified_date) VALUES ('test', 'blah', 5, 5.501, now() - 10, now() - 10)");
		db()->Execute("INSERT INTO {test_orm_table} (test_field, create_date, modified_date) VALUES ('test2', now(), now())");
		db()->Execute("INSERT INTO {test_orm_table} (test_field, create_date, modified_date) VALUES ('test3', now(), now())");
		
		SilkDatabase::create_table('test_orm_table_child', "
			id I KEY AUTO,
			parent_id I,
			some_other_field C(255),
			version I,
			create_date T,
			modified_date T
		");
		
		db()->Execute("INSERT INTO {test_orm_table_child} (parent_id, some_other_field, create_date, modified_date) VALUES (1, 'test', now(), now())");
	}
	
	public function tearDown()
	{
		SilkDatabase::drop_table('test_orm_table_child');
		SilkDatabase::drop_table('test_orm_table');
		Cache::clear();
	}
	
	public function testGetTableShouldKnowFancyPrefixStuff()
	{
		$obj = orm('TestOrmTable');
		$this->assertEqual(db_prefix() . 'test_orm_table', $obj->get_table());
		$this->assertEqual(db_prefix() . 'test_orm_table.test_field', $obj->get_table('test_field'));
	}
	
	public function testGetColumnsInTableShouldWork()
	{
		$obj = orm('TestOrmTable');
		$result = $obj->get_columns_in_table();
		$this->assertEqual(8, count($result));
		$this->assertEqual('int', $result['id']->type);
		$this->assertEqual('varchar', $result['test_field']->type);
		$this->assertEqual('datetime', $result['create_date']->type);
	}
	
	public function testFindAllShouldReturnAllRows()
	{
		$result = TestOrmTable::find_all();
		$this->assertIsA($result, 'array');
		$this->assertEqual(3, count($result));
	}
	
	public function testFindOneShouldReturnOneRow()
	{
		$result = TestOrmTable::find();
		$this->assertIsA($result, 'TestOrmTable');
		$this->assertEqual(1, count($result));
	}
	
	public function testFindCountShouldReturnACountDuh()
	{
		$result = TestOrmTable::find_count();
		$this->assertEqual(3, $result);
	}
	
	public function testDateTimeShouldBeASilkDateTimeObject()
	{
		$result = TestOrmTable::find();
		$this->assertNotA($result->test_field, 'SilkDateTime');
		$this->assertIsA($result->create_date, 'SilkDateTime');
		$this->assertIsA($result->modified_date, 'SilkDateTime');
	}
	
	public function testOtherFieldsShouldBeString()
	{
		$result = TestOrmTable::find();
		$this->assertIsA($result->test_field, 'string');
	}
	
	public function testAutoNumberingShouldWork()
	{
		$result = TestOrmTable::find();
		$this->assertEqual(1, $result->id);
	}
	
	public function testArrayAccessorsShouldWork()
	{
		$result = TestOrmTable::find();
		$this->assertEqual(1, $result->id);
		$this->assertEqual(1, $result['id']);
	}

	public function testDynamicFindersShouldRawk()
	{
		$result = TestOrmTable::find_all_by_test_field('test2');
		$this->assertEqual(1, count($result));
		/*
		$result = TestOrmTable::find_all_by_test_field_or_another_test_field('test2', 'blah');
		$this->assertEqual(2, count($result));
		$result = TestOrmTable::find_all_by_test_field_and_another_test_field('test', 'blah');
		$this->assertEqual(1, count($result));
		$result = TestOrmTable::find_all_by_test_field_and_another_test_field('test2', 'blah');
		$this->assertEqual(0, count($result));
		$result = TestOrmTable::find_by_test_field('test2');
		$this->assertEqual(1, count($result));
		$result = TestOrmTable::find_count_by_test_field('test2');
		$this->assertEqual(1, $result);
		$result = TestOrmTable::find_count_by_test_field_or_another_test_field('test2', 'blah');
		$this->assertEqual(2, $result);
		$result = TestOrmTable::find_count_by_test_field_and_another_test_field('test', 'blah');
		$this->assertEqual(1, $result);
		$result = TestOrmTable::find_count_by_test_field_and_another_test_field('test2', 'blah');
		$this->assertEqual(0, $result);
		*/
	}
	
	public function testFindByQueryShouldRawkAsWellJustNotQuiteAsHard()
	{
		$result = TestOrmTable::find_all_by_query("SELECT * FROM {test_orm_table} ORDER BY id ASC");
		$this->assertEqual(3, count($result));                                
		$result = TestOrmTable::find_all_by_query("SELECT * FROM {test_orm_table} WHERE test_field = ? ORDER BY id ASC", array('test'));
		$this->assertEqual(1, count($result));
	}
	
	public function testSaveShouldWorkAndBumpTimestampAndTheDirtyFlagShouldWork()
	{
		#Once without a change
		$result = TestOrmTable::find();
		$old_timestamp = $result->modified_date->timestamp();
		$result->save();
		$result = TestOrmTable::find();
		$this->assertEqual($old_timestamp, $result->modified_date->timestamp());
		
		#Once with
		$old_timestamp = $result->modified_date->timestamp();
		$result->test_field = 'test10';
		$result->save();
		$result = TestOrmTable::find();
		$this->assertNotEqual($old_timestamp, $result->modified_date->timestamp());
		$this->assertEqual('test10', $result->test_field);
	}
	
	public function testHasParameterDoesItsThing()
	{
		$result = TestOrmTable::find();
		$this->assertTrue($result->has_parameter('test_field'));
		$this->assertTrue($result->has_parameter('another_test_field'));
		$this->assertTrue($result->has_parameter('create_date'));
		$this->assertTrue($result->has_parameter('modified_date'));
		$this->assertFalse($result->has_parameter('i_made_this_up'));
	}
	
	public function testValidatorWillNotAllowSaves()
	{
		$result = TestOrmTable::find();
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
		$result = TestOrmTable::find();
		$result->some_int = '';  #We are testing numbers, not empty strings -- do another validation
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
		$result = TestOrmTable::find_by_id(1);
		$this->assertNotNull($result);
		$this->assertEqual(1, count($result->children));
		$this->assertEqual('test', $result->children[0]->some_other_field);
	}
	
	public function testBelongsToShouldWorkAsWell()
	{
		$result = TestOrmTable::find_by_id(1);
		$this->assertNotNull($result);
		$this->assertEqual(1, count($result->children));
		$this->assertNotNull(count($result->children[0]->parent));
		$this->assertEqual(1, $result->children[0]->parent->id);
	}
	
	public function testDeleteShouldActuallyDelete()
	{
		$result = TestOrmTable::find_by_id(1);
		$this->assertNotNull($result);
		$result->delete();
		$result = TestOrmTable::find_all();
		$this->assertEqual(2, count($result));
	}
	
	public function testLoadCallbacksShouldGetCalled()
	{
		$result = TestOrmTable::find();
		$this->assertNotNull($result);
		$this->assertEqual(1, $result->counter);
	}
	
	public function testSaveCallbacksShouldGetCalled()
	{
		$result = TestOrmTable::find();
		$this->assertNotNull($result);
		$this->assertEqual(1, $result->counter);
		
		#First no updates -- before gets called, after does not
		$result->save();
		$this->assertEqual(2, $result->counter);
		
		#Now with updates -- before and after get called
		$result->test_field = 'test10';
		$result->save();
		$this->assertEqual(5, $result->counter);
	}
	
	public function testDeleteCallbacksShouldGetCalled()
	{
		$result = TestOrmTable::find();
		$this->assertNotNull($result);
		$this->assertEqual(1, $result->counter);
		
		$result->delete();
		$this->assertEqual(4, $result->counter);
		
		$result = TestOrmTable::find_all();
		$this->assertEqual(2, count($result));
	}
	
	/*
	public function testBadTransactionsShouldFail()
	{
		$db = db();
		if ($db->hasTransactions)
		{
			TestOrmTable::begin_transaction();
			$db->Execute("INSERT INTO {test_orm_table} (test_field, another_test_field, some_int, some_float, create_date, modified_date) VALUES ('test good', 'blah', 5, 5.501, now() - 10, now() - 10)"); //Good SQL
			try {
				@$db->Execute("INSERT INTO {test_orm_table} (test_field, another_test_field, some_int, some_float, create_date, modified_date) VALUES ('test bad', 'blah', 5, 5.501, now() - 10, now() - 10, 'kjk')"); //Bad SQL
			}
			catch (Exception $e) {}
			$this->assertFalse(TestOrmTable::complete_transaction());
			$this->assertNull(TestOrmTable::find_by_test_field('test good'));
		}
	}
	
	public function testGoodTransactionsShouldWork()
	{
		$db = db();
		if ($db->hasTransactions)
		{
			TestOrmTable::begin_transaction();
			$db->Execute("INSERT INTO {test_orm_table} (test_field, another_test_field, some_int, some_float, create_date, modified_date) VALUES ('test good', 'blah', 5, 5.501, now() - 10, now() - 10)"); //Good SQL
			$this->assertTrue(TestOrmTable::complete_transaction());
			$this->assertNotNull(TestOrmTable::find_by_test_field('test good'));
		}
	}
	*/
	
}

class TestOrmTable extends ActiveRecord
{
	var $counter = 0;

	public function setup()
	{
		$this->create_has_many_association('children', 'TestOrmTableChild', 'parent_id');
		//$this->assign_acts_as('Versioned');
	}

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

class TestOrmTableChild extends ActiveRecord
{	
	public function setup()
	{
		$this->create_belongs_to_association('parent', 'test_orm_table', 'parent_id');
	}
}

# vim:ts=4 sw=4 noet
