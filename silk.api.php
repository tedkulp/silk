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

/**
 * @file silk.api.php
 * Functions that need to be in a global scope.  Mostly for utility and
 * shortcuts.
 *
 * @since 1.0
 */

if (version_compare(PHP_VERSION, '5.3.0') < 0)
{
	echo "Silk Framework requires a minimum version of PHP 5.3.0\n";
	die();
}

//Defines
if (!defined('ROOT_DIR'))
{
	//If not set, default to 2 directories back and assume
	//we're in lib/silk
	define("ROOT_DIR", dirname(dirname(dirname(__FILE__))));
}

if (!defined('PUBLIC_DIR'))
{
	define('PUBLIC_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'public');
}

define("SILK_LIB_DIR", dirname(__FILE__));
define("DS", DIRECTORY_SEPARATOR);

// Load

/**
 * The one and only autoload function for the system.  This basically allows us
 * to remove a lot of the require_once BS and keep the file loading to as much
 * of a minimum as possible.
 */
function silkAutoload($class_name)
{
	//Get list of files
	$files = scanClasses();

	//Does the classname contain a namespace?
	if (strpos($class_name, "\\") !== FALSE)
	{
		//Pull the class_name off of the end
		$ary = explode("\\", $class_name);
		$class_name = array_pop($ary);

		//Now grab the rest
		$namespace = '';
		if (count($namespace))
			$namespace = implode("\\", $ary) . "\\";

		//See if the class exists in the cache
		if (array_key_exists($namespace . $class_name . '.php', $files))
		{
			require_once($files[$namespace . $class_name . '.php']);
			return;
		}

		//Try once with a \ on the front
		if (array_key_exists("\\" . $namespace . $class_name . '.php', $files))
		{
			require_once($files["\\" . $namespace . $class_name . '.php']);
			return;
		}
	}

	if (array_key_exists($class_name . '.php', $files))
	{
		require_once($files[$class_name . '.php']);
		return;
	}
	else if (array_key_exists($class_name . '.php', $files))
	{
		require_once($files[$class_name . '.php']);
		return;
	}

}

spl_autoload_register('silkAutoload');

function scanClasses()
{
	// If the final class list is set (because class directory
	// list hasn't changed), just return it.
	if (isset($GLOBALS['final_class_list']))
		return $GLOBALS['final_class_list'];

	if (!isset($GLOBALS['class_dirs']))
	{
		$dir = joinPath(SILK_LIB_DIR, 'lib');
		$GLOBALS['class_dirs'][$dir] = null;
	}

	$files = array();

	foreach (array_keys($GLOBALS['class_dirs']) as $one_dir)
	{
		$found_files = array();
		if ($GLOBALS['class_dirs'][$one_dir] == null && !is_array($GLOBALS['class_dirs'][$one_dir]))
		{
			scanClassesRecursive($one_dir, $found_files);
			foreach($found_files as $k => $v)
			{
				$namespaced = str_replace("/", "\\", str_replace($one_dir . DS, '', $v));

				if ($namespaced != $k)
				{
					$found_files[$namespaced] = $v;
				}
			}
			$GLOBALS['class_dirs'][$one_dir] = $found_files ? $found_files : array();
		}
		else
		{
			$found_files = $GLOBALS['class_dirs'][$one_dir];
		}
		
		$files = array_merge($found_files, $files);
	}

	// Set the list so we're not redoing this
	$GLOBALS['final_class_list'] = $files;

	return $files;
}

function addClassDirectory($dir)
{
	if (is_dir($dir))
		$GLOBALS['class_dirs'][$dir] = null;

	// Unset the final list since we need to rescan some directories
	unset($GLOBALS['final_class_list']);
}

function addIncludePath($path)
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

function removeIncludePath($path)
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

function scanClassesRecursive($dir = '.', &$files)
{
	## Greg Froese 2008.12.30 - file_exists is necessary, this function fails and results in a fatal
	## 							error if we don't check for the dir's existence
	if (file_exists($dir))
	{
		$class_dir = joinPath(SILK_LIB_DIR, 'classes');
		$file = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
		while($file->valid())
		{
			if (!$file->isDot() && !$file->isDir())
			{
				#Pull off a path without $dir on it
				$rel_path = str_replace('/', '\\', str_replace($dir, '', $file->getPathname()));

				#See if it's one of the old naed files
				$old_school_class_name = preg_match('/(class|interface)\.silk_/', $file->getPathname());

				#See if this is a system directory, and make sure it doesn't start with class.silk_
				#If it does, then it's a namespace-less class and must be put down.
				if ($dir == $class_dir &&
					$rel_path != '\\' . basename($file->getPathname()) &&
					!$old_school_class_name)
				{
					$rel_path = '\\silk' . $rel_path;
				}
				#If they're using the lib directory, treat the namespaces
				#the same as the silk ones
				else if (preg_match('/lib\/(.*?)\/classes$/', $dir, $matches) && !$old_school_class_name)
				{
					if (count($matches) == 2)
						$rel_path = '\\' . $matches[1] . $rel_path;
				}
					
				#If this is a system path, then we add classes by key based on their full
				#classpath.  Otherwise, we just add files.  Eventually, we may need to
				#actually look at the files to find a namespace and store by that, but
				#for now, this will work just looking at directory structure.
				#
				#The .silk_ check is only for backwards compat reasons and will be removed
				#once all the core classes are properly namespaced.
				if ($rel_path != '\\' . basename($file->getPathname()) && !$old_school_class_name)
				{
					$files[$rel_path] = $file->getPathname();
				}
				else
				{
					$files[basename($file->getPathname())] = $file->getPathname();
				}
			}
			$file->next();
		}
	}
	return $files;
}

/**
 * Attempts to return the Silk Framework variable $silk_var.
 * @param $silk_var the Silk Framework variable to set
 * @param $default The value to return if the variable doesn't exist.
 * @return The value of $silk_var
 */
function get($silk_var, $default = null)
{
	$val = \silk\core\Application::getInstance()->get($silk_var);

	if ($val == null)
		return $default;

	return $val;

}

/**
 * Sets $silk_var to $value.
 * @param $silkVar the Silk Framework variable to set
 * @param $value The value we want to set $silk_var to.
 */
function set($silk_var, $value)
{
	\silk\core\Application::getInstance()->set($silk_var, $value);
}

/** 
 * @return global SilkApplication singleton
 *
 */
function silk()
{
	return \silk\core\Application::getInstance();
}

/**
 * Returns a reference to the adodb connection singleton object.
 */
function db()
{
	return \silk\database\Database::getConnection();
}

/**
 * Returns the Rack request object
 */
function request()
{
	return \silk\core\Application::getInstance()->request;
}

/**
 * Returns the Rack response object
 */
function response()
{
	return \silk\core\Application::getInstance()->response;
}

/**
 * Returns a reference to the global smarty object.
 * @return Global Smarty Object
 */
function smarty()
{
	return \silk\display\Smarty::getInstance();
}

/**
 * Returns the instance of the logger.
 *
 * @return PEAR Log instance
 **/
function logger($handler = 'file', $name = '')
{
	return SilkLogger::getInstance($handler, $name);
}

/**
 * Returns the instance of the SilkForm object.
 *
 * @return SilkForm The SilkForm object
 **/
function forms()
{
	return SilkForm::getInstance();
}

/**
 * Joins a path together using proper directory separators
 * Taken from: http://www.php.net/manual/en/ref.dir.php
 *
 * @since 1.0
 */
function joinPath()
{
	return implode(DS, array_filter(func_get_args()));
}

/**
 * Joins a path together using url separators
 */
function joinUrl()
{
	return implode('/', array_filter(func_get_args()));
}

/**
 * Joins a full class name w/ namspaces using backslashes
 */
function joinNamespace()
{
	return implode('\\', array_filter(func_get_args()));
}

function startsWith($str, $sub)
{
	return ( substr( $str, 0, strlen( $sub ) ) == $sub );
}

function endsWith( $str, $sub )
{
	return ( substr( $str, strlen( $str ) - strlen( $sub ) ) == $sub );
}

/**
 * Returns given $lower_case_and_underscored_word as a camelCased word.
 * Taken from cakephp (http://cakephp.org)
 * Licensed under the MIT License
 *
 * @param string $lower_case_and_underscored_word Word to camelize
 * @return string Camelized word. likeThis.
 */
function camelize($lower_case_and_underscored_word)
{
	return str_replace(" ", "", ucwords(str_replace("_", " ", $lower_case_and_underscored_word)));
}

/**
 * Returns an underscore-syntaxed ($like_this_dear_reader) version of the $camel_cased_word.
 * Taken from cakephp (http://cakephp.org)
 * Licensed under the MIT License
 *
 * @param string $camel_cased_word Camel-cased word to be "underscorized"
 * @return string Underscore-syntaxed version of the $camel_cased_word
 */
function underscore($camel_cased_word)
{
	return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camel_cased_word));
}

/**
 * Returns a human-readable string from $lower_case_and_underscored_word,
 * by replacing underscores with a space, and by upper-casing the initial characters.
 * Taken from cakephp (http://cakephp.org)
 * Licensed under the MIT License
 *
 * @param string $lower_case_and_underscored_word String to be made more readable
 * @return string Human-readable string
 */
function humanize($lower_case_and_underscored_word)
{
	return ucwords(str_replace("_", " ", $lower_case_and_underscored_word));
}

/**
 * Looks through the hash given.  If a key named val1 exists, then it's value is
 * returned.  If not, then val2 is returned.  Furthermore, passing one of the php
 * filter ids (http://www.php.net/manual/en/ref.filter.php) will filter the
 * returned value.
 *
 * @param array The has to parse through
 * @param string The key to look for
 * @param mixed The value to return if the key isn't found
 * @param integer An optional filter id to pass the returned value through
 * @param array Optional parameters for the filter_var call
 * @return mixed The result of the coalesce
 **/
function coalesceKey($array, $val1, $val2, $filter = -1, $filter_options = array())
{
	if (isset($array[$val1]))
	{
		if ($filter > -1)
			return filterVar($array[$val1], $filter, $filter_options);
		else
			return $array[$val1];
	}
	return $val2;
}

/**
 * Finds all keys_to_remove in the hash, removes them,
 * and returns the new hash.
 *
 * @param array The original hash
 * @param array The names of the keys to remove
 * @return array The result of the key removal
 */
function removeKeys($array, $keys_to_remove)
{
	if (is_array($array))
	{
		foreach ($keys_to_remove as $k)
		{
			if (array_key_exists($k, $array))
			{
				unset($array[$k]);
			}
		}
	}

	return $array;
}

/**
 * Check to see if all keys in the given hash exist
 * in the check array.  If there are any extra, it will
 * return false.
 *
 * @param $array array The hash to check
 * @param $valid_keys array The hash to test against
 **/
function areAllKeysValid($array, $valid_keys)
{
	$invalid_keys = invalidKey($array, $valid_keys);
	var_dump($invalid_keys);
	if ($invalid_keys)
	{
		throw new \silk\exception\InvalidKeyException(implode(', ', invalid_key($params, $default_params))); 
	}
	else
	{
		return !$invalid_keys;
	}
}

/**
 * Check to see if all keys in the given hash exist
 * in the check array.  If there are any extra, it will
 * return the name of the first extra one.
 *
 * @param array The hash to check
 * @param array The hash to test against
 * @return array of the extra keys, if any, otherwise returns false.
 **/
function invalidKey($array, $valid_keys)
{
	if (array_keys($valid_keys) != $valid_keys)
		$valid_keys = array_keys($valid_keys);

	foreach (array_keys($array) as $one_key)
	{
		if (!in_array($one_key, $valid_keys))
		{
			$invalid_keys[] = $one_key;
		}
	}
	
	if (count($invalid_keys) > 0) {
		return $invalid_keys;
	}

	return false;
}

function arraySearchKeys($array, $keys_to_search)
{
	$result = array();

	foreach ($array as $k=>$v)
	{
		if (in_array($k, $keys_to_search))
		{
			$result[$key] = $value;
		}
	}

	return $result;
}

/**
 * Global wrapper for CmsResponse::redirect()
 *
 * @param $to The url to redirect to
 *
 * @return void
 **/
function redirect($to)
{
	\silk\action\Response::redirect($to);
}

/**
 * Returns the currently configured database prefix.
 *
 * @since 0.4
 */
function dbPrefix()
{
	return \silk\database\Database::getPrefix();
}

function substrMatch($str1, $str2, $reverse = false)
{
	$len = strlen($str1) <= strlen($str2) ? strlen($str1) : strlen($str2);
	$i = 0;

	$cmpstr1 = $str1;
	$cmpstr2 = $str2;

	if ($reverse)
	{
		$cmpstr1 = strrev($str1);
		$cmpstr2 = strrev($str2);
	}

	for (; $i < $len; $i++)
	{
		if (!isset($cmpstr1[$i]) || !isset($cmpstr2[$i]) || $cmpstr1[$i] != $cmpstr2[$i])
		{
			break;
		}
	}

	if ($reverse)
	{
		$i = strlen($str1) - $i;
		return substr($str1, $i);
	}
	else
	{
		return substr($str1, 0, $i);
	}

}

/**
 * Include all files in the supplied directory
 * @param $dir directory to search
 */
function loadAdditionalControllers($dir)
{
	$files = scandir($dir);
	foreach( $files as $file )
	{
		if( is_file(joinPath($dir, $file)) && substr( $file, strlen( $file ) -4) == ".php" )
		{
			include_once( joinPath( $dir, $file ) );
		}
	}
}

/**
 * Loads the config.php file and returns it's contents as an associative array.
 *
 * @return hash of config file contents
 */
function loadConfig($config_file = null)
{
	if ($config_file == null)
		$config_file = joinPath(ROOT_DIR, 'config', 'config.php');

	$loaded_config = array();

	if (is_file($config_file))
	{
		{
			$config = null;
			include($config_file);

			if ($config != null)
			{
				$loaded_config = $config;
			}
		}
	}

	//Now look for any extension config files
	$dirs = silk()->getExtensionDirectories();
	foreach ($dirs as $one_dir)
	{
		if (is_file(joinPath($one_dir, 'config', 'config.php')))
		{
			{
				$config = null;
				include(joinPath($one_dir, 'config', 'config.php'));

				if ($config != null)
				{
					$loaded_config = array_merge($loaded_config, $config);
				}
			}
		}
	}

	return $loaded_config;
	/*
	//static $modified = null;
	static $configHash = null;
	// Default config files. Will be added upon if we find more.
	// Note ordering here is important, to ensure the user config overrides any default settings
	if (null == $configFiles)
	{
		$configFiles = array(joinPath(SILK_LIB_DIR, 'silk.config.yml'));
	}

	// get any config files
	foreach ($configFiles as $configFile)
	{
		$configFile = array_shift($configFiles);
		if (is_file($configFile))
		{
			// only bother loading the file if it has changed since
			// the last time we read it
			//$current_modified = filemtime($configFile);
			//if ($current_modified != $modified)
			//{
			//	$modified = $current_modified;
				$configHash = SilkYaml::loadFile($configFile);
			//}

			if (isset($configHash['config_file']))
			{
				// add any additional config files seperated by commas, but trim any whitespace.
				$newConfigs = array_map('trim', explode(',', $configHash['config_file']));
				return loadConfig($newConfigs);
			}
		}
	}

	return $configHash;
	*/
}

/**
 * Returns value of $key in config file. Loads the config files if they haven't already been loaded.
 * @return Value of config entry $key. null if the key cannot be found.
 * @see load_config()
 */
function config($key)
{
	$config = get('config');

	if (isset($config[$key]))
	{
		return $config[$key];
	}
	else
	{
		return null;
	}
}

/**
 * Handy debugging/utility function for returning the contents of a variable in an html/console friendly manner.
 * Uses var_dump output. Do not leave this function in production code. Debugging use only.
 * @see export_var 
 */
function dumpVar($var, $title = '', $htmloutput = true, $export_function = 'dump')
{
	return exportVar($var, $title, $htmloutput, $export_function);
}

/**
 * Handy utility function for returning the contents of a variable in an html/console friendly manner. 
 * Uses var_export output. Do not leave this function in production code. Debugging use only.
 * @param $var The variable to get information about, eg $myvariable
 * @param $title Descriptive title for this output, such as the variable name, eg "\$myVariable = "
 * @param $html_output bool if true will return html formatted output, otherwise output is formatted for a console. Default true
 * @param $info_type 'export' or 'dump'. Selects output format. Uses var_export or var_dump respectively. If unknown value selected, defaults to var_export. Default var_export. In most cases you'll want to simply use dump_var instead of this parameter. 
 * @return html/console variable information
 */
function exportVar($var, $title = '', $html_output = true, $export_function = 'export')
{
	@ob_start();	
	if ($export_function != 'export' || $export_function != 'dump')
	{
		$export_function = 'var_export';
	}
	else
	{
		$export_function = "var_$export_function";
	}

	if ($html_output)
	{
		echo "\n<h3>$title</h3>\n";
		echo "<pre>\n";
		$export_function($var);
		echo "\n</pre>\n";
	}
	else
	{
		echo "\n----------\n";
		echo "$title\n";
		echo "\n";
		$export_function($var);
		echo "\n";
		echo "\n----------\n";
	}

	return @ob_get_clean();
}

function inDebug()
{
	return config("debug") == true;
}

/**
 * Dummy function (for now)
 */
function __($lang)
{
	return $lang;
}

/**
 * Dummy function (for now)
 */
function lang($lang)
{
	return $lang;
}

# vim:ts=4 sw=4 noet
