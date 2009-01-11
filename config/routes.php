<?php

SilkRoute::register_route("/:controller/:action/:id");
SilkRoute::register_route("/:controller/:action", array("id" => ''));
SilkRoute::register_route("/:controller", array("id" => '', 'action' => 'index'));

?>