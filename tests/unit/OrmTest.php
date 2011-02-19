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

class DataMapperTest extends TestCase
{
	public function beforeTest()
	{
		$this->afterTest();
		
		TestDataMapperTable::migrate();
		$test_orm = new TestDataMapperTable();
		
		TestDataMapperTableChild::migrate();
		$test_orm_child = new TestDataMapperTableChild();
		
		$pdo = Database::getConnection();
		
		$pdo->executeUpdate("INSERT INTO {test_data_mapper_table} (test_field, another_test_field, some_int, some_float, create_date, modified_date) VALUES ('test', 'blah', 5, 5.501, now() - 10, now() - 10)");
		$pdo->executeUpdate("INSERT INTO {test_data_mapper_table} (test_field, create_date, modified_date) VALUES ('test2', now(), now())");
		$pdo->executeUpdate("INSERT INTO {test_data_mapper_table} (test_field, create_date, modified_date) VALUES ('test3', now(), now())");
		
		$pdo->executeUpdate("INSERT INTO {test_data_mapper_table_child} (parent_id, some_other_field, create_date, modified_date) VALUES (1, 'test', now(), now())");
	}
	
	public function afterTest()
	{
		Database::dropTable('test_data_mapper_table_child');
		Database::dropTable('test_data_mapper_table');
		
		//Cache::clear();
	}
	
	public function testGetTableShouldKnowFancyPrefixStuff()
	{
		$em = Database::getEntityManager();

		$class = $em->getClassMetadata('TestDataMapperTable');
		
		$this->assertEquals(dbPrefix() . 'test_data_mapper_table', $class->getTableName());
	}
	
	public function testFindAllShouldReturnAllRows()
	{
		$result = TestDataMapperTable::findAll();
		$this->assertInternalType('array', $result);
		$this->assertEquals(3, count($result));
	}

	public function testMigrateDoesntAffectUnrelatedTables()
	{
		$schema = Database::getNewSchema();

		$myTable = $schema->createTable("{unrelated_table}");
		$myTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
		$myTable->addColumn("create_date", "datetime");
		$myTable->addColumn("modified_date", "datetime");
		$myTable->setPrimaryKey(array("id"));

		Database::createTable($schema);

		TestDataMapperTableChild::migrate();

		$sm = Database::getSchemaManager();

		$found = false;
		$tables = $sm->listTables();

		foreach ($tables as $table)
		{
			if ($table->getName() == dbPrefix() . 'unrelated_table')
			{
				$found = true;
			}
		}

		$this->assertTrue($found, 'Table was removed even if it shouldn\'t have been');

		Database::dropTable('unrelated_table');
	}
	
	public function testFindOneShouldReturnOneRow()
	{
		$result = TestDataMapperTable::findOne();
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
	
	/*
	public function testDateTimeShouldBeADateTimeObject()
	{
		$result = TestDataMapperTable::findOne();
		
		$this->assertNotInstanceOf('DateTime', $result->test_field);
		$this->assertInstanceOf('DateTime', $result->create_date);
		$this->assertInstanceOf('DateTime', $result->modified_date);
	}
	*/
	
	/*
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
	*/

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
	
	/*
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
	*/
}

/**
 * @Entity
 * @Table(name="test_data_mapper_table")
 */
class TestDataMapperTable extends \silk\model\Model
{
	var $counter = 0;
	var $ext_counter = 0;
	static public $static_counter = 0;

	/**
	 * @Id @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id;

	/**
	 * @OneToMany(targetEntity="TestDataMapperTableChild", mappedBy="parent")
	 */
	protected $children;

	/**
	 * @Column
	 */
	protected $test_field;

	/**
	 * @Column
	 */
	protected $another_test_field;

	/**
	 * @Column(type="integer")
	 */
	protected $some_int;

	/**
	 * @Column(type="float")
	 */
	protected $some_float;

	/**
	 * @Column(type="integer")
	 */
	protected $version;

	/**
	 * @Column(type="datetime")
	 */
	protected $create_date;

	/**
	 * @Column(type="datetime")
	 */
	protected $modified_date;

	/*
	var $_acts_as = array(
			'ActsAsUnitTest',
	);
	*/

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
	
	public function afterLoad()
	{
		$this->counter++;
	}
	
	public function beforeSave()
	{
		$this->counter++;
	}
	
	public function afterSave()
	{
		$this->counter++;
		$this->counter++;
	}
	
	public function beforeDelete()
	{
		$this->counter++;
	}
	
	public function afterDelete()
	{
		$this->counter++;
		$this->counter++;
	}
}

/**
 * @Entity
 * @Table(name="test_data_mapper_table_child")
 */
class TestDataMapperTableChild extends \silk\model\Model
{
	/**
	 * @Id @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id;

	/**
	 * @ManyToOne(targetEntity="TestDataMapperTable", inversedBy="children")
	 * @JoinColumn(name="parent_id", referencedColumnName="id")
	 */
	protected $parent;

	/**
	 * @Column
	 */
	protected $some_other_field;

	/**
	 * @Column(type="integer")
	 */
	protected $version;

	/**
	 * @Column(type="datetime")
	 */
	protected $create_date;

	/**
	 * @Column(type="datetime")
	 */
	protected $modified_date;
}

class ActsAsUnitTest
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function afterLoad(&$obj)
	{
		$obj->ext_counter++;
	}
	
	public function testMe(&$obj)
	{
		$obj->ext_counter++;
	}
}

# vim:ts=4 sw=4 noet
