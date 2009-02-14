<?php

class AdminController extends SilkControllerBase
{
	function before_filter()
	{
		$this->check_access(SilkUserSession::is_logged_in(), array(), 'redirect_to_login');
	}
	
	function redirect_to_login()
	{
		$_SESSION['redirect_to'] = SilkRequest::get_requested_uri();
		SilkResponse::redirect_to_action(array('component' => 'app', 'action' => 'index', 'controller' => 'login'));
	}
	
	function index($params)
	{
		$this->set('posts', orm('BlogPost')->find_all(array('order' => 'id desc')));
	}
	
	function add($params)
	{
		if (array_key_exists('cancelpost', $params))
		{
			redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'index')));
		}
		
		$blog_post = new BlogPost();
		$blog_post->author_id = SilkUserSession::get_current_user()->id;
		
		if (isset($params['submitpost']) || isset($params['submitpublish']))
		{
			$blog_post->update_parameters($params['blog_post']);
			
			if (isset($params['post_date_Month']))
			{
				$blog_post->post_date = new SilkDateTime(mktime($params['post_date_Hour'], $params['post_date_Minute'], $params['post_date_Second'], $params['post_date_Month'], $params['post_date_Day'], $params['post_date_Year']));
			}
			
			if (isset($params['submitpublish']))
			{
				$blog_post->status = 'publish';
			}
			
			if ($blog_post->save())
			{
				if (isset($params['blog_post']['categories']))
				{
					$blog_post->clear_categories();
					foreach ($params['blog_post']['categories'] as $k => $v)
					{
						if ($v == 1)
							$blog_post->set_category($k);
					}
				}
				
				redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'index')));
			}
		}

		$this->set('categories', orm('BlogCategory')->find_all(array('order' => 'name ASC')));
		//$smarty->assign('processors', CmsTextProcessor::list_processors_for_dropdown());

		$this->set('form_action', 'add');
		$this->set('post_date_prefix', 'post_date_');
		$this->set('blog_post', $blog_post);
		
		return $this->render_template('edit', $params);
	}
	
	function edit($params)
	{
		if (array_key_exists('cancelpost', $params))
		{
			redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'index')));
		}

		$blog_post = orm('BlogPost')->find_by_id($params['id']);
		if ($blog_post == null)
		{
			redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'index')));
		}

		if (isset($params['submitpost']) || isset($params['submitpublish']))
		{
			$blog_post->update_parameters($params['blog_post']);

			if (isset($params['post_date_Month']))
			{
				$blog_post->post_date = new SilkDateTime(mktime($params['post_date_Hour'], $params['post_date_Minute'], $params['post_date_Second'], $params['post_date_Month'], $params['post_date_Day'], $params['post_date_Year']));
			}

			if (isset($params['submitpublish']))
			{
				$blog_post->status = 'publish';
			}

			if ($blog_post->save())
			{
				if (isset($params['blog_post']['categories']))
				{
					$blog_post->clear_categories();
					foreach ($params['blog_post']['categories'] as $k => $v)
					{
						if ($v == 1)
							$blog_post->set_category($k);
					}
				}
				
				redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'index')));
			}
		}

		$this->set('categories', orm('BlogCategory')->find_all(array('order' => 'name ASC')));
		//$smarty->assign('processors', CmsTextProcessor::list_processors_for_dropdown());

		$this->set('form_action', 'edit');
		$this->set('post_date_prefix', 'post_date_');
		$this->set('blog_post', $blog_post);
	}
	
	function delete($params)
	{
		if ($params['id'])
		{
			$post = orm('BlogPost')->find_by_id($params['id']);
			$post->delete();
		}
		
		redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'index')));
	}
	
	function categories($params)
	{
		$this->set('categories', orm('BlogCategory')->find_all(array('order' => 'name ASC')));
	}
	
	function addcategory($params)
	{
		if (array_key_exists('cancel', $params))
		{
			redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'categories')));
		}
		
		$blog_category = new BlogCategory();
		
		if (isset($params['submitpost']))
		{
			$blog_category->update_parameters($params['blog_category']);
			
			if ($blog_category->save())
			{
				redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'categories')));
			}
		}
		
		$this->set('form_action', 'addcategory');
		$this->set('blog_category', $blog_category);
		
		return $this->render_template('editcategory', $params);
	}
	
	function editcategory($params)
	{
		if (array_key_exists('cancel', $params))
		{
			redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'categories')));
		}
		
		$blog_category = orm('BlogCategory')->find_by_id($params['id']);
		if ($blog_category == null)
		{
			redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'categories')));
		}
		
		if (isset($params['submitpost']))
		{
			$blog_category->update_parameters($params['blog_category']);
			if ($blog_category->save())
			{
				redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'categories')));
			}
		}
		
		$this->set('form_action', 'editcategory');
		$this->set('blog_category', $blog_category);
	}
	
	function deletecategory($params)
	{
		if ($params['id'])
		{
			$category = orm('BlogCategory')->find_by_id($params['id']);
			$category->delete();
		}
		
		redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'categories')));
	}
}

?>