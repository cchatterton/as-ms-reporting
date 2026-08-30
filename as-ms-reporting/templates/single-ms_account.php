<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login/'));
    exit;
}

$user = wp_get_current_user();

/**
 * Show "Add Data" button if NOT subscriber
 */
$roles = (array) $user->roles;
$can_add_data = !in_array('subscriber', $roles);

get_header();

$id   = get_the_ID();
$data = as_ms_get_report_data($id);
$vars = json_decode(get_post_meta($id, 'ms_variations_json', true), true) ?: [];

$plan = (float) get_post_meta($id, 'ms_monthly_plan', true);

$user = wp_get_current_user();
$is_user = in_array('user', $user->roles);

// ==========================
// LOAD OVERRIDES
// ==========================

$month_overrides = [];
$onboarding_overrides = [];

for ($i = 1; $i <= 12; $i++) {
    $month_overrides[$i] = get_post_meta($id, "ms_month_override_$i", true);
}

for ($i = 1; $i <= 3; $i++) {
    $onboarding_overrides[$i] = get_post_meta($id, "ms_onboarding_training_pct_$i", true);
}


// ==========================
// BUILD 12 MONTH WINDOW (FROM DATA)
// ==========================

$months = [];
$labels = [];

// get all dates from report data
$all_dates = array_column($data, 'date');

// fallback if no data
if (empty($all_dates)) {
    $start = new DateTime('first day of this month');
} else {
    sort($all_dates);
    $start = new DateTime(date('Y-m-01', strtotime($all_dates[0])));
}

// build 12 sequential months from oldest data
for ($i = 0; $i < 12; $i++) {

    $key   = $start->format('Y-m');
    $label = $start->format('M-y');

    $labels[$key] = $label;

    $months[$key] = [
        'actual' => 0,
        'hours'  => 0,
        'role'   => [],
        'type'   => [],
        'topup'  => 0
    ];

    $start->modify('+1 month');
}


// ==========================
// APPLY DATA
// ==========================

foreach ($data as $r) {

    $m = date('Y-m', strtotime($r['date']));
    if (!isset($months[$m])) continue;

    $amount = (float) $r['amount'];

    $months[$m]['actual'] += $amount;

    $months[$m]['hours'] += $amount; // reuse field for totals

    $months[$m]['role'][$r['role']] =
        ($months[$m]['role'][$r['role']] ?? 0) + $amount;

    $months[$m]['type'][$r['type']] =
        ($months[$m]['type'][$r['type']] ?? 0) + $amount;
}


// ==========================
// APPLY VARIATIONS
// ==========================

foreach ($vars as $v) {

    $m = date('Y-m', strtotime($v['date']));
    if (!isset($months[$m])) continue;

    $amount = (float) $v['amount'];

    $months[$m]['topup'] += $amount;
}


// ==========================
// HELPERS
// ==========================

function ms_money($value) {
    if (!$value) return '';
    return number_format($value, 0);
}

function ms_pct($value) {
    return round($value) . '%';
}

function ms_table_header($labels) {
    echo '<tr><th class="label-col"></th>';
    foreach ($labels as $l) {
        echo '<th>' . esc_html($l) . '</th>';
    }
    echo '</tr>';
}

function ms_red_alpha($value, $plan) {
    if ($value >= 0 || !$plan) return 0;
    return min(abs($value) / $plan, 1);
}

function ms_has_actual($actual) {
    return (float) $actual > 0;
}

function ms_adjusted_plan($plan, $total_variations, $actual_to_date, $months_remaining) {

    if ($months_remaining <= 0) return 0;

    $tcv = $plan * 12;
    $remaining = $tcv + $total_variations - $actual_to_date;

    return $remaining / $months_remaining;
}

function ms_rolling_heat($value, $plan) {

    if (!$plan || !$value) {
        return '';
    }

    $ratio = abs($value - $plan) / $plan;
    $alpha = min($ratio, 1);

    if ($value <= $plan) {
        return 'rgba(0,128,0,' . $alpha . ')';
    }

    return 'rgba(255,0,0,' . $alpha . ')';
}

function ms_has_override($value) {
    return $value !== '' && $value !== null;
}

function ms_parse_number($value) {
    return (float) str_replace(',', '', (string) $value);
}

function ms_actual_with_override($raw_actual, $override) {
    return ms_has_override($override)
        ? ms_parse_number($override)
        : (float) $raw_actual;
}


// ==========================
// FINAL ACTUALS WITH OVERRIDES
// ==========================

$final_actuals = [];

$i = 1;
foreach ($months as $m => $d) {

    $final_actuals[$m] = ms_actual_with_override(
        $d['actual'],
        $month_overrides[$i] ?? ''
    );

    $i++;
}

?>




<div class="ms-report">

<h1><?php the_title(); ?></h1>

<?php

$current_notes = get_post_meta($id, 'ms_current_month_notes', true);
$current_summary = get_post_meta($id, 'ms_current_month_ai_summary', true);

$original_tcv = $plan * 12;

$total_variations = 0;
foreach ($months as $m => $d) {
    $total_variations += $d['topup'];
}

$current_tcv = $original_tcv + $total_variations;

$actual_to_date = 0;
$months_delivered = 0;

foreach ($months as $m => $d) {

    $actual = $final_actuals[$m] ?? $d['actual'];

    if ($actual > 0) {
        $actual_to_date += $actual;
        $months_delivered++;
    }
}

$remaining_balance = $current_tcv - $actual_to_date;
$months_remaining = max(12 - $months_delivered, 0);

$suggested_pace = $months_remaining
    ? $remaining_balance / $months_remaining
    : 0;

$current_rolling_average = $months_delivered
    ? $actual_to_date / $months_delivered
    : 0;

$guidance = 'Stay the Course';

if ($remaining_balance < 0) {

    // Already over TCV
    $guidance = 'Decrease Pace';

} elseif ($months_remaining <= 0) {

    // No future recovery period
    $guidance = 'Closed';

} elseif ($suggested_pace <= 0) {

    // No remaining spend needed
    $guidance = 'Decrease Pace';

} elseif ($current_rolling_average > ($suggested_pace * 1.1)) {

    // Current run-rate is materially above required pace
    $guidance = 'Decrease Pace';

} elseif ($current_rolling_average < ($suggested_pace * 0.9)) {

    // Current run-rate is materially below required pace
    $guidance = 'Increase Pace';

}

?>

<div class="ms-summary-grid">

    <div class="">
        <h2>AI Month Summary (From Notes)</h2>

        <?php if ($current_summary): ?>

            <?php echo wpautop(esc_html($current_summary)); ?>

        <?php elseif ($current_notes): ?>

            <?php echo wpautop(esc_html($current_notes)); ?>

        <?php else: ?>

            <p class="ms-empty">No current month summary yet.</p>

        <?php endif; ?>
    </div>

    <div class="ms-summary-card">
        <h2>Pace Guidance</h2>

        <table class="ms-guidance-table">
            <tr>
                <td>Current TCV</td>
                <td>$<?php echo ms_money($current_tcv); ?></td>
            </tr>
            <tr>
                <td>Actual to Date</td>
                <td>$<?php echo ms_money($actual_to_date); ?></td>
            </tr>
            <tr>
                <td>Remaining Balance</td>
                <td class="<?php echo $remaining_balance < 0 ? 'ms-negative' : ''; ?>">
                    $<?php echo ms_money($remaining_balance); ?>
                </td>
            </tr>
            <tr>
                <td>Months Delivered</td>
                <td><?php echo esc_html($months_delivered); ?> of 12</td>
            </tr>
            <tr>
                <td>Suggested Monthly Pace</td>
                <td>$<?php echo ms_money($suggested_pace); ?></td>
            </tr>
            <tr>
                <td>Current Rolling Average</td>
                <td>$<?php echo ms_money($current_rolling_average); ?></td>
            </tr>
            <tr>
                <td>Guidance</td>
                <td class="ms-guidance-result"><?php echo esc_html($guidance); ?></td>
            </tr>
        </table>
    </div>

</div>


<?php if ($is_user): ?>
    <p>
        <a class="ms-add-data" href="<?php echo esc_url(add_query_arg('account_id', absint($id), home_url('/upload/'))); ?>">
            Add Data
        </a>
    </p>
<?php endif; ?>


<!-- ========================== -->
<!-- PLAN VS ACTUAL -->
<!-- ========================== -->

<h2 class="ms-section-title">Plan vs Actual</h2>

<table class="ms-table">
<?php ms_table_header($labels); ?>

<tr>
    <td class="label-col">Original Month Plan</td>
    <?php foreach ($months as $m => $d): ?>
        <td class="dollar"><?php echo ms_money($plan); ?></td>
    <?php endforeach; ?>
</tr>

<tr>
    <td class="label-col">Month Actual</td>
    <?php foreach ($months as $m => $d): 
    
        $actual = $final_actuals[$m] ?? $d['actual'];

        if (!ms_has_actual($actual)) {
            echo '<td></td>';
            continue;
        }
    
    ?>
        <td class="dollar"><?php echo ms_money($final_actuals[$m]); ?></td>
    <?php endforeach; ?>
</tr>

<tr>
    <td class="label-col">Month Difference</td>
    <?php foreach ($months as $m => $d):
        
        $actual = $final_actuals[$m] ?? $d['actual'];

        if (!ms_has_actual($actual)) {
            echo '<td></td>';
            continue;
        }

        $actual = $final_actuals[$m];
        $diff = $plan - $actual;

    ?>
        <td class="dollar <?php echo $diff < 0 ? 'ms-negative' : ''; ?>">
            <?php echo ms_money($diff); ?>
        </td>
    <?php endforeach; ?>
</tr>

<tr>
    <td class="label-col">Topups (Variations)</td>
    <?php foreach ($months as $m => $d): 
    
        $actual = $final_actuals[$m] ?? $d['actual'];

        if (!ms_has_actual($actual)) {
            echo '<td></td>';
            continue;
        }
    
    ?>
        <td class="dollar"><?php echo ms_money($d['topup']); ?></td>
    <?php endforeach; ?>
</tr>

<tr>
    <td class="label-col">Month Balance</td>
    <?php foreach ($months as $m => $d):
        
        $actual = $final_actuals[$m] ?? $d['actual'];

        if (!ms_has_actual($actual)) {
            echo '<td></td>';
            continue;
        }

        $actual = $final_actuals[$m];
        $balance = ($plan - $actual) + $d['topup'];
        

    ?>
        <td class="dollar <?php echo $balance < 0 ? 'ms-negative' : ''; ?>">
            <?php echo ms_money($balance); ?>
        </td>
    <?php endforeach; ?>
</tr>

<tr>
    <td class="label-col">Rolling Average</td>
    <?php

    $rolling_actual = 0;
    $rolling_topups = 0;
    $rolling_count  = 0;

    foreach ($months as $m => $d):
        
        $actual = $final_actuals[$m] ?? $d['actual'];

        if (!ms_has_actual($actual)) {
            echo '<td></td>';
            continue;
        }

        $actual = $final_actuals[$m];

        if ($actual > 0) {
            $rolling_actual += $actual;
            $rolling_topups += $d['topup'];
            $rolling_count++;
        }

        $avg = $rolling_count
            ? ($rolling_actual - $rolling_topups) / $rolling_count
            : 0;

    ?>
        <td class="dollar" data-asms-background="<?php echo esc_attr(ms_rolling_heat($avg, $plan)); ?>">
            <?php echo ms_money($avg); ?>
        </td>
    <?php endforeach; ?>
</tr>

<tr>
    <td class="label-col dollar-label">Adjusted Month Plan</td>
    <?php

    $total_variations = 0;
    foreach ($months as $m => $d) {
        $total_variations += $d['topup'];
    }

    $actual_to_date = 0;
    $month_index = 1;

    foreach ($months as $m => $d):
        
        $actual = $final_actuals[$m] ?? $d['actual'];

        if (!ms_has_actual($actual)) {
            echo '<td></td>';
            continue;
        }

        $months_remaining = 13 - $month_index;

        $adjusted_plan = ms_adjusted_plan(
            $plan,
            $total_variations,
            $actual_to_date,
            $months_remaining
        );

        $actual_to_date += $final_actuals[$m] ?? $d['actual'];

    ?>
        <td class="dollar"><?php echo ms_money($adjusted_plan); ?></td>
    <?php
        $month_index++;
    endforeach;
    ?>
</tr>

<tr>
    <td class="label-col">% to Adjusted Plan</td>
    <?php foreach ($months as $m => $d):
        
        $actual = $final_actuals[$m] ?? $d['actual'];

        if (!ms_has_actual($actual)) {
            echo '<td></td>';
            continue;
        }

        $actual = $final_actuals[$m];
        $pct = $plan ? ($actual / $plan) * 100 : 0;

    ?>
        <td><?php echo ms_pct($pct); ?></td>
    <?php endforeach; ?>
</tr>

</table>


<!-- ========================== -->
<!-- CHART -->
<!-- ========================== -->

<!-- ========================== -->
<!-- CHART (ALIGNED + CORRECT) -->
<!-- ========================== -->

<div class="ms-chart-wrap">
    <canvas id="msChart"></canvas>
</div>

<?php

$chart_labels = array_values($labels);

$chart_base = [];
$chart_over = [];

foreach ($months as $m => $d) {

    $actual = (float) $final_actuals[$m];

    $base = min($actual, $plan);      // grey portion
    $over = max($actual - $plan, 0);  // red portion

    $chart_base[] = $base;
    $chart_over[] = $over;
}

wp_localize_script(
    'asms-report',
    'ASMSReportData',
    [
        'labels' => $chart_labels,
        'base'   => $chart_base,
        'over'   => $chart_over,
    ]
);

?>



<!-- ========================== -->
<!-- ROLE -->
<!-- ========================== -->

<h2 class="ms-section-title">Service Breakdown — By Role</h2>

<?php

$role_order = [
    'Director',
    'Managed Services Lead',
    'FC/TDL',
    'Platform Owner',
    'TechOps/Dev',
    'Other'
];

$all_roles = [];

foreach ($role_order as $role) {
    $all_roles[$role] = true;
}

?>

<table class="ms-table">
<?php ms_table_header($labels); ?>

<?php foreach ($all_roles as $role => $_): ?>

<tr>
    <td class="label-col"><?php echo esc_html($role); ?></td>

    <?php foreach ($months as $m => $d):

        $total = $d['hours'] ?: 0;
        $val   = $d['role'][$role] ?? 0;
        $pct   = $total ? ($val / $total) : 0;

    ?>

        <td
            class="ms-heat-cell"
            data-asms-heat-alpha="<?php echo esc_attr($pct); ?>"
        >
            <?php echo ms_pct($pct * 100); ?>
        </td>

    <?php endforeach; ?>

</tr>

<?php endforeach; ?>

</table>


<!-- ========================== -->
<!-- CATEGORY -->
<!-- ========================== -->

<h2 class="ms-section-title">Service Breakdown — By Category</h2>

<?php

$category_order = [
    'Onboarding/Documentation',
    'Consulting/Investigation',
    'Break/Fix',
    'Enhancements',
    'Content/Config/Access',
    'Outsourcing/Training',
    'Maintenance/Optimisation',
    'Planning/Coordination',
    'Other'
];

$all_types = [];

foreach ($category_order as $type) {
    $all_types[$type] = true;
}

?>

<table class="ms-table">
<?php ms_table_header($labels); ?>

<?php foreach ($all_types as $type => $_): ?>

<tr>
    <td class="label-col"><?php echo esc_html($type); ?></td>

    <?php
    $month_index = 1;

    foreach ($months as $m => $d):

        $total = $d['hours'] ?: 0;
        $val   = $d['type'][$type] ?? 0;
        $pct   = $total ? ($val / $total) : 0;

        // ==========================
        // ONBOARDING OVERRIDE
        // Months 1-3 only.
        // Onboarding gets the fixed value.
        // All other categories share the remaining percentage proportionally.
        // ==========================

        if ($month_index <= 3 && ms_has_override($onboarding_overrides[$month_index] ?? '')) {

            $override_pct = ms_parse_number($onboarding_overrides[$month_index]) / 100;
            $override_pct = max(0, min(1, $override_pct));

            $onboarding_val = $d['type']['Onboarding/Documentation'] ?? 0;
            $remaining_total = $total - $onboarding_val;

            if ($type === 'Onboarding/Documentation') {

                $pct = $override_pct;

            } else {

                $remaining_pct = 1 - $override_pct;

                $pct = ($remaining_total > 0)
                    ? (($val / $remaining_total) * $remaining_pct)
                    : 0;

            }

        }

    ?>

        <td
            class="ms-heat-cell"
            data-asms-heat-alpha="<?php echo esc_attr($pct); ?>"
        >
            <?php echo ms_pct($pct * 100); ?>
        </td>

    <?php
        $month_index++;
    endforeach;
    ?>

</tr>

<?php endforeach; ?>

</table>

</div>

<?php get_footer(); ?>
