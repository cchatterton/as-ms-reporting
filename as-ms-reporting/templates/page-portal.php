<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login/'));
    exit;
}

get_header();

$user_id = get_current_user_id();

$q = new WP_Query([
    'post_type' => 'ms_account',
    'posts_per_page' => -1
]);

echo '<h1>My Accounts</h1>';

while ($q->have_posts()) {
    $q->the_post();

    $ids = get_post_meta(get_the_ID(), 'ms_access_users', true);
    $ids = array_map('absint', explode(',', $ids));

    if (in_array($user_id, $ids, true)) {

        echo '<p><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></p>';
    }
}

wp_reset_postdata();

get_footer();
