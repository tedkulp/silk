<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
// 
// Copyright (c) 2008-2011 Ted Kulp
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

desc('Run migrations automatically for models');
task('migrate', function($app)
{
	$config = get('config');
	$models = array();
	if (isset($config['auto_migrate']))
	{
		if (isset($config['auto_migrate']['include']) && is_array($config['auto_migrate']['include']))
		{
			foreach($config['auto_migrate']['include'] as $one_entry)
			{
				$models = array_merge($models, glob(ROOT_DIR . '/components/*/models/' . $one_entry . '.php'));
				foreach(silk()->getExtensionDirectories('models') as $one_dir)
				{
					$models = array_merge($models, glob(joinPath($one_dir, $one_entry . '.php')));
				}
			}
		}
		$models = array_unique($models);
		if (isset($config['auto_migrate']['exclude']) && is_array($config['auto_migrate']['exclude']))
		{
			$exclude = array();
			foreach($config['auto_migrate']['exclude'] as $one_entry)
			{
				foreach($models as $one_model)
				{
					if (strpos($one_model, $one_entry) !== false)
					{
						$exclude[] = $one_model;
						break;
					}
				}
			}
			$exclude = array_unique($exclude);
			$models = array_diff($models, $exclude);
		}
	}

	if (count($models))
	{
		foreach ($models as $one_model)
		{
			//Is this is an app model?
			if (strpos($one_model, ROOT_DIR . '/components') !== false)
			{
				$model = basename($one_model, '.' . substr(strrchr($one_model, '.'), 1));
			}
			else
			{
				//It's an extension model -- pull off the namespace instead
				$pos = strlen($one_model) - strrpos(strrev($one_model), strrev('vendor' . DS . 'extensions'));
				$model = str_replace(DS, '\\', substr(substr($one_model, $pos), 0, strrpos(substr($one_model, $pos), '.')));
			}
			if (class_exists($model) && is_subclass_of($model, '\silk\model\Model'))
			{
				echo "Migrating model: " . $model . "\n";
				$model::migrate();
			}
		}
		echo "Done!\n";
	}
	else
	{
		echo "No models found\n";
	}

});

# vim:ts=4 sw=4 noet filetype=php
