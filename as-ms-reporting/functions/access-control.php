<?php
/**
 * Front-end account access controls.
 *
 * @package ASMSReporting
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restrict managed-services account pages to related users and administrators.
 */
function asms_protect_account_single_page() {
	if ( ! is_singular( 'ms_account' ) || current_user_can( 'edit_posts' ) ) {
		return;
	}

	$post_id         = get_queried_object_id();
	$current_user_id = get_current_user_id();
	$related_users   = get_post_meta( $post_id, 'ms_related_users', true );
	$related_users   = is_array( $related_users ) ? array_map( 'absint', $related_users ) : array();

	if ( ! $current_user_id || ! in_array( $current_user_id, $related_users, true ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}

add_action( 'template_redirect', 'asms_protect_account_single_page' );
