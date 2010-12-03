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

namespace silk\database\orm\acts_as;

/**
 * Class to easily allow your object to act as if it's in a list.  Includes
 * methods to move_up, move_down and automatically inserts new records at
 * the bottom of the list.
 * To use this class:
 * 1. Add an order_num field to your table's schema
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class ActsAsList extends ActsAs
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function before_save(&$obj)
	{
		//Fix the max value of "order_num" and set that before the save
		$field_name = $this->get_order_field($obj);
		$table_name = $obj->get_table();
		$curval = $obj->$field_name;
		if ($curval == null || intval($curval) < 1)
		{
			$new_map = array_flip($obj->field_maps);
			if (array_key_exists($field_name, $new_map)) $field_name = $new_map[$field_name];

			$params = array();
			$query = "SELECT max({$field_name}) FROM {$table_name}" . $this->generate_where_clause($obj, $params);
			
			$new_order_num = cms_db()->GetOne($query, $params);
			if ($new_order_num)
			{
				$obj->$field_name = $new_order_num + 1;
			}
			else
			{
				$obj->$field_name = 1;
			}
		}
	}
	
	public function can_move_up(&$obj)
	{
		$field_name = $this->get_order_field($obj);
		return $obj[$field_name] > 1;
	}
	
	public function move_up(&$obj)
	{
		$field_name = $this->get_order_field($obj);
		$table_name = $obj->get_table();

		if (!isset($obj->$field_name))
		{
			if ($this->can_move_up($obj))
			{
				$original_place = $obj[$field_name];

				$new_place = $original_place - 1;
				$new_map = array_flip($obj->field_maps);
				if (array_key_exists($field_name, $new_map)) $field_name = $new_map[$field_name];
			
				$params = array();
				$query = "UPDATE {$table_name} SET {$field_name} = {$original_place}" . $this->generate_where_clause($obj, $params, "{$field_name} = {$new_place}");
				cms_db()->Execute($query, $params);
			
				$obj->$field_name = $new_place;

				return $obj->save();
			}
		}

		return false;
	}
	
	public function can_move_down(&$obj)
	{
		$field_name = $this->get_order_field($obj);
		$count = $obj->find_count();
		return $obj[$field_name] < $count;
	}
	
	public function move_down(&$obj)
	{
		$field_name = $this->get_order_field($obj);
		$table_name = $obj->get_table();
		$id_field = $obj->id_field;

		if (!isset($obj->$field_name))
		{
			if ($this->can_move_down($obj))
			{
				$original_place = $obj[$field_name];

				$new_place = $original_place + 1;
				$new_map = array_flip($obj->field_maps);
				if (array_key_exists($field_name, $new_map)) $field_name = $new_map[$field_name];
			
				$params = array();
				$query = "UPDATE {$table_name} SET {$field_name} = {$original_place}" . $this->generate_where_clause($obj, $params, "{$field_name} = {$new_place}");
				cms_db()->Execute($query, $params);
			
				$obj->$field_name = $new_place;

				return $obj->save();
			}
		}

		return false;
	}
	
	public function after_delete(&$obj)
	{
		$field_name = $this->get_order_field($obj);
		$table_name = $obj->get_table();
		
		if (!isset($obj->$field_name))
		{
			$original_place = $obj[$field_name];
			
			$new_map = array_flip($obj->field_maps);
			if (array_key_exists($field_name, $new_map)) $field_name = $new_map[$field_name];
			
			$params = array();
			$query = "UPDATE {$table_name} SET {$field_name} = {$field_name} - 1" . $this->generate_where_clause($obj, $params, "{$field_name} > {$original_place}");
			cms_db()->Execute($query, $params);
		}
	}
	
	private function get_order_field(&$obj)
	{
		$field_name = 'order_num';
		if ($obj != null && isset($obj->order_field))
		{
			$field_name = $obj->order_field;
		}
		return $field_name;
	}
	
	private function generate_where_clause(&$obj, &$params, $extra_where = '')
	{
		$query_ext = '';

		if (isset($obj->list_filter_fields) && is_array($obj->list_filter_fields))
		{
			$query_ext = " WHERE " . $extra_where;

			foreach ($obj->list_filter_fields as $one_field)
			{
				$check_field = $one_field;
				$new_map = array_flip($obj->field_maps);
				if (array_key_exists($one_field, $new_map)) $check_field = $new_map[$one_field];
				if ($query_ext != ' WHERE ')
					$query_ext .= ' AND';

				$query_ext .= " {$check_field} = ?";
				$params[] = $obj->$check_field;
			}
		}

		return $query_ext;
	}
}

# vim:ts=4 sw=4 noet
