<?php


/** 
 * How you might use the bootstrap call, as per the default index.php file.
*/

define('ROOT_DIR', dirname(__FILE__));

include_once('lib/silk/silk.api.php');

\silk\core\Application::get_instance()->run();



?>
