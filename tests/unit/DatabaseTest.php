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
use \silk\database\Database;
//use \silk\performance\Cache;

class DatabaseTest extends TestCase
{
	public function beforeTest()
	{
		$pdo = Database::getConnection();
		$sm = Database::getSchemaManager();

		$this->afterTest();

		$schema = new \Doctrine\DBAL\Schema\Schema();

		$myTable = $schema->createTable("{test_orm_table}");
		$myTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
		$myTable->addColumn("test_field", "string", array("length" => 255));
		$myTable->addColumn("another_test_field", "string", array("length" => 255));
		$myTable->addColumn("some_int", "integer");
		$myTable->addColumn("some_float", "float");
		$myTable->addColumn("version", "integer");
		$myTable->addColumn("create_date", "datetime");
		$myTable->addColumn("modified_date", "datetime");
		$myTable->setPrimaryKey(array("id"));

		$myChildTable = $schema->createTable("{test_orm_table_child}");
		$myChildTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
		$myChildTable->addColumn("parent_id", "integer");
		$myChildTable->addColumn("some_other_field", "string", array("length" => 255));
		$myChildTable->addColumn("version", "integer");
		$myChildTable->addColumn("create_date", "datetime");
		$myChildTable->addColumn("modified_date", "datetime");
		$myChildTable->setPrimaryKey(array("id"));

		$queries = $schema->toSql($pdo->getDatabasePlatform());

		foreach ($queries as $one_query)
			$pdo->executeQuery($one_query);

		$pdo->executeQuery("INSERT INTO {test_orm_table} (test_field, another_test_field, some_int, some_float, create_date, modified_date) VALUES ('test', 'blah', 5, 5.501, now() - 10, now() - 10)");
		$pdo->executeQuery("INSERT INTO {test_orm_table} (test_field, create_date, modified_date) VALUES ('test2', now(), now())");
		$pdo->executeQuery("INSERT INTO {test_orm_table} (test_field, create_date, modified_date) VALUES ('test3', now(), now())");

		$pdo->executeQuery("INSERT INTO {test_orm_table_child} (parent_id, some_other_field, create_date, modified_date) VALUES (1, 'test', now(), now())");

		/*
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
		
		$pdo->execute_sql("INSERT INTO {test_orm_table} (test_field, another_test_field, some_int, some_float, create_date, modified_date) VALUES ('test', 'blah', 5, 5.501, now() - 10, now() - 10)");
		$pdo->execute_sql("INSERT INTO {test_orm_table} (test_field, create_date, modified_date) VALUES ('test2', now(), now())");
		$pdo->execute_sql("INSERT INTO {test_orm_table} (test_field, create_date, modified_date) VALUES ('test3', now(), now())");
		
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
				'sub_table' => array(
					'type' => 'table',
					'fields' => array(
						'parent_id' => array(
							'type' => 'int',
						),
						'create_date' => array(
							'type' => 'create_date',
						),
						'modified_date' => array(
							'type' => 'modified_date',
						),
					),
				),
			)
		);
		
		$pdo->execute_sql("INSERT INTO {test_orm_table_child} (parent_id, some_other_field, create_date, modified_date) VALUES (1, 'test', now(), now())");

		$pdo->execute_sql("INSERT INTO {sub_table} (parent_id, create_date, modified_date) VALUES (1, now(), now())");
		*/
	}
	
	public function afterTest()
	{
		Database::dropTable('test_orm_table_child');
		Database::dropTable('test_orm_table');
	}
	
	public function testConnection()
	{
		$pdo = Database::getConnection();
		$this->assertNotNull($pdo);
		//$this->assertTrue(count($pdo->getAvailableDrivers()) > 0);
	}
	
	public function testAutoPrefix()
	{
		$pdo = Database::getConnection();
		$this->assertEquals(count($pdo->fetchAll("SELECT * FROM " . dbPrefix() . 'test_orm_table')), count($pdo->fetchAll("SELECT * FROM {test_orm_table}")));
		$this->assertEquals(count($pdo->fetchAll("SELECT * FROM " . dbPrefix() . 'test_orm_table_child')), count($pdo->fetchAll("SELECT * FROM {test_orm_table_child}")));
	}
	
	public function testFetchAll()
	{
		$pdo = Database::getConnection();
		$ret = $pdo->fetchAll('SELECT * FROM {test_orm_table}');
		$this->assertTrue(is_array($ret));
		$this->assertTrue(count($ret) > 0);
		$this->assertTrue(isset($ret[0]['id']));
		$this->assertTrue(is_numeric($ret[0]['id']));
	}
	
	/*
	public function testFetchColumn()
	{
		$pdo = Database::getConnection();
		$ret = $pdo->fetchColumn('SELECT id FROM {test_orm_table}');
		$this->assertTrue(is_array($ret));
		$this->assertTrue(count($ret) > 0);
		$this->assertTrue(is_numeric($ret[0]));
		
		$ret = $pdo->fetchColumn('SELECT test_field, id FROM {test_orm_table}', array(), 1);
		$this->assertTrue(is_array($ret));
		$this->assertTrue(count($ret) > 0);
		$this->assertTrue(is_numeric($ret[0]));
	}
	*/
	
	/*
	public function testGetOne()
	{
		$pdo = Database::getInstance();
		$ret = $pdo->get_one('SELECT count(*) FROM {test_orm_table}');
		$this->assertTrue(is_numeric($ret));
		$this->assertTrue($ret > 0);
		
		$ret = $pdo->get_one('SELECT id, test_field FROM {test_orm_table}');
		$this->assertTrue(is_numeric($ret));
		$this->assertTrue($ret > 0);
	}
	
	public function testSelect()
	{
		$pdo = Database::getInstance();
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
		$pdo = Database::getInstance();
		
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
		$pdo = Database::getInstance();
		
		$result = false;
		foreach ($pdo->select("*")->from('{test_orm_table}') as $one_row)
		{
			$result = true;
		}
		
		$this->assertTrue($result);
	}

	public function testSubTable()
	{
		$pdo = Database::getInstance();

		$result = false;
		foreach ($pdo->select("*")->from('{sub_table}') as $one_row)
		{
			if ($one_row['parent_id'] == 1)
				$result = true;
		}
		
		$this->assertTrue($result);
	}
	*/
}

# vim:ts=4 sw=4 noet
