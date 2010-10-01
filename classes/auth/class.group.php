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

namespace silk\auth;

use \silk\orm\ActiveRecord;

/**
 * Represents a user group in the database.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class Group extends ActiveRecord
{
	var $params = array('id' => -1, 'name' => '', 'active' => true);
//	var $field_maps = array('group_name' => 'name');
	var $table = 'groups';
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function setup()
	{
		$this->create_has_and_belongs_to_many_association('users', 'user', 'user_groups', 'user_id', 'group_id');
	}
	
	public function validate()
	{
//		$this->validate_not_blank('name', lang('nofieldgiven',array(lang('username'))));
		
		// Username validation
		if ($this->name != '')
		{
			// Make sure the name is unique
			$result = $this->find_by_name($this->name);
			if ($result)
			{
				if ($result->id != $this->id)
				{
					$this->add_validation_error(lang('The group name is already in use'));
				}
			}
			
			// Make sure the name has no illegal characters
			if ( !preg_match("/^[a-zA-Z0-9\.]+$/", $this->name) ) 
			{
				$this->add_validation_error(lang('illegalcharacters', array(lang('groupname'))));
			}
		}
	}

	public function add_user($user)
	{
		if ($this->id > -1)
		{
			$date = db()->DBTimeStamp(time());
			return db()->Execute('INSERT INTO ' . db_prefix() . "user_groups (user_id, group_id, create_date, modified_date) VALUES (?,?,{$date},{$date})", array($user->id, $this->id));
		}
		
		return false;
	}

	public function remove_user($user)
	{
		if ($this->id > -1)
		{
			return db()->Execute('DELETE FROM '.db_prefix().'user_groups WHERE user_id = ? AND group_id = ?', array($user->id, $this->id));
		}

		return false;
	}
	
	//Callback handlers
	public function before_save()
	{
//		SilkEvents::send_event( 'Core', ($this->id == -1 ? 'AddGroupPre' : 'EditGroupPre'), array('group' => &$this));
	}
	
	public function after_save()
	{
/*		//Add the group to the aro table so we can do acls on it
		//Only happens on a new insert
		if ($this->create_date == $this->modified_date)
		{
			//SilkAcl::add_aro($this->id, 'Group');
		}
		SilkEvents::send_event( 'Core', ($this->create_date == $this->modified_date ? 'AddGroupPost' : 'EditGroupPost'), array('group' => &$this));*/
	}
	
	public function before_delete()
	{
		db()->Execute('DELETE FROM '.db_prefix().'user_groups WHERE group_id = ?', array($this->id));
		//SilkEvents::send_event('Core', 'DeleteGroupPre', array('group' => &$this));
	}
	
	public function after_delete()
	{
		//SilkAcl::delete_aro($this->id, 'Group');
		//SilkEvents::send_event('Core', 'DeleteGroupPost', array('group' => &$this));
	}
	
	public static function get_groups_for_dropdown($add_everyone = false)
	{
		$result = array();
		
		if ($add_everyone)
		{
			$result[-1] = lang('Everyone');
		}
		

		$groups = \silk\auth\Group::find_all(array('order' => 'name ASC'));
		foreach ($groups as $group)
		{
			$result[$group->id] = $group->name;
		}
		
		return $result;
	}
}

# vim:ts=4 sw=4 noet
?>