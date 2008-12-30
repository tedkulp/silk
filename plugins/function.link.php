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

function smarty_function_link($params, &$smarty)
{
	$module =& $smarty->get_template_vars('cms_mapi_module');
	$id = $smarty->get_template_vars('cms_mapi_id');
	$return_id = $smarty->get_template_vars('cms_mapi_return_id');
	$translate = coalesce_key($params, 'translate', true, FILTER_VALIDATE_BOOLEAN);

	$value = ($translate === true) ? $module->lang($params['value']) : $params['value'];
	$warn_message = ($translate === true) ? $module->lang($params['warn_message']) : $params['warn_message'];

	/*
	if (isset($params['theme_image']))
	{
		$themeObject = CmsAdminTheme::get_instance();
		$image = $themeObject->display_image($params['theme_image'], $value,'','','systemicon');
		if( isset($params['showtext']) )
		{
			$value = $image.'&nbsp;'.$value;
		}
		else
		{
			$value = $image;
		}
	}
	*/

	$other_params = remove_keys($params, array('action', 'value', 'warn_message', 'translate', 'only_href', 
						'inline', 'additional_text', 'target_container_only', 'pretty_url', 'theme_image'));

	$blah = $module->create_link($id, $params['action'], $return_id, $value, 
				     $other_params, $warn_message, coalesce_key($params, 'only_href', false, FILTER_VALIDATE_BOOLEAN), 
				     coalesce_key($params, 'inline', false, FILTER_VALIDATE_BOOLEAN), coalesce_key($params, 'additional_text', ''), 
				     coalesce_key($params, 'target_container_only', false, FILTER_VALIDATE_BOOLEAN), coalesce_key($params, 'pretty_url', ''));
						
	return $blah;
}

# vim:ts=4 sw=4 noet
?>
