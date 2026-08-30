<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display MS Accounts related to the current user.
 *
 * Usage: [ms_related_accounts]
 */
 
/**
 * Redirect logged-out visitors when [ms_require_login]
 * is present on the current page.
 */
function ms_require_login_redirect() {

    // Never redirect someone who is already logged in.
    if (is_user_logged_in()) {
        return;
    }

    if (!is_singular() || is_page('login-pin')) {
        return;
    }

    $post = get_queried_object();

    if (
        $post instanceof WP_Post
        && has_shortcode($post->post_content, 'ms_require_login')
    ) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        nocache_headers();
        wp_safe_redirect(home_url('/login-pin/'));
        exit;
    }
}

add_action('template_redirect', 'ms_require_login_redirect', 1);


/**
 * The shortcode has no visible output.
 */
function ms_require_login_shortcode() {
    return '';
}

add_shortcode('ms_require_login', 'ms_require_login_shortcode');
 
function ms_related_accounts_shortcode() {

    if (!is_user_logged_in()) {
        return '<p>Please log in to view your accounts.</p>';
    }

    $current_user_id = get_current_user_id();

    $accounts = get_posts([
        'post_type'      => 'ms_account',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'     => 'ms_related_users',
                'value'   => 'i:' . $current_user_id . ';',
                'compare' => 'LIKE',
            ],
        ],
    ]);

    if (!$accounts) {
        return '<p>No accounts found.</p>';
    }

    $output = '<ul class="ms-related-accounts">';

    foreach ($accounts as $account) {
        $output .= sprintf(
            '<li><a href="%s">%s</a></li>',
            esc_url(get_permalink($account->ID)),
            esc_html(get_the_title($account->ID))
        );
    }

    $output .= '</ul>';

    return $output;
}

add_shortcode(
    'ms_related_accounts',
    'ms_related_accounts_shortcode'
);
