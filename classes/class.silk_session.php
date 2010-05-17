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
 * Static methods for handling session creation.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkSession extends \silk\core\Object
{
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Sets up the session properly for the system
	 *
	 * @return void
	 * @author Ted Kulp
	 **/
	public static function setup()
	{
		#Setup session with different id and start it
		@session_name('SILKSESSID' . SilkSession::generate_session_key());
		@ini_set('url_rewriter.tags', '');
		@ini_set('session.use_trans_sid', 0);
		
		if(!@session_id())
		{
		    @session_start();
		}
	}
	
	/**
	 * Generates a string that should be unique value depending on the directory
	 * that Silk resides in.  Allows us to have multiple Silk installs or apps on 
	 * the same domain and not share sessions.
	 *
	 * @return string
	 * @author Ted Kulp
	 **/
	public static function generate_session_key()
	{
		return substr(md5(dirname(__FILE__)), 0, 8);
	}
}

# vim:ts=4 sw=4 noet
?>
