<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008 Ted Kulp
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

require_once join_path(SILK_LIB_DIR,'xmlrpc','xmlrpc.inc');
require_once join_path(SILK_LIB_DIR,'xmlrpc','xmlrpcs.inc');

/**
 * Class for handling xmlrpc message.  Wraps the xmlrpc library.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkXmlrpc extends CmsObject
{
	static private $server = null;

	function __construct()
	{
		parent::__construct();
	}
	
	function create_server()
	{
		self::$server = new xmlrpc_server(null, false);
		self::$server->functions_parameters_type = 'phpvals';
	}
	
	function handle_requests()
	{
		if (self::$server == null)
			self::create_server();
		
		self::$server->service();
	}
	
	function add_method($name, $callback, $namespace = '')
	{
		if (self::$server == null)
			self::create_server();
			
		if ($namespace != '')
			$name = $namespace . "." . $name;

		self::$server->add_to_map($name, $callback);
	}
	
	function send_message($name, $params, $url)
	{
		$msg = new xmlrpcmsg($name, $params);
		$client = new xmlrpc_client($url);
		$client->return_type = 'phpvals';
		
		$result = $client->send($msg);
		if (!$result->faultCode())
		{
			return $result->value();
		}
		else
		{
			throw new SilkXmlRpcException($result->faultString(), $result->faultCode());
		}
	}
}

class SilkXmlRpcException extends Exception
{
	
}

# vim:ts=4 sw=4 noet
?>