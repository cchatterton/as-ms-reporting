<?php
/**
 * Runtime configuration helpers.
 *
 * @package ASMSReporting
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the fallback OpenAI API key supplied through wp-config.php or the environment.
 *
 * WordPress 7.0 and later use the WordPress AI Client and configured connector
 * before this fallback is considered.
 *
 * @return string
 */
function asms_get_openai_api_key() {
	if ( defined( 'ASMS_OPENAI_API_KEY' ) ) {
		return trim( (string) ASMS_OPENAI_API_KEY );
	}

	$environment_key = getenv( 'ASMS_OPENAI_API_KEY' );

	return is_string( $environment_key ) ? trim( $environment_key ) : '';
}
