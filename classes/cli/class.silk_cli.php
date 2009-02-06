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

class SilkCli extends SilkObject
{
	public $argc = null;
	public $argv = null;
	
	function __construct($argc, $argv)
	{
		parent::__construct();
		$this->argc = $argc;
		$this->argv = $argv;
	}
	
	public function run()
	{
		$args = $this->arguments($this->argv);
		
		if (!isset($args['arguments'][0]))
		{
			die("No task given.  Use --help or pass a valid task.\n");
		}
		
		$task = array_shift($args['arguments']);
		$task_class = camelize($task . '_task');
		if (!class_exists($task_class))
		{
			$task_class_silk = camelize('silk_' . $task . '_task');
			if (!class_exists($task_class_silk))
			{
				die("Task class '{$task_class}' not found.  Aborting.\n");
			}
			else
			{
				$task_class = $task_class_silk;
			}
		}
		
		$task_obj = new $task_class;
		$task_obj->run($args['arguments'], $args['flags'], $args['options']);
	}
	
	/**
	 * Parses the command line arguments into their various pieces (exec, options,
	 * flag and arguments).
	 * Lifted from: http://us.php.net/manual/en/features.commandline.php#86616
	 *
	 * @param array $args The args from $argv
	 * @return array
	 * @author Anonymous
	 */
	function arguments($args)
	{
		$ret = array(
			'exec'      => '',
			'options'   => array(),
			'flags'     => array(),
			'arguments' => array(),
		);
		
		$ret['exec'] = array_shift( $args );
		
		while (($arg = array_shift($args)) != NULL)
		{
			// Is it a option? (prefixed with --)
			if ( substr($arg, 0, 2) === '--' )
			{
				$option = substr($arg, 2);
				
				// is it the syntax '--option=argument'?
				if (strpos($option,'=') !== FALSE)
					array_push( $ret['options'], explode('=', $option, 2) );
				else
					array_push( $ret['options'], $option );
				
				continue;
			}
			
			// Is it a flag or a serial of flags? (prefixed with -)
			if ( substr( $arg, 0, 1 ) === '-' )
			{
				for ($i = 1; isset($arg[$i]) ; $i++)
					$ret['flags'][] = $arg[$i];
				
				continue;
			}
			
			// finally, it is not option, nor flag
			$ret['arguments'][] = $arg;
			continue;
		}
		return $ret;
	}
}

?>