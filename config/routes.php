<?php
SilkRoute::register_route("/blog/admin/:action/:id", array('component' => 'blog', 'controller' => 'admin'));
SilkRoute::register_route("/blog/admin/:action", array('component' => 'blog', 'controller' => 'admin'));
SilkRoute::register_route("/blog/admin", array('action' => 'index', 'component' => 'blog', 'controller' => 'admin'));

SilkRoute::register_route('/blog/rss.xml', array('action' => 'index', 'rss' => true, 'showtemplate' => false, 'component' => 'blog', 'controller' => 'view'));
//SilkRoute::register_route('/blog\/category\/(?P<category>[a-zA-Z\-_\ ]+)\.xml$/', array('action' => 'list_by_category', 'rss' => true, 'showtemplate' => false));
//SilkRoute::register_route('/blog\/category\/(?P<category>[a-zA-Z\-_\ ]+)$/', array('action' => 'list_by_category'));
SilkRoute::register_route('/blog/(?P<year>[0-9]{4})', array('action' => 'filter_list', 'month' => '-1', 'day' => '-1', 'component' => 'blog', 'controller' => 'view'));
SilkRoute::register_route('/blog/(?P<year>[0-9]{4})/(?P<month>[0-9]{2})', array('action' => 'filter_list', 'day' => '-1', 'component' => 'blog', 'controller' => 'view'));
SilkRoute::register_route('/blog/(?P<year>[0-9]{4})/(?P<month>[0-9]{2})/(?P<day>[0-9]{2})', array('action' => 'filter_list', 'component' => 'blog', 'controller' => 'view'));
SilkRoute::register_route('/blog/(?P<url>[0-9]{4}/[0-9]{2}/[0-9]{2}/.*?)', array('component' => 'blog', 'action' => 'detail', 'controller' => 'view'));
SilkRoute::register_route('/blog/entry/:id', array('component' => 'blog', 'action' => 'detail', 'controller' => 'view'));
SilkRoute::register_route("/blog/:page", array('action' => 'index', 'component' => 'blog', 'controller' => 'view'));
SilkRoute::register_route('/blog', array('component' => 'blog', 'action' => 'index', 'controller' => 'view', 'page' => 1));

SilkRoute::register_route('/admin/login', array('component' => 'app', 'action' => 'index', 'controller' => 'login'));
SilkRoute::register_route('/admin/logout', array('component' => 'app', 'action' => 'logout', 'controller' => 'login'));
SilkRoute::register_route("/:controller/:action/:id", array('component' => 'app'));
SilkRoute::register_route("/:controller/:action", array("id" => '', 'component' => 'app'));
SilkRoute::register_route("/:controller", array("id" => '', 'action' => 'index', 'component' => 'app'));
SilkRoute::register_route("/", array("component" => 'app', 'controller' => 'home', "id" => '', 'action' => 'index'));
?>