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
class ConnectionWrapper extends \Doctrine\DBAL\Connection
{
	public function executeUpdate($query, array $params = array(), array $types = array())
    {
		return parent::executeUdate($this->addPrefixToQuery($query), $params, $types);
	}

    public function executeQuery($query, array $params = array(), $types = array())
    {
		return parent::executeQuery($this->addPrefixToQuery($query), $params, $types);
	}

	public function addPrefixToQuery($query)
	{
		return strtr($query, array('{' => dbPrefix(), '}' => ''));
	}
}

# vim:ts=4 sw=4 noet
