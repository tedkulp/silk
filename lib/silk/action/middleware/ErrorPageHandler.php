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

namespace silk\action\middleware;

/**
 * Middleware to give the total execution time. Make sure
 * this is the top of the stack for the most accurate results.
 */
class ErrorPageHandler
{
	function __construct(&$app)
	{
		$this->app =& $app;
	}
	
	function call(&$env)
	{
		// call the next middleware in the stack
		list($status, $headers, $body) = $this->app->call($env);

		if ($status > 200)
		{
			$filename = joinPath(PUBLIC_DIR, $status . '.html');
			if (is_file($filename))
			{
				$body = array(file_get_contents($filename));
			}
		}

		return array($status, $headers, $body);
	}
}
