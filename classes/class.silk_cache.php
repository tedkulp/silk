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

/**
 * Wrapper class around Cache:Lite for caching full pages and function output.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkCache extends SilkObject
{
	static private $instances = null;
	private $cache = null;
	
	function __construct($type = 'function')
	{
		parent::__construct();

		// Set a few options
		$options = array(
		    'cacheDir' => join_path(ROOT_DIR, 'tmp', 'cache'.DS),
		    'lifeTime' => 300
		);

		if ($type == 'function')
		{
			//if (!SilkConfig::get('function_caching') || SilkConfig::get('debug'))
			//if (!SilkConfig::get('function_caching'))
			//	$options['caching'] = false;

			require_once(join_path(SILK_LIB_DIR, 'pear', 'cache', 'lite', 'Function.php'));
			$this->cache = new Cache_Lite_Function($options);
			$this->cache->_fileNameProtection = false;
		}
		else
		{
			require_once(join_path(SILK_LIB_DIR, 'pear', 'cache', 'lite', 'Function.php'));
			$this->cache = new Cache_Lite($options);
			$this->cache->_fileNameProtection = false;
		}
	}
	
	public static function get_instance($type = 'function')
	{
		if (self::$instances == null)
		{
			self::$instances = array();
		}

		if (empty(self::$instances[$type]))
		{
			self::$instances[$type] = new SilkCache($type);
		}

		return self::$instances[$type];
	}
	
	public function get($id, $group = 'default', $doNotTestCacheValidity = FALSE)
	{
		return $this->cache->get($id, $group, $doNotTestCacheValidity);
	}
	
	public function save($data, $id = NULL, $group = 'default')
	{
		return $this->cache->save($data, $id, $group);
	}
	
	public function call()
	{
		$args = func_get_args();
		return call_user_func_array(array($this->cache, 'call'), $args);
	}
	
	public function drop()
	{
		$args = func_get_args();
		return call_user_func_array(array($this->cache, 'drop'), $args);
	}
	
	public function clean($group = FALSE, $mode = 'ingroup')
	{
		return $this->cache->clean($group, $mode);
	}
	
	static public function clear($group = FALSE, $mode = 'ingroup')
	{
		return self::get_instance()->clean($group, $mode);
	}
}

# vim:ts=4 sw=4 noet
?>