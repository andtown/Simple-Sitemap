<?php
/**
* Plugin Name: Simple Sitemap
* Plugin URI: 
* Version: 1.0.5
* Author: Andtown
* Author URI: 
* Description: This is a simple sitemap plugin, It's used to facilitate the search engine bots for site crawling purposes.
* License: GPL 3
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

defined('SIMPLE_SITEMAP_PLUGIN_PATH') || define( 'SIMPLE_SITEMAP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, array('Simple_Sitemap','activate_plugin') );
register_deactivation_hook( __FILE__, array('Simple_Sitemap','deactivate_plugin') );

require plugin_dir_path( __FILE__ ) . 'includes/class-simple-sitemap.php';

Simple_Sitemap::get_instance();