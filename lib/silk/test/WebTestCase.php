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

namespace silk\test;

require_once 'PHPUnit/Autoload.php';

class WebTestCase extends TestCase
{
	/**
	 * Create and send a request to in instanced version
	 * of the application. It will instantiate rack, setup
	 * the framework, and send the request similar to how
	 * it would work on apache. If valid, the html returned
	 * will be sent to a pure PHP version of a DOM parser, where
	 * tests can be run accordingly.
	 *
	 * @param string The type of request. GET, POST, DELETE, PUT are all supported.
	 * @param string The uri to request, without the http://hostname
	 * @param array A hash of parameters to send
	 * @return WebTestCaseResponse An object containing all the information from the 
	 *         request
	 */
	public function sendRequest($type, $uri, array $params = array())
	{
		$type = strtoupper($type);

		$env_params['HTTP_REFERER'] = 'http://localhost' . $uri;
		$env_params['HTTP_HOST'] = $env_params['SERVER_NAME'] = 'localhost';
		$env_params['SERVER_PORT'] = '80';
		$env_params['SERVER_PROTOCOL'] = 'HTTP/1.1';
		$env_params['REQUEST_METHOD'] = $type;
		$env_params['REQUEST_URI'] = $uri;
		$env_params['SCRIPT_NAME'] = $params['PHP_SELF'] = '/index.php';
		$env_params['SCRIPT_FILENAME'] = joinPath(ROOT_DIR, 'index.php');

		if (!empty($params))
		{
			//TODO: Make RACK have a better way to override this
			if ($type == 'GET')
				$_GET = $params;
			else if ($type == 'POST')
				$_POST = $params;
		}

		include_once(joinPath(SILK_LIB_DIR, 'vendor', 'rack', 'lib', 'Rack.php'));

		\rack\Rack::add("\\silk\\core\\Application", null, \silk\core\Application::getInstance());

		@ob_start();
		$result = \rack\Rack::run($env_params);
		@ob_end_clean();

		return new WebTestCaseResponse($result);
	}
}

# vim:ts=4 sw=4 noet
