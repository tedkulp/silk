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

class SilkCreateTask extends SilkTask
{
    public function __construct()
    {
	   $this->AddOption('version', array(
		  'long_name'   => '--version',
		  'description' => "Runs some stuff.",
		  'action'      => 'StoreString',
		  'final' => true)
	   );

	   $this->addArgument('create_type');
	   $this->addArgument('additional_args', array(
		  'multiple' => true,
		  'optional' => true)
	   );

	   return parent::__construct(array(
		  'name' => 'create',
		  'description' => "Various methods for handling Silk Framework configuration.",
		  'version'     => '0.0.1')
	   );
    }

    public function run($argc, $argv)
    {
	   $result = $this->parse($argc, $argv);

	   if (isset($result->args['create_type']))
	   {
		  $create_type = $result->args['create_type'];
		  //TODO: find real way to make this extensible
		  $method_name = "{$create_type}_command";
		  if (method_exists($this, $method_name))
		  {
			 $this->$method_name($result->args['additional_args']);
		  }
	   }
    }

    public function app_command($args)
    {
	   if (empty($args) || count($args) != 1)
	   {
		  echo "\nThis command requires exactly one argument for the application name. Exiting!\n\n";
		  exit(1);
	   }

	   //Here is where we create the skeleton
    }
}

# vim:ts=5 sw=4 noet
