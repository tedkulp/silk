<?php
	function up($dict, $db_prefix)
	{
		SilkDatabase::create_table('users', "
			id I KEY AUTO,
			username C(25),
			password C(75),
			first_name C(50),
			last_name C(50),
			email C(255),
			openid C(255),
			salt C(255),
			checksum C(255),
			active I1,
			create_date T,
			modified_date T
			");
		
		$user = new SilkUser();
		$user->username = 'admin';
		$user->password = 'admin';
		$user->first_name = 'Admin';
		$user->last_name = 'Admin';
		$user->email = 'ted@silkframework.com';
		$user->active = true;
		$user->save();
	}
	
	function down($dict, $db_prefix)
	{
		SilkDatabase::drop_table('users');
	}
?>