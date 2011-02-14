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

require_once(joinPath(SILK_LIB_DIR,'vendor','Console','CommandLine.php'));

class Task extends \Console_CommandLine
{
	public $needs_db = false;

	public function __construct($params = array())
	{
		$parent = parent::__construct($params);
		// small hack, setting filename to taskName so that 
		// task name appears correctly in help.
		$this->filename = $this->taskName();
		return $parent;
	}

	/**
	 * Get this objects task name, as per the class name.
	 * @throws LogicException when called on an object that is not a subclass of SilkTask.
	 * @return string This object's task
	 */
	public function taskName()
	{
		static $taskName = null;
		if (!is_subclass_of($this, 'silk\cli\Task'))
		{
			throw new TaskException('There is no task when called on a SilkTask object. Only call taskName() on objects extending SilkTask.');
		} 
		if (null == $taskName)
		{
			$taskName = strtolower(rtrim(ltrim(get_class($this), 'Silk'), 'Task'));
		}
		return $taskName;
	}

	/**
	 *	Main routine for Task. Called automatically when task is invoked, eg silk.php taskname.
	 */
	public function run($argc, $argv)
	{

	}

	/**
	 * Verify a directory exists and return dir name sans trailing slashes. 
	 */
	protected function verifyDir($value, $option, $result, $parser, $params=array())
	{
		$dir = (rtrim($value, '/'));
		if (!is_dir($dir))
		{
			throw new Exception('Directory does not exist: '.$value);	
		}
		return $dir;
	}
}

class TaskException extends \Exception
{

}

# vim:ts=4 sw=4 noet
