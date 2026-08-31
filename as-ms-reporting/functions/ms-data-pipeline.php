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
// AI GENERATION
// ==========================

/**
 * Generate text through the WordPress 7 AI Client, with direct OpenAI fallback.
 *
 * @param string     $input        User input.
 * @param string     $instructions System instructions.
 * @param array|null $schema       Optional JSON response schema.
 * @return string|WP_Error
 */
function asms_generate_ai_text($input, $instructions, $schema = null) {
    $wordpress_ai_error = null;

    if (function_exists('wp_ai_client_prompt')) {
        try {
            $builder = wp_ai_client_prompt($input)
                ->using_system_instruction($instructions);

            if (defined('ASMS_OPENAI_MODEL') && ASMS_OPENAI_MODEL) {
                $builder = $builder->using_model_preference(ASMS_OPENAI_MODEL);
            }

            if (is_array($schema)) {
                $builder = $builder->as_json_response($schema);
            }

            $result = $builder->generate_text();

            if (!is_wp_error($result)) {
                return trim((string) $result);
            }

            $wordpress_ai_error = $result;
        } catch (Throwable $error) {
            $wordpress_ai_error = new WP_Error(
                'asms_wordpress_ai_client_error',
                $error->getMessage()
            );
        }
    }

    $api_key = asms_get_openai_api_key();

    if ('' === $api_key) {
        if (is_wp_error($wordpress_ai_error)) {
            return $wordpress_ai_error;
        }

        return new WP_Error(
            'asms_ai_unavailable',
            'No configured WordPress AI connector or ASMS_OPENAI_API_KEY was available.'
        );
    }

    $request_body = [
        'model'        => defined('ASMS_OPENAI_MODEL') ? ASMS_OPENAI_MODEL : 'gpt-5.4',
        'instructions' => $instructions,
        'input'        => $input,
        'store'        => false,
    ];

    if (is_array($schema)) {
        $request_body['text'] = [
            'format' => [
                'type'   => 'json_schema',
                'name'   => 'asms_response',
                'strict' => true,
                'schema' => $schema,
            ],
        ];
    }

    $response = wp_remote_post('https://api.openai.com/v1/responses', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body'    => wp_json_encode($request_body),
        'timeout' => 45,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if (200 > $response_code || 300 <= $response_code) {
        $error_response = json_decode($response_body, true);
        $error_message = $error_response['error']['message'] ?? 'The AI request failed.';

        return new WP_Error('asms_ai_api_error', sanitize_text_field($error_message));
    }

    $json = json_decode($response_body, true);
    $text = is_array($json) ? (string) ($json['output_text'] ?? '') : '';

    if ('' === $text && !empty($json['output']) && is_array($json['output'])) {
        foreach ($json['output'] as $output_item) {
            foreach (($output_item['content'] ?? []) as $content_item) {
                if ('output_text' === ($content_item['type'] ?? '') && isset($content_item['text'])) {
                    $text .= (string) $content_item['text'];
                }
            }
        }
    }

    if ('' === trim($text)) {
        return new WP_Error('asms_ai_empty_response', 'The AI provider returned an empty response.');
    }

    return trim($text);
}


// ==========================
// AI CLASSIFICATION
// ==========================

function as_ms_classify_data($rows) {

    if (empty($rows)) {
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

    $instructions =
        'You are an analyst. Consider the task and the role that performed it. Classify each item into exactly one allowed category. '
        . 'Return one classification for every input item, in the same order.\n\n'
        . 'Category meanings:\n'
        . '- Onboarding/Documentation = onboarding and documentation work.\n'
        . '- Consulting/Investigation = analysis, diagnosis, advisory work, investigation, scoping, feasibility, solution review, or working out what should happen before execution.\n'
        . '- Break/Fix = reactive support, bugs, incidents, errors, broken functionality, failed forms/pages/integrations, or ticket fixes.\n'
        . '- Enhancements = development, new functionality, improvements, evolutionary development, or feature changes.\n'
        . '- Content/Config/Access = work completed with clicks rather than code, including configuration, content, access, and administration.\n'
        . '- Outsourcing/Training = work requiring an external partner or client training.\n'
        . '- Maintenance/Optimisation = maintenance, upgrades, cleanup, health checks, optimisation, or preventive work.\n'
        . '- Planning/Coordination = planning, scheduling, status updates, coordination, or project administration.\n'
        . '- Other = last resort only when no other category reasonably fits.\n\n'
        . 'Decision rules:\n'
        . '- Other is a last resort.\n'
        . '- Managed Services Leads and Directors commonly perform Planning/Coordination; FC/TDL may do so occasionally.\n'
        . '- TechOps/Dev and sometimes Platform Owners perform Maintenance/Optimisation.\n'
        . '- Platform Owners generally perform Break/Fix, Enhancements, Content/Config/Access, or Maintenance/Optimisation.\n'
        . '- FC/TDL generally perform Consulting/Investigation, Enhancements, or Content/Config/Access.\n'
        . '- Directors mostly perform Consulting/Investigation but may also perform Planning/Coordination.\n'
        . '- In Salesforce and WordPress, issues solved with clicks rather than code are usually Content/Config/Access.\n'
        . '- The most common categories are Content/Config/Access, Consulting/Investigation, then Break/Fix.';

    $schema = [
        'type'                 => 'object',
        'properties'           => [
            'classifications' => [
                'type'  => 'array',
                'items' => [
                    'type'                 => 'object',
                    'properties'           => [
                        'type' => [
                            'type' => 'string',
                            'enum' => $allowed_types,
                        ],
                    ],
                    'required'             => ['type'],
                    'additionalProperties' => false,
                ],
            ],
        ],
        'required'             => ['classifications'],
        'additionalProperties' => false,
    ];

    $text = asms_generate_ai_text(
        wp_json_encode(['items' => $payload]),
        $instructions,
        $schema
    );

    if (is_wp_error($text)) {
        return $text;
    }

    $decoded = json_decode($text, true);
    $classifications = is_array($decoded) ? ($decoded['classifications'] ?? null) : null;

    if (!is_array($classifications) || count($classifications) !== count($rows)) {
        return new WP_Error(
            'asms_classification_invalid_response',
            'AI classification returned an invalid or incomplete response.'
        );
    }

    foreach ($rows as $i => $row) {

        $type = $classifications[$i]['type'] ?? '';

        if (!in_array($type, $allowed_types, true)) {
            return new WP_Error(
                'asms_classification_invalid_category',
                'AI classification returned an unsupported category.'
            );
        }

        $rows[$i]['type'] = $type;
    }

    return $rows;
}

/**
 * Reclassify report rows that do not contain a supported category.
 *
 * @param int $post_id Managed-services account post ID.
 * @return int|WP_Error Number of repaired rows, or an error.
 */
function asms_reclassify_uncategorized_report_data($post_id) {
    $report_data = json_decode(get_post_meta($post_id, 'ms_report_data_json', true), true);

    if (!is_array($report_data) || !$report_data) {
        return 0;
    }

    $allowed_types = as_ms_allowed_types();
    $row_indexes = [];
    $rows_to_classify = [];

    foreach ($report_data as $index => $row) {
        $type = $row['type'] ?? '';

        if (!in_array($type, $allowed_types, true)) {
            $row_indexes[] = $index;
            $rows_to_classify[] = $row;
        }
    }

    if (!$rows_to_classify) {
        return 0;
    }

    $classified_rows = as_ms_classify_data($rows_to_classify);

    if (is_wp_error($classified_rows)) {
        return $classified_rows;
    }

    foreach ($classified_rows as $classified_index => $classified_row) {
        $report_data[$row_indexes[$classified_index]] = $classified_row;
    }

    update_post_meta($post_id, 'ms_report_data_json', wp_json_encode($report_data));

    return count($classified_rows);
}

function as_ms_summarise_notes($notes) {
    if (empty($notes)) {
        return '';
    }

    $instructions = 'Create a high-level executive summary for the month based on managed-services timesheet notes for a monthly client report.

Output:
1. A short 1-2 sentence narrative summary.
2. A very short list of bullet points covering key activity themes.
3. Do not invent outcomes, dates, status, or completion.
4. Do not include individual staff names unless essential.
5. Keep the tone calm, professional, and useful.
6. Be concise and use natural language.

Return plain text only.';

    $summary = asms_generate_ai_text((string) $notes, $instructions);

    return is_wp_error($summary) ? '' : trim($summary);
}


// ==========================
// DEDUPE
// ==========================

function as_ms_dedupe_rows($existing, $parsed) {
    return array_merge($existing, $parsed);
}
