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
 * Class for handling a belongs_to assocation.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkBelongsToAssociation extends SilkObjectRelationalAssociation
{
	var $belongs_to_obj = null;
	var $belongs_to_class_name = '';
	var $child_field = '';

	/**
	 * Create a new belongs_to association.
	 *
	 * @author Ted Kulp
	 **/
	public function __construct($association_name)
	{
		parent::__construct($association_name);
	}
	
	/**
	 * Returns the associated belongs_to association's object.
	 *
	 * @return mixed The object, if it exists.  null if not.
	 * @author Ted Kulp
	 **/
	public function get_data(&$obj)
	{
		$belongs_to = null;
		if ($obj->has_association($this->association_name))
		{
			$belongs_to = $obj->get_association($this->association_name);
		}
		else
		{
			if ($this->belongs_to_class_name != '' && $this->child_field != '')
			{
				$class = orm()->{$this->belongs_to_class_name};
				if ($obj->{$this->child_field} > -1)
				{
					$belongs_to = call_user_func_array(array(&$class, 'find_by_id'), $obj->{$this->child_field});
					$obj->set_association($this->association_name, $belongs_to);
				}
			}
		}
		return $belongs_to;
	}
}

# vim:ts=4 sw=4 noet
?>