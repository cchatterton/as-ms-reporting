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

    $grouped_accounts = [];

    foreach ($accounts as $account) {
        $card_data = asms_get_account_card_data($account->ID);
        $group_key = $card_data['latest_month'] ?: 'no-data';

        if (!isset($grouped_accounts[$group_key])) {
            $grouped_accounts[$group_key] = [];
        }

        $grouped_accounts[$group_key][] = [
            'account' => $account,
            'data'    => $card_data,
        ];
    }

    uksort($grouped_accounts, function($first, $second) {
        if ('no-data' === $first) {
            return 1;
        }

        if ('no-data' === $second) {
            return -1;
        }

        return strcmp($second, $first);
    });

    $output = '<div class="ms-related-accounts">';

    foreach ($grouped_accounts as $month => $group_accounts) {
        $month_label = 'no-data' === $month
            ? 'No data available'
            : 'Report Month: ' . wp_date('F Y', strtotime($month . '-01'));

        $output .= '<section class="ms-account-group">';
        $output .= '<h2 class="ms-account-group-title">' . esc_html($month_label) . '</h2>';
        $output .= '<div class="ms-account-card-grid">';

        foreach ($group_accounts as $group_account) {
            $output .= asms_render_account_card(
                $group_account['account'],
                $group_account['data']
            );
        }

        $output .= '</div></section>';
    }

    if (asms_current_user_can_view_account_totals()) {
        $summary = asms_get_accounts_summary($grouped_accounts);

        $output .= '<section class="ms-account-group ms-account-totals-group">';
        $output .= '<h2 class="ms-account-group-title">Portfolio Totals</h2>';
        $output .= '<div class="ms-account-card-grid">';
        $output .= asms_render_accounts_total_card($summary);
        $output .= '</div>';
        $output .= asms_render_accounts_heatmaps($summary);
        $output .= '</section>';
    }

    $output .= '</div>';

    return $output;
}

/**
 * Check whether the current user can see portfolio-wide account totals.
 *
 * @return bool
 */
function asms_current_user_can_view_account_totals() {
    $user = wp_get_current_user();

    if (!$user || !$user->exists()) {
        return false;
    }

    return false !== stripos((string) $user->user_login, 'alphasys.com.au')
        || false !== stripos((string) $user->user_email, 'alphasys.com.au');
}

/**
 * Aggregate all account cards into portfolio totals.
 *
 * @param array<string, array<int, array<string, mixed>>> $grouped_accounts Grouped cards.
 * @return array<string, mixed>
 */
function asms_get_accounts_summary($grouped_accounts) {
    $summary = [
        'current_tcv'       => 0,
        'actual_to_date'    => 0,
        'remaining_balance' => 0,
        'suggested_pace'    => 0,
        'months_delivered'  => array_fill(0, 13, 0),
        'guidance'          => [
            'Increase Pace'   => 0,
            'Stay the Course' => 0,
            'Decrease Pace'   => 0,
            'Closed'          => 0,
        ],
    ];

    foreach ($grouped_accounts as $group_accounts) {
        foreach ($group_accounts as $group_account) {
            $data = $group_account['data'];
            $months_delivered = min(12, max(0, absint($data['months_delivered'])));

            $summary['current_tcv'] += (float) $data['current_tcv'];
            $summary['actual_to_date'] += (float) $data['actual_to_date'];
            $summary['remaining_balance'] += (float) $data['remaining_balance'];
            $summary['suggested_pace'] += (float) $data['suggested_pace'];
            $summary['months_delivered'][$months_delivered]++;

            if (isset($summary['guidance'][$data['guidance']])) {
                $summary['guidance'][$data['guidance']]++;
            }
        }
    }

    return $summary;
}

/**
 * Render the AlphaSys-only portfolio totals card.
 *
 * @param array<string, mixed> $summary Aggregated card data.
 * @return string
 */
function asms_render_accounts_total_card($summary) {
    $rows = [
        'Total TCV'                    => asms_card_money($summary['current_tcv']),
        'Total Actual to Date'         => asms_card_money($summary['actual_to_date']),
        'Total Remaining Budget'       => asms_card_money($summary['remaining_balance']),
        'Total Suggested Monthly Pace' => asms_card_money($summary['suggested_pace']),
    ];

    $output = '<article class="ms-summary-card ms-account-card ms-account-total-card">';
    $output .= '<table class="ms-guidance-table"><tbody>';

    foreach ($rows as $label => $value) {
        $classes = [];

        if ('Total Remaining Budget' === $label && $summary['remaining_balance'] < 0) {
            $classes[] = 'ms-negative';
        }

        $class_attribute = $classes
            ? ' class="' . esc_attr(implode(' ', $classes)) . '"'
            : '';

        $output .= '<tr><td>' . esc_html($label) . '</td><td' . $class_attribute . '>'
            . esc_html($value) . '</td></tr>';
    }

    $output .= '</tbody></table></article>';

    return $output;
}

/**
 * Render the AlphaSys-only delivery and guidance heatmap tables.
 *
 * @param array<string, mixed> $summary Aggregated card data.
 * @return string
 */
function asms_render_accounts_heatmaps($summary) {
    $month_counts = [];

    for ($month = 1; $month <= 12; $month++) {
        $month_counts['Month ' . $month] = $summary['months_delivered'][$month] ?? 0;
    }

    $guidance_counts = [
        'Increase Pace'   => $summary['guidance']['Increase Pace'] ?? 0,
        'Stay the Course' => $summary['guidance']['Stay the Course'] ?? 0,
        'Decrease Pace'   => $summary['guidance']['Decrease Pace'] ?? 0,
    ];

    $output = '<div class="ms-portfolio-heatmaps">';
    $output .= asms_render_accounts_heatmap_table(
        'Customers by Months Delivered',
        $month_counts
    );
    $output .= asms_render_accounts_heatmap_table(
        'Customers by Pace Guidance',
        $guidance_counts
    );
    $output .= '</div>';

    return $output;
}

/**
 * Render a single-row customer-count heatmap table.
 *
 * @param string             $title  Table title.
 * @param array<string, int> $counts Labelled customer counts.
 * @return string
 */
function asms_render_accounts_heatmap_table($title, $counts) {
    $maximum = $counts ? max($counts) : 0;
    $output = '<section class="ms-portfolio-heatmap">';
    $output .= '<h3>' . esc_html($title) . '</h3>';
    $output .= '<table><thead><tr>';

    foreach ($counts as $label => $count) {
        $output .= '<th scope="col">' . esc_html($label) . '</th>';
    }

    $output .= '</tr></thead><tbody><tr>';

    foreach ($counts as $count) {
        $alpha = $maximum > 0 ? (float) $count / $maximum : 0;
        $background = 'rgba(0, 128, 0, ' . number_format($alpha, 3, '.', '') . ')';

        $output .= '<td class="ms-heat-cell" style="background-color: '
            . esc_attr($background) . '">' . esc_html($count) . '</td>';
    }

    $output .= '</tr></tbody></table></section>';

    return $output;
}

/**
 * Calculate the pace data shown on an account card.
 *
 * @param int $account_id Account post ID.
 * @return array<string, mixed>
 */
function asms_get_account_card_data($account_id) {
    $data = as_ms_get_report_data($account_id);
    $plan = (float) get_post_meta($account_id, 'ms_monthly_plan', true);
    $vars = json_decode(get_post_meta($account_id, 'ms_variations_json', true), true);
    $vars = is_array($vars) ? $vars : [];

    $dated_rows = array_filter($data, function($row) {
        return !empty($row['date']) && false !== strtotime($row['date']);
    });

    $timestamps = array_map(function($row) {
        return strtotime($row['date']);
    }, $dated_rows);

    $latest_timestamp = $timestamps ? max($timestamps) : null;
    $latest_month = $latest_timestamp ? wp_date('Y-m', $latest_timestamp) : '';

    if ($timestamps) {
        $start_timestamp = min($timestamps);
        $start = new DateTime(wp_date('Y-m-01', $start_timestamp));
    } else {
        $start = new DateTime('first day of this month');
    }

    $months = [];

    for ($index = 1; $index <= 12; $index++) {
        $month_key = $start->format('Y-m');
        $months[$month_key] = [
            'actual'    => 0,
            'variation' => 0,
            'override'  => get_post_meta($account_id, 'ms_month_override_' . $index, true),
        ];
        $start->modify('+1 month');
    }

    foreach ($dated_rows as $row) {
        $month_key = wp_date('Y-m', strtotime($row['date']));

        if (isset($months[$month_key])) {
            $months[$month_key]['actual'] += (float) ($row['amount'] ?? 0);
        }
    }

    foreach ($vars as $variation) {
        if (empty($variation['date']) || false === strtotime($variation['date'])) {
            continue;
        }

        $month_key = wp_date('Y-m', strtotime($variation['date']));

        if (isset($months[$month_key])) {
            $months[$month_key]['variation'] += (float) ($variation['amount'] ?? 0);
        }
    }

    $actual_to_date = 0;
    $months_delivered = 0;
    $total_variations = 0;

    foreach ($months as $month) {
        $actual = '' !== $month['override'] && null !== $month['override']
            ? (float) str_replace(',', '', (string) $month['override'])
            : $month['actual'];

        if ($actual > 0) {
            $actual_to_date += $actual;
            $months_delivered++;
        }

        $total_variations += $month['variation'];
    }

    $current_tcv = ($plan * 12) + $total_variations;
    $remaining_balance = $current_tcv - $actual_to_date;
    $months_remaining = max(12 - $months_delivered, 0);
    $suggested_pace = $months_remaining ? $remaining_balance / $months_remaining : 0;
    $current_rolling_average = $months_delivered ? $actual_to_date / $months_delivered : 0;
    $guidance = 'Stay the Course';

    if ($remaining_balance < 0) {
        $guidance = 'Decrease Pace';
    } elseif ($months_remaining <= 0) {
        $guidance = 'Closed';
    } elseif ($suggested_pace <= 0) {
        $guidance = 'Decrease Pace';
    } elseif ($current_rolling_average > ($suggested_pace * 1.1)) {
        $guidance = 'Decrease Pace';
    } elseif ($current_rolling_average < ($suggested_pace * 0.9)) {
        $guidance = 'Increase Pace';
    }

    return [
        'latest_month'           => $latest_month,
        'current_tcv'            => $current_tcv,
        'actual_to_date'         => $actual_to_date,
        'remaining_balance'      => $remaining_balance,
        'months_delivered'       => $months_delivered,
        'suggested_pace'         => $suggested_pace,
        'current_rolling_average' => $current_rolling_average,
        'guidance'               => $guidance,
        'related_users'          => asms_get_external_related_users($account_id),
    ];
}

/**
 * Return related users whose email address is not an AlphaSys address.
 *
 * @param int $account_id Account post ID.
 * @return WP_User[]
 */
function asms_get_external_related_users($account_id) {
    $user_ids = get_post_meta($account_id, 'ms_related_users', true);

    if (!is_array($user_ids)) {
        return [];
    }

    $users = [];

    foreach (array_unique(array_map('absint', $user_ids)) as $user_id) {
        $user = get_userdata($user_id);

        if (!$user || false !== stripos($user->user_email, 'alphasys.com.au')) {
            continue;
        }

        $users[] = $user;
    }

    usort($users, function($first, $second) {
        return strcasecmp($first->display_name, $second->display_name);
    });

    return $users;
}

/**
 * Format a monetary value for the account card.
 *
 * @param float $value Monetary value.
 * @return string
 */
function asms_card_money($value) {
    return '$' . number_format((float) $value, 0);
}

/**
 * Render a single account pace card.
 *
 * @param WP_Post              $account Account post.
 * @param array<string, mixed> $data    Calculated card data.
 * @return string
 */
function asms_render_account_card($account, $data) {
    $rows = [
        'Current TCV'             => asms_card_money($data['current_tcv']),
        'Actual to Date'          => asms_card_money($data['actual_to_date']),
        'Remaining Balance'       => asms_card_money($data['remaining_balance']),
        'Months Delivered'        => absint($data['months_delivered']) . ' of 12',
        'Suggested Monthly Pace'  => asms_card_money($data['suggested_pace']),
        'Current Rolling Average' => asms_card_money($data['current_rolling_average']),
        'Guidance'                => $data['guidance'],
    ];

    $output = '<article class="ms-summary-card ms-account-card">';
    $output .= sprintf(
        '<h2><a href="%s">%s</a></h2>',
        esc_url(get_permalink($account->ID)),
        esc_html(get_the_title($account->ID))
    );
    $output .= '<table class="ms-guidance-table"><tbody>';

    foreach ($rows as $label => $value) {
        $classes = [];

        if ('Remaining Balance' === $label && $data['remaining_balance'] < 0) {
            $classes[] = 'ms-negative';
        }

        if ('Guidance' === $label) {
            $classes[] = 'ms-guidance-result ' . $value;
        }

        $class_attribute = $classes
            ? ' class="' . esc_attr(implode(' ', $classes)) . '"'
            : '';

        $output .= '<tr><td>' . esc_html($label) . '</td><td' . $class_attribute . '>'
            . esc_html($value) . '</td></tr>';
    }

    $output .= '</tbody></table>';
    $output .= '<div class="ms-account-users">';

    if ($data['related_users']) {
        $output .= '<ul>';

        foreach ($data['related_users'] as $user) {
            $label = $user->display_name;

            // if ($user->user_email) {
            //     $label .= ' (' . $user->user_email . ')';
            // }

            $output .= '<li>' . esc_html($label) . '</li>';
        }

        $output .= '</ul>';
    } else {
        $output .= '<p class="ms-empty">No external related users.</p>';
    }

    $output .= '</div></article>';

    return $output;
}

add_shortcode(
    'ms_related_accounts',
    'ms_related_accounts_shortcode'
);
