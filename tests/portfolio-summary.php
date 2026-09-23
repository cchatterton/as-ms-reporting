<?php

define('ABSPATH', __DIR__);

function add_action() {
}

function add_shortcode() {
}

function absint($value) {
    return abs((int) $value);
}

function wp_date($format, $timestamp = null) {
    $timestamp = null === $timestamp ? strtotime('2026-09-23 12:00:00 UTC') : $timestamp;

    return gmdate($format, $timestamp);
}

function esc_attr($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function esc_html($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sanitize_title($value) {
    return trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $value)), '-');
}

function sanitize_html_class($value) {
    return preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value);
}

require dirname(__DIR__) . '/as-ms-reporting/functions/shortcodes.php';

function asms_portfolio_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$base_data = [
    'current_tcv'       => 100,
    'actual_to_date'    => 50,
    'remaining_balance' => 50,
    'suggested_pace'    => 10,
    'months_delivered'  => 4,
    'guidance'          => 'Stay the Course',
];

$grouped_accounts = [
    '2026-08' => [
        ['data' => array_merge($base_data, ['latest_month' => '2026-08'])],
        ['data' => array_merge($base_data, ['latest_month' => '2026-08', 'months_delivered' => 6])],
    ],
    '2026-07' => [
        ['data' => array_merge($base_data, ['latest_month' => '2026-07', 'months_delivered' => 6])],
    ],
    '2026-06' => [
        ['data' => array_merge($base_data, ['latest_month' => '2026-06', 'months_delivered' => 12])],
    ],
    '2026-05' => [
        ['data' => array_merge($base_data, ['latest_month' => '2026-05', 'months_delivered' => 12])],
    ],
];

$summary = asms_get_accounts_summary($grouped_accounts);

asms_portfolio_assert(1 === $summary['months_delivered'][4], 'Agreement age should count cards by delivered month.');
asms_portfolio_assert(2 === $summary['months_delivered'][6], 'Repeated agreement ages should be aggregated.');
asms_portfolio_assert(2 === $summary['month_actuals'][-1], 'Month -1 should count August reports in September.');
asms_portfolio_assert(1 === $summary['month_actuals'][-2], 'Month -2 should count July reports in September.');
asms_portfolio_assert(1 === $summary['month_actuals'][-3], 'Month -3 should count June reports in September.');

$html = asms_render_accounts_heatmaps($summary);

asms_portfolio_assert(false !== strpos($html, '<h3>MS Agreement Age</h3>'), 'Agreement-age title should render.');
asms_portfolio_assert(false !== strpos($html, '<h3>Month Actuals</h3>'), 'Month-actuals title should render.');
asms_portfolio_assert(false !== strpos($html, '<th scope="col">Month -3</th>'), 'Month -3 column should render.');
asms_portfolio_assert(false !== strpos($html, '<th scope="col">Month -1</th>'), 'Month -1 column should render.');

echo "Portfolio summary tests passed.\n";
