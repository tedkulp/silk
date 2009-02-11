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
	
	public static function lang($constant, $text, $section) {
		if(!empty($constant) && !empty($text)) {
			return self::get_translated_text($constant, $text, $section);
		}
	}
	
	private static function get_translated_text($constant, $text, $section) {
		if (is_file(join_path(ROOT_DIR, 'config', 'setup.yml')))
			$config = SilkYaml::load(join_path(ROOT_DIR, 'config', 'setup.yml'));
		else
			die("Config file not found!");
			
		$lang = isset($_SESSION["lang"]) ? $_SESSION["lang"] : $config["default_lang"];
		$filename = self::get_lang_filename($lang);
		
		if(file_exists($filename)) {
			include_once($filename);
			if(defined($constant)) {
				return constant($constant);
			} else {
				return self::update_file($filename, $constant, $text, $section);
			}
		} else {
			return self::update_file($filename, $constant, $text, $section);
		}
		
		if(empty($lang)) {
			return $text;
		}
	}
	
	public function update_file($filename, $constant, $text, $section = "General", $lang = "") {
		if (is_file(join_path(ROOT_DIR, 'config', 'setup.yml')))
			$config = SilkYaml::load(join_path(ROOT_DIR, 'config', 'setup.yml'));
		else
			die("Config file not found!");
			
		// don't update the file if it is the default language
//		echo "default: $config[default_lang] - session: $_SESSION[lang]";
		if(!empty($lang)) {
			if($config["default_lang"] == $lang) {
				return $text;
			}
		}
		if($config["default_lang"] == $_SESSION["lang"]) {
			return $text;
		} else {
			$text = "NEEDS TRANSLATION: $text";
		}
		
		// don't update the file if the value is already in it
		if(self::entry_exists($filename, $constant)) {
			return;
		}

		if(!file_exists($filename)) {
			$lines = array("<?php\n", "?>\n");
		} else {
			$lines = file($filename);
		}
		
		// remove starting and ending tags
		$php_start = array_shift($lines);
		$php_end = array_pop($lines);
		
		/*	add untranslated text to appropriate language file
			in the appropriate section	*/
		$new_lines = array();
		$found_section = false;
		
		foreach($lines as $line) {
			if(self::create_section_header($section) == $line && !$found_section) {
				$new_lines[] = $line;
				$new_lines[] = "define( '$constant', '$text' );\n";
				$found_section = true;
			} else {
				$new_lines[] = $line;
			}
		}
		if(!$found_section) {
			$new_lines[] = self::create_section_header($section);
			$new_lines[] = "define( '$constant', '$text' );\n";
		}

		//add php tags back in
		array_unshift($new_lines, $php_start);
		array_push($new_lines, $php_end);		
		
		$blanks = array("", '', null);
		file_put_contents($filename, array_diff($new_lines, $blanks));
		
		// actually define it because we haven't yet
		if(!defined($constant)) {
			define( $constant, $text );
		}
		return $text;
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
		return join_path(ROOT_DIR, "config", "language", "lang_".$lang.".php");
	}
	
	public function entry_exists($filename, $constant) {
		if(file_exists($filename)) {
			$lines = file($filename);
			foreach($lines as $line) {
				$start = strpos($line, "'") + 1;
				$end = strpos($line, "'", $start);
				$const = substr($line, $start, $end - $start);
				if(!in_array($const, array(">", "*", "?"))) {
					if($const == $constant) {
						return true;
					}
				}
			}
		}
		return false;
	}
}

?>
