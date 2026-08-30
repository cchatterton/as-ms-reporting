<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ==========================
// TYPES
// ==========================

function as_ms_allowed_types() {
    return [
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
}


// ==========================
// NAME NORMALISER (CRITICAL)
// ==========================

function as_ms_normalize_name($str) {

    $str = trim(strtolower($str));

    // remove weird encoding safely
    $normalised = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);

    if (false !== $normalised) {
        $str = $normalised;
    }

    // remove any leftover junk chars
    $str = preg_replace('/[^a-z0-9\s]/', '', $str);

    // collapse spaces
    $str = preg_replace('/\s+/', ' ', $str);

    return $str;
}


// ==========================
// PARSE CSV (TSV SAFE)
// ==========================

function as_ms_parse_csv($raw, $post_id) {

    $handle = fopen('php://temp', 'r+');
    fwrite($handle, $raw);
    rewind($handle);

    $header = [];
    $rows = [];

    // ✅ NORMALISE ROLE MAP KEYS
    $role_map_raw = json_decode(get_post_meta($post_id, 'ms_role_map_json', true), true) ?: [];
    $role_map = [];

    foreach ($role_map_raw as $k => $v) {
        $role_map[as_ms_normalize_name($k)] = $v;
    }

    while (($data = fgetcsv($handle, 0, "\t")) !== false) {

        // HEADER
        if (empty($header)) {
            $header = array_map(function($h) {
                return strtolower(trim($h));
            }, $data);
            continue;
        }

        // ✅ NEVER DROP ROWS — NORMALISE
        $diff = count($header) - count($data);

        if ($diff > 0) {
            $data = array_merge($data, array_fill(0, $diff, ''));
        } elseif ($diff < 0) {
            $data = array_slice($data, 0, count($header));
        }

        $row = array_combine($header, $data);

        // NAME (NORMALISED)
        $name = as_ms_normalize_name(
            ($row['first name'] ?? '') . ' ' . ($row['last name'] ?? '')
        );

        // TASK
        $task = trim($row['task'] ?? '');

        // DATE
        $raw_date = trim($row['date'] ?? '');
        $date = DateTime::createFromFormat('j/n/Y', $raw_date);

        if (!$date) {
            $date = DateTime::createFromFormat('d/m/Y', $raw_date);
        }

        // only skip truly broken rows
        if (!$date || !$name || !$task) {
            continue;
        }

        $rows[] = [
            'name'   => $name,
            'role'   => $role_map[$name] ?? '',
            'date'   => $date->format('Y-m-d'),
            'amount' => (float) str_replace(',', '', (string) ($row['billable amount'] ?? '0')),
            'title'  => $task,
            'type'   => ''
        ];
    }

    fclose($handle);

    return $rows;
}


// ==========================
// AI CLASSIFICATION
// ==========================

function as_ms_classify_data($rows) {

    $api_key = asms_get_openai_api_key();

    if (empty($rows) || '' === $api_key) {
        return $rows;
    }

    $allowed_types = as_ms_allowed_types();

    $payload = [];

    foreach ($rows as $r) {
        $payload[] = [
            'role' => $r['role'] ?? '',
            'task' => $r['title'] ?? ''
        ];
    }

    $res = wp_remote_post('https://api.openai.com/v1/responses', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json'
        ],
        'body' => wp_json_encode([
            'model' => defined('ASMS_OPENAI_MODEL') ? ASMS_OPENAI_MODEL : 'gpt-5.4',
            'input' => wp_json_encode([
                            
'instructions' =>
    'You are an analyst. Consider the task and the role that performed it. You role is to classify each row into EXACTLY ONE of these categories: '
    . implode(', ', $allowed_types)
    . '.

Category meanings:
- Consulting/Investigation = analysis, diagnosis, advisory work, investigation, scoping, feasibility, solution review, working out what should happen before execution.
- Break/Fix = reactive support, bugs, incidents, errors, broken functionality, failed forms/pages/integrations, CSD/SUP ticket fixes.
- Enhancements = develop something, new functionality, improvements, evolutionary development, feature changes
- Content/Config/Access = items that can be compelted with clicks not code, configuration changes, adminstration
- Outsourcing/Training = work that would not be solveable at AlphaSys, Work that we would give to an external partner, or where we simply need to train the client. 
- Maintenance/Optimisation = maintenance, upgrades, cleanup, health checks, optimisation, preventive work.
- Planning/Coordination =  planning, scheduling, status updates, coordination, project admin
- Other = last resort only when no other category reasonably fits.

Decision rules:
- Consdier the combination of task and role. 
- Other is a last resort. Avoid Other unless absolutely necessary.
- Typically only Managed Service Leads and or Directors do Planning/Coordination, occasionally TDL/FC may, but it is not normal. 
- Only TechOps/Dev & Somtimes Platform Owners do Maintenance/Optimisation.
- Platform Owner only do Break/Fix, Enhancements, Content/Config/Access, or Maintenance/Optimisation
- FC/TDL only do Consulting/Investigation, Enhancements or Content/Config/Access - consider split their time amongst these if there is no clear signals.
- Director mostly do Consulting/Investigation, but can also do Planning/Coordination.
- In salesforce and wordpress, its easy to solve issues with clicks not code, a lot of what seems Break/Fix, is likely Content/Config/Access
- Most common work in order of likleyhod is Content/Config/Access, then Consulting/Investigation, then Break/Fix

Return ONLY a JSON array in the same order like [{"type":"Break/Fix"}].', 
                
                'items' => $payload
            ])
        ]),
        'timeout' => 45
    ]);

    if (is_wp_error($res) || 200 > wp_remote_retrieve_response_code($res) || 300 <= wp_remote_retrieve_response_code($res)) {
        return $rows;
    }

    $json = json_decode(wp_remote_retrieve_body($res), true);
    $text = $json['output'][0]['content'][0]['text'] ?? '';
    $decoded = json_decode($text, true);

    if (!is_array($decoded)) {
        return $rows;
    }

    foreach ($rows as $i => $row) {

        $type = $decoded[$i]['type'] ?? 'Other';

        if (!in_array($type, $allowed_types, true)) {
            $type = 'Other';
        }

        $rows[$i]['type'] = $type;
    }

    return $rows;
}

function as_ms_summarise_notes($notes) {

    $api_key = asms_get_openai_api_key();

    if (empty($notes) || '' === $api_key) {
        return '';
    }

    $res = wp_remote_post('https://api.openai.com/v1/responses', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json'
        ],
        'body' => wp_json_encode([
            'model' => defined('ASMS_OPENAI_MODEL') ? ASMS_OPENAI_MODEL : 'gpt-5.4',
            'input' => wp_json_encode([
                'instructions' =>
                    'Create an high-level Executive Summary for the month based on these managed service timesheet notes for a monthly client report.

Output:
1. A short 1-2 sentence narrative summary.
2. a very short list of bullet points of key activity themes.
4. Do not invent outcomes, dates, status, or completion.
5. Do not include individual staff names unless essential.
6. Keep the tone calm, professional, and useful.
7. Be concise and use natural language and tone. 

Return plain text only.',
                'notes' => $notes
            ])
        ]),
        'timeout' => 45
    ]);

    if (is_wp_error($res) || 200 > wp_remote_retrieve_response_code($res) || 300 <= wp_remote_retrieve_response_code($res)) {
        return '';
    }

    $json = json_decode(wp_remote_retrieve_body($res), true);

    return trim(
        $json['output'][0]['content'][0]['text']
        ?? $json['output_text']
        ?? ''
    );
}


// ==========================
// DEDUPE
// ==========================

function as_ms_dedupe_rows($existing, $parsed) {
    return array_merge($existing, $parsed);
}
