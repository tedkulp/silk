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

namespace silk\cli;

class Cli extends Task
{
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

	public static function getInstance()
	{
		static $parser = null;
		if (null == $parser)
		{
			$parser = new SilkCli();
		}	

		return $parser;
	}	

	public function init()
	{
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
				'list' => array_keys(self::getTaskList()),
				'msg' => "\nCurrent tasks are: \n\t",
				'delimiter' => "\n\t"
			)
		));
	}
	
	/** 
	 * Prints list of available tasks.
	 * @return void
	*/
	public static function printTaskList($del = '')
	{
		echo "\r\n";
		$tasks = self::getTaskList();
		if (empty($tasks))
		{
			echo "No Tasks Found\r\n";
		}
		else
		{
			foreach($tasks as $task => $class)
			{
				echo $del . $task . "\r\n";
			}
		}
	}
	
	/**
	 *	Scans through all available class files, and extracts names of all 
	 *	Tasks. A task 'taskname' looks like class.silk_taskname_task.php
	 *	@return array List of available tasks. 
	*/
	public static function getTaskList()
	{
		$classes = scanClasses();
		$task_list = array();
		// Extract names
		foreach ($classes as $class)
		{
			$shortened_class = basename($class, '.php');
			//Check if it ends with Task.php
			//TODO: Make me suck less
			$match = array();
			if (preg_match('/(.+?)Task$/', $shortened_class, $match))
			{
				$the_match = underscore(trim($match[1])); 
				if (!in_array($the_match, $task_list))
					$task_list[$the_match] = $class;
			}
		}
		return $task_list;
	}
	
	/**
	 * @return an instance of the passed task object
	 * @throws Exception if task class is not found. 
	 **/
	private function instantiateTask($task, $task_list)
	{
		$task_obj = null;

		if (trim($task) == '')
		{
			throw new UnexpectedValueException('Task cannot be empty string.');
		}
		$task_class = '\silk\tasks\\' . basename($task_list[$task], '.php');
		if (!class_exists($task_class))
		{
			throw new \Exception("Task class '$task_class' not found.\n");
		}
		
		if (is_subclass_of($task_class, 'silk\cli\Task'))
		{
			$task_obj = new $task_class();
		}
		else
		{
			throw new Exception("Task: '$task_class' must extend \silk\cli\Task");
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
			$task_list = self::getTaskList();
			foreach ($task_list as $task => $class)
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
				$task = $this->instantiateTask($result->args['task'], $task_list);
				array_shift($argv);
				$argc--;
				$task->run($argc, $argv);
			}
			else
			{
				echo "\nCurrent tasks are:";
				self::printTaskList("\t");
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

# vim:ts=4 sw=4 noet
