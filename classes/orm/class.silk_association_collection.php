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

/**
 * Class to represent an array of objects coming from an ORM finder responce
 * with multiple records.  We use a class instead of an array so we can later
 * define more methods to act on this collection in relation to associations.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkAssociationCollection extends SilkObject implements ArrayAccess, Iterator, Countable
{
	var $children = array();
	var $currentIndex = 0;

	function __construct()
	{
		parent::__construct();
	}
	
	function count()
	{
		return count($this->children);
	}
	
	//Region ArrayAccess
	function offsetExists($offset)
	{
		return ($offset < $this->count());
	}

	function offsetGet($offset)
	{
		return $this->offsetExists($offset) ? $this->children[$offset] : null;
	}

	function offsetSet($offset,$value)
	{
		throw new Exception("This collection is read only.");
		//$ary = $this->fill_data();
		//$ary[$offset] = $value;
	}

	function offsetUnset($offset)
	{
		throw new Exception("This collection is read only.");
		//$ary = $this->fill_data();
		//unset($ary[$offset]);
	}
	//EndRegion
	
	//Region Iterator
	function current()
	{
		return $this->offsetGet($this->currentIndex);
	}

	function key()
	{
		return $this->currentIndex;
	}

	function next()
	{
		return $this->currentIndex++;
	}

	function rewind()
	{
		$this->currentIndex = 0;
	}

	function valid()
	{
		return ($this->offsetExists($this->currentIndex));
	}

	function append($value)
	{
		throw new Exception("This collection is read only");
	}

	function getIterator()
	{
		return $this;
	}
}

# vim:ts=4 sw=4 noet
?>