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

/**
 * Base class for all Silk classes
 *
 * @author Ted Kulp
 * @since 1.0
 **/
abstract class SilkObject
{
	/**
	 * Base constructor.  Doesn't really do anything, but
	 * gives methods extending CmsObject something to call.
	 *
	 * @author Ted Kulp
	 **/
	public function __construct()
	{
		//echo 'instantiate - ', $this->__toString(), '<br />';
	}

	/**
	 * Base toString override.
	 *
	 * @return string The name of the class
	 * @author Ted Kulp
	 **/
	public function __toString()
	{
		return "Object(".get_class($this).")";
	}

	function autoform( $override_fieldtypes=null ){
		global $cfg;
		if( isset( $this->_fieldtypes ) ){
			//allow ability to override the field types to be used.
			$fieldtypes = &$this->_fieldtypes;
			if( !is_null( $override_fieldtypes ) )
				$fieldtypes = $override_fieldtypes;

			$fieldnames = get_object_vars( $this );
			$classname = get_class( $this );
			require_once( "$cfg->abspath/includes/common.html.php" );
			echo "<div class='autoform autoform_$classname'>";
			$i=0;
			$commonHTML = new commonHTML(); //needed because method_exists dosn't seem to be able to handle a static method.
			foreach( $fieldnames as $fieldname=>$fieldvalue ) {
				if( strpos( $fieldname, "_" ) !== 0 ) {
					if( key_exists( $fieldname, $fieldtypes ) ) //added May 19, 2008
						@list( $type, $class ) = split( "_", $fieldtypes[$fieldname] );
					else
						@list( $type, $class ) = split( "_", $fieldtypes[$i] );
					if( $type == 'method' ) {
						$this->$class();
					}else{
						if( $type == 'review' )
							$method = $type;
						else
							$method = "input_".$type;

						if( method_exists( $commonHTML, $method ) )
							commonHTML::$method( $fieldname, $fieldvalue, $class );
						else
							commonHTML::input_hidden( $fieldname, $fieldvalue, $class );
					}
				}
				$i++;
			}
			echo '</div>';
		}
	}
}
?>