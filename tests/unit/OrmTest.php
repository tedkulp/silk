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
		
		$pdo->executeUpdate("INSERT INTO {test_data_mapper_table} (testField, anotherTestField, someInt, someFloat, createDate, modifiedDate) VALUES ('test', 'blah', 5, 5.501, now() - 10, now() - 10)");
		$pdo->executeUpdate("INSERT INTO {test_data_mapper_table} (testField, createDate, modifiedDate) VALUES ('test2', now(), now())");
		$pdo->executeUpdate("INSERT INTO {test_data_mapper_table} (testField, createDate, modifiedDate) VALUES ('test3', now(), now())");
		
		$pdo->executeUpdate("INSERT INTO {test_data_mapper_table_child} (parentId, someOtherField, createDate, modifiedDate) VALUES (1, 'test', now(), now())");
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
		$myTable->addColumn("createDate", "datetime");
		$myTable->addColumn("modifiedDate", "datetime");
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
	
	public function testDateTimeShouldBeADateTimeObject()
	{
		$result = TestDataMapperTable::findOne();
		
		$this->assertNotInstanceOf('DateTime', $result->getTestField());
		$this->assertInstanceOf('DateTime', $result->getCreateDate());
		$this->assertInstanceOf('DateTime', $result->getModifiedDate());
	}
	
	public function testOtherFieldsShouldBeString()
	{
		$result = TestDataMapperTable::findOne();
		
		$this->assertInternalType('string', $result->getTestField());
	}
	
	public function testAutoNumberingShouldWork()
	{
		$result = TestDataMapperTable::findOne();
		
		$this->assertEquals(1, $result->getId());
	}

	public function testArrayAccessorsShouldWork()
	{
		$result = TestDataMapperTable::findOne();
		
		$this->assertEquals(1, $result->getId());
		$this->assertEquals(1, $result['id']);
	}

	public function testDynamicFindersShouldRawk()
	{
		$this->assertEquals(1, count(TestDataMapperTable::findByTestField('test2')));
	}
	
	/*
	public function testFindByQueryShouldRawkAsWellJustNotQuiteAsHard()
	{
		TestDataMapperTable::find
		$result = cms_orm('test_data_mapper_table')->find_all_by_query("SELECT * FROM {test_data_mapper_table} ORDER BY id ASC");
		$this->assertEquals(3, count($result));
		$result = cms_orm('test_data_mapper_table')->find_all_by_query("SELECT * FROM {test_data_mapper_table} WHERE test_field = ? ORDER BY id ASC", array('test'));
		$this->assertEquals(1, count($result));
	}
	*/
	
	public function testSaveShouldWorkAndBumpTimestampAndTheDirtyFlagShouldWork()
	{
		#Once without a change
		$result = TestDataMapperTable::findOne();
		
		$old_timestamp = $result['modifiedDate'];
		$result->save();
		
		$this->assertEquals($old_timestamp, $result['modifiedDate']);

		$old_timestamp = $result['modifiedDate'];
		$result['testField'] = 'test10';
		$result->save();
		
		$this->assertNotEquals($old_timestamp, $result['modifiedDate']);
		$this->assertEquals('test10', $result['testField']);
	}
	
	public function testHasParameterDoesItsThing()
	{
		$result = TestDataMapperTable::findOne();
		
		$this->assertTrue($result->hasParameter('testField'));
		$this->assertTrue($result->hasParameter('anotherTestField'));
		$this->assertTrue($result->hasParameter('createDate'));
		$this->assertTrue($result->hasParameter('modifiedDate'));
		$this->assertFalse($result->hasParameter('iMadeThisUp'));
	}
	
	/*
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
	*/
	
	public function testHasManyShouldWork()
	{
		$result = TestDataMapperTable::load(1);
		
		$this->assertNotNull($result);
		$this->assertEquals('test', $result->getChildren()->first()->getSomeOtherField());
		$this->assertEquals('test', $result['children'][0]['someOtherField']);
	}
	
	public function testBelongsToShouldWorkAsWell()
	{
		$result = TestDataMapperTable::load(1);
		
		$this->assertNotNull($result);
		$this->assertEquals(1, count($result->getChildren()));
		$this->assertEquals(1, count($result['children']));
		$this->assertNotNull($result->getChildren()->first()->getParent());
		$this->assertEquals(1, $result->getChildren()->first()->getParent()->getId());
		$this->assertEquals(1, $result['children'][0]['parent']['id']);
	}

	/*
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
	*/
	
	public function testDeleteShouldActuallyDelete()
	{
		$result = TestDataMapperTable::load(2);
		
		$this->assertNotNull($result);
		$result->delete();
		$result = TestDataMapperTable::findAll();
		$this->assertEquals(2, count($result));
	}
	
	/*
	public function testLoadCallbacksShouldGetCalled()
	{
		TestDataMapperTable::$static_counter = 0;
		$test_orm = new TestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$this->assertNotNull($result);
		$this->assertEquals(1, $result->counter);
		$this->assertEquals(1, TestDataMapperTable::$static_counter);
	}
	*/
	
	public function testSaveCallbacksShouldGetCalled()
	{
		$result = TestDataMapperTable::findOne();
		
		$this->assertNotNull($result);
		//Reset counter -- since it's been in memory for all these tests
		$result->counter = 1;
		
		#First no updates -- no callbacks get called
		$result->save();
		$this->assertEquals(1, $result->counter);
		
		#Now with updates -- before and after get called
		$result->setTestField('test10');
		$result->save();
		$this->assertEquals(4, $result->counter);
	}
	
	public function testDeleteCallbacksShouldGetCalled()
	{
		$result = TestDataMapperTable::load(2);
		
		$this->assertNotNull($result);
		//Reset counter -- since it's been in memory for all these tests
		$result->counter = 1;
		
		$result->delete();
		$this->assertEquals(4, $result->counter);
		
		$result = TestDataMapperTable::findAll();
		$this->assertEquals(2, count($result));
	}

	/*
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
	protected $testField;

	/**
	 * @Column
	 */
	protected $anotherTestField;

	/**
	 * @Column(type="integer")
	 */
	protected $someInt;

	/**
	 * @Column(type="float")
	 */
	protected $someFloat;

	/**
	 * @Column(type="integer")
	 */
	protected $version;

	/**
	 * @Column(type="datetime")
	 */
	protected $createDate;

	/**
	 * @Column(type="datetime")
	 */
	protected $modifiedDate;

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
	 * @JoinColumn(name="parentId", referencedColumnName="id")
	 */
	protected $parent;

	/**
	 * @Column
	 */
	protected $someOtherField;

	/**
	 * @Column(type="integer")
	 */
	protected $version;

	/**
	 * @Column(type="datetime")
	 */
	protected $createDate;

	/**
	 * @Column(type="datetime")
	 */
	protected $modifiedDate;
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
