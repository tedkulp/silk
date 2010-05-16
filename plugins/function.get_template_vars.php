<?php
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
//
// Author: Tim Oxley

/**
	Prints known smarty variables to your page, recursively to a certain depth, also printing the operand to access the variables (. or ->).
	@param depth integer How deep to recurse.
	\code
		{get_template_vars}
		{* 
		Example Result:
				$SCRIPT_NAME = /index.php 
				$params = Array ( 
					.id =  
					.action = index 
					.controller = entry 
					.1 = entry 
				) 

				$entries = Array ( 
		) 
		*}
	
		{get_template_vars depth=1}

		{* 
		Example Result:
		
			$SCRIPT_NAME = /index.php 
			$params = Array ( ) 

			$entries = Array ( ) 
		*}

	\endcode
*/

function smarty_function_get_template_vars($params, &$smarty)
{
	$smarty = SilkSmarty::get_instance();
	$tpl_vars = $smarty->getTemplateVars();
	$str = '';

	// If depth provided, use that. Otherwise, use default from get_var_objects
	if(isset($params['depth'])) {
		$str = get_var_contents($tpl_vars, $params['depth']);
	} else {
		$str = get_var_contents($tpl_vars);
	}

	return "<pre>$str</pre>";
}

/* Recursive helper function to drill down into data */
function get_var_contents($objects, $max_depth = 2, $depth = 0, $padding = '', $parent = array()) {
	$str = '';
	// Put in the right prefix so it's clear how to access the property
	if ($depth < $max_depth) {
		foreach( $objects as $key => $value ) {
			$prefix = '$';
			if (is_array($parent) && $depth > 0) {
				if (count($parent) > 0) {
					
					$directParent = end($parent);
					$parentValue = $directParent['parentValue']; 
					if (is_object($parentValue)) {
						$prefix = '->';
						
					} else if (is_array($parentValue)) {
						$prefix = '.';
					}
					reset($parent);
				}
			}

			$paddingSet = $padding;
			// check if this is a compound object
			if (is_array($value) || is_object($value)) {
				if (is_object($value) && ! is_array($value)) {	
					$class = get_class($value) . ' ';
				}

				$str .= $padding . $prefix . "$key = ".$class.$value." ( ";
				// Add padding and new lines as necessary	
				if ($depth < ($max_depth - 1)) {
					$str .= "\n";
					$paddingSet .= "\t";
				}
				
				$parentAdd = $parent;
				$parentAdd[] = array('parentKey' => $key, 'parentValue' => $value); 
				// Recursion occurs here.	
				$str .= get_var_contents($value, $max_depth, $depth + 1, $paddingSet, $parentAdd) . ") \n\n";
			} else {
				$str .= $padding . $prefix .$key." = $value \n";
			}
			
		}
	  }
	return $str;
}
