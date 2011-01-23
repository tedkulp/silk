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

namespace silk\core;

require_once(join_path(SILK_LIB_DIR, 'rack', 'lib', 'rack.php'));

/**
 * Class with implements the Rack spec and starts up a Silk
 * application object and send back the headers and body back
 * to rack for further processsing.
 *
 * @since 1.0
 */
class RackApp
{
	protected $env = null;

	function call(&$env)
	{
		$this->env = $env;

		$request = new \silk\action\Request($env);
		$response = new \silk\action\Response();

		$app = Application::get_instance();
		$app->run($request, $response);

		list($code, $headers, $body) = $response->finish();
		return array($code, $headers, $body);
	}
}

# vim:ts=4 sw=4 noet
