<?php
/*
Plugin Name: MLA Red Admin Bar
Author: Jonathan Reeve, Amy Pospiech
Author URI: http://jonreeve.com 
Description: This plugin adds the MLA Commons admin bar to any theme.
*/

/**
* Some high-level customization for the admin bar.  
*/
function mla_add_my_stylesheet() {
	// Respects SSL, Style.css is relative to the current file
	wp_register_style( 'mla-admin-bar-style', plugins_url( 'style.css', __FILE__ ) );
	wp_enqueue_style( 'mla-admin-bar-style' );
}
add_action( 'wp_enqueue_scripts', 'mla_add_my_stylesheet' );
add_action( 'admin_enqueue_scripts', 'mla_add_my_stylesheet' );

/**
* Add link to admin bar
*/
function mla_admin_bar_render() {
	global $wp_admin_bar;

	// we can remove a menu item, like the Comments link, just by knowing the right $id
	// $wp_admin_bar->remove_menu('comments');
	$wp_admin_bar->remove_menu( 'site-name' );

	if ( is_user_logged_in() ) {
		$wp_admin_bar->remove_menu( 'my-sites' );
		$wp_admin_bar->add_menu( array(
			'parent' => false,
			'id' => 'my-sites',
			'title' => __( 'My Sites' ),
			'href' => '/', 
		) );
	} else { 
		$wp_admin_bar->remove_menu( 'bp-login' ); 
		$wp_admin_bar->add_menu( array( 
			'id' => 'bp-login', 
			'parent' => 'top-secondary', 
			'title' => __( 'Log in' ), 
			'href' => wp_login_url( bp_get_requested_url() ),  
		) ); 
	} 

	// or we can remove a submenu, like New Link.
	// $wp_admin_bar->remove_menu('new-link', 'new-content');
	if ( ! is_admin() && ! is_super_admin() ) {
		$wp_admin_bar->remove_menu( 'blog-1', 'my-sites' );
	}

	// Remove the WordPress logo...
	$wp_admin_bar->remove_menu( 'wp-logo' );

	// Remove the WordPress search...
	$wp_admin_bar->remove_menu( 'search' );

	// Remove the default notifications menu,
	// because we're going to recreate it below. 
	$wp_admin_bar->remove_menu( 'bp-notifications' ); 
}

add_action( 'wp_before_admin_bar_render', 'mla_admin_bar_render' );

// new way to do this modeled after
// this stackexchange answer:
// http://wordpress.stackexchange.com/questions/125997/how-can-i-specify-the-position-of-an-admin-bar-item-added-with-wp-admin-bar-ad/126326?iemail=1&noredirect=1#126326
function mla_add_commonslink($admin_bar) {
	$args = array(
		'id'	=> 'mla-link',
		'title' => __('MLA Commons'),
		'href'	=> network_home_url()
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
	if ( 'hidden' == $group->status ) { 
		if ( ! bp_loggedin_user_id() ) { 
			// logged-out users can't see hidden pages
			return;
		} 
		if ( ! is_super_admin() && ! groups_is_user_member( bp_loggedin_user_id(), $group_id ) ) { 
			// non-members of hidden groups can't see those groups
			return; 
		} 
	} 	
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

function mla_loggedin_user_admin_bar( $wp_admin_bar ) {
	
	// Only show these items to logged-in users. 
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$user_id = get_current_user_id();
	$current_user = wp_get_current_user();
	$profile_url = get_edit_profile_url( $user_id );
	if (substr($profile_url, -5) == 'edit/') { 
		$profile_url = substr($profile_url, 0, -5); 
	} 

	$member_activity_url = bp_loggedin_user_domain() . bp_get_activity_slug(); 

	/* Add the "My Account" menu */
	$avatar = get_avatar( $user_id, 28 );
	$howdy = 'My Commons';	
	$class = empty( $avatar ) ? '' : 'with-avatar';

	$wp_admin_bar->add_menu( array(
		'id' => 'me', 
		'parent' => 'top-secondary', 
		'title' => $avatar, 
		'href' => $profile_url, 
		'meta' => array(
			'class' => $class,
		),
	) ); 

	$wp_admin_bar->add_menu( array(
		'id' => 'my-commons',
		'parent' => 'top-secondary',
		'title' => $howdy, 
		'href' => $member_activity_url,
		'meta' => '', 
	) );

	// Custom notifications menu without dropdown. 
	$notifications = bp_notifications_get_notifications_for_user( bp_loggedin_user_id(), 'object' );
	$count	       = ! empty( $notifications ) ? count( $notifications ) : 0;
	$alert_class   = (int) $count > 0 ? 'pending-count alert' : 'count no-alert';
	$menu_title    = '<span id="ab-pending-notifications" class="' . $alert_class . '"><span class="dashicons dashicons-testimonial"></span><span id="notification-number">' . number_format_i18n( $count ) . '</span></span>';
	$menu_link     = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );

	// Add the top-level Notifications button
	$wp_admin_bar->add_menu( array(
		'parent'    => 'top-secondary',
		'id'	    => 'mla-bp-notifications',
		'title'     => $menu_title,
		'href'	    => $menu_link,
	) );
	
	// Add a Log Out button. 
	$wp_admin_bar->add_menu( array(
		'id' => 'log-out', 
		'parent' => 'top-secondary', 
		'title' => 'Log Out', 
		'href' => wp_logout_url(), 
	) ); 

	$wp_admin_bar->remove_menu( 'my-account' ); 
}
add_action( 'admin_bar_menu', 'mla_loggedin_user_admin_bar', 11 );
