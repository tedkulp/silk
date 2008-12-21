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
// 
// File apapted from (do not remove the copyright below!): 
// 
// @version profiler.php 5270 2006-10-02 03:30:29Z webImagery
// @package Joomla
// @copyright Copyright (C) 2005 - 2006 Open Source Matters. All rights reserved.
// @license GNU/GPL, see LICENSE.php
// Joomla! is free software. This version may have been modified pursuant
// to the GNU General Public License, and as distributed it includes or
// is derivative of works licensed under the GNU General Public License or
// other free or open source software licenses.
// See COPYRIGHT.php for copyright notices and details.

/**
 * Class for handling profiling of various aspects of the system.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkProfiler extends SilkObject
{
	/**
	 * Constructor
	 *
	 * @access protected
	 * @param string Prefix for mark messages
	 **/
	function __construct( $prefix = '', $start_time = null )
	{
		$this->_start = ($start_time == null ? $this->get_microtime() : $start_time);
		$this->_prefix = $prefix;
		$this->_buffer = array();
	}
	
	/**
	 * Returns a reference to the global Profiler object, only creating it
	 * if it doesn't already exist.
	 *
	 * This method must be invoked as:
	 *              <pre>  $browser = SilkProfiler::getInstance([$prefix]);</pre>
	 *
	 * @access public
	 * @return SilkProfiler  The Profiler object.
	 **/
	public static function get_instance($prefix = '', $start_time = null)
	{
		static $instances;

		if (!isset($instances))
		{
			$instances = array();
		}

		if (empty($instances[$prefix]))
		{
			$instances[$prefix] = new CmsProfiler($prefix, $start_time);
		}

		return $instances[$prefix];
	}

	/**
	 * Output a time mark
	 *
	 * @access public
	 * @var string A label for the time mark
	 **/
	function mark( $label )
	{
		$mark = @sprintf ( "\n<div class=\"profiler\">$this->_prefix %.4f $label</div>", $this->get_microtime() - $this->_start );
		$this->_buffer[] = $mark;
		return $mark;
	}
	
	function get_time()
	{
		return sprintf("%.4f", $this->get_microtime() - $this->_start);
	}
	
	/**
	 * Reports on the buffered marks
	 *
	 * @access public
	 * @param string Glue string
	 **/
	function report( $memory = true, $database = true, $glue='' )
	{
		//$db = cms_db();
		
		echo '<div id="profiler_output" style="font-size: .75em;">';

		echo implode( $glue, $this->_buffer );
		if ($memory)
		{
			echo '<br />';
			echo $this->get_memory();
		}
		if ($database)
		{
			echo '<br />' . CmsDatabase::query_count() . ' queries executed';
		}
		
		echo '</div>';
	}
	
	/**
	 *
	 * @access public
	 * @return float The current time
	 **/
	public static function get_microtime()
	{
		list( $usec, $sec ) = explode( ' ', microtime() );
		return ((float)$usec + (float)$sec);
	}
	
	/**
	 *
	 * @access public
	 * @return int The memory usage
	 **/
	public static function get_memory()
	{
		static $isWin;

		if (function_exists( 'memory_get_usage' ))
		{
			return memory_get_usage();
		}
		else
		{
			if (is_null( $isWin ))
			{
				$isWin = (substr(PHP_OS, 0, 3) == 'WIN');
			}
			if ($isWin)
			{
				// Windows workaround
				$output = array();
				$pid = getmypid();
				exec( 'tasklist /FI "PID eq ' . $pid . '" /FO LIST', $output );
				if (!isset($output[5]))
				{
					$output[5] = null;
				}
				return substr( $output[5], strpos( $output[5], ':' ) + 1 );
			}
			else
			{
				return 0;
			}
		}
	}
}

# vim:ts=4 sw=4 noet
?>