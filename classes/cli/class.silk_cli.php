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

class SilkCli extends SilkTask implements SilkSingleton {
	public $argc = null;
	public $argv = null;
	
	public function __construct()
	{
		parent::__construct(array(
			'name' => 'Command line interface to the Silk Framework',
			'description' => "Command line interface to access various \"tasks\" related to the development and maintenance of applications written using the silk framework.  Tasks are a dynamic system and can be added to and removed at will.  For a list of current tasks use the --list option. To get help for a specific task, use: silk.php task --help.",
			'version'     => '0.0.1'
		));

		$this->init();		
	}

	public static function get_instance() {
		static $parser = null;
		if (null == $parser) {
			$parser = new SilkCli();
		}	

		return $parser;
	}	

	public function init() {
		//create task list
		$this->addArgument('task', array(
			'description'=>"Task to run. Use --list to list tasks.",
			'optional'=>'TRUE'
		));

		$this->addOption('list_tasks', array(
			'short_name' => '-l',
			'long_name'  => '--list',
			'description'=> 'List available tasks',
			'action'     => 'List',
			'action_params' => array(
				'list' => self::get_task_list(),
				'msg' => "\nCurrent tasks are: \n\t",
				'delimiter' => "\n\t"
			)
		));
	}
	
	/** 
	 * Prints list of available tasks.
	 * @return void
	 * @author Tim Oxley
	*/
	public static function print_task_list($del = '')
	{
		echo "\r\n";
		$tasks = self::get_task_list();
		foreach($tasks as $task)
		{
			echo $del . $task . "\r\n";
		}
	}
	
	/**
	 *	Scans through all available class files, and extracts names of all 
	 *	Tasks. A task 'taskname' looks like class.silk_taskname_task.php
	 *	@return array List of available tasks. 
	 *  @author Tim Oxley
	*/
	public static function get_task_list()
	{
		$classes = scan_classes();
		$taskList = array();
		// Extract names
		foreach ($classes as $class)
		{
			$class = basename($class, '.php');
			//Check if starts with class.silk and ends with task.php
			$match = array();
			if (preg_match('/class\.silk_(.*)_task/', $class, $match))
			{
				if (!in_array($match[1], $taskList))
					$taskList[] = $match[1];
			}
		}
		return $taskList;
	}
	
	/**
	 * 
	 * @return an instance of the passed task object
	 * @throws Exception if task class is not found. 
	 **/
	private function instantiate_task($task)
	{
		$task_obj = null;

		if (trim($task) == '')
		{
			throw new UnexpectedValueException('Task cannot be empty string.');
		}
		$task_class = camelize($task . '_task');
		if (!class_exists($task_class))
		{
			$task_class_silk = camelize('silk_' . $task . '_task');
			if (!class_exists($task_class_silk))
			{
				throw new Exception("Task class '$task_class' not found.\n");
			}
			else
			{
				$task_class = $task_class_silk;
			}
		}
		
		if (is_subclass_of($task_class, 'SilkTask'))
		{
			$class = new ReflectionClass($task_class);
			if ($class->implementsInterface('SilkSingleton'))
			{
				$task_obj = call_user_func_array(array($task_class, 'get_instance'), array());
			}
			else
			{
				$task_obj = new $task_class();
			}
		}
		else
		{
			throw new Exception("Task: '$task_class' must extend SilkTask");
		}

		if ($task_obj == null) {
			throw new SilkImproperInitialisationException($task_obj, '$task_obj');
		}
		
		return $task_obj;
	}
	
	public function run($argc, $argv) 
	{
		$this->argc = $argc;
		$this->argv = $argv;

		try
		{
			// Cut off all aruments after task name so as to not hassle the task
			// parser ($this) with those details.
			$taskArgv = $argv;
			foreach (self::get_task_list() as $task)
			{
				$key = array_search($task, $argv);
				if(false !== $key)
				{
					$taskArgv = array_slice($argv, 0, $key + 1); 
					break;	
				} 
				
			}
			$result = $this->parse(count($taskArgv), $taskArgv);
			
			// Run the task
			if (isset($result->args['task']))
			{
				$task = $this->instantiate_task($result->args['task']);
				array_shift($argv);
				$argc--;
				$task->run($argc, $argv);
			}
			else
			{
				echo "\nCurrent tasks are:";
				self::print_task_list("\t");
			}
		}
		catch (Exception $exc)
		{
			$this->displayError($exc->getMessage());
		}
	}
	
	public function __clone()
	{
		
	}
	
	public function __wakeup()
	{
		
	}
}

?>
