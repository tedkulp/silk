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
	static public $prefix = '';
	static public $dbal_connection = null;
	static public $entity_manager = null;

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

			self::$dbal_connection = \Doctrine\DBAL\DriverManager::getConnection($connection_params);
		}

		return self::$dbal_connection;
	}

	static function getEntityManager()
	{
		if (self::$entity_manager == null)
		{
			// TODO: Make this more dynamic
			// Make sure proxy_dir is set from components, etc.
			$cache = new \Doctrine\Common\Cache\ArrayCache;

			$config = new \Doctrine\ORM\Configuration;
			$config->setMetadataCacheImpl($cache);
			$driverImpl = $config->newDefaultAnnotationDriver(joinPath(ROOT_DIR,'components','default','models'));
			$config->setMetadataDriverImpl($driverImpl);
			$config->setQueryCacheImpl($cache);
			$proxy_dir = joinPath(ROOT_DIR,'tmp','cache','proxies');
			@mkdir($proxy_dir);
			$config->setProxyDir($proxy_dir);
			$config->setProxyNamespace('SilkFrameworkTmp\Proxies');

			$config->setAutoGenerateProxyClasses(true);

			self::$entity_manager = \Doctrine\ORM\EntityManager::create(self::getConnection(), $config);
		}

		return self::$entity_manager;
	}

	static function getPrefix()
	{
		return self::$prefix;
	}
}

# vim:ts=4 sw=4 noet
