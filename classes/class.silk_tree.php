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
 * Generic tree and node classes for storing hierarchies of data.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkTree extends \silk\core\Object
{
	var $root = null;
	
	function __construct()
	{
		parent::__construct();
	}
	
	function get_root_node()
	{
		return $this->root;
	}
	
	function create_node()
	{
		$node = new SilkNode();
		$node->tree = $this;
		return $node;
	}
	
	function add_child($node)
	{
		$node->set_parent($this->root);
		$this->root->children[] = $node;
	}
	
	function &get_flat_list()
	{
		$temp_var =& $this->get_root_node()->get_flat_list();
		return $temp_var;
	}
	
	function &getFlatList()
	{
		$temp_var =& $this->get_root_node()->get_flat_list();
		return $temp_var;
	}
}

class SilkNode extends \silk\core\Object
{
	var $tree = null;
	var $parentnode = null;
	var $children = array();
	
	function __construct()
	{
		parent::__construct();
	}
	
	function add_child($node)
	{
		$node->set_parent($this);
		$node->tree = $this->tree;
		$this->children[] = $node;
	}
	
	function get_tree()
	{
		return $this->tree;
	}
	
	function depth()
	{
		$depth = 0;
		$currLevel = &$this;

		while ($currLevel->parentnode)
		{
			$depth++;
			$currLevel = &$currLevel->parentnode;
		}
		
		return $depth;
	}
	
	function get_level()
	{
		return $this->depth();
	}
	
	function getLevel()
	{
		return $this->depth();
	}
	
	function get_parent()
	{
		return $this->parentnode;
	}
	
	function set_parent($node)
	{
		$this->parentnode = $node;
	}
	
	function has_children()
	{
		return count($this->children) > 0;
	}
	
	function get_children_count()
	{
		return count($this->children);
	}
	
	function getChildrenCount()
	{
		return $this->get_children_count();
	}

	function &get_children()
	{
		return $this->children;
	}
	
	function &get_flat_list()
	{
		$return = array();

		if ($this->has_children())
		{
			for ($i=0; $i<count($this->children); $i++)
			{
				$return[] = &$this->children[$i];
				$return = array_merge($return, $this->children[$i]->get_flat_list());
			}
		}

		return $return;
	}

	function &getFlatList()
	{
		$tmp =& $this->get_flat_list();
		return $tmp;
	}
}

# vim:ts=4 sw=4 noet
?>