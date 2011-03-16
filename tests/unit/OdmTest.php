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

class OdmTest extends TestCase
{
	public function beforeTest()
	{
		if (!Database::isMongoDb())
		{
			$this->markTestSkipped('Can\'t test this without mongo.');
			return;
        }

		$this->afterTest();
		
		OdmTestDataMapperTable::migrate();
		OdmTestDataMapperTableChild::migrate();
		
		$pdo = Database::getDatabase();
		
		$arys = array();
		$arys[] = array('id' => 1, 'testField' => 'test', 'anotherTestField' => 'blah', 'someInt' => 5, 'someFloat' => 5.501, 'createDate' => new DateTime("@" . (time() - 10000)), 'modifiedDate' => new DateTime("@" . (time() - 10000)));
		$arys[] = array('id' => 2, 'testField' => 'test2', 'anotherTestField' => 'blah2', 'createDate' => new DateTime(), 'modifiedDate' => new DateTime());
		$arys[] = array('id' => 3, 'testField' => 'test3', 'anotherTestField' => 'blah3', 'createDate' => new DateTime(), 'modifiedDate' => new DateTime());
		foreach ($arys as $one_ary)
		{
			$parent = new OdmTestDataMapperTable;
			foreach ($one_ary as $key => $value)
			{
				$parent[$key] = $value;
			}
			$parent->save(false);
		}

		$arys = array();
		$arys[] = array('id' => 1, 'someOtherField' => 'test', 'createDate' => new DateTime(), 'modifiedDate' => new DateTime());
		foreach ($arys as $one_ary)
		{
			$parent = new OdmTestDataMapperTableChild;
			foreach ($one_ary as $key => $value)
			{
				$parent[$key] = $value;
			}
			$parent->save(false);
		}

		Database::flush();

		$parent = OdmTestDataMapperTable::load(1);
		$child = OdmTestDataMapperTableChild::load(1);

		//Totally unrealistic, but fine for testing
		$child->setParent($parent);
		$parent->addChildren($child);

		$parent->save();
	}
	
	public function afterTest()
	{
		OdmTestDataMapperTableChild::dropTable();
		OdmTestDataMapperTable::dropTable();
		
		//Cache::clear();
	}
	
	public function testFindOneShouldReturnOneRow()
	{
		$result = OdmTestDataMapperTable::findOne();
		$this->assertInstanceOf('OdmTestDataMapperTable', $result);
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
		$result = OdmTestDataMapperTable::findOneBy(array('id' => 1));
		
		$this->assertNotInstanceOf('DateTime', $result->getTestField());
		$this->assertInstanceOf('DateTime', $result->getCreateDate());
		$this->assertInstanceOf('DateTime', $result->getModifiedDate());
	}
	
	public function testOtherFieldsShouldBeString()
	{
		$result = OdmTestDataMapperTable::findOneBy(array('id' => 1));
		
		$this->assertInternalType('string', $result->getTestField());
	}
	
	public function testArrayAccessorsShouldWork()
	{
		$result = OdmTestDataMapperTable::findOneBy(array('id' => 1));
		
		$this->assertEquals(1, $result->getId());
		$this->assertEquals(1, $result['id']);
	}

	public function testDynamicFindersShouldRawk()
	{
		$this->assertEquals(1, count(OdmTestDataMapperTable::findByTestField('test2')));
	}
	
	/*
	public function testFindByQueryShouldRawkAsWellJustNotQuiteAsHard()
	{
		OdmTestDataMapperTable::find
		$result = cms_orm('test_data_mapper_table')->find_all_by_query("SELECT * FROM {test_data_mapper_table} ORDER BY id ASC");
		$this->assertEquals(3, count($result));
		$result = cms_orm('test_data_mapper_table')->find_all_by_query("SELECT * FROM {test_data_mapper_table} WHERE test_field = ? ORDER BY id ASC", array('test'));
		$this->assertEquals(1, count($result));
	}
	*/
	
	public function testSaveShouldWorkAndBumpTimestampAndTheDirtyFlagShouldWork()
	{
		#Once without a change
		$result = OdmTestDataMapperTable::findOneBy(array('id' => 1));

		$old_timestamp = clone($result['modifiedDate']);
		$this->assertTrue($result->save());
		
		$this->assertEquals($old_timestamp, $result['modifiedDate']);

		sleep(2);

		$result['testField'] = 'test10';
		$this->assertTrue($result->save());
		
		$this->assertNotEquals($old_timestamp, clone($result['modifiedDate']));
		$this->assertEquals('test10', $result['testField']);
	}
	
	public function testHasParameterDoesItsThing()
	{
		$result = OdmTestDataMapperTable::findOne();
		
		$this->assertTrue($result->hasParameter('testField'));
		$this->assertTrue($result->hasParameter('anotherTestField'));
		$this->assertTrue($result->hasParameter('createDate'));
		$this->assertTrue($result->hasParameter('modifiedDate'));
		$this->assertFalse($result->hasParameter('iMadeThisUp'));
	}
	
	public function testValidatorWillNotAllowSaves()
	{
		$result = OdmTestDataMapperTable::findOne();
		
		$result['testField'] = '';
		$result['anotherTestField'] = '';
		$this->assertFalse($result->save());
		$this->assertEquals(2, count($result->validation_errors));
		$this->assertEquals("testField must be defined", $result->validation_errors[0]);
		$this->assertEquals("This thing is wrong", $result->validation_errors[1]);

		$result['testField'] = 'test';
		$this->assertFalse($result->save());
		$this->assertEquals(1, count($result->validation_errors));
		$this->assertEquals("This thing is wrong", $result->validation_errors[0]);

		$result['anotherTestField'] = 'blah';
		$this->assertTrue($result->save());
	}
	
	/*
	public function testNumericalityOfValidatorShouldActuallyWork()
	{
		$test_orm = new OdmTestDataMapperTable();
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
		$result = OdmTestDataMapperTable::load(1);
		
		$this->assertNotNull($result);
		$this->assertEquals('test', $result->getChildren()->first()->getSomeOtherField());
		$this->assertEquals('test', $result['children'][0]['someOtherField']);
	}
	
	public function testBelongsToShouldWorkAsWell()
	{
		$result = OdmTestDataMapperTable::load(1);
		
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
		$test_orm = new OdmTestDataMapperTable();
		$result = $test_orm->load(1);
		
		$this->assertNotNull($result);
		$this->assertEquals(1, count($result->children_through));
		$this->assertNotNull($result->children_through[0]->parent);
		$this->assertEquals(1, $result->children_through[0]->parent->id);

		$test_orm = new OdmTestDataMapperTableChild();
		$result = $test_orm->load(1);
		
		$this->assertNotNull($result);
		$this->assertEquals(2, count($result->parent_through));
		$this->assertNotNull($result->parent_through[0]->children[0]);
		$this->assertEquals(1, $result->parent_through[0]->children[0]->id);
	}
	*/
	
	public function testDeleteShouldActuallyDelete()
	{
		$result = OdmTestDataMapperTable::load(2);
		
		$this->assertNotNull($result);
		$result->delete();
		$result = OdmTestDataMapperTable::findAll();
		$this->assertEquals(2, count($result));
	}
	
	/*
	public function testLoadCallbacksShouldGetCalled()
	{
		OdmTestDataMapperTable::$static_counter = 0;
		$test_orm = new OdmTestDataMapperTable();
		$result = $test_orm->first()->execute();
		
		$this->assertNotNull($result);
		$this->assertEquals(1, $result->counter);
		$this->assertEquals(1, OdmTestDataMapperTable::$static_counter);
	}
	*/
	
	public function testSaveCallbacksShouldGetCalled()
	{
		$result = OdmTestDataMapperTable::findOne();
		
		$this->assertNotNull($result);
		//Reset counter -- since it's been in memory for all these tests
		$result->counter = 1;
		
		#First no updates -- no callbacks get called
		$result->save();
		$this->assertEquals(1, $result->counter);
		
		#Now with updates -- before and after get called
		$result->setTestField('test10');
		$this->assertTrue($result->save());
		$this->assertEquals(4, $result->counter);
	}
	
	public function testDeleteCallbacksShouldGetCalled()
	{
		$result = OdmTestDataMapperTable::load(2);
		
		$this->assertNotNull($result);
		//Reset counter -- since it's been in memory for all these tests
		$result->counter = 1;
		
		$result->delete();
		$this->assertEquals(4, $result->counter);
		
		$result = OdmTestDataMapperTable::findAll();
		$this->assertEquals(2, count($result));
	}

	/*
	public function testBasicActsAsShouldWorkWithBeforeLoad()
	{
		$test_orm = new OdmTestDataMapperTable();
		$result = $test_orm->first()->execute();

		$this->assertNotNull($result);
		$this->assertEquals(1, $result->ext_counter);
	}

	public function testBasicActsAsShouldAllowMethodCalls()
	{
		$test_orm = new OdmTestDataMapperTable();
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
 * @Document(collection="test_data_mapper_table")
 */
class OdmTestDataMapperTable extends \silk\model\Model
{
	var $counter = 0;
	var $ext_counter = 0;
	static public $static_counter = 0;

	/**
	 * @Id(strategy="NONE")
	 */
	protected $id;

	/**
	 * @ReferenceMany(targetDocument="OdmTestDataMapperTableChild")
	 */
	protected $children;

	/**
	 * @Field
	 * @Validation:NotEmpty
	 */
	protected $testField;

	/**
	 * @Field
	 * @Validation:NotEmpty(message = "This thing is wrong")
	 */
	protected $anotherTestField;

	/**
	 * @Field(type="int")
	 */
	protected $someInt;

	/**
	 * @Field(type="float")
	 */
	protected $someFloat;

	/**
	 * @Field(type="int")
	 */
	protected $version;

	/**
	 * @Field(type="date")
	 */
	protected $createDate;

	/**
	 * @Field(type="date")
	 */
	protected $modifiedDate;

    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection;
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
 * @Document(collection="test_data_mapper_table_child")
 */
class OdmTestDataMapperTableChild extends \silk\model\Model
{
	/**
	 * @Id(strategy="NONE")
	 */
	protected $id;

	/**
	 * @ReferenceOne(targetDocument="OdmTestDataMapperTable")
	 */
	protected $parent;

	/**
	 * @Field
	 */
	protected $someOtherField;

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
