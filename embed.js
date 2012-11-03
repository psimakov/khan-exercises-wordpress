/*

	Khan Exercises for WordPress

	Copyright (C) 2012 Pavel Simakov (pavel@vokamis.com)
	https://github.com/psimakov/khan-exercises-wordpress

*/

(function() {

	// sanitize CSS style and other restricted alphanumeric input
	function sanitize(text){
		var out = "";
		var len = text.length;
		for (i = 0; i < len; i++) {
			var c = text.charAt(i);
			if ('a' <= c && c <= 'z' ||
				'A' <= c && c <= 'Z' ||
				'0' <= c && c <= '9' ||
				':' == c || ';' == c ||
				'-' == c || '#' == c ||
				'{' == c || '}' == c ||
				' ' == c || '.' == c ||
				'_' == c
				){
				out = out + c;
			} else {
				out = out + "*";
			}
		}
		return out;
	}

	// figure out base URL by looking at the last DOM node that embedded this script
	var scripts = document.getElementsByTagName('script');
	var parts = scripts[scripts.length - 1].src.split('?');
	var path = parts[0];	// resource
	var base = path.split('/').slice(0, -1).join('/')+'/';		// remove last filename part of path

	// embed frame uid
	if (typeof(window['ity_ef_uid']) == "undefined"){
		window['ity_ef_uid'] = 0;
	} else { 
		ity_ef_uid = parseInt(ity_ef_uid) + 1;
	}

	// get exercise id (protocol:name)
	var id = "static:adding decimals";
	if (typeof(window['ity_ef_id']) != "undefined"){
		id = sanitize(ity_ef_id);		// use one defined by a variable
		window['ity_ef_id'] = undefined;
	} else {
		if (parts.length == 2){
			id = sanitize(parts[1]);	// use one passed in a query string
		}
	}

	// get custom style
	var style = "width: 100%; min-height: 550px; overflow: hidden; border: none;";
	if (typeof(window['ity_ef_style']) != "undefined"){
		style = style + sanitize(ity_ef_style);
	 	window['ity_ef_style'] = undefined;
	}

	// prepare iframe html
	var uid = "ity-ef-exercise-" + ity_ef_uid;
	var src = base + "/khan-exercises/indirect/?ity_ef_slug=" + id + "&ity_ef_site=raw";
	var body = "<a name='" + uid + "-ancor'></a><iframe src='" + src + "' style='" + style + "' frameborder='0' scrolling='no' id='" + uid + "'></iframe>";

	// render it out
	document.write(body);

})();