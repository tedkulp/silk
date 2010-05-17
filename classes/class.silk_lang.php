<?php

class SilkLang extends \silk\core\Object {
	public static function lang($params) {
		$config = load_config();
		$lang = self::load_language_file();
		if( isset( $lang[$params["section"]][$params["name"]]) ) {
			return array("text" => $lang[$params["section"]][$params["name"]], "lang" => $lang);
		} else {
			if( $config["default_lang"] != self::get_lang() ) {
				$params["text"] .= " (" . self::get_lang() . ")";
			}
			return array("text" => $params["text"], "lang" => $lang);
		}
	}
	
	public static function update_value($params) {
		$lang = SilkLang::load_language_file($params["langIndicator"]);
		$lang[$params["section"]][$params["key"]] = $params["text"];
		self::write_language_file(array("langIndicator" => $params["langIndicator"], "lang" => $lang));
	}
	
	public static function load_language_file( $lang = "" ) {
		if( file_exists(self::build_language_filename() ) ) {
			return SilkYaml::load_file( self::build_language_filename( $lang ) );
		}
	}
	
	public static function write_language_file($params) {
		file_put_contents(self::build_language_filename( $params["langIndicator"] ), SilkYaml::dump( $params["lang"] ) );
	}
	
	public static function build_language_filename( $lang = "") {
		if( empty( $lang )) {
			return join_path(ROOT_DIR, "config", "language", "lang_" . self::get_lang() . ".yml");
		} else {
			return join_path(ROOT_DIR, "config", "language", "lang_" . $lang . ".yml" );
		}
	}
	
	public static function get_lang() {
		$config = load_config();
		
		if(isset($_SESSION["lang"])) {
			return $_SESSION["lang"];
		} else {
			self::set_lang($config["default_lang"]);
			return $config["default_lang"];
		}
	}
	
	public static function set_lang($lang) {
		$_SESSION["lang"] = $lang;
	}
	
	public static function get_language_links() {
		$config = load_config();
		
		$links = array();
		foreach( $config["available_languages"] as $lang ) {
			$links[$lang] = SilkResponse::create_url(array(	"controller" => "language",
															"action" => "changeLanguage",
															"lang" => $lang,
															"redirect" => SilkRequest::get_calculated_url_base(true) . SilkRequest::get_requested_page()));
		}
		return $links;
	}
}
?>