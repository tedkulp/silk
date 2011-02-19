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

		$schema = Database::getNewSchema();

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

		Database::createTable($schema);

		$pdo->executeQuery("INSERT INTO {test_orm_table} (test_field, another_test_field, some_int, some_float, create_date, modified_date) VALUES ('test', 'blah', 5, 5.501, now() - 10, now() - 10)");
		$pdo->executeQuery("INSERT INTO {test_orm_table} (test_field, create_date, modified_date) VALUES ('test2', now(), now())");
		$pdo->executeQuery("INSERT INTO {test_orm_table} (test_field, create_date, modified_date) VALUES ('test3', now(), now())");

		$pdo->executeQuery("INSERT INTO {test_orm_table_child} (parent_id, some_other_field, create_date, modified_date) VALUES (1, 'test', now(), now())");
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
		$this->assertTrue($pdo->isConnected());
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
	
	public function testFetchColumn()
	{
		$pdo = Database::getConnection();
		$ret = $pdo->fetchColumn('SELECT count(*) FROM {test_orm_table}');
		$this->assertTrue(is_numeric($ret));
		$this->assertTrue($ret > 0);
		
		$ret = $pdo->fetchColumn('SELECT id, test_field FROM {test_orm_table}', array(), 0);
		$this->assertTrue(is_numeric($ret));
		$this->assertTrue($ret > 0);
	}
	
	public function testIterator()
	{
		$pdo = Database::getConnection();
		
		$result = false;
		foreach ($pdo->fetchAssoc('select * from {test_orm_table}') as $one_row)
		{
			$result = true;
		}
		
		$this->assertTrue($result);
	}
}

# vim:ts=4 sw=4 noet
