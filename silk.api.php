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

/**
 * Functions that need to be in a global scope.  Mostly for utility and
 * shortcuts.
 *
 * @since 1.0
 */

//Defines
define("ROOT_DIR", dirname(dirname(dirname(__FILE__))));
define("SILK_LIB_DIR", dirname(__FILE__));
define("DS", DIRECTORY_SEPARATOR);

/**
 * The one and only autoload function for the system.  This basically allows us
 * to remove a lot of the require_once BS and keep the file loading to as much
 * of a minimum as possible.
 */
function silk_autoload($class_name)
{

	$files = scan_classes();
//	echo "<pre>"; var_dump($files); echo "</pre>";
	if (array_key_exists('class.' . underscore($class_name) . '.php', $files))
	{
		require($files['class.' . underscore($class_name) . '.php']);
	}
	else if (array_key_exists('class.' . strtolower($class_name) . '.php', $files))
	{
		require($files['class.' . strtolower($class_name) . '.php']);
	}
}

spl_autoload_register('silk_autoload');

function scan_classes()
{
	if (!isset($GLOBALS['class_dirs']))
	{
		$dir = array(join_path(SILK_LIB_DIR, 'classes'));
		$GLOBALS['class_dirs'] = $dir;
	}
	if (!isset($GLOBALS['dirscan']))
	{
		$files = array();
		foreach ($GLOBALS['class_dirs'] as $one_dir) {
			scan_classes_recursive($one_dir, $files);
		}
		$GLOBALS['dirscan'] = $files;

		return $files;
	}
	else
	{
		return $GLOBALS['dirscan'];
	}
}

function add_class_directory($dir)
{
	unset($GLOBALS['dirscan']);
	$GLOBALS['class_dirs'][] = $dir;
}

function scan_classes_recursive($dir = '.', &$files)
{
	## Greg Froese 2008.12.30 - file_exists is necessary, this function fails and results in a fatal
	## 							error if we don't check for the dir's existence
	if (file_exists($dir))
	{
		foreach(new DirectoryIterator($dir) as $file)
		{
			if (!$file->isDot() && $file->getFilename() != '.svn')
			{
				if ($file->isDir())
				{
					$newdir = $file->getPathname();
					scan_classes_recursive($newdir, $files);
				}
				else
				{
					if (starts_with(basename($file->getPathname()), 'class.')) {
						$files[basename($file->getPathname())] = $file->getPathname();
					}
				}
			}
		}
	}
	return $files;
}

/**
 * Returns the global SilkApplication singleton.
 */
function silk()
{
	return SilkApplication::get_instance();
}

/**
 * Returns a reference to the adodb connection singleton object.
 */
function db()
{
	return SilkDatabase::get_instance();
}

function orm($class = '')
{
	if ($class == '')
		return SilkObjectRelationalManager::get_instance();
	else
		return SilkObjectRelationalManager::get_instance()->$class;
}

/**
 * Returns a reference to the global smarty object.  Replaces
 * the global $gCms; $config =& $gCms->GetSmarty() routine.
 */
function smarty()
{
	return SilkSmarty::get_instance();
}

/**
 * Returns the instance of the logger.
 *
 * @return PEAR Log instance
 * @author Ted Kulp
 **/
function logger($handler = 'file', $name = '')
{
	return SilkLogger::get_instance($handler, $name);
}

/**
 * Returns the instance of the SilkForm object.
 *
 * @return SilkForm The SilkForm object
 * @author Ted Kulp
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
 * @author Ted Kulp
 * @since 1.1
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
 * @author Ted Kulp
 * @since 1.1
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
 * @param array The hash to check
 * @param array The hash to test against
 * @return boolean Returns false if there are extra keys
 * @author Ted Kulp
 **/
function are_all_keys_valid($array, $valid_keys)
{
	return invalid_key($array, $valid_keys) == null;
}

/**
 * Check to see if all keys in the given hash exist
 * in the check array.  If there are any extra, it will
 * return the name of the first extra one.
 *
 * @param array The hash to check
 * @param array The hash to test against
 * @return string The name of the extra key, if any.  If there are none, it returns null.
 * @author Ted Kulp
 **/
function invalid_key($array, $valid_keys)
{
	if (array_keys($valid_keys) != $valid_keys)
		$valid_keys = array_keys($valid_keys);

	foreach (array_keys($array) as $one_key)
	{
		if (!in_array($one_key, $valid_keys))
		{
			return $one_key;
		}
	}

	return null;
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
 * @author Ted Kulp
 **/
function redirect($to)
{
	SilkResponse::redirect($to);
}

/**
 * Returns the currently configured database prefix.
 *
 * @since 0.4
 */
function db_prefix()
{
	return SilkDatabase::get_prefix();
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
 * @author Greg Froese
 *
 * @param unknown_type $component_name
 */
function add_component_dependent($component_name) {
	add_class_directory(join_path(dirname(dirname(SILK_LIB_DIR)), "components", $component_name, "models"));
//	$GLOBALS["class_dirs"][] = join_path(dirname(dirname(SILK_LIB_DIR)), "app", "components", $component_name, "models");
//	unset ($GLOBALS['dirscan']);
}

/**
 * Include any additional class files in the component's controller directory
 * @author Greg Froese
 *
 */
function load_additional_controllers($dir) {
	$files = scandir($dir);
	foreach( $files as $file ) {
		if( is_file(join_path($dir, $file)) && substr( $file, strlen( $file ) -4) == ".php" ) {
			include_once( join_path( $dir, $file ) );
		}
	}
}
# vim:ts=4 sw=4 noet
?>