<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008 Ted Kulp
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

/**
 * Class to handle url routes for modules to handle pretty urls.
 *
 * @author Ted Kulp
 * @since 1.0
 **/
class SilkRoute extends SilkObject
{
	var $module;
	var $regex;
	var $defaults;

	function __construct()
	{
		parent::__construct();
	}
	
	public static function match_route($page)
	{	
		if (strpos($page, '/') !== FALSE)
		{
			$routes =& cmsms()->variables['routes'];

			$matched = false;
			foreach ($routes as $route)
			{
				$matches = array();
				if (preg_match($route->regex, $page, $matches))
				{
					//Now setup some assumptions
					if (!isset($matches['id']))
						$matches['id'] = 'cntnt01';
					if (!isset($matches['action']))
						$matches['action'] = 'defaulturl';
					if (!isset($matches['inline']))
						$matches['inline'] = 0;
					if (!isset($matches['returnid']))
						$matches['returnid'] = ''; #Look for default page
					if (!isset($matches['module']))
						$matches['module'] = $route->module;

					//Get rid of numeric matches
					foreach ($matches as $key=>$val)
					{
						if (is_int($key))
						{
							unset($matches[$key]);
						}
						else
						{
							if ($key != 'id')
								$_REQUEST[$matches['id'] . $key] = $val;
						}
					}

					//Now set any defaults that might not have been in the url
					if (isset($route->defaults) && count($route->defaults) > 0)
					{
						foreach ($route->defaults as $key=>$val)
						{
							$_REQUEST[$matches['id'] . $key] = $val;
						}
					}

					//Get a decent returnid
					if ($matches['returnid'] == '')
					{
						$matches['returnid'] = CmsContentOperations::get_default_page_id();
					}

					$_REQUEST['mact'] = $matches['module'] . ',' . $matches['id'] . ',' . $matches['action'] . ',' . $matches['inline'];
					$_REQUEST[$matches['id'] . 'returnid'] = $matches['returnid'];
					$page = $matches['returnid'];
					$smarty->id = $matches['id'];

					$matched = true;
				}
			}

			if (!$matched)
			{
				$page = substr($page, strrpos($page, '/') + 1);
			}
		}

		return $page;
	}
}

# vim:ts=4 sw=4 noet
?>