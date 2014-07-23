<?php
/*
Plugin Name: MLA Red Admin Bar
Author: Amy Pospiech
Author URI: http://communitystructure.com
Description: This plugin adds the MLA Commons admin bar to any theme.
*/


/**
* Enqueue plugin style-file
*/
function mla_add_my_stylesheet() {
  // Respects SSL, Style.css is relative to the current file
  wp_register_style('mla-admin-bar-style', plugins_url('style.css', __FILE__));
  wp_enqueue_style('mla-admin-bar-style');
}
add_action('wp_enqueue_scripts', 'mla_add_my_stylesheet');


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
	        'href' => '/'
	    ) );
	}


  // or we can remove a submenu, like New Link.
  // $wp_admin_bar->remove_menu('new-link', 'new-content');
	if (!is_admin() && !is_super_admin()) {
		$wp_admin_bar->remove_menu('blog-1', 'my-sites');
	}

	// Remove the WordPress logo...
	$wp_admin_bar->remove_menu('wp-logo');

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
		'href' => network_home_url() . 'site-search/'
	);
	$admin_bar->add_menu($args);
}

add_action('admin_bar_menu', 'mla_add_searchlink', 15);

// add breadcrumb to blogs pages, so users can get back to blogs list easily. 
// -JR
function mla_add_blog_breadcrumb($admin_bar) {
	$this_blogid = get_current_blog_id(); 
	$this_blog_details = get_blog_details(); 
	$this_blog_name = $this_blog_details->blogname; 
	$group_id = get_groupblog_group_id( get_current_blog_id() );
	$group = groups_get_group( array( 'group_id' => $group_id) );
	$url = network_home_url() . 'groups/' . $group->slug; 
	// don't show blog breadcrumb for these blogs
	$disabled_blogids = array( 
		0, // just in case
		1, // main Commons page 
		14, // news page, also displayed on main Commons page
		15, // faq page, also displayed on main Commons page
	); 
	if ( $url && $group_id && ! in_array($this_blogid, $disabled_blogids)) { 
		$args = array(
			'id' => 'mla-group-breadcrumb',
			'title' => $this_blog_name, 
			'href' => $url, 
		);
		$admin_bar->add_menu($args);
	} 
}
add_action('admin_bar_menu', 'mla_add_blog_breadcrumb', 12);

function mla_admin_bar_change_howdy_target( $wp_admin_bar ) {
	$user_id = get_current_user_id();
	$current_user = wp_get_current_user();
	$profile_url = get_edit_profile_url( $user_id );
	if (substr($profile_url, -5) == 'edit/') { 
		$profile_url = substr($profile_url, 0, -5); 
	} 

	if ( 0 != $user_id ) {
		/* Add the "My Account" menu */
		$avatar = get_avatar( $user_id, 28 );
		$howdy = sprintf( __('Welcome, %1$s'), $current_user->display_name );
		$class = empty( $avatar ) ? '' : 'with-avatar';

		$wp_admin_bar->add_menu( array(
			'id' => 'my-account',
			'parent' => 'top-secondary',
			'title' => $howdy . $avatar,
			'href' => $profile_url,
			'meta' => array(
				'class' => $class,
			),
		) );

	}
}
add_action( 'admin_bar_menu', 'mla_admin_bar_change_howdy_target', 11 );

/* Custom my-account menu that links to user/profile
 * instead of user/profile/edit. 
 */ 
function mla_admin_bar_my_account_menu($wp_admin_bar) { 
	$user_id      = get_current_user_id();
	$current_user = wp_get_current_user();
	$edit_profile_url  = get_edit_profile_url( $user_id );
	if (substr($edit_profile_url, -5) == 'edit/') { 
		$profile_url = substr($edit_profile_url, 0, -5); 
	} 

	if ( ! $user_id )
		return;

	$wp_admin_bar->add_group( array(
		'parent' => 'my-account',
		'id'     => 'user-actions',
	) );

	$user_info  = get_avatar( $user_id, 64 );
	$user_info .= "<span class='display-name'>{$current_user->display_name}</span>";

	if ( $current_user->display_name !== $current_user->user_login )
		$user_info .= "<span class='username'>{$current_user->user_login}</span>";

	$wp_admin_bar->add_menu( array(
		'parent' => 'user-actions',
		'id'     => 'user-info',
		'title'  => $user_info,
		'href'   => $profile_url,
		'meta'   => array(
			'tabindex' => -1,
		),
	) );
	$wp_admin_bar->add_menu( array(
		'parent' => 'user-actions',
		'id'     => 'edit-profile',
		'title'  => __( 'Edit My Profile' ),
		'href' => $profile_url,
	) );
	$wp_admin_bar->add_menu( array(
		'parent' => 'user-actions',
		'id'     => 'logout',
		'title'  => __( 'Log Out' ),
		'href'   => wp_logout_url(),
	) );
}
remove_action( 'admin_bar_menu', 'wp_admin_bar_my_account_menu'); 
add_action( 'admin_bar_menu', 'mla_admin_bar_my_account_menu');
