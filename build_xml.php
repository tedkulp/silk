<?php

$dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;

$count = 0;
$files = array();

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
while ($it->valid())
{
	if (!$it->isDot() && $it->isFile())
	{
		if (!preg_match('/\\/.git\//', $it->getPathname()) &&
			!preg_match('/\\/tmp\//', $it->getPathname()) &&
			!preg_match('/\.old$/', $it->getPathname()) &&
			!preg_match('/\.swp$/', $it->getPathname()) &&
			!preg_match('/\.DS_Store$/', $it->getPathname()) &&
			!preg_match('/~$/', $it->getPathname()) &&
			!preg_match('/package.xml$/', $it->getPathname()) &&
			!preg_match('/\.gitignore$/', $it->getPathname())
		)
		{
			if (!in_array($it->getPathname(), $files))
			{
				$files[] = str_replace($dir, '', $it->getPathname());
			}
		}
	}
	$it->next();
	$count++;
}

$xml_to_replace = '';

//sort($files);
foreach ($files as $one_file)
{
	$md5sum = md5_file($one_file);
	$role = 'doc';
	if ($info = pathinfo($one_file))
	{
		if ($info['basename'] == 'silk.php')
		{
			$role = 'script';
		}
		else if (isset($info['extension']) && in_array($info['extension'], array('php', 'inc', 'html', 'yml')))
		{
			$role = 'php';
		}
		else if (isset($info['extension']) && in_array($info['extension'], array('js', 'css', 'png', 'jpg', 'gif', 'sh', 'xml', 'dtd')))
		{
			$role = 'data';
		}
	}
	if ($role == 'script' && $one_file == 'silk.php')
		$xml_to_replace .= "    <file install-as=\"silk\" baseinstalldir=\"/\" md5sum=\"{$md5sum}\" name=\"{$one_file}\" role=\"{$role}\" />\n";
	else if ($role == 'script')
		$xml_to_replace .= "    <file baseinstalldir=\"/\" md5sum=\"{$md5sum}\" name=\"{$one_file}\" role=\"{$role}\" />\n";
	else
		$xml_to_replace .= "    <file baseinstalldir=\"silk\" md5sum=\"{$md5sum}\" name=\"{$one_file}\" role=\"{$role}\" />\n";
}

$base_file = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'package.xml.base');
$base_file = str_replace("@put_files_here@", $xml_to_replace, $base_file);

file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'package.xml', $base_file);
