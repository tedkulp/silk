<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-

/**
 * @author Greg Froese
 *
 */
add_component_dependent("stub");

class PhotoController extends SilkControllerBase {

	function test($params) {
		$stub = new Stub();
		$photo = new Photo();
		$findme = new FindMeController();
		$findme->loveme();
		$photo2 = new Photo2();
		$this->set("what", "hello nurse");
		$params["whatever"] = "More stuff";
	}
	/**
	 *
	 * @author Greg Froese
	 */
	function run_default($params) {

	}

	/**
	 * Just here for an example
	 */
	function test_ajax($params)  {
	    $this->show_layout = false;
	    $resp = new SilkAjax();
	    $resp->replace_html("#some_content", "New content says 'Hi!'");
	    $resp->replace("#some_content", "style", "color: red;");
	    $resp->insert("#some_content", " Append me, baby!");
	    $resp->insert("#some_content", "Prepend me, too. ", "prepend");
	    $resp->insert("#some_content", "<div id='after'>After</div>", "after");
	    $resp->insert("#some_content", "Before ", "before");
	    $resp->remove("#after");
	    return $resp->get_result();
	}
}
?>