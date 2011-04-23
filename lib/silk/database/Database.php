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

namespace silk\database;

use \silk\core\Object;

/**
 * Global object that holds references to various data structures
 * needed by classes/functions.
 */
class Database extends Object
{
	static private $prefix = '';
	static public $dbal_connection = null;
	static public $entity_manager = null;
	static public $schema_manager = null;
	static public $event_manager = null;

	static function getConnection()
	{
		if (self::$dbal_connection == null)
		{
			$connection_params = array(
				'wrapperClass' => 'silk\database\ConnectionWrapper',
			);

			$config = get('config');
			if (isset($config['database']))
				$connection_params = $config['database'] + $connection_params;

			if (isset($config['database']['prefix']))
				self::$prefix = $config['database']['prefix'];

			if ($config['database']['driver'] == 'mongodb')
				self::$dbal_connection = new \Doctrine\MongoDB\Connection('mongodb://' . $config['database']['host'], $connection_params, null, self::getEventManager());
			else
				self::$dbal_connection = \Doctrine\DBAL\DriverManager::getConnection($connection_params, null, self::getEventManager());
		}

		return self::$dbal_connection;
	}

	static function getDatabase()
	{
		$config = get('config');
		if (self::isMongoDb())
			return self::getConnection()->selectDatabase($config['database']['dbname']);
		else
			return self::getConnection();
	}

	static function getSchemaManager()
	{
		if (self::isMongoDb())
			return null;

		if (self::$schema_manager == null)
		{
			self::$schema_manager = self::getConnection()->getSchemaManager();
		}

		return self::$schema_manager;
	}

	static function getEventManager()
	{
		if (self::$event_manager == null)
		{
			$config = get('config');
			$evm = new \Doctrine\Common\EventManager;

			// Setup Table Prefix on ORM
			if ($config['database']['driver'] == 'mongodb')
			{
				$table_prefix = new \silk\database\extensions\OdmTablePrefix();
				$evm->addEventListener(\Doctrine\ODM\MongoDB\Events::loadClassMetadata, $table_prefix);
			}
			else
			{
				$table_prefix = new \silk\database\extensions\TablePrefix();
				$evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $table_prefix);
			}

			self::$event_manager = $evm;
		}

		return self::$event_manager;
	}

	static function getEntityManager()
	{
		if (self::$entity_manager == null)
		{
			$silk_config = get('config');

			// TODO: Make this more dynamic
			// Make sure proxy_dir is set, etc.
			if (Database::isMongoDb())
			{
				//If this is mongo -- it's actually a documentmanager, but whatever
				$config = new \Doctrine\ODM\MongoDB\Configuration();
				$proxy_dir = joinPath(ROOT_DIR,'tmp','cache','proxies');
				@mkdir($proxy_dir);
				$config->setProxyDir($proxy_dir);
				$config->setProxyNamespace('SilkFrameworkTmp\Proxies');

				$config->setHydratorDir($proxy_dir);
				$config->setHydratorNamespace('SilkFrameworkTmp\Hydrators');

				$reader = new \Doctrine\Common\Annotations\AnnotationReader();
				$reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
				$config->setMetadataDriverImpl(new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader, joinPath(ROOT_DIR,'app','models')));

				$config->setAutoGenerateProxyClasses(true);

				$config->setDefaultDB($silk_config['database']['dbname']);

				self::$entity_manager = \Doctrine\ODM\MongoDB\DocumentManager::create(self::getConnection(), $config, self::getEventManager());
			}
			else
			{
				$config = new \Doctrine\ORM\Configuration;
				$config->setMetadataCacheImpl(get('cache'));
				$driverImpl = $config->newDefaultAnnotationDriver(joinPath(ROOT_DIR,'app','models'));
				$config->setMetadataDriverImpl($driverImpl);
				$config->setQueryCacheImpl(get('cache'));
				$proxy_dir = joinPath(ROOT_DIR,'tmp','cache','proxies');
				@mkdir($proxy_dir);
				$config->setProxyDir($proxy_dir);
				$config->setProxyNamespace('SilkFrameworkTmp\Proxies');

				$config->setAutoGenerateProxyClasses(true);

				self::$entity_manager = \Doctrine\ORM\EntityManager::create(self::getConnection(), $config, self::getEventManager());
			}
		}

		return self::$entity_manager;
	}

	static function isMongoDb()
	{
		$config = get('config');
		return (isset($config['database']['driver']) && $config['database']['driver'] == 'mongodb');
	}

	static function getPrefix()
	{
		$prefix = self::$prefix;

		//Are we testing? Add a test_ to the prefix.
		if (defined('SILK_TEST_DIR'))
			$prefix = 'test_' . $prefix;

		return $prefix;
	}

	static function getNewSchema()
	{
		return new \Doctrine\DBAL\Schema\Schema();
	}

	static function createTable(\Doctrine\DBAL\Schema\Schema $schema)
	{
		$pdo = self::getConnection();

		try
		{
			$queries = $schema->toSql($pdo->getDatabasePlatform());

			foreach ($queries as $one_query)
				$pdo->executeQuery($one_query);

			return true;
		}
		catch (\Exception $e)
		{
		}

		return false;
	}

	static function dropTable($table_name, $add_prefix = true)
	{
		if ($add_prefix)
		{
			if (!startsWith($table_name, self::getPrefix()))
				$table_name = self::getPrefix() . $table_name;
		}

		if (self::isMongoDb())
		{
			$config = get('config');
			$pdo = self::getConnection();
			$pdo->selectDatabase($config['database']['dbname'])->dropCollection($table_name);
		}
		else
		{
			try
			{
				$pdo = self::getConnection();
				$sm = self::getSchemaManager();
				$fromSchema = $sm->createSchema();
				$toSchema = clone $fromSchema;
				$toSchema->dropTable($table_name);
				$sql = $fromSchema->getMigrateToSql($toSchema, $pdo->getDatabasePlatform());
				if (count($sql))
				{
					$pdo->executeQuery($sql[0]);
					return true;
				}
			}
			catch (\Exception $e)
			{
			}

			return false;
		}
	}

	public static function flush()
	{
		$em = self::getEntityManager();
		if ($em)
			$em->flush();
	}

}

# vim:ts=4 sw=4 noet
