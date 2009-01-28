<?php

class Stage extends SilkObjectRelationalMapping {
	var $table = "stages";

	function __construct()
    {
        parent::__construct();
    }

    function setup()
    {
//    	$this->create_belongs_to_association("seasons", "season_id");
//    	$this->has_association("stages", "stage_id");
//      $this->create_belongs_to_association('author', 'CmsUser', 'author_id');
//      $this->create_has_and_belongs_to_many_association('categories', 'BlogCategory', 'blog_post_categories', 'category_id', 'post_id', array('order' => 'name ASC'));
    }
}
?>