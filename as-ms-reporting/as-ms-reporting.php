<?php
/**
 * Plugin Name: AS Managed Services Reporting
 * Description: Provides managed-services account reporting, access controls, data imports, and AI-assisted summaries.
 * Version: 1.3.9
 * Requires at least: 7.0
 * Requires PHP: 8.1
 * Update URI: https://github.com/cchatterton/as-ms-reporting
 * Author: AlphaSys
 * Author URI: https://alphasys.com.au/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: as-ms-reporting
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ASMS_VERSION', '1.3.9' );
define( 'ASMS_PLUGIN_FILE', __FILE__ );
define( 'ASMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'ASMS_OPENAI_MODEL' ) ) {
	define( 'ASMS_OPENAI_MODEL', 'gpt-5.4' );
}

$asms_function_files = array(
	'assets.php',
	'cpt-meta.php',
	'templates.php',
	'ms-data-pipeline.php',
	'shortcodes.php',
	'login-redirect.php',
	'access-control.php',
	'github-updater.php',
);

foreach ( $asms_function_files as $asms_function_file ) {
	require_once ASMS_PLUGIN_DIR . 'functions/' . $asms_function_file;
}
