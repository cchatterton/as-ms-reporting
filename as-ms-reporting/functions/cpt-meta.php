<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ==========================
// POST TYPE
// ==========================

add_action('init', function() {
    register_post_type('ms_account', [
        'label' => 'MS Accounts',
        'public' => true,
        'show_ui' => true,
        'supports' => ['title'],
    ]);
});


// ==========================
// META BOXES
// ==========================

add_action('add_meta_boxes', function() {
    
    add_meta_box('ms_csv_import', 'CSV Import (Paste)', function($p){

        $csv = get_post_meta($p->ID,'ms_csv_import',true);

        echo '<textarea class="ms-meta-field" name="ms_csv_import">'
            . esc_textarea($csv)
            . '</textarea>';

    }, 'ms_account');


    add_meta_box('ms_missing_names', 'Missing Names', function($p){

        $missing = get_post_meta($p->ID,'ms_missing_names',true);

        echo '<textarea class="ms-meta-field" readonly>'
            . esc_textarea($missing)
            . '</textarea>';

    }, 'ms_account');

    add_meta_box('ms_role_map', 'Role Map (JSON)', function($p){

        wp_nonce_field('ms_save_meta', 'ms_meta_nonce');

        echo '<textarea class="ms-meta-field" name="ms_role_map_json">'
            . esc_textarea(get_post_meta($p->ID,'ms_role_map_json',true))
            . '</textarea>';

        echo "Roles: Platform Owner, Managed Services Lead, Director, FC/TDL, TechOps/Dev or Other";

    }, 'ms_account');
    
    add_meta_box('ms_current_month_notes', 'This Month Notes', function($p){

        $notes = get_post_meta($p->ID,'ms_current_month_notes',true);
    
        echo '<textarea class="ms-meta-field" name="ms_current_month_notes">'
            . esc_textarea($notes)
            . '</textarea>';
    
    }, 'ms_account');
    
    
    add_meta_box('ms_current_month_ai_summary', 'This Month AI Summary', function($p){
    
        $summary = get_post_meta($p->ID,'ms_current_month_ai_summary',true);
    
        echo '<textarea class="ms-meta-field" name="ms_current_month_ai_summary">'
            . esc_textarea($summary)
            . '</textarea>';
    
    }, 'ms_account');


    add_meta_box('ms_report_data', 'Report Data (JSON)', function($p){

        $classification_error = get_post_meta($p->ID, 'ms_classification_error', true);

        if ($classification_error) {
            echo '<p class="notice notice-error inline"><strong>AI classification error:</strong> '
                . esc_html($classification_error)
                . ' Save the account to retry.</p>';
        }

        echo '<textarea class="ms-meta-field" name="ms_report_data_json">'
            . esc_textarea(get_post_meta($p->ID,'ms_report_data_json',true))
            . '</textarea>';

    }, 'ms_account');


add_meta_box('ms_variations', 'Variations (JSON)', function($p){
    
        $variations = get_post_meta($p->ID, 'ms_variations_json', true);
    
        if ($variations === '') {
            $variations = '[ 
      { "date": "2026-01-01", "amount": 0}, 
      { "date": "2026-02-01", "amount": 0} 
]';
        }
    
        echo '<textarea class="ms-meta-field" name="ms_variations_json">'
            . esc_textarea($variations)
            . '</textarea>';
    
    }, 'ms_account');


    add_meta_box('ms_access', 'Access (User IDs CSV)', function($p){

        echo '<input class="ms-meta-field" name="ms_access_users" value="'
            . esc_attr(get_post_meta($p->ID,'ms_access_users',true))
            . '">';

    }, 'ms_account');


    add_meta_box('ms_related_users', 'Related Users', function($p){

        $selected_users = get_post_meta($p->ID, 'ms_related_users', true);
        $selected_users = is_array($selected_users)
            ? array_values(array_unique(array_filter(array_map('absint', $selected_users))))
            : [];

        // No role filter: subscribers and users at every other level are included.
        $users = get_users([
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'fields'  => ['ID', 'display_name', 'user_email'],
        ]);

        $user_select = function($selected_user_id = 0) use ($users) {
            echo '<select class="ms-meta-field" name="ms_related_users[]">';
            echo '<option value="">Select a user</option>';

            foreach ($users as $user) {
                $label = $user->display_name;

                if ($user->user_email !== '') {
                    $label .= ' (' . $user->user_email . ')';
                }

                echo '<option value="' . esc_attr($user->ID) . '" '
                    . selected($selected_user_id, $user->ID, false) . '>'
                    . esc_html($label)
                    . '</option>';
            }

            echo '</select>';
        };

        echo '<input type="hidden" name="ms_related_users_present" value="1">';
        echo '<div class="ms-related-users-rows">';

        if (empty($selected_users)) {
            $selected_users = [0];
        }

        foreach ($selected_users as $selected_user_id) {
            echo '<div class="ms-related-user-row">';
            $user_select($selected_user_id);
            echo '<button type="button" class="button ms-remove-related-user">Remove</button>';
            echo '</div>';
        }

        echo '</div>';
        echo '<button type="button" class="button ms-add-related-user">Add user</button>';

        echo '<template class="ms-related-user-template">';
        echo '<div class="ms-related-user-row">';
        $user_select();
        echo '<button type="button" class="button ms-remove-related-user">Remove</button>';
        echo '</div>';
        echo '</template>';

        ?>
        <?php

    }, 'ms_account');


    add_meta_box('ms_finance', 'Finance', function($p){

        $plan = get_post_meta($p->ID, 'ms_monthly_plan', true);

        echo '<p>Monthly Plan ($)</p>';
        echo '<div>';
        echo '<input class="ms-meta-field" name="ms_monthly_plan" value="'.esc_attr($plan).'">';
        echo '</div>';

        // ==========================
        // NEW: MONTH OVERRIDES
        // ==========================
        echo '<hr><strong>Monthly Overrides (Column-based)</strong>';
        echo '<div>';

        for ($i = 1; $i <= 12; $i++) {
            $val = get_post_meta($p->ID, "ms_month_override_$i", true);
            echo "<p>Month $i: <input name='ms_month_override_$i' value='".esc_attr($val)."'></p>";
        }
        echo '</div>';

        // ==========================
        // NEW: CATEGORY OVERRIDE
        // ==========================
        echo '<hr><strong>Onboarding/Documentation % Override (Months 1–3)</strong>';
        echo '<div>';

        for ($i = 1; $i <= 3; $i++) {
            $val = get_post_meta($p->ID, "ms_onboarding_training_pct_$i", true);
            echo "<p>Month $i: <input name='ms_onboarding_training_pct_$i' value='".esc_attr($val)."'>%</p>";
        }
        echo '</div>';

    }, 'ms_account');


});


// ==========================
// SAVE
// ==========================

add_action('save_post', function($id){

    if (get_post_type($id) !== 'ms_account') return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ( wp_is_post_revision( $id ) || ! current_user_can( 'edit_post', $id ) ) return;

    if (!isset($_POST['ms_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ms_meta_nonce'])), 'ms_save_meta')) {
        return;
    }

    // ==========================
    // SAVE STANDARD FIELDS
    // ==========================

    $textarea_fields = [
        'ms_role_map_json',
        'ms_report_data_json',
        'ms_variations_json',
        'ms_current_month_notes',
        'ms_current_month_ai_summary',
    ];

    foreach ( $textarea_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $id, $key, sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) );
        }
    }

    if ( isset( $_POST['ms_access_users'] ) ) {
        update_post_meta( $id, 'ms_access_users', sanitize_text_field( wp_unslash( $_POST['ms_access_users'] ) ) );
    }

    if ( isset( $_POST['ms_monthly_plan'] ) ) {
        update_post_meta( $id, 'ms_monthly_plan', sanitize_text_field( wp_unslash( $_POST['ms_monthly_plan'] ) ) );
    }

    // Store related users as an array of unique, valid WordPress user IDs.
    if (isset($_POST['ms_related_users_present'])) {
        $related_users = isset($_POST['ms_related_users']) && is_array($_POST['ms_related_users'])
            ? array_map('absint', wp_unslash($_POST['ms_related_users']))
            : [];

        $related_users = array_values(array_unique(array_filter(
            $related_users,
            function($user_id) {
                return get_userdata($user_id) !== false;
            }
        )));

        if ($related_users) {
            update_post_meta($id, 'ms_related_users', $related_users);
        } else {
            delete_post_meta($id, 'ms_related_users');
        }
    }

    // ==========================
    // NEW: SAVE MONTH OVERRIDES
    // ==========================
    for ($i = 1; $i <= 12; $i++) {
        if (isset($_POST["ms_month_override_$i"])) {
            update_post_meta($id, "ms_month_override_$i", sanitize_text_field(wp_unslash($_POST["ms_month_override_$i"])));
        }
    }

    // ==========================
    // NEW: SAVE CATEGORY OVERRIDES
    // ==========================
    for ($i = 1; $i <= 3; $i++) {
        if (isset($_POST["ms_onboarding_training_pct_$i"])) {
            update_post_meta($id, "ms_onboarding_training_pct_$i", sanitize_text_field(wp_unslash($_POST["ms_onboarding_training_pct_$i"])));
        }
    }
    
    if (!empty($_POST['ms_current_month_notes'])) {

        $notes = trim(sanitize_textarea_field(wp_unslash($_POST['ms_current_month_notes'])));
    
        if ($notes !== '') {
            $summary = as_ms_summarise_notes($notes);
    
            if ($summary !== '') {
                update_post_meta($id, 'ms_current_month_ai_summary', $summary);
            }
        }
    }


    // ==========================
    // CSV IMPORT
    // ==========================

    if (!empty($_POST['ms_csv_import'])) {

        $csv = trim(sanitize_textarea_field(wp_unslash($_POST['ms_csv_import'])));

        update_post_meta($id, 'ms_csv_import', $csv);

        $parsed = as_ms_parse_csv($csv, $id);

        if (empty($parsed)) {
            update_post_meta($id, 'ms_missing_names', 'Parsing failed');
            return;
        }

        $missing = [];

foreach ($parsed as $r) {
    if (empty($r['role']) && !empty($r['name'])) {
        $missing[] = $r['name'];
    }
}

$missing = array_values(array_unique($missing));

if (!empty($missing)) {

    $missing_json = [];

    foreach ($missing as $name) {
        $missing_json[$name] = 'Other';
    }

    update_post_meta(
        $id,
        'ms_missing_names',
        wp_json_encode($missing_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    return;
}

        delete_post_meta($id, 'ms_missing_names');

        $parsed = as_ms_classify_data($parsed);

        if (is_wp_error($parsed)) {
            update_post_meta($id, 'ms_classification_error', $parsed->get_error_message());
            return;
        }

        delete_post_meta($id, 'ms_classification_error');

        $existing = json_decode(get_post_meta($id,'ms_report_data_json',true),true) ?: [];

        $merged = as_ms_dedupe_rows($existing, $parsed);

        update_post_meta($id,'ms_report_data_json', wp_json_encode($merged));

        delete_post_meta($id,'ms_csv_import');
        delete_post_meta($id,'ms_missing_names');
    }

    // Repair rows previously saved without a valid category. This runs when an
    // editor saves the account, so no AI request is made from the front end.
    $reclassified = asms_reclassify_uncategorized_report_data($id);

    if (is_wp_error($reclassified)) {
        update_post_meta($id, 'ms_classification_error', $reclassified->get_error_message());
    } else {
        delete_post_meta($id, 'ms_classification_error');
    }

});


// ==========================
// HELPERS
// ==========================

function as_ms_get_report_data($id){
    return json_decode(get_post_meta($id,'ms_report_data_json',true),true) ?: [];
}

function as_ms_get_variations($id){
    return json_decode(get_post_meta($id,'ms_variations_json',true),true) ?: [];
}

function as_ms_get_role_map($id){
    return json_decode(get_post_meta($id,'ms_role_map_json',true),true) ?: [];
}
