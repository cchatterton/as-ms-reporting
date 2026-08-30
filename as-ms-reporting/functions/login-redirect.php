<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect subscribers to the homepage after login.
 */
add_filter( 'login_redirect', function ( $redirect_to, $requested_redirect_to, $user ) {
	if (
		$user instanceof WP_User
		&& in_array( 'subscriber', (array) $user->roles, true )
		&& ! user_can( $user, 'edit_posts' )
	) {
		return home_url( '/' );
	}

	return $redirect_to;
}, 10, 3 );

/**
 * Block subscribers from accessing wp-admin.
 */
add_action( 'admin_init', function () {
	$user = wp_get_current_user();

	if (
		in_array( 'subscriber', (array) $user->roles, true )
		&& ! current_user_can( 'edit_posts' )
		&& ! wp_doing_ajax()
	) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
} );

/**
 * Redirect logged-out visitors from the standard login page to the homepage.
 * POST requests and password-reset actions remain available.
 */
add_action( 'login_init', function () {
	$action = isset( $_REQUEST['action'] )
		? sanitize_key( wp_unslash( $_REQUEST['action'] ) )
		: 'login';

	if (
		! is_user_logged_in()
		&& isset( $_SERVER['REQUEST_METHOD'] )
		&& 'GET' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
		&& 'login' === $action
	) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
} );
