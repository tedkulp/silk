<?php
//Route examples
SilkRoute::register_route("/:controller/:action/:id", array('component' => 'app'));
SilkRoute::register_route("/:controller/:action", array("id" => '', 'component' => 'app'));
SilkRoute::register_route("/:controller", array("id" => '', 'action' => 'index', 'component' => 'app'));
SilkRoute::register_route("/?", array("component" => 'app', 'controller' => 'home', "id" => '', 'action' => 'index'));
?>