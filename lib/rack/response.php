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

class Response
{

	protected $status = 200;
	protected $headers = array();
	protected $body = array();
	protected $length = 0;

	public function __construct($status = 200, $headers = array(), $body = array())
	{
		$this->length = 0;
		$this->status = (int)$status;
		$this->headers = $headers;
		if (is_string($body))
			$this->write($body);
		else if (is_array($body))
		{
			foreach ($body as $one_line)
				$this->write($one_line);
		}
	}

	public function write($string)
	{
		$body[] = $string;
		//TODO: Hack alert
		$this->length += strlen(utf8_decode($string));
		$this->headers['Content-Length'] = $this->length;
		return $string;
	}

	public function redirect($target, $status = 302)
	{
		$this->status = $status;
		$this->headers['Location'] = $target;
	}

	public function finish()
	{
		if ($this->status == 204 || $this->status == 304)
		{
			unset($this->headers['Content-Type']);
			return array($this->status, $this->headers, array());
		}

		return array($this->status, $this->headers, $this->body);
	}

	public function is_empty()
	{
		return count($this->body) == 0;
	}

}
