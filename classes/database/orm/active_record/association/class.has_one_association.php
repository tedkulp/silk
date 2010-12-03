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

namespace silk\orm\active_record\association;

/**
 * Class for handling a one-to-one assocation.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class HasOneAssociation extends ObjectRelationalAssociation
{
	var $children = array();
	var $child_class = '';
	var $child_field = '';

	/**
	 * Create a new has_one association.
	 *
	 * @author Ted Kulp
	 **/
	public function __construct($association_name)
	{
		parent::__construct($association_name);
	}
	
	/**
	 * Returns the associated has_one association's objects.
	 *
	 * @return mixed The object, if it exists.  If not, null.
	 * @author Ted Kulp
	 **/
	public function get_data(&$obj)
	{
		$child = null;
		if ($obj->has_association($this->association_name))
		{
			$child = $obj->get_association($this->association_name);
		}
		else
		{
			if ($this->child_class != '' && $this->child_field != '')
			{
				$class = orm()->{$this->child_class};
				if ($obj->{$obj->id_field} > -1)
				{
					$queryattrs = $this->extra_params;
					$conditions = "{$this->child_field} = ?";
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
					$child = $class->find($queryattrs);
					$obj->set_association($this->association_name, $child);
				}
			}
		}
		return $child;
	}
}

# vim:ts=4 sw=4 noet
