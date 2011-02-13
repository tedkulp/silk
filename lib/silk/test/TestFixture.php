<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
#CMS - CMS Made Simple
#(c)2004-2010 by Ted Kulp (ted@cmsmadesimple.org)
#This project's homepage is: http://cmsmadesimple.org
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

namespace silk\test;

use \silk\core\Object;
use \silk\database\Database;

class TestFixture extends Object
{
	function __construct()
	{
		parent::__construct();
	}

	function setup()
	{
		/*
		$table = isset($this->table) ? $this->table : '';
		if (isset($this->model))
		{
			$the_model = new $this->model;
			$the_model->migrate();
			$table = $the_model->get_table();
		}

		if (isset($this->records))
		{
			foreach($this->records as $one_record)
			{
				if (is_array($one_record))
				{
					$query = "INSERT INTO " . $table . " (" . implode(", ", array_keys($one_record)) . ") VALUES (" . implode(',', array_fill(0, count($one_record), '?')) . ")";
					db()->execute_sql($query, array_values($one_record));
				}
			}
		}
		*/
	}

	function teardown()
	{
		/*
		$table = isset($this->table) ? $this->table : '';
		if (isset($this->model))
		{
			$the_model = new $this->model;
			$table = $the_model->get_table('', false);
			db()->drop_table($table);
		}
		*/
	}
}

# vim:ts=4 sw=4 noet
