<?php // -*- mode:php; tab-width:4; indent-tabs-mode:t; c-basic-offset:4; -*-
// The MIT License
//
// Copyright (c) 2008 Ted Kulp
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

class SilkLang extends SilkObject {
	
	public static function lang($key, $text, $section) {
		if(!isset($_SESSION["lang"])) {
			$config = self::load_config();		
			$_SESSION["lang"] = $config["default_lang"];
		}
		if(!empty($key) && !empty($text)) {
			return self::get_translated_text($key, $text, $section);
		}
	}
	
	private static function get_translated_text($key, $text, $section) {
		$config = self::load_config();
			
		$lang = isset($_SESSION["lang"]) ? $_SESSION["lang"] : $config["default_lang"];
		$filename = self::get_lang_filename($lang);
		
		if(file_exists($filename)) {
			$langText = self::load_language_file($filename);
			if(isset($langText[$section][$key])) {
				return $langText[$section][$key];
			} else {
				return self::update_file($filename, $key, $text, $section, $_SESSION["lang"]);
			}
		} else {
			return self::update_file($filename, $key, $text, $section, $_SESSION["lang"]);
		}
		
		if(empty($lang)) {
			return $text;
		}
	}
	
	public function update_file($filename, $key, $text, $section, $lang) {
		$config = self::load_config();
			
		// don't update the file if it is the default language
		if($config["default_lang"] == $lang) {
			return $text;
		}
		$text .= " ($lang)";

		// don't update the file if the value is already in it
		echo "Does $key already exist?<br />";
		$value = self::entry_exists($filename, $key, $section);
		if($value) {
			return $value;
		}

		$langText = self::load_language_file($filename);
		$langText[$section][$key] = $text;

		self::write_text_to_file($filename, $langText);
		return $text;
	}
	
	public function write_text_to_file($filename, $langText) {
		$langText = array_diff($langText, array("-", "--", "---"));
		file_put_contents($filename, SilkYaml::dump($langText));
	}
	
	public function update_value($lang, $section, $key, $text) {
		$filename = self::get_lang_filename($lang);
		$langText = self::load_language_file($filename);
		$langText[$section][$key] = $text;
		self::write_text_to_file($filename, $langText);
	}
	
	private function create_section_header($section) {
		return "/* ======== Section: $section *======== */\n";
	}
	
	public static function get_language_links() {
		if (is_file(join_path(ROOT_DIR, 'config', 'setup.yml')))
			$config = SilkYaml::load(join_path(ROOT_DIR, 'config', 'setup.yml'));
		else
			die("Config file not found!");
			
		$get_params = "?";
		foreach($_GET as $key=>$value) $get_params .= "$key=$value&";
		$params["redirect"] = SilkRequest::get_calculated_url_base(true) . SilkRequest::get_requested_page() . $get_params;
		
		$changeLanguageLinks = array();
		foreach($config["available_languages"] as $lang) {
			$changeLanguageLinks[$lang] = SilkResponse::create_url(array("controller" => "language",
	  													"action" => "changeLanguage",
	  													"lang" => "$lang",
	  													"redirect" => $params["redirect"]
	  													));
		}
		return $changeLanguageLinks;
	}
	
	public function get_lang() {
		if(!isset($_SESSION["lang"])) {
			$_SESSION["lang"] = $config["default_lang"];
		}
		return $_SESSION["lang"];
	}
	
	public function set_lang($lang) {
		$_SESSION["lang"] = $lang;
	}
	
	public function get_lang_filename($lang) {
		return join_path(ROOT_DIR, "config", "language", "lang_".$lang.".yml");
	}
	
	public function entry_exists($filename, $key, $section) {
		if(file_exists($filename)) {
			$langText = self::load_language_file($filename);
			if(isset($langText[$section][$key])) {
				return $langText[$section][$key];
			}
		}
		return false;
	}
	
	public function load_config() {
		if (is_file(join_path(ROOT_DIR, 'config', 'setup.yml')))
			return SilkYaml::load(join_path(ROOT_DIR, 'config', 'setup.yml'));
		else
			die("Config file not found!");
	}
	
	public static function load_language_file($filename) {
		return SilkYaml::load($filename);
	}
}

?>
