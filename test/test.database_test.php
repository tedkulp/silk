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

require_once(dirname(dirname(__FILE__)) . '/silk.api.php');

use \silk\test\TestCase;
use \silk\database\Database;
use \silk\database\Query;
use \silk\performance\Cache;

class DatabaseTest extends TestCase
{
	public function setUp()
	{
		$this->tearDown();
		$pdo = Database::get_instance();
		
		$pdo->create_table('test_orm_table',
			array(
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
			)
		);
		
		$pdo->execute("INSERT INTO {test_orm_table} (test_field, another_test_field, some_int, some_float, create_date, modified_date) VALUES ('test', 'blah', 5, 5.501, now() - 10, now() - 10)");
		$pdo->execute("INSERT INTO {test_orm_table} (test_field, create_date, modified_date) VALUES ('test2', now(), now())");
		$pdo->execute("INSERT INTO {test_orm_table} (test_field, create_date, modified_date) VALUES ('test3', now(), now())");
		
		$pdo->create_table('test_orm_table_child',
			array(
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
			)
		);
		
		$pdo->execute("INSERT INTO {test_orm_table_child} (parent_id, some_other_field, create_date, modified_date) VALUES (1, 'test', now(), now())");
	}
	
	public function tearDown()
	{
		$pdo = Database::get_instance();
		$pdo->drop_table('test_orm_table_child');
		$pdo->drop_table('test_orm_table');
		Cache::clear();
	}
	
	public function testConnection()
	{
		$pdo = Database::get_instance();
		$this->assertNotNull($pdo);
		$this->assertTrue(count($pdo->getAvailableDrivers()) > 0);
	}
	
	public function testAutoPrefix()
	{
		$pdo = Database::get_instance();
		$this->assertEqual($pdo->query("SELECT * FROM " . db_prefix() . 'test_orm_table')->rowCount(), $pdo->query("SELECT * FROM {test_orm_table}")->rowCount());
		$this->assertEqual($pdo->query("SELECT * FROM " . db_prefix() . 'test_orm_table_child')->rowCount(), $pdo->query("SELECT * FROM {test_orm_table_child}")->rowCount());
	}
	
	public function testFetchAll()
	{
		$pdo = Database::get_instance();
		$ret = $pdo->fetch_all('SELECT * FROM {test_orm_table}');
		$this->assertTrue(is_array($ret));
		$this->assertTrue(count($ret) > 0);
		$this->assertTrue(isset($ret[0]['id']));
		$this->assertTrue(is_numeric($ret[0]['id']));
	}
	
	public function testFetchColumn()
	{
		$pdo = Database::get_instance();
		$ret = $pdo->fetch_column('SELECT id FROM {test_orm_table}');
		$this->assertTrue(is_array($ret));
		$this->assertTrue(count($ret) > 0);
		$this->assertTrue(is_numeric($ret[0]));
		
		$ret = $pdo->fetch_column('SELECT test_field, id FROM {test_orm_table}', array(), 1);
		$this->assertTrue(is_array($ret));
		$this->assertTrue(count($ret) > 0);
		$this->assertTrue(is_numeric($ret[0]));
	}
	
	public function testGetOne()
	{
		$pdo = Database::get_instance();
		$ret = $pdo->get_one('SELECT count(*) FROM {test_orm_table}');
		$this->assertTrue(is_numeric($ret));
		$this->assertTrue($ret > 0);
		
		$ret = $pdo->get_one('SELECT id, test_field FROM {test_orm_table}');
		$this->assertTrue(is_numeric($ret));
		$this->assertTrue($ret > 0);
	}
	
	public function testSelect()
	{
		$pdo = Database::get_instance();
		$row = $pdo->select("*")->from('{test_orm_table}')->first();
		$this->assertNotNull($row);
		//$this->assertNotEmpty($row);
		$this->assertTrue(is_numeric($row['id']));
		$this->assertFalse(is_numeric($row['test_field']));
		
		$row = $pdo->select("id, test_field")->from('{test_orm_table}')->first();
		$this->assertNotNull($row);
		//$this->assertNotEmpty($row);
		$this->assertTrue(is_numeric($row['id']));
		$this->assertFalse(is_numeric($row['test_field']));
		
		$row = $pdo->select(array('id', 'test_field'))->from('{test_orm_table}')->first();
		$this->assertNotNull($row);
		//$this->assertNotEmpty($row);
		$this->assertTrue(is_numeric($row['id']));
		$this->assertFalse(is_numeric($row['test_field']));
	}
	
	public function testCountAndCountSpl()
	{
		$pdo = Database::get_instance();
		
		//Tested earlier -- we know this works
		$fast_count = $pdo->get_one('SELECT count(*) FROM {test_orm_table}');
		
		//Yes, we should be using count(*)
		$slow_count = $pdo->select("*")->from('{test_orm_table}')->count();
		$this->assertNotNull($slow_count);
		$this->assertTrue(is_numeric($slow_count));
		$this->assertTrue($slow_count > 0);
		$this->assertTrue($fast_count == $slow_count);
		
		//Much quicker, but wordier
		$row = $pdo->select("count(*) as count")->from('{test_orm_table}')->first();
		$this->assertNotNull($row);
		//$this->assertNotEmpty($row);
		$this->assertTrue(is_numeric($row['count']));
		$this->assertTrue($row['count'] > 0);
		$this->assertTrue($row['count'] == $fast_count);
	}
	
	public function testIteratorSpl()
	{
		$pdo = Database::get_instance();
		
		$result = false;
		foreach ($pdo->select("*")->from('{test_orm_table}') as $one_row)
		{
			$result = true;
		}
		
		$this->assertTrue($result);
	}
}

# vim:ts=4 sw=4 noet
