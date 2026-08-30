<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter('template_include', function($template) {

    if (is_singular('ms_account')) {
        return plugin_dir_path(__DIR__) . 'templates/single-ms_account.php';
    }

    if (is_page('portal')) {
        return plugin_dir_path(__DIR__) . 'templates/page-portal.php';
    }

    return $template;

});
