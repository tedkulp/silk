<?php

class HomeController extends SilkControllerBase
{
	function before_filter()
	{
		$this->set('base_url', SilkRequest::get_calculated_url_base(true) . 'blog/');
	}
}

?>