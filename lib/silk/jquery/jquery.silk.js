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

function silk_ajax_call(sUrl, aArgs, iInt)
{
	aArgs[aArgs.length] = {name:'is_silk_ajax', value:'1'};
	$.ajax({
			url: sUrl,
			data: aArgs,
			type: "POST",
			cache: false,
			success: function (data, textStatus) {
				silk_ajax_callback(data);
			},
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				alert(textStatus);
			}
	});
}

function silk_ajax_callback(xml)
{
	$(xml).find('ajax').children().each(
		function()
		{
			if (this.tagName == 'sc') //from ->script
			{
				var val = $(this).find("t").text();
				eval(val);
			}
		}
	);
}

function silk_ajax_array_to_xml(ary)
{
	var xml = "<ajaxarray>";
	for (i = 0; i < ary.length; i++)
	{
		var elem = String(ary[i]);
		if (elem.indexOf("<sf>") == 0) //handle a serialized form
			xml += elem;
		else
			xml += '<e><![CDATA[' + ary[i] + ']]></e>';
	}
	xml += "</ajaxarray>";

	return xml;
}

jQuery.fn.serializeForSilkAjax = function() {

    return '<sf><![CDATA[' + this.serialize() + ']]></sf>';

};

jQuery.fn.highlight = function(color, speed, easing, callback) {
    
	/* current color of the element */
	var originalColor = jQuery(this).css('backgroundColor');
	
	/* find the first "real" color from the parent elements */
	var parentEl = this.parentNode;
	while(originalColor == 'transparent' && parentEl) {
		originalColor = jQuery(parentEl).css('backgroundColor');
		parentEl = parentEl.parentNode;
	}
	
	/* swap element to the highlight color */
	jQuery(this).css('backgroundColor', color);
	
	/* in IE, style is an object */
	if(typeof this.oldStyleAttr == 'object') {
	    this.oldStyleAttr = this.oldStyleAttr["cssText"];
	}
	
	/* animate back to the original color */
	jQuery(this).animate(
		{'backgroundColor':originalColor},
		speed
	);
};
