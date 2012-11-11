<?php
/*
Plugin Name: Khan Exercises for WordPress
Plugin URI: https://github.com/psimakov/khan-exercises-wordpress
Description: Brings Khan Academy exercises into your WordPress blogs.
Version: 1.1
Author: pavel@vokamis.com (Pavel Simakov)
Author URI: https://github.com/psimakov/khan-exercises-wordpress
License: LGPL
*/


/*

	Khan Exercises for WordPress

	Copyright (C) 2012 Pavel Simakov (pavel@vokamis.com)
	https://github.com/psimakov/khan-exercises-wordpress

	This library is free software; you can redistribute it and/or
	modify it under the terms of the GNU Lesser General Public
	License as published by the Free Software Foundation; either
	version 2.1 of the License, or (at your option) any later version.

	This library is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	Lesser General Public License for more details.

	You should have received a copy of the GNU Lesser General Public
	License along with this library; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

*/

function ity_ef_starts_with($haystack, $needle){
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}


// render licensing terms warning section
function ity_ef_render_warning_template() {
	return "
	<p style='background-color: #67E667; padding: 16px; margin: 16px; max-width: 500px;'>
	<strong>LICENSE TERMS:</strong><br/>
	<br/>
	This plugin is <strong>Copyright 2012 <a href='https://github.com/psimakov/khan-exercises-wordpress'>Pavel Simakov</a></strong> and is licensed under <a href='http://www.gnu.org/licenses/lgpl-2.1.html'>LGPL 2</a>.<br/>
	<br/>
	This plugin includes a distribution of Khan Academy Exercise Framework & Khan Academy Exercises both <strong>Copyright 2012 <a href='".plugins_url('/khan-exercises/README.md', __FILE__)."'>Khan Academy</a></strong>. The exercise framework is <a href='http://en.wikipedia.org/wiki/MIT_License'>[MIT licensed]</a>. The exercises are protected under a <a href='http://creativecommons.org/licenses/by-nc-sa/3.0/'>[Creative Commons by-nc-sa license]</a>.<br/>
	<br/>
	You required by law to <strong>respect</strong> the above licensing terms!</p>";
}


// render all exercises found in the /khan-exercises/exercises/... folder
function ity_ef_render_list_items() {
	$body = 
		'<style>
			li.ity-ef-item div {
				margin-left: 48px;
				display: none;
			}
			li.ity-ef-item div pre {
				background-color: #D0D0D0;
				margin: 0px;
				padding: 8px;
			}
		</style>
		<script language="javascript">
		function toggleDiv(divid){
			if(document.getElementById(divid).style.display == "block"){
				document.getElementById(divid).style.display = "none";
			} else {
				document.getElementById(divid).style.display = "block";}
			}
		</script>
		';

	$body .= "<span style='font-weight: bold; font-size: larger;'>Khan Academy Exercises</a></span> (see <a href='".plugins_url('/khan-exercises/README.md', __FILE__)."'>license terms</a>)<br/>Choose one of the exercises below:<ul style='padding-left: 32px; list-style: circle inside;'>";


	$files = scandir(dirname(__FILE__).'/khan-exercises/exercises/');
	if ($files) {
		$i = 0;
		foreach ($files as $entry){
			if (ity_ef_starts_with($entry, "khan-") || $entry == "." || $entry == ".." || $entry == "test") {
				continue;
			} 
			$slug = str_replace(".html", "", $entry);
			$caption = str_replace("_", " ", $slug);
			$url = plugins_url('/khan-exercises/indirect/?ity_ef_format=iframe&ity_ef_slug=static:'.$slug, __FILE__);
			$src = plugins_url('/embed.js?static:'.$slug, __FILE__);
			$embed = "<script\n  type='text/javascript'\n  src='".$src."'\n></script>";
			$body .= "<li class='ity-ef-item'>";
			$body .= esc_html($caption);
			$body .= " [<a style='text-decoration: none;' href='".$url."'>view</a>, ";
			$body .= "<a href='javascript:;' onclick='toggleDiv(\"ity-ef-item-".$i."\");'>embed</a>]";
			$body .= "<div id='ity-ef-item-".$i."'>";
			$body .= "Here is how to link to an exercise page: <pre>".esc_html("<a href='".$url."'>".esc_html($caption)."</a>")."</pre>";
			$body .= "Here is how to embed an exercise into a WordPress post or page using a shortcode: <pre>[khan_exercise src='static:".$slug."' /]</pre>";
			$body .= "Here is how to embed an exercise into any web page using an &lt;iframe&gt;:<br/><pre>".esc_html($embed)."</pre>";
			$body .= "</div>";
			$body .= "</li>\n";
		
			$i++;
		}
	}
	$body .= "</ul>";
	return $body;
}


// render Khan Exercise container
function ity_ef_render_container_template() {
	$debug = current_user_can('manage_options');
	return
		'
        <header style="display: none;" />
		<div id="container" class="single-exercise visited-no-recolor" style="overflow: hidden;">
			<article class="exercises-content clearfix">
			<div class="exercises-header"><h2 class="section-headline">
					<div class="topic-exercise-badge">&nbsp;</div>
					<span class="practice-exercise-topic-context">Practicing</span>
			</h2></div>
			<div class="exercises-body">
				<div class="exercises-stack">&nbsp;</div>
				<div class="exercises-card current-card">
					<div class="current-card-container card-type-problem">
						<div class="current-card-container-inner vertical-shadow">
							<div class="current-card-contents">
							</div>
						</div>
						<div id="extras" class="single-exercise">
							<ul>
								<li> <a id="scratchpad-show" href style>Show scratchpad</a>
									<span id="scratchpad-not-available" style="display: none;">Scratchpad not available</span>
								</li>'. ($debug ? '
									<li class="debug-mode"> <a href="?debug">Debug mode</a></li>
									<li> <a id="problem-permalink" href>Problem permalink</a></li>
								' : '').'
							</ul>
						</div>
					</div>
				</div>
			</div>
			</article>
		</div>
        <footer id="footer" class="short" style="display: none;"></footer>';
}


// save audit data to a file
function ity_ef_save_audit_data($json){
	// get user information
	global $current_user;
	get_currentuserinfo();

	$decoded = json_decode($json, true);
	$pass = $decoded['pass'] ? "pass" : "fail";
	$context = $decoded['context'];

	// prepare log line
	$line = 
		date(DATE_RFC822).
		"\t".$_SERVER['REMOTE_ADDR'].
		"\t".$current_user->ID.
		"\t".$pass.
		"\t".$context.
		"\t".$json."\n";

	// append to log file
	$file = dirname(__FILE__)."/audit.log";
	$fh = fopen($file, 'a');
	fwrite($fh, $line);
	fclose($fh);
}


// register all custom query vars
function ity_ef_query_vars( $query_vars ){
    array_push($query_vars, 'ity_ef_rule', 'ity_ef_audit');
    return $query_vars;
}
add_filter('query_vars', 'ity_ef_query_vars');

// gets a query string parameter by name and rmoves magic quotes
function ity_ef_get_param($name){
	if (array_key_exists($name, $_GET)) {
		$value = $_GET[$name];
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
		return $value;
	}
	return null;
}

// handle here all custom URL's supported by our plugin
add_action('template_redirect', 'ity_ef_controller', 1);
function ity_ef_controller(){
	$data = ity_ef_get_param('ity_ef_audit');
	$rule = ity_ef_get_param('ity_ef_rule');
	$slug = ity_ef_get_param('ity_ef_slug');

	// WP always has magic quotes on; remove them here
	$data = stripslashes_deep($data);

	// find the right callback
	if ($data) {
		if (function_exists('ity_ef_save_audit_data_hook')){
			ity_ef_save_audit_data_hook($data);
		} else {
			ity_ef_save_audit_data($data);
		}
		
		// return valid JSON
		echo "{}";
		exit;
	}
	
	// render exercise container
	if ($rule == "container") {
		get_header();
		echo '<div style="width: 100%;" align="center"><div style="width: 85%;" align="left">';
		echo ity_ef_render_container_template();
		echo '</div></div>';
		get_footer();
		exit;
	}

	// render raw
	if ($rule == "raw") {
		echo 
			'<!DOCTYPE html>
			<html">
			<head>
			    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<head>
			<body><div style="margin-left: 2%;">';
		echo ity_ef_render_container_template();
		echo '</div></body></html>';
		exit;
	}

	// list all exercises
	if ($rule == "list") {
		get_header();
		echo "<div style='margin: 48px;'>".ity_ef_render_list_items()."</div>";
		get_footer();
		exit;
	}
	
	// handle indirect
	if ($slug) {
		ity_ef_render_indirect($slug);
	}
}


// render content of exercise not located on the file system
function ity_ef_render_indirect($slug){
	// figure out a protocol and an identifier from the request
	$parts = explode(":", $slug);
	if (count($parts) != 2) {
		echo "Error processing request. Expected 'ity_ef_slug' in a forms of 'protocol:identifier'.";
		exit;
	}

	// sanitize protocol
	$protocol = preg_replace('/[^0-9a-zA-Z_]/', '', $parts[0]);
	if ($protocol != $parts[0]) {
		echo "Protocol name can only have '0-9a-zA-Z_'.";
		exit;
	}

	// sanitize identifier
	$identifier = preg_replace('/[^0-9a-zA-Z_\-\.]/', '', $parts[1]);
	if ($identifier != $parts[1]) {
		echo "Identifier name can only have '0-9a-zA-Z_\-\.'.";
		exit;
	}

	// handle request for iframe embed
	if (array_key_exists('ity_ef_format', $_GET) && $_GET['ity_ef_format'] == "iframe") {
		get_header();
		echo '<div style="width: 100%;" align="center"><div style="width: 85%;" align="left">';
		echo "
<script
  type='text/javascript'
  src='".plugins_url('/embed.js?'.$protocol.':'.$identifier, __FILE__)."'
></script>";
		echo '</div></div>';
		get_footer();
		exit;
	}

	// handle static exercise file request
	if ($protocol == 'static') {
		$fname = dirname(__FILE__).'/khan-exercises/exercises/'.$identifier.'.html';
		if(file_exists($fname)) {
			include $fname;
		} else {
			header("HTTP/1.0 404 Not Found");
			echo "Static resource not found.";
			exit;
		}
		exit;
	}

	// handle other requests using the hook function if it's defined
	$func = 'ity_ef_render_indirect_'.$protocol.'_hook';
	if (function_exists($func)){
		$func($identifier);
		exit;
	}

	// we dont really know how to handle the request further
	header("HTTP/1.0 404 Not Found");
	echo "Unsupported protocol.";
}


// demo custom function to generate dynamic exercise text
function ity_ef_render_indirect_demo_hook($identifier) {
 	echo
		'<!DOCTYPE html>
		<html data-require="word-problems math subhints">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<title>Multiple Choice Test ('.esc_html($identifier).')</title>
			<script src="../khan-exercise.js"></script>
		</head>
		<body>
			<div class="exercise">
				<div class="problems">
					<div id="sky">
						<div class="question"><p>What color is the sky?</p></div>
						<div class="solution">Blue</div>
						<ul class="choices"><li>Green</li><li>Red</li><li>Yellow</li></ul>
					</div>
				</div>
			</div>
		</body></html>';
}


// add a new page to the admin interface
if (is_admin()){
	add_action('admin_menu', 'ity_ef_admin_menu');

	function ity_ef_admin_menu() {
		add_options_page('Khan Exercises', 'Khan Exercises', 'administrator', 'khan-exercises', 'ity_ef_html_page');
	}

	function ity_ef_html_page() {
		echo '<style>
		  div#ity-ef-about a {
			  background-color: #FFDE40;
			  padding-left: 4px;
			  padding-right: 4px;
			  color: #444444;
		  }

		  div#ity-ef-about a:hover {
			  background-color: #876ED7;
		  }
		</style>';
		echo "<div style='padding: 16px; margin: 16px; max-width: 600px;'>";
		echo "<h2>Khan Exercises for WordPress!</h2>";
		echo ity_ef_render_warning_template();

		echo "<div id='ity-ef-about'>";

		echo "<h2>Is there a demo?</h2>Yes. Follow <a href='".plugins_url('/khan-exercises/indirect/?ity_ef_format=iframe&ity_ef_slug=static:functions_2', __FILE__)."'>this link</a> to see a demo exercise. You should see a blog page with a chart, a question and 'Check Answer' button. It may take 3-4 seconds to load.";

		echo "<h2>How many exercises are included?</h2>There are <a href='".get_bloginfo('url')."/?ity_ef_rule=list'>423&nbsp;exercises</a> available. After you select an exercise, you can either link to it, embed it into a post or a page via shortcode, or embed it into any web page via &lt;iframe&gt;.";
				
		echo "<h2>Can an exercise be embedded into a WordPress post or a page?</h2>Yes. Yes. Edit the post and insert the following code anywhere inside the post text (including the square brackets): <code>[khan_exercise src=\"static:absolute_value_of_complex_numbers\" /]</code>. The proper shortcodes are listed next to each of the <a href='".get_bloginfo('url')."/?ity_ef_rule=list'>423&nbsp;exercises</a>.";
		
		echo "<h2>Can an exercise be embedded into any web page using an &lt;iframe&gt;?</h2>Yes. Follow <a href='".plugins_url('/khan-exercises/indirect/?ity_ef_slug=static:adding_fractions&ity_ef_site=raw', __FILE__)."'>this link</a> to see a demo exercise that has no header or footer (notice <code>ity_ef_site=raw</code> in the query string). You can put this page into an &lt;iframe&gt; on any web page you like. The proper &lt;iframe&gt; embed code is listed next to each of the <a href='".get_bloginfo('url')."/?ity_ef_rule=list'>423&nbsp;exercises</a>. If you embed an exercise into a website that has a different domain name than your WordPress blog, dynamic exercise &lt;iframe&gt; height adjustment may not work.";
		
		echo "<h2>Can an exercise be embedded into a WordPress post without an &lt;iframe&gt;?</h2>Yes, but... If your theme uses advanced JavaScript libraries like JQuery, YUI or Google Analytics they will likely conflict with the JavaScript code of the exercises. Using an &lt;iframe&gt; resolves any possible issues. You can try <a href='".plugins_url('/khan-exercises/indirect/?ity_ef_slug=static:functions_2', __FILE__)."'>this link</a> to see how embedding works without &lt;iframe&gt;. Note that we have removed the query string parameter <code>ity_ef_format=iframe</code>, which controls whether an exercise is embedded directly into a WordPress page or linked into it via an &lt;iframe&gt;. If page does not load or you see a JavaScript error - your blog should use &lt;iframe&gt; embedding.";

		echo "<h2>Where are the exercise results?</h2>Every time a visitor does an exercise the results are sent to your WordPress installation and placed into either <code>/wp-content/khan-exercises/audit.log</code> file or a database. You can't access this log file via a web interface because we protected it with <code>.htaccess</code>; use FTP. Don't forget to backup this file when updating the plugin!";
		
		echo "<h2>Can the exercise results be recorded into a database?</h2>Yes. Simply declare a new PHP function <code>ity_ef_save_audit_data_hook($json)</code>, and provide an alternative storage implementation. Plugin will call this new function instead of appending the data to a file.";
		
		echo "<h2>Can the exercise source be created dynamically or loaded from a database?</h2>Yes. Every time you navigate to an exercise a <code>ity_ef_render_indirect()</code> function is triggered. It expects the protocol and the identifier of the exercise passed as a <code>ity_ef_slug</code> query string parameter, for example <code>ity_ef_slug=static:absolute_value</code>. For accessing the exercises located on the file system we use <code>static</code> as a protocol name. Its handler simply loads the file (with a name <code>absolute_value</code> in the example above) from the file system. You can declare a new protocol name and provide your own handler function to either construct the exercises on the fly or load them from a database.<br/>
		<br/> 
		We have a simple demo included to help you get started. Open the PHP source code for this plugin and look at the <code>ity_ef_render_indirect_demo_hook($identifier)</code> function. See the exercise it generates in PHP code by following <a href='".plugins_url('/khan-exercises/indirect/?ity_ef_format=iframe&ity_ef_slug=demo:my_exercise_id_1', __FILE__).'\'>this link</a>. Note how the protocol name <code>demo</code> in the link URL is mapped onto the <code>ity_ef_render_indirect_demo_hook($identifier)</code> function name. Similarly if you want to declare a protocol named <code>foo</code>, you can handle it in the function named <code>ity_ef_render_indirect_foo_hook($identifier)</code> and so on. You can declare a new function anywhere in your WordPress installation, but it is probably better to do it in your theme <code>functions.php</code> file.';
		
		echo "<h2>Can I contribute to this project?</h2>Yes. You can contribute new exercises and code improvements to our <a href='https://github.com/psimakov/khan-exercises'>khan-exercises</a> and <a href='https://github.com/psimakov/khan-exercises-wordpress'>khan-exercises-wordpress</a> open-source Git repositories. Here is what is currently missing:
		<ol>
		  <li>Enable dynamic exercise iframe height resize for cross domain embedding.</li>
		  <li>Enable storing of the exercise results in a database and allow users to view their own exercise results.</li>
		  <li>Remove unnecessary JavaScript and CSS from the exercise framework</li>
		  <li>Administer a series of exercises in a defined order.</li>
		  <li>Collect exercise responses without immediately revealing the answers to the user.</li>
		</ol>";

		echo "</div>";
		echo "</div>";
	}
}


// add [khan_exercise src='protocol:identifier' /] shortcode
function ity_ef_shortcode_khan_exercise($atts, $content = null) {
	extract(shortcode_atts(array('src' => '#'), $atts));
	if ($src){
		$body =
			"<script
			  type='text/javascript'
			  src='".plugins_url("/embed.js?".$src, __FILE__)."'
			></script>";
		return $body;
	} else {
		return 'Expected a shortcode with \'src\' attribute, for example: [khan_exercise src="static:absolute_value_of_complex_numbers" /].';
	}
}
add_shortcode('khan_exercise', 'ity_ef_shortcode_khan_exercise');

?>