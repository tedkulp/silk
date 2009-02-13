<?php
	function up($dict, $db_prefix)
	{
		SilkDatabase::create_table('blog_posts', "
		    id I KEY AUTO,
		    author_id I,
		    post_date T,
		    post_year I,
		    post_month I,
		    post_day I,
		    title C(255),
		    slug C(255),
		    url C(255),
		    content XL,
		    summary XL,
		    status C(25),
		    use_comments I(1) default 1,
		    processor C(25) default '',
		    create_date T,
		    modified_date T
		");
		SilkDatabase::create_index('blog_posts', 'blog_post_slug', 'slug');
		SilkDatabase::create_index('blog_posts', 'blog_post_url', 'url');
		SilkDatabase::create_index('blog_posts', 'blog_post_date', 'post_year,post_month,post_day');
		SilkDatabase::create_index('blog_posts', 'blog_post_month', 'post_year,post_month');
		SilkDatabase::create_index('blog_posts', 'blog_post_year', 'post_year');

		SilkDatabase::create_table('blog_categories', "
		    id I KEY AUTO,
		    name C(255),
		    slug C(255),
		    create_date T,
		    modified_date T
		");
		
		$date = db()->BindTimeStamp(time());
		db()->Execute("INSERT INTO {$db_prefix}blog_categories (name, slug, create_date, modified_date) VALUES (?,?,?,?)", array('General', 'general', $date, $date));

		SilkDatabase::create_table('blog_post_categories', "
		    blog_category_id I,
		    blog_post_id I,
		    create_date T,
		    modified_date T
		");
	}
	
	function down($dict, $db_prefix)
	{
		SilkDatabase::drop_table('blog_post_categories');
		SilkDatabase::drop_table('blog_categories');
		SilkDatabase::drop_table('blog_posts');
	}
?>