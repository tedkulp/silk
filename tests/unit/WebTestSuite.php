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

require_once(dirname(dirname(dirname(__FILE__))) . '/silk.api.php');

use \silk\test\WebTestCase;

class WebSuiteTest extends WebTestCase
{
	public function beforeTest()
	{
		\silk\action\Route::clearRoutes();
		\silk\action\Route::buildDefaultComponentRoutes();
	}

	public function testRun()
	{
		$response = $this->sendRequest('GET', '/i_made_this_up', array('test' => 'blah'));
		$this->assertEquals(404, $response->statusCode);
		$this->assertEquals('404 Not Found', $response->headers['Status']);
		$this->assertContains('<title>404 Not Found</title>', $response->content);
		$this->assertEquals('404 Not Found', $response->dom->find('title', 0)->innertext);
	}

	public function testController()
	{
		$response = $this->sendRequest('GET', '/web_test');
		$this->assertEquals(200, $response->statusCode);
		$this->assertEquals('200 OK', $response->headers['Status']);
		$this->assertEquals('It works!', $response->dom->find('title', 0)->innertext);
		$this->assertEquals('It works!', $response->dom->find('h1', 0)->innertext);
		$this->assertEquals('Value 1', $response->dom->find('ul', 0)->find('li', 0)->innertext);
		$this->assertEquals('Value 2', $response->dom->find('ul', 0)->find('li', 1)->innertext);
		$this->assertEquals(2, count($response->dom->find('li')));
		$count = 1;
		foreach($response->dom->find('ul', 0)->find('li') as $node)
		{
			$this->assertEquals('Value ' . $count, $node->innertext);
			$count++;
		}
	}
}

class WebTestController extends \silk\action\Controller
{
	function index()
	{
		return "<html><head><title>It works!</title></head><body><h1>It works!</h1><p><ul><li>Value 1</li><li>Value 2</li></ul></p></body></html>";
	}
}

# vim:ts=4 sw=4 noet
