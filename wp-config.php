<?php

/**
 * Wordpress configuration file
 *
 * You may want to edit app/config/wordpress.yml to change :
 *   Database settings
 *   Authentication Keys
 *   Debug mode
 *   Post types
 *   Taxonomies
 *   Admin page removal
 *   Image size
 *   theme support
 *   menus
 *   options page
 *   page templates
 *
 *  WP_HOME is automatically generated from $_SERVER but you can set it here
 */

// prevent direct access
if( !defined('AUTOLOAD') && !defined('ABSPATH') ){

	header("HTTP/1.0 404 Not Found");
	exit;
}

 // Add your configuration here
define('WP_REMOTE', false);

include dirname(__DIR__) . '/vendor/metabolism/rocket-wordpress/src/load-config.php';