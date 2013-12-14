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


/**
* Add link to admin bar
*/

function mla_admin_bar_render() {
    global $wp_admin_bar;

    // we can remove a menu item, like the Comments link, just by knowing the right $id
    // $wp_admin_bar->remove_menu('comments');
	$wp_admin_bar->remove_menu('site-name');

	if(is_user_logged_in()) {
		$wp_admin_bar->remove_menu('my-sites');
	    $wp_admin_bar->add_menu( array(
			'parent' => false,
	        'id' => 'my-sites',
			'title' => __('My Sites'),
	        'href' => '#'
	    ) );
	}


    // or we can remove a submenu, like New Link.
    // $wp_admin_bar->remove_menu('new-link', 'new-content');
	if (!is_admin() && !is_super_admin()) {
		$wp_admin_bar->remove_menu('blog-1', 'my-sites');
	}
	
	//Remove the WordPress logo...
	$wp_admin_bar->remove_menu('wp-logo'); 

	//Remove the search box ...
	$wp_admin_bar->remove_menu('adminbar-search'); 

	/* Disabling this temporarily 
	$wp_admin_bar->add_menu( array(
		'id' => 'mla-link',
		'title' => __('MLA Commons'),
		'href' => network_home_url()
	) );
	$wp_admin_bar->add_menu( array(
		'id' => 'mla-search',
		'title' => __('Search'),
		'href' => site_url().'/site-search/' 
	) );
	 */

}

add_action( 'wp_before_admin_bar_render', 'mla_admin_bar_render' );

// new way to do this modeled after
// this stackexchange answer: 
// http://wordpress.stackexchange.com/questions/125997/how-can-i-specify-the-position-of-an-admin-bar-item-added-with-wp-admin-bar-ad/126326?iemail=1&noredirect=1#126326 
// -JR

function mla_add_commonslink($admin_bar) { 
	$args = array( 
		'id'    => 'mla-link', 
		'title' => __('MLA Commons'),
		'href'  => network_home_url()
	); 
	$admin_bar->add_menu($args); 
} 

add_action('admin_bar_menu', 'mla_add_commonslink', 10); 

function mla_add_searchlink($admin_bar) { 
	$args = array( 
		'id' => 'mla-search',
		'title' => __('Search'),
		'href' => site_url().'/site-search/' 
	); 
	$admin_bar->add_menu($args); 
} 

add_action('admin_bar_menu', 'mla_add_searchlink', 20); 

?>
