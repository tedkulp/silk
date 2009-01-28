<?php
class Stub extends SilkObjectRelationalMapping {
	var $table = "stub";
	
	function __construct()
    {
        parent::__construct();
//        $this->post_date = new CmsDateTime();
    }
    
    function setup()
    {
//        $this->create_belongs_to_association('author', 'CmsUser', 'author_id');
//        $this->create_has_and_belongs_to_many_association('categories', 'BlogCategory', 'blog_post_categories', 'category_id', 'post_id', array('order' => 'name ASC'));
    }
}

?>