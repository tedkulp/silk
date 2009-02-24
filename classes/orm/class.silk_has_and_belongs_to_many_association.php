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
 * Class for handling a one-to-many assocation.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkHasAndBelongsToManyAssociation extends SilkObjectRelationalAssociation
{
	var $child_class = '';
	var $join_table = '';
	var $join_other_id_field = '';
	var $join_this_id_field = '';

	/**
	 * Create a new has_many association.
	 *
	 * @author Ted Kulp
	 **/
	public function __construct($association_name)
	{
		parent::__construct($association_name);
	}
	
	/**
	 * Returns the associated has_many association's objects.
	 *
	 * @return array Any array of objects, if they exist.
	 * @author Ted Kulp
	 **/
	public function get_data(&$obj)
	{
		return $this->fill_data($obj);
	}
	
	private function fill_data(&$obj)
	{
		$ary = null;
		if ($obj->has_association($this->association_name))
		{
			$ary = $obj->get_association($this->association_name);
		}
		else
		{
			$ary = new SilkAssociationCollection();
			if ($this->child_class != '' && $this->join_table != '')
			{
				$class = orm()->{$this->child_class};
				$table = $class->get_table();
				$other_id_field = $class->id_field;
			
				if ($obj->{$obj->id_field} > -1)
				{
					$queryattrs = $this->extra_params;
					$queryattrs['joins'] = "INNER JOIN {$this->join_table} ON {$this->join_table}.{$this->join_other_id_field} = {$table}.{$other_id_field}";
					$conditions = "{$this->join_table}.{$this->join_this_id_field} = ?";
					$params = array($obj->{$obj->id_field});
					if (array_key_exists('conditions', $this->extra_params))
					{
						$conditions = "({$conditions}) AND ({$this->extra_params['conditions'][0]})";
						if (count($this->extra_params['conditions']) > 1)
						{
							$params = array_merge($params, array_slice($this->extra_params['conditions'], 1));
						}
					}
					$queryattrs['conditions'] = array_merge(array($conditions), $params);
					$ary->children = $class->find_all($queryattrs);
					$obj->set_association($this->association_name, $ary);
				}
			}
		}
		return $ary;
	}
}

# vim:ts=4 sw=4 noet
?>