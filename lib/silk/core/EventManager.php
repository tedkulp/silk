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

namespace silk\core;

//use \silk\performance\Profiler;

class EventManager extends Object
{
	static private $event_store = null;
	
	static public function init_store()
	{
		if (self::$event_store == null)
		{
			self::$event_store = array();
		}
	}

	static public function register_event_handler($name, $function, $top = false)
	{
		self::init_store();

		//Create the store for this event if it doesn't exist
		if (!isset(self::$event_store[$name]))
		{
			self::$event_store[$name] = array();
		}

		//Make sure they passed a callback of some sort.
		//If so, add it to the queue.
		if (is_callable($function))
		{
			//Profiler::get_instance()->mark('Registered event: ' . $name);
			if ($top && count(self::$event_store[$name]) > 0)
				array_unshift(self::$event_store[$name], $function);
			else
				self::$event_store[$name][] = $function;
			return true;
		}

		return false;
	}

	static public function remove_event_handler($name, $function)
	{
		self::init_store();

		if (!empty(self::$event_store[$name]))
		{
			$found = null;
			foreach (self::$event_store[$name] as $k=>$callback)
			{
				if ($callback == $function)
				{
					$found = $k;
				}
			}

			if ($found !== null)
			{
				unset(self::$event_store[$name][$found]);
				return true;
			}
		}

		return false;
	}
	
	static public function remove_event($name)
	{
		self::init_store();
		
		if (!empty(self::$event_store[$name]))
		{
			unset(self::$event_store[$name]);
			return true;
		}
		
		return false;
	}

	static public function send_event($name, $arguments = array(), $first_time = true)
	{
		self::init_store();
		
		$send_params = array($name, &$arguments);
		//Profiler::get_instance()->mark('Trying event: ' . $name);
		if (isset(self::$event_store[$name]))
		{
			foreach (self::$event_store[$name] as &$one_method)
			{
				if (is_callable($one_method))
				{
					call_user_func_array($one_method, $send_params);
				}
			}
		}
	}
	
	static public function list_registered_events($with_counts = false)
	{
		self::init_store();
		
		if ($with_counts)
		{
			$res = array();
			foreach (self::$event_store as $k=>$v)
				$res[$k] = count(self::$event_store[$k]);
			return $res;
		}
		
		return array_keys(self::$event_store);
	}
	
	static public function is_registered_event($name)
	{
		self::init_store();
		
		return isset(self::$event_store[$name]);
	}
}

# vim:ts=4 sw=4 noet
