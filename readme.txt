=== Khan Exercises for WordPress ===
Contributors: psimakov
Donate link: http://www.softwaresecretweapons.com/jspwiki/about
Tags: Khan, Khan Academy, exercises, worksheets, Math, school
Requires at least: 2.7.0
Tested up to: 3.4
Stable tag: 1.0
License: LGPL
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Brings Khan Academy exercises into your WordPress blogs. 

== Description ==

Khan Exercises for WordPress plugin lets you host Khan Academy exercises in your WordPress blog.
[Khan Exercises](http://www.khanacademy.org/exercisedashboard) are great and free to everyone!
However, the framework is very complex; it's difficult to embed its exercises into another web site.
We made <a href='https://github.com/psimakov/khan-exercises'>framework changes</a>
than make it easier to embed the exercises and collect the exercise results right in your WordPress blog.

== Installation ==

Installation is very simple:

1. Download plugin zip file, unzip it
2. Upload `khan-exercises.php` and all other files to the `/wp-content/plugins/khan-exercises/` directory
3. Activate the `Khan Exercises` plugin through the `Plugins` menu in WordPress
4. Login as Admin and go to `Settings` > `Khan Exercises`
5. Review the plugin settings page and navigate the links to see exercises

== Frequently Asked Questions ==

= Where are the exercises hosted? =

All exercise files, HTML, CSS and JavaScript are served from your WordPress installation.
Khan Academy official website does not serve any files or receives any data from your site.

= Where do the exercise results go? =

Every time a visitor answers an exercise the results are sent to your WordPress installation
and placed into `/wp-content/khan-exercises/audit.log` or a database.

= What are the licensing terms for using Khan Exercise Framework and Khan Exercises in my blog? =

The exercise framework is [MIT licensed](http://en.wikipedia.org/wiki/MIT_License).
The exercises are protected under a [Creative Commons by-nc-sa license](http://creativecommons.org/licenses/by-nc-sa/3.0/).

== Who developed this plugin and why? ==

This plugin was developed by [Pavel Simakov]( http://www.softwaresecretweapons.com/jspwiki/about)
and uses a fork of Khan Academy Exercises Framework. Pavel currently works at Google as a lead engineer
on [Google Course Builder](https://code.google.com/p/course-builder/) and other educational initiatives.
He is also a founder of an adaptive education site [itestyou.com](http://www.itestyou.com).
Engaging interactive activities are fundamental to effective learning. Khan Academy Exercises are great!
Now it’s also easy to create and use them everywhere.

== Screenshots ==

1. An example of exercise in a standard WordPress theme
2. A list of all available Khan Exercises - over 400 exercises!
3. An example exercise hosted in a WordPress blog, but embedded via an `iframe` into another blog at blogger.com
4. Plugin settings page

== Changelog ==

= 1.0 =
* First version

== Upgrade Notice ==

= 1.0 =
First version