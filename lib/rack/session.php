<?php
/*

   PHP Rack v0.1.0

   Copyright (c) 2010 Jim Myhrberg.

   Permission is hereby granted, free of charge, to any person obtaining
   a copy of this software and associated documentation files (the
   'Software'), to deal in the Software without restriction, including
   without limitation the rights to use, copy, modify, merge, publish,
   distribute, sublicense, and/or sell copies of the Software, and to
   permit persons to whom the Software is furnished to do so, subject to
   the following conditions:

   The above copyright notice and this permission notice shall be
   included in all copies or substantial portions of the Software.

   THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
   EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
   MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
   IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
   CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
   TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
   SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

namespace Rack;

class Session implements \Iterator, \ArrayAccess
{
	private $loaded = false;

	public function __construct()
	{
		/*
		$session_key = substr(md5(RACK_ROOT), 0, 8);
		@session_name('RACKSESS_' . $session_key);
		@ini_set('url_rewriter.tags', '');
		@ini_set('session.use_trans_sid', 0);
		if(!@session_id()) session_start();
		 */
		$this->loaded = true;
	}

	public function offsetExists($index)
	{
		return isset($_SESSION[$index]);
	}

	public function offsetGet($index)
	{
		return isset($_SESSION[$index]) ? $_SESSION[$index] : null;
	}

	public function offsetSet($index, $value)
	{
		$_SESSION[$index] = $value;
	}

	public function offsetUnset($index)
	{
		unset($_SESSION[$index]);
	}

	public function rewind()
	{
		return reset($_SESSION);
	}

	public function current()
	{
		return current($_SESSION);
	}

	public function key()
	{
		return key($_SESSION);
	}

	public function next()
	{
		return next($_SESSION);
	}

	public function valid()
	{
		return key($_SESSION) !== null;
	}
	
}
