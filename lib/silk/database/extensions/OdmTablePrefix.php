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

namespace silk\database\extensions;

use \silk\core\Object;
use \silk\database\Database;

/**
 * Global object that holds references to various data structures
 * needed by classes/functions.
 */
class OdmTablePrefix extends Object
{
	public function loadClassMetadata(\Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs $eventArgs)
	{
		$prefix = Database::getPrefix(); 
		$classMetadata = $eventArgs->getClassMetadata();
		if (!startsWith($classMetadata->getCollection(), $prefix))
			$classMetadata->setCollection($prefix . $classMetadata->getCollection());
		/*
		foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping)
		{
			if ($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY)
			{
				$mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
				$classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
			}
		}
		*/
	}
}

# vim:ts=4 sw=4 noet
