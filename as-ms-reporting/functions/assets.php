<?php
/**
 * Front-end and admin asset registration.
 *
 * @package ASMSReporting
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue the lightweight report stylesheet on the front end.
 *
 * The account-list shortcode can be rendered by page builders, widgets, or
 * templates, where its shortcode is not present in the queried post content.
 * Loading the stylesheet globally ensures those cards are always styled.
 */
function asms_enqueue_report_assets() {
	$is_account_page = is_singular( 'ms_account' );

	wp_enqueue_style(
		'asms-report',
		ASMS_PLUGIN_URL . 'styles/as-ms-reporting.css',
		array(),
		ASMS_VERSION
	);

	if ( ! $is_account_page ) {
		return;
	}

	wp_enqueue_script(
		'asms-chart',
		ASMS_PLUGIN_URL . 'vendor/chart.js/chart.umd.min.js',
		array(),
		'4.5.1',
		true
	);

	wp_enqueue_script(
		'asms-report',
		ASMS_PLUGIN_URL . 'scripts/as-ms-reporting.js',
		array( 'asms-chart' ),
		ASMS_VERSION,
		true
	);
}

add_action( 'wp_enqueue_scripts', 'asms_enqueue_report_assets' );

/**
 * Enqueue account-editor assets only on the managed-services post type.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function asms_enqueue_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'ms_account' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_style(
		'asms-admin',
		ASMS_PLUGIN_URL . 'styles/as-ms-reporting-admin.css',
		array(),
		ASMS_VERSION
	);

	wp_enqueue_script(
		'asms-admin',
		ASMS_PLUGIN_URL . 'scripts/as-ms-reporting-admin.js',
		array(),
		ASMS_VERSION,
		true
	);
}

add_action( 'admin_enqueue_scripts', 'asms_enqueue_admin_assets' );
