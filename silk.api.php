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
define("SILK_LIB_DIR", dirname(__FILE__));
define("DS", DIRECTORY_SEPARATOR);

//Valid prefixes for Silk Framework source files
define("PREFIXES","class,interface");

// Load

/**
 * The one and only autoload function for the system.  This basically allows us
 * to remove a lot of the require_once BS and keep the file loading to as much
 * of a minimum as possible.
 */
function silk_autoload($class_name)
{
	// get valid file prefixes
	$prefixes = explode(',',PREFIXES);
	$files = scan_classes($prefixes);

	//Loop through each prefix
	foreach ($prefixes as $prefix)
	{
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
			if (array_key_exists($namespace . $prefix .'.'. underscore($class_name) . '.php', $files))
			{
				require_once($files[$namespace . $prefix .'.'. underscore($class_name) . '.php']);
				break;
			}

			//Try once with a \ on the front
			if (array_key_exists("\\" . $namespace . $prefix .'.'. underscore($class_name) . '.php', $files))
			{
				require_once($files["\\" . $namespace . $prefix .'.'. underscore($class_name) . '.php']);
				break;
			}
		}
		
		if (array_key_exists($prefix .'.'. underscore($class_name) . '.php', $files))
		{
			require_once($files[$prefix .'.'. underscore($class_name) . '.php']);
			break;
		}
		else if (array_key_exists($prefix .'.'. strtolower($class_name) . '.php', $files))
		{
			require_once($files[$prefix .'.'. strtolower($class_name) . '.php']);
			break;
		}
	}
}


function get_prefixes() {
	return array('class', 'interface');
}

spl_autoload_register('silk_autoload');

function scan_classes()
{
	if (!isset($GLOBALS['class_dirs']))
	{
		$dir = join_path(SILK_LIB_DIR, 'classes');
		$GLOBALS['class_dirs'][$dir] = null;
	}

	$files = array();

	foreach (array_keys($GLOBALS['class_dirs']) as $one_dir)
	{
		$found_files = array();
		if ($GLOBALS['class_dirs'][$one_dir] == null && !is_array($GLOBALS['class_dirs'][$one_dir]))
		{
			scan_classes_recursive($one_dir, $found_files);
			foreach($found_files as $k => $v)
			{
				$namespaced = str_replace("/", "\\", str_replace($one_dir . DS, '', $v));

				if ($one_dir == join_path(SILK_LIB_DIR, 'classes'))
					$namespaced = "silk\\" . $namespaced;

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

	return $files;
}

function add_class_directory($dir)
{
	$GLOBALS['class_dirs'][$dir] = null;
}

function scan_classes_recursive($dir = '.', &$files)
{
	## Greg Froese 2008.12.30 - file_exists is necessary, this function fails and results in a fatal
	## 							error if we don't check for the dir's existence
	if (file_exists($dir))
	{
		$file = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
		while($file->valid())
		{
			if (!$file->isDot() && !$file->isDir())
			{
				$prefixes = explode(',', PREFIXES);
				foreach	($prefixes as $prefix)
				{
					if (starts_with(basename($file->getPathname()), $prefix.'.'))
					{
						#Pull off a path without $dir on it
						$rel_path = str_replace('/', '\\', str_replace($dir, '', $file->getPathname()));

						#See if it's one of the old naed files
						$old_school_class_name = preg_match('/(class|interface)\.silk_/', $file->getPathname());

						#See if this is a system directory, and make sure it doesn't start with class.silk_
						#If it does, then it's a namespace-less class and must be put down.
						if ($dir == join_path(SILK_LIB_DIR, 'classes') &&
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
				}
			}
			$file->next();
		}
	}
	return $files;
}

/**
 * Attempts to return the Silk Framework variable $silkVar.
 * If $silkVar does not exist, and a default is provided, then we set $silkVar to default.
 * <pre>
 * <code>
 * // These three calls will return exactly the same variable
 * $var1 = get()->variables['var'];
 * $var2 = get()->get('var');
 * $var3 = get('var');
 * if ($var1 === $var2 && $var1 === $var3) {
 *     echo 'all equal!';
 * } else {
 *     echo 'not equal!';
 * }
 * </code>
 * </pre>
 * @param $silkVar the Silk Framework variable to set
 * @param $default The value we want to set $silkVar to.
 * @throws SilkVariableNotFoundException If $silkVar doesn't exist and a non-null $default isn't provided.
 * @return The value of $silkVar
 */
function get($silkVar, $default = null)
{
	try {
		return \silk\core\Application::get_instance()->get($silkVar);
	} catch (InvalidArgumentException $e) {
		if (null != $default) {
			\silk\core\Application::get_instance()->set($silkVar, $default);
			return \silk\core\Application::get_instance()->get($silkVar);
		} 
		throw $e;	
	} 
}

/**
 * Sets $silkVar to $value.
 * @see get()
 * @param $silkVar the Silk Framework variable to set
 * @param $value The value we want to set $silkVar to.
 * @throws SilkVariableNotFoundException If $silkVar doesn't exist and a non-null $default isn't provided.
 * @return The new value of $silkVar
 */
function set($silkVar, $value) {
	return get($silkVar, $value);
}

/** 
 * @return global SilkApplication singleton
 *
 */
function silk() {
	return \silk\core\Application::get_instance();
}

/**
 * Returns a reference to the adodb connection singleton object.
 */
function db()
{
	return \silk\database\Database::get_instance();
}

/**
 * Returns the Rack request object
 */
function request()
{
	return silk()->request;
}

/**
 * Returns the Rack response object
 */
function response()
{
	return silk()->response;
}

/**
 * Returns a reference to the global smarty object.  Replaces
 * the global $gCms; $config =& $gCms->GetSmarty() routine.
 * @return Global Smarty Object
 */
function smarty()
{
	return \silk\display\Smarty::get_instance();
}

/**
 * Returns the instance of the logger.
 *
 * @return PEAR Log instance
 **/
function logger($handler = 'file', $name = '')
{
	return SilkLogger::get_instance($handler, $name);
}

/**
 * Returns the instance of the SilkForm object.
 *
 * @return SilkForm The SilkForm object
 **/
function forms()
{
	return SilkForm::get_instance();
}

/**
 * Joins a path together using proper directory separators
 * Taken from: http://www.php.net/manual/en/ref.dir.php
 *
 * @since 1.0
 */
function join_path()
{
 	$num_args = func_num_args();
	$args = func_get_args();
	$path = $args[0];

	if( $num_args > 1 )
	{
		for ($i = 1; $i < $num_args; $i++)
		{
			$path .= DS.$args[$i];
		}
	}

	return $path;
}

/**
 * Joins a path together using url separators
 *
 * @since 1.0
 */
function join_url()
{
 	$num_args = func_num_args();
	$args = func_get_args();
	$path = $args[0];

	if( $num_args > 1 )
	{
		for ($i = 1; $i < $num_args; $i++)
		{
			$path .= '/'.$args[$i];
		}
	}

	return $path;
}

function starts_with($str, $sub)
{
	return ( substr( $str, 0, strlen( $sub ) ) == $sub );
}

function ends_with( $str, $sub )
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
function coalesce_key($array, $val1, $val2, $filter = -1, $filter_options = array())
{
	if (isset($array[$val1]))
	{
		if ($filter > -1)
			return filter_var($array[$val1], $filter, $filter_options);
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
function remove_keys($array, $keys_to_remove)
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
 * @throws \silk\exception\InvalidKeyException if there are extra in $array keys
 **/
function are_all_keys_valid($array, $valid_keys)
{
	$invalid_keys = invalid_key($array, $valid_keys);
	if ($invalid_keys) {;
		throw new \silk\exception\InvalidKeyException(implode(', ', invalid_key($params, $default_params))); 
	} else {
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
function invalid_key($array, $valid_keys)
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

function array_search_keys($array, $keys_to_search)
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
function db_prefix()
{
	return \silk\database\Database::get_prefix();
}

function substr_match($str1, $str2, $reverse = false)
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
 * Setup a dependency to another component so models can be shared
 * Just a wrapper for add_class_directory()
 *
 * @param unknown_type $component_name
 */
function add_component_dependent($component_name)
{
	add_class_directory(join_path(dirname(dirname(SILK_LIB_DIR)), "components", $component_name, "models"));
//	$GLOBALS["class_dirs"][] = join_path(dirname(dirname(SILK_LIB_DIR)), "app", "components", $component_name, "models");
//	unset ($GLOBALS['dirscan']);
}

/**
 * Include all files in the supplied directory
 * @param $dir directory to search
 */
function load_additional_controllers($dir)
{
	$files = scandir($dir);
	foreach( $files as $file )
	{
		if( is_file(join_path($dir, $file)) && substr( $file, strlen( $file ) -4) == ".php" )
		{
			include_once( join_path( $dir, $file ) );
		}
	}
}

/**
 * Loads the setup.yml config file and returns it's contents as an associative array.
 *
 * @return hash of config file contents
 */
function load_config($configFiles = null)
{
	//static $modified = null;
	static $configHash = null;
	// Default config files. Will be added upon if we find more.
	// Note ordering here is important, to ensure the user config overrides any default settings
	if (null == $configFiles)
	{
		$configFiles = array(join_path(SILK_LIB_DIR, 'silk.config.yml'));
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
				$configHash = SilkYaml::load_file($configFile);
			//}

			if (isset($configHash['config_file']))
			{
				// add any additional config files seperated by commas, but trim any whitespace.
				$newConfigs = array_map('trim', explode(',', $configHash['config_file']));
				return load_config($newConfigs);
			}
		}
	}

	return $configHash;
}

/**
 * Returns value of $key in config file. Loads the config files if they haven't already been loaded.
 * @return Value of config entry $key. null if the key cannot be found.
 * @see load_config()
 */
function config($key)
{
	try
	{
		$config = get('config');
	}
	catch (Exception $e)
	{
		$config = get('config', load_config());
	}
	
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
function dump_var($var, $title = '', $htmloutput = true, $export_function = 'dump')
{
	return export_var($var, $title, $htmloutput, $export_function);
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
function export_var($var, $title = '', $html_output = true, $export_function = 'export')
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

function in_debug()
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
