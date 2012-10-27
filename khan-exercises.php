<?php
/*
Plugin Name: Khan Exercises for WordPress
Plugin URI: https://github.com/psimakov/khan-exercises-wordpress
Description: Brings Khan Academy exercises into your WordPress blogs.
Version: 1.0
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


/*

	The plugin helps to:
		+ display a static exercise file in a themed post page
			/wp-content/plugins/khan-exercises/khan-exercises/indirect/?ity_ef_slug=static:adding_decimals

		+ display a dynamic exercise content in a themed post page
			via custom ity_ef_render_indirect_$protocol_hook($identifier) function

		+ embed exercise via <iframe>; render an exercise without header or footer
			<iframe src="/wp-content/plugins/khan-exercises/khan-exercises/indirect/?ity_ef_slug=static:adding_decimals&ity_ef_site=raw"></iframe>

		+ make a page listing all available exercises
			/?ity_ef_rule=list

		+ collect exercise results into audit.log file for all visitors,
		  even anonymous; defent this file against viewing over HTTP

		+ collect exercise results into a database
			via custom ity_ef_save_audit_data_hook() function

	Next steps:
		- let user see the results of his attempts
		- put an exercise source code inside the post

*/


function ity_ef_starts_with($haystack, $needle){
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}


// render licensing terms warning section
function ity_ef_render_warning_template() {
	return "
	<p style='background-color: #D0FFD0; padding: 16px; margin: 16px; max-width: 500px;'>
	<strong>LICENSE TERMS:</strong><br/>
	<br/>
	This plugin is <strong>Copyright 2012 <a href='https://github.com/psimakov/khan-exercises-wordpress'>Pavel Simakov</a></strong> and is licensed under <a href='http://www.gnu.org/licenses/lgpl-2.1.html'>LGPL 2</a>.<br/>
	<br/>
	It also includes a distribution of Khan Academy Exercise Framework & Khan Academy Exercises both <strong>Copyright 2012 <a href='".plugins_url('/khan-exercises/README.md', __FILE__)."'>Khan Academy</a></strong>. The exercise framework is <a href='http://en.wikipedia.org/wiki/MIT_License'>[MIT licensed]</a>. The exercises are protected under a <a href='http://creativecommons.org/licenses/by-nc-sa/3.0/'>[Creative Commons by-nc-sa license]</a>.<br/>
	<br/>
	You required by law to <strong>respect</strong> the above licensing terms!</p>";
}


// render all exercises found in the /khan-exercises/exercises/... folder
function ity_ef_render_list_items() {
	$body = 
		'<style>
			li.ity-ef-item div {
				display: none;
			}
			li.ity-ef-item:hover div {
				margin-left: 48px;
				display: block;
			}
			li.ity-ef-item:hover div pre {
				background-color: #D0D0FF;
				margin: 0px;
				padding: 4px;
			}
		</style>';

	$body .= "<span style='font-weight: bold; font-size: larger;'>Khan Academy Exercises</a></span> (see <a href='".plugins_url('/khan-exercises/README.md', __FILE__)."'>license terms</a>)<br/>Choose one of the exercises below:<ul style='padding-left: 32px; list-style: circle inside;'>";
	if ($handle = opendir(dirname(__FILE__).'/khan-exercises/exercises')) {
		while (false !== ($entry = readdir($handle))) {
			if (ity_ef_starts_with($entry, "khan-") || $entry == "." || $entry == ".." || $entry == "test") {
				continue;
			}
			$slug = str_replace(".html", "", $entry);
			$caption = str_replace("_", " ", $slug);
			$url = plugins_url('/khan-exercises/indirect/?ity_ef_slug=static:'.$slug, __FILE__);
			$embed = "
<iframe 
  src='".$url."&ity_ef_site=raw'
  style='width: 100%; min-height: 600px; overflow: none; border: none;'
>
</iframe>";

			$body .= "<li class='ity-ef-item'>";
			$body .= "<a style='text-decoration: none;' href='".$url."'>".esc_html($caption)."</a>";
			$body .= "<div>Here is how to embed:<br/><pre>".esc_html($embed)."</pre></div>";
			$body .= "</li>\n";
		}
	}
	$body .= "</ul>";
	return $body;
}


// render Khan Exercise container
function ity_ef_render_container_template() {
	$debug = is_admin();
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

	// decode json to extract specific fields
	if (get_magic_quotes_gpc())
		$json = stripslashes($json);
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
    $query_vars = array('ity_ef_rule', 'ity_ef_audit');
    return $query_vars;
}
add_filter('query_vars', 'ity_ef_query_vars');


// handle here all custom URL's supported by our plugin
add_action('template_redirect', 'ity_ef_controller', 1);
function ity_ef_controller(){
	global $wp_query;

	// handle audit data postback
	$data = $wp_query->get('ity_ef_audit');
	if ($data) {
		if (function_exists('ity_ef_save_audit_data_hook')){
			ity_ef_save_audit_data_hook($data);
		} else {
			ity_ef_save_audit_data($data);
		}
		exit;
	}
	
	// render exercise container
	if ($wp_query->get('ity_ef_rule') == "container") {
		get_header();
		echo '<div style="width: 100%;" align="center"><div style="width: 85%;" align="left">';
		echo ity_ef_render_container_template();
		echo '</div></div>';
		get_footer();
		exit;
	}

	// render raw
	if ($wp_query->get('ity_ef_rule') == "raw") {
		echo 
			'<!DOCTYPE html>
			<html">
			<head>
			    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<head>
			<body><div style="margin-left: 48px;">';
		echo ity_ef_render_container_template();
		echo '</div></body></html>';
		exit;
	}

	// list all exercises
	if ($wp_query->get('ity_ef_rule') == "list") {
		get_header();
		echo "<div style='margin: 48px;'>".ity_ef_render_list_items()."</div>";
		get_footer();
		exit;
	}
}


// render content of exercise not located on the file system
function ity_ef_render_indirect(){
	// figure out a protocol and an identifier from the request
	$slug = $_GET["ity_ef_slug"]; 
	$parts = explode(":", $slug);
	if (count($parts) != 2) {
		echo "Error processing request. Expected 'ity_ef_slug' in a forms of 'protocol:identifier'.";
		exit;
	}

	// sanitize a protocol
	$protocol = preg_replace('/[^a-zA-Z_]/', '', $parts[0]);
	if ($protocol != $parts[0]) {
		echo "Protocol name can only have 'a-zA-Z_'.";
		exit;
	}

	// handle static exercise file request
	if ($protocol == 'static') {
		$base_name = preg_replace('/[^a-zA-Z0-9_]/', '', $parts[1]);
		$fname = dirname(__FILE__).'/khan-exercises/exercises/'.$base_name.'.html';
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
		$func($parts[1]);
		exit;
	}

	// we dont really know how to handle the request further
	header("HTTP/1.0 404 Not Found");
	echo "Unsupported protocol.";
}


// demo custom function to generate dynamic exercise text
function ity_ef_render_indirect_demo_hook() {
 	echo
		'<!DOCTYPE html>
		<html data-require="word-problems math subhints">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<title>Multiple Choice Test</title>
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
		echo "<h2>Khan Exercises for WordPress!</h2>";
		echo ity_ef_render_warning_template();
		echo "<h2>How many exercises are there?</h2>Here are all <a href='/?ity_ef_rule=list'>423 exercises</a> available in this distribution.";
		echo "<h2>Where is a demo?</h2>Follow <a href='".plugins_url('/khan-exercises/indirect/?ity_ef_slug=static:functions_2', __FILE__)."'>this link</a> to see a demo exercise. You should see a chart, a questions, and some buttons. It may take 3-4 seconds to load.";
		echo "<h2>Can an exercise be embedded into an &lt;iframe&gt;?</h2>Yes. Follow <a href='".plugins_url('/khan-exercises/indirect/?ity_ef_slug=static:adding_fractions&ity_ef_site=raw', __FILE__)."'>this link</a> to see a demo exercise that has no header or footer (notice ity_ef_site=raw in the query string). It may take 3-4 seconds to load.";
		echo "<h2>Where are the exercise results?</h2>Every time a visitor answers an exercise the results are sent to your WordPress installation and placed into `/wp-content/khan-exercises/audit.log` or a database. You can't get that file via web interface because we protected it with .htaccess; use FTP. Don't forget to back up this file when updating the plugin!";
		echo "<h2>Can the exercise results be recorded into a database?</h2>Yes. Simply declare a new PHP function ity_ef_save_audit_data_hook(). Plugin will call it, instead of appending the data to a file.";
		echo "<h2>Can the exercise source be created dynamically?</h2>Yes. Open the PHP source code for this plugin and look for the ity_ef_render_indirect_demo_hook() function. This function will be triggered when you follow <a href='".plugins_url('/khan-exercises/indirect/?ity_ef_slug=demo:identifier', __FILE__)."'>this link</a>. Note how the protocol name 'demo' in the link is mapped onto the ity_ef_render_indirect_$protocol_hook($identifier) function. Simply declare a new PHP function using a protocol name you desire and generate the exercise text in this function.";
		echo "<h2>Why was this plugin developed?</h2>Khan Exercises are great! But the framework is very complex. It's difficult to embed an exercise into another web site. We made <a href='https://github.com/psimakov/khan-exercises'>some minor changes</a> to the Khan Exercise Framework so it's easier to embed the exercises and collect the exercise results. We hope to let many more people to use the exercises in their blogs as a teaching tool. We hope you will create new interactive educational content to help students to learn and get better.";
	}
}

?>