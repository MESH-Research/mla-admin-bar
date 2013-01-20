<?php
/*
Plugin Name: MLA Red Admin Bar
Author: Amy Pospiech
Author URI: http://communitystructure.com
Description: This plugin adds the red admin bar to any theme.
 */

// disable admin bar style that adds 28px to top of screen
// add_theme_support( 'admin-bar', array( 'callback' => '__return_false') );


/**
	* Enqueue plugin style-file
	*/
function mla_add_my_stylesheet() {
				// Respects SSL, Style.css is relative to the current file
				wp_register_style( 'mla-admin-bar-style', plugins_url('style.css', __FILE__) );
				wp_enqueue_style( 'mla-admin-bar-style' );
}
add_action( 'wp_enqueue_scripts', 'mla_add_my_stylesheet' );

?>