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
 * Class to easily add attributes to your ORM class.
 * To use this class:
 * 1. Create variables named $attr_module and $attr_extra in your class.
 * 2. Set them to the name of your module and an optional "namespace"
 *    (ex. $attr_module = 'MyModule';  $attr_extra = 'Widget';)
 *
 * @package default
 * @author Ted Kulp
 **/
class SilkActsAsAttributed extends SilkActsAs
{
	function __construct()
	{
		parent::__construct();
	}
	
	function check_variables_are_set(&$obj)
	{
		if (!isset($obj->attr_module) || !isset($obj->attr_extra))
		{
			die('Must set the $attr_module and $attr_extra variables to use SilkActsAsAttributed');
		}
	}
	
	function get_attribute_defnitions(&$obj)
	{
		$this->check_variables_are_set($obj);
		return cms_orm('SilkAttributeDefinition')->find_all_by_module_and_extra_attr($obj->attr_module, $obj->attr_extra);
	}
	
	function set_attribute_definition(&$obj, $name, $type = 'text', $optional = false, $user_generated = false)
	{
		$this->check_variables_are_set($obj);
		
		//See if it exists -- return true
		foreach ($this->get_attribute_defnitions($obj) as $one_def)
		{
			if ($one_def->name == $name)
			{
				return true;
			}
		}
		
		//Create the new one
		$def = new SilkAttributeDefinition();
		$def->module = $obj->attr_module;
		$def->extra_attr = $obj->attr_extra;
		$def->name = $name;
		$def->attribute_type = $type;
		$def->optional = $optional;
		$def->user_generated = $user_generated;
		return $def->save();
	}
	
	function get_attribute_by_name(&$obj, $attribute_name)
	{
		$this->check_variables_are_set($obj);
		$prefix = CMS_DB_PREFIX;
		return cms_orm('SilkAttribute')->find(array('joins' => "INNER JOIN {$prefix}attribute_defns ON {$prefix}attribute_defns.id = {$prefix}attributes.attribute_id", 'conditions' => array("{$prefix}attribute_defns.module = ? AND {$prefix}attribute_defns.extra_attr = ? AND {$prefix}attribute_defns.name = ? AND {$prefix}attributes.object_id = ?", $obj->attr_module, $obj->attr_extra, $attribute_name, $obj->id)));
	}
	
	function get_attribute_value(&$obj, $attribute_name)
	{
		$attribute = $this->get_attribute_by_name($obj, $attribute_name);

		if ($attribute != null)
			return $attribute->content;

		return null;
	}
	
	function set_attribute_value(&$obj, $attribute_name, $attribute_value)
	{
		$attribute = $this->get_attribute_by_name($obj, $attribute_name);

		if ($attribute != null)
		{
			$attribute->content = $attribute_value;
			return $attribute->save();
		}

		return false;
	}

}

# vim:ts=4 sw=4 noet
?>