<?php

class AdminController extends SilkControllerBase
{
	function index($params)
	{
		$this->set('posts', orm('BlogPost')->find_all(array('order' => 'id desc')));
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
				if (isset($params['blog_post']['category']))
				{
					$blog_post->clear_categories();
					foreach ($params['blog_post']['category'] as $k => $v)
					{
						if ($v == 1)
							$blog_post->set_category($k);
					}
				}

				redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'index')));
			}
		}

		//$smarty->assign('categories', cms_orm('BlogCategory')->find_all(array('order' => 'name ASC')));
		//$smarty->assign('processors', CmsTextProcessor::list_processors_for_dropdown());

		$this->set('form_action', 'edit');
		$this->set('post_date_prefix', 'post_date_');
		$this->set('blog_post', $blog_post);
	}
	
	function delete($params)
	{
		if ($params['id'])
		{
			$post = $blog_post = orm('BlogPost')->find_by_id($params['id']);
			$post->delete();
		}
		
		redirect(SilkResponse::create_url(array('controller' => 'admin', 'action' => 'index')));
	}
}

?>