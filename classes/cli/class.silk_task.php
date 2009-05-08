<?php

require_once('Console/CommandLine.php');
class SilkTask extends Console_CommandLine
{
	public $needs_db = false;

	public function __construct($params = array())
	{
		$parent = parent::__construct($params);
		// small hack, setting filename to taskName so that 
		// task name appears correctly in help.
		$this->filename = $this->task_name();
		return $parent;
	}

	/**
	 * Get this objects task name, as per the class name.
	 * @throws LogicException when called on an object that is not a subclass of SilkTask.
	 * @return string This object's task
	 */
	public function task_name()
	{
		static $taskName = null;
		if (!is_subclass_of($this, 'SilkTask'))
		{
			throw new LogicException('There is no task when called on a SilkTask object. Only call taskName() on objects extending SilkTask.');
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
	protected function verify_dir($value, $option, $result, $parser, $params=array())
	{
		$dir = (rtrim($value, '/'));
		if (!is_dir($dir))
		{
			throw new Exception('Directory does not exist: '.$value);	
		}
		return $dir;
	}
}

?>