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

class SilkAjax extends SilkObject
{
	var $result = '';

	function __construct()
	{
		parent::__construct();
	}
	
	function replace_html($selector, $text)
	{
		$text = str_replace("'", "\\'", $text);
		$this->script("\$('{$selector}').html('{$text}')");
	}
	
	function replace($selector, $attribute, $text)
	{
		$text = str_replace("'", "\\'", $text);
		$this->script("\$('{$selector}').attr('{$attribute}', '{$text}')");
	}
	
	function insert($selector, $text, $position = "append")
	{
		$text = str_replace("'", "\\'", $text);
		$position = trim(strtolower($position));
		if ($position == "before" || $position == "after" || $position == "prepend" || $position == "append")
			$this->script("\$('{$selector}').{$position}('{$text}')");
	}
	
	function remove($selector)
	{
		$this->script("\$('{$selector}').remove()");
	}
	
	function show($selector)
	{
		$this->script("\$('{$selector}').show()");
	}
	
	function hide($selector)
	{
		$this->script("\$('{$selector}').hide()");
	}
	
	function toggle($selector)
	{
		$this->script("\$('{$selector}').toggle()");
	}
	
	function script($text)
	{
		$this->result .= '<sc><t><![CDATA[' . $text . ']]></t></sc>';
	}
	
	function get_result()
	{
		header("Content-Type: text/xml; charset=utf-8");
		return '<?xml version="1.0" encoding="utf-8"?><ajax>' . $this->result . '</ajax>';
	}
}

# vim:ts=4 sw=4 noet
?>