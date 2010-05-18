<?php

use \silk\action\Route;

//Route examples
//Route::register_route("/:controller/:action/:id");
//Route::register_route("/:controller/:action", array("id" => ''));
//Route::register_route("/:controller", array("id" => '', 'action' => 'index'));

// Build default routes
Route::build_default_component_routes();
?>