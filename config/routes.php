<?php

SilkRoute::register_route("/:controller/:action/:id");
SilkRoute::register_route("/:controller/:action", array("id" => 'default_id'));

?>