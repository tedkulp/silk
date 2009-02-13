<?php

class ViewController extends SilkControllerBase
{
	function before_filter()
	{
		$this->set('base_url', SilkRequest::get_calculated_url_base(true) . 'blog/');
	}
	
	function index($params)
	{
		$posts = orm('BlogPost')->find_all(array('conditions' => array('status = ?', 'publish'), 'order' => 'post_date DESC'));
		$this->set('posts', $posts);
		
		if (coalesce_key($params, 'rss', false) == true)
		{
			$this->show_layout = false;
			return $this->render_template('rss', $params);
		}
	}
	
	function detail($params)
	{
		$post = null;
		if (isset($params['url']))
		{
			$post = orm('BlogPost')->find_by_url($params['url']);
		}
		else if (isset($params['id']))
		{
			$post = orm('BlogPost')->find_by_id($params['id']);
		}
		$this->set('post', $post);
	}
	
	function filter_list($params)
	{
		$conditions = array();
		
		if ($params['day'] > -1)
		{
			$conditions = array('status = ? and post_year = ? and post_month = ? and post_day = ?', 'publish', $params['year'], $params['month'], $params['day']);
		}
		else if ($params['month'] > -1)
		{
			$conditions = array('status = ? and post_year = ? and post_month = ?', 'publish', $params['year'], $params['month']);
		}
		else if ($params['year'] > -1)
		{
			$conditions = array('status = ? and post_year = ?', 'publish', $params['year']);
		}
		else
		{
			$conditions = array('status = ?', 'publish');
		}
		
		$this->set('posts', orm('BlogPost')->find_all(array('order' => 'post_date desc', 'conditions' => $conditions)));
		
		return $this->render_template('index', $params);
	}
}

?>