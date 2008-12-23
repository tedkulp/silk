<?php

include_once('lib/silk/silk.api.php');

$config = SilkYaml::load(join_path(ROOT_DIR, 'config', 'setup.yml'));

include_once('config/routes.php');

//SilkDatabase::connect($config['database']['dsn'], $config['debug'], true, $config['database']['prefix']);

SilkRequest::setup();

$params = array();
try
{
	$params = SilkRoute::match_route(SilkRequest::get_requested_page());
}
catch (SilkRouteNotMatchedException $ex)
{
	var_dump("route not found");
}

include_once('app/controllers/' . $params['controller'] . '_controller.php');
$class_name = camelize($params['controller'] . '_controller');
$controller = new $class_name;

$method = $params['action'];
echo call_user_func_array(array($controller, $method), array());

?>