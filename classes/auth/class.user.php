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
 * Generic user class.  This can be used for any logged in user or user related function.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class User extends ActiveRecord
{
	var $params = array('id' => -1, 'username' => '', 'password' => '', 'first_name' => '', 'last_name' => '', 'email' => '', 'active' => false);
	var $table = 'users';
	
	var $attr_module = 'Core';
	var $attr_extra = 'User';
	
	public function __construct()
	{
		parent::__construct();
		//$this->assign_acts_as('Attributed');
	}

	public function validate()
	{
		
		// Username validation
		if ($this->username != '')
		{
			// Make sure the name is unique
			$result = $this->find_by_username($this->username);
			if ($result)
			{
				if ($result->id != $this->id)
				{
					$this->add_validation_error('The username is already in use');
				}
			}
			
			// Make sure the name has no illegal characters
			if ( !preg_match("/^[a-zA-Z0-9\.]+$/", $this->username) ) 
			{
				$this->add_validation_error(lang('illegalcharacters', array(lang('username'))));
			} 

			// Make sure the name is a valid length
			// the min_username_length member may not exist
			if( $this->min_username_length != null) 
			{
				if( strlen($this->username) < $this->min_username_length )
				{
					$this->add_validation_error(lang('The username is too short'));
				}
			}

			// the minimum password length
			// the min_password_length member may not exist
			if( ($this->min_password_length != null) && 
				($this->clear_password != null) )
			{
				if( strlen($this->clear_password) < $this->min_password_length )
				{
					$this->add_validation_error(lang('The password is too short'));
				}
			}

			// the clear_repeat_password and clear_password members
			// may not exist, but if they do, verify that they
			// match.
			if( ($this->clear_repeat_password != null) && 
				($this->clear_password != null) )
			{
				if( $this->clear_repeat_password != $this->clear_password )
				{
					$this->add_validation_error(lang('The passwords do not match'));
				}
			}
		}
		
		//Make sure the open id is unique
		if ($this->openid != '')
		{
			$result = $this->find_by_openid($this->openid);
			if ($result)
			{
				if ($result->id != $this->id)
				{
					$this->add_validation_error(lang('The openid address is already in use'));
				}
			}
		}
	}	

	function setup($first_time = false)
	{
		$this->create_has_and_belongs_to_many_association('groups', 'group', 'user_groups', 'group_id', 'user_id');
		$this->create_has_and_belongs_to_many_association("mygroups", "group", "user_groups", "group_id", "user_id");
	}

	/**
	 * Encrypts and sets password for the User
	 *
	 * @param string The password to encrypt and set for the user
	 *
	 * @since 0.6.1
	 */
	public function set_password($password)
	{
		//Set params directly so that we don't get caught in a loop
		$this->params['password'] = \silk\auth\UserSession::encode_password( $password );
	}
	
	//Callback handlers
	protected function before_save()
	{
		//SilkEvents::send_event( 'Core', ($this->id == -1 ? 'AddUserPre' : 'EditUserPre'), array('user' => &$this));
	}
	
	protected function after_save()
	{
		//SilkEvents::send_event( 'Core', ($this->create_date == $this->modified_date ? 'AddUserPost' : 'EditUserPost'), array('user' => &$this));
	}
	
	protected function before_delete()
	{
		//SilkEvents::send_event('Core', 'DeleteUserPre', array('user' => &$this));
	}
	
	protected function after_delete()
	{
		//SilkEvents::send_event('Core', 'DeleteUserPost', array('user' => &$this));
	}
	
	public function is_anonymous()
	{
		return false;
	}
	
	function generate_salt()
	{
		return substr(md5(uniqid(rand(), true)), 0, 8);
	}
	
	public function full_name()
	{
		return $this->first_name . ' ' . $this->last_name;
	}
	
	public function get_preference($prefname, $default = '')
	{
		return get_preference($this->id, $prefname, $default);
	}
	
	function set_preference($userid, $prefname, $value)
	{
		return set_preference($this->id, $prefname, $value);
	}
}

# vim:ts=4 sw=4 noet
?>