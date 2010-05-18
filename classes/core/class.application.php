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

namespace silk\core;

/**
 * Global object that holds references to various data structures
 * needed by classes/functions.
 *
 * @author Ted Kulp
 * @since 1.0
 */
class Application extends \silk\core\Object
{
	/**
	 * Variables object - various objects and strings needing to be passed 
	 */
	public $variables;

	/**
	 * Site Preferences object - holds all current site preferences so they're only loaded once
	 */
	private static $siteprefs = array();

	/**
	 * Internal error array - So functions/modules can store up debug info and spit it all out at once
	 */
	public $errors;
	
	public $ormclasses;
	
	public $params = array();
	
	public $orm;
	
	private static $instance = NULL;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->errors = array();
		$this->variables['routes'] = array();
		$this->orm = array();
	}
	
	/**
	 * Returns an instnace of the \silk\core\Application singleton.  Most 
	 * people can generally use silk() instead of this, but they 
	 * both do the same thing.
	 *
	 * @return \silk\core\Application The singleton \silk\core\Application instance
	 * @author Ted Kulp
	 **/
	public static function get_instance()
	{
		if (self::$instance == NULL)
		{
			self::$instance = new \silk\core\Application();
		}
		return self::$instance;
	}
	
	public function get($name)
	{
		if (!isset($this->variables[$name])) {
			throw new \InvalidArgumentException("Cannot get($name), $name is not set.");
		}
		return $this->variables[$name];
	}
	
	public function set($name, $value)
	{
		$this->variables[$name] = $value;
	}
	
	/**
	 * Getter overload method.  Called when an $obj->field and field
	 * does not exist in the object's variable list.  In this case,
	 * it will get a db or smarty instance (for backwards 
	 * compatibility), or call get on the given field name.
	 *
	 * @param string The field to look up
	 * @return mixed The value for that field, if it exists
	 * @author Ted Kulp
	 **/
	public function __get($name)
	{
		if ($name == 'db')
			return \SilkDatabase::get_instance();
		else if ($name == 'smarty')
			return \SilkSmarty::get_instance();
		else
			return $this->get($name);
	}
	
    /**
     * Setter overload method.  Called when an $obj->field and field
     * does not exist in the object's variable list.  In this case,
     * it will get a db or smarty instance (for backwards 
     * compatibility), or call get on the given field name.
     *
     * @param string The field to set
     * @param string The value to set it to
     * @author Ted Kulp
     **/
	public function __set($name, $value)
	{
		if ($name != 'db' && $name != 'smarty')
			$this->set($name, $value);
	}
	
	public function __isset($name)
	{
		return isset($this->variables[$name]);
	}

	public function __unset($name)
	{
		unset($this->variables[$name]);
	}
	
	public function get_current_user()
	{
		return \SilkLogin::get_current_user();
	}
	
	/**
	 * Loads a cache of site preferences so we only have to do it once.
	 *
	 * @since 0.6
	 */
	public static function load_site_preferences()
	{
		$db = db();
		
		$result = array();

		$query = "SELECT sitepref_name, sitepref_value from ".db_prefix()."siteprefs";
		$dbresult = &$db->Execute($query);

		while ($dbresult && !$dbresult->EOF)
		{
			$result[$dbresult->fields['sitepref_name']] = $dbresult->fields['sitepref_value'];
			$dbresult->MoveNext();
		}

		if ($dbresult) $dbresult->Close();

		return $result;
	}

	/**
	 * Gets the given site prefernce
	 *
	 * @since 0.6
	 */
	public static function get_preference($prefname, $defaultvalue = '')
	{
		$value = $defaultvalue;

		if (count(self::$siteprefs) == 0)
		{
			self::$siteprefs = \SilkCache::get_instance()->call('SilkCache::load_site_preferences');
		}

		if (isset(self::$siteprefs[$prefname]))
		{
			$value = self::$siteprefs[$prefname];
		}

		return $value;
	}

	/**
	 * Removes the given site preference
	 *
	 * @param string Preference name to remove
	 */
	public static function remove_preference($prefname)
	{
		$db = db();

		$query = "DELETE from ".db_prefix()."siteprefs WHERE sitepref_name = ?";
		$result = $db->Execute($query, array($prefname));

		if (isset(self::$siteprefs[$prefname]))
		{
			unset(self::$siteprefs[$prefname]);
		}

		if ($result) $result->Close();
		\SilkCache::clear();
	}

	/**
	 * Sets the given site preference with the given value.
	 *
	 */
	public static function set_preference($prefname, $value)
	{
		$doinsert = true;

		$db = db();

		$query = "SELECT sitepref_value from ".db_prefix()."siteprefs WHERE sitepref_name = ".$db->qstr($prefname);
		$result = $db->Execute($query);

		if ($result && $result->RecordCount() > 0)
		{
			$doinsert = false;
		}

		if ($result) $result->Close();

		if ($doinsert)
		{
			$query = "INSERT INTO ".db_prefix()."siteprefs (sitepref_name, sitepref_value) VALUES (".$db->qstr($prefname).", ".$db->qstr($value).")";
			$db->Execute($query);
		}
		else
		{
			$query = "UPDATE ".db_prefix()."siteprefs SET sitepref_value = ".$db->qstr($value)." WHERE sitepref_name = ".$db->qstr($prefname);
			$db->Execute($query);
		}
		self::$siteprefs[$prefname] = $value;
		\SilkCache::clear();
	}
	
	public function add_include_path($path)
	{
		foreach (func_get_args() AS $path)
		{
			if (!file_exists($path) OR (file_exists($path) && filetype($path) !== 'dir'))
			{
				//trigger_error("Include path '{$path}' not exists", E_USER_WARNING);
				continue;
			}

			$paths = explode(PATH_SEPARATOR, get_include_path());

			if (array_search($path, $paths) === false)
				array_push($paths, $path);

			set_include_path(implode(PATH_SEPARATOR, $paths));
		}
	}

	public function remove_include_path($path)
	{
		foreach (func_get_args() AS $path)
		{
			$paths = explode(PATH_SEPARATOR, get_include_path());

			if (($k = array_search($path, $paths)) !== false)
				unset($paths[$k]);
			else
				continue;

			if (!count($paths))
			{
				//trigger_error("Include path '{$path}' can not be removed because it is the only", E_USER_NOTICE);
				continue;
			}

			set_include_path(implode(PATH_SEPARATOR, $paths));
		}
	}

	public function __destruct()
	{
		//*cough* Hack
		\SilkDatabase::close();
	}
}

# vim:ts=4 sw=4 noet
?>
