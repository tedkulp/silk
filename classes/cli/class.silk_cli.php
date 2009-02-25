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

	/** 
	 * Prints list of available tasks.
	 * @return void
	 * @author Tim Oxley
	*/
	public static function print_task_list() {
		echo "\r\n";
		$tasks = self::get_task_list();
		foreach($tasks as $task) {
			echo $task . "\r\n";
		}
	}
	
	/**
	 *	Scans through all available class files, and extracts names of all 
	 *	Tasks. A task 'taskname' looks like class.silk_taskname_task.php
	 *	@return array List of available tasks. 
	 * @author Tim Oxley
	*/
	public static function get_task_list() {
		$classes = scan_classes();
		$taskList = array();
		// Extract names
		foreach($classes as $class) {
			$class = basename($class, '.php');
			//Check if starts with class.silk and ends with task.php
			$match = array();
			if	(ereg('class\.silk_(.*)_task', $class, $match)){;
				 $taskList[] = $match[1];
			}
		}
		return $taskList;
	}
	
	public function run()
	{
		$args = $this->arguments($this->argv);
		
		$has_help = in_array('help', array_keys($args['options']));
		
		if (!isset($args['arguments'][0]))
		{
			if ($has_help)
			{
				echo <<<EOF
Name:
silk.php - Command line inferface to the Silk Framework

Synopsis:
silk.php task [task specific options]

Description:
Command line interface to access various "tasks" related to the developement
and maintenance of applications written using the silk framework.  Tasks are
a dynamic system and can be added to and removed at will.  For a list of tasks
see below.  To get help for a specific task, use: silk.php task --help.

Available Tasks:
EOF;
self::print_task_list();
				return 0;
			}
			else
			{
				echo "No task given.  Use --help or pass a valid task.\n";
				return 1;
			}
		}
		
		$task = array_shift($args['arguments']);
		$task_class = camelize($task . '_task');
		if (!class_exists($task_class))
		{
			$task_class_silk = camelize('silk_' . $task . '_task');
			if (!class_exists($task_class_silk))
			{
				echo "Task class '{$task_class}' not found.  Aborting.\n";
				return 2;
			}
			else
			{
				$task_class = $task_class_silk;
			}
		}
		
		$task_obj = new $task_class;
		if ($has_help && $task_obj->has_help)
		{
			echo $task_obj->help($args['arguments'], $args['flags'], $args['options']);
			return 0;
		}
		else
		{
			if  ($task_obj->needs_db)
				SilkBootstrap::get_instance()->setup_database();
			
			return $task_obj->run($args['arguments'], $args['flags'], $args['options']);
		}
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
				{
					list($k, $v) = explode('=', $option, 2);
					$ret['options'][$k] = $v;
				}
				else
				{
					$ret['options'][$option] = null;
				}
				
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
