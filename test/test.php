<?php

require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'rack.php');

use Rack\Rack;

class RackTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
	}

	public function tearDown()
	{
		Rack::clear();
	}

	public function testApp()
	{
		ob_start();
		Rack::add('MockApp', MockApp);
		Rack::run();
		$buffer = trim(ob_get_contents());
		ob_end_clean();
		$this->assertEquals('test output', $buffer);
	}

	public function testAddMiddleware()
	{
		ob_start();
		Rack::add('MockMiddleware', MockMiddleware);
		Rack::add('MockApp', MockApp);
		Rack::run();
		$buffer = trim(ob_get_contents());
		ob_end_clean();
		$this->assertEquals('TEST OUTPUT', $buffer);
	}

	public function testInsertBeforeMiddleware()
	{
		ob_start();
		Rack::add('MockApp', MockApp);
		Rack::insert_before('MockApp', 'MockMiddleware', MockMiddleware);
		Rack::run();
		$buffer = trim(ob_get_contents());
		ob_end_clean();
		$this->assertEquals('TEST OUTPUT', $buffer);
	}

	public function testInsertAfterMiddleware()
	{
		ob_start();
		Rack::add('MockMiddleware', MockMiddleware);
		Rack::insert_after('MockMiddleware', 'MockApp', MockApp);
		Rack::run();
		$buffer = trim(ob_get_contents());
		ob_end_clean();
		$this->assertEquals('TEST OUTPUT', $buffer);
	}
}

class MockApp
{
	public function __construct(&$app)
	{
		$this->app = $app;
	}

	public function call(&$env)
	{
		return array(200, array("Content-Type" => "text/html"), array("test output"));
	}
}

class MockMiddleware
{
	public function __construct(&$app)
	{
		$this->app = $app;
	}

	public function call(&$env)
	{
		list($status, $headers, $body) = $this->app->call($env);
		return array($status, $headers, array_map('strtoupper', $body));
	}
}
