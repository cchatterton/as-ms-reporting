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
 * Return the configured rAIven model preference when the connector is ready.
 *
 * @return array<int, string>|null
 */
function asms_get_raiven_model_preference() {
    if (
        !function_exists('as329_rai_get_settings')
        || !function_exists('as329_rai_get_api_key')
        || !function_exists('as329_rai_get_model_ids')
    ) {
        return null;
    }

    try {
        $settings = as329_rai_get_settings();
        $model = is_array($settings) && is_string($settings['model'] ?? null)
            ? trim($settings['model'])
            : '';

        if ('' === $model || '' === trim((string) as329_rai_get_api_key())) {
            return null;
        }

        $available_models = as329_rai_get_model_ids();

        if (!is_array($available_models) || !in_array($model, $available_models, true)) {
            return null;
        }

        return ['raiven', $model];
    } catch (Throwable $error) {
        return null;
    }
}

/**
 * Return AI providers in the order requests should be attempted.
 *
 * @return array<int, array<string, mixed>>
 */
function asms_get_ai_provider_preferences() {
    $preferences = [];
    $raiven = asms_get_raiven_model_preference();

    if ($raiven) {
        $preferences[] = [
            'name'                    => 'rAIven',
            'provider_id'             => 'raiven',
            'model_preference'        => $raiven,
            'native_structured_output' => false,
        ];
    }

    $openai_model = defined('ASMS_OPENAI_MODEL')
        ? trim((string) ASMS_OPENAI_MODEL)
        : '';

    if ('' !== $openai_model) {
        $preferences[] = [
            'name'                    => 'OpenAI',
            'provider_id'             => 'openai',
            'model_preference'        => ['openai', $openai_model],
            'native_structured_output' => true,
        ];
    }

    return $preferences;
}

/**
 * Remove an optional Markdown fence from a JSON-only AI response.
 *
 * @param string $text Generated text.
 * @return string
 */
function asms_normalize_ai_json_text($text) {
    $text = trim((string) $text);

    if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $text, $matches)) {
        return trim($matches[1]);
    }

    return $text;
}

/**
 * Validate decoded JSON against the subset of JSON Schema used by this plugin.
 *
 * @param mixed $value  Decoded JSON value.
 * @param array $schema JSON schema.
 * @return bool
 */
function asms_ai_value_matches_schema($value, $schema) {
    $type = $schema['type'] ?? null;

    if ('object' === $type) {
        if (!is_array($value) || array_is_list($value)) {
            return false;
        }

        foreach (($schema['required'] ?? []) as $required_key) {
            if (!array_key_exists($required_key, $value)) {
                return false;
            }
        }

        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];

        if (false === ($schema['additionalProperties'] ?? true)) {
            foreach (array_keys($value) as $key) {
                if (!array_key_exists($key, $properties)) {
                    return false;
                }
            }
        }

        foreach ($properties as $key => $property_schema) {
            if (array_key_exists($key, $value) && !asms_ai_value_matches_schema($value[$key], $property_schema)) {
                return false;
            }
        }

        return true;
    }

    if ('array' === $type) {
        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }

        $item_count = count($value);

        if (isset($schema['minItems']) && $item_count < (int) $schema['minItems']) {
            return false;
        }

        if (isset($schema['maxItems']) && $item_count > (int) $schema['maxItems']) {
            return false;
        }

        foreach ($value as $item) {
            if (!asms_ai_value_matches_schema($item, $schema['items'] ?? [])) {
                return false;
            }
        }

        return true;
    }

    if ('string' === $type) {
        return is_string($value)
            && (!isset($schema['enum']) || in_array($value, $schema['enum'], true));
    }

    if ('integer' === $type) {
        return is_int($value);
    }

    if ('number' === $type) {
        return is_int($value) || is_float($value);
    }

    if ('boolean' === $type) {
        return is_bool($value);
    }

    return true;
}

/**
 * Validate a generated JSON response before accepting a provider result.
 *
 * @param string $text   Generated text.
 * @param array  $schema JSON schema.
 * @return bool
 */
function asms_ai_json_matches_schema($text, $schema) {
    $decoded = json_decode($text, true);

    return JSON_ERROR_NONE === json_last_error()
        && asms_ai_value_matches_schema($decoded, $schema);
}

/**
 * Generate text through rAIven when configured, then fall back to OpenAI.
 *
 * @param string     $input        User input.
 * @param string     $instructions System instructions.
 * @param array|null $schema       Optional JSON response schema.
 * @return string|WP_Error
 */
function asms_generate_ai_text($input, $instructions, $schema = null) {
    if (!function_exists('wp_ai_client_prompt')) {
        return new WP_Error(
            'asms_wordpress_ai_client_unavailable',
            'The WordPress AI Client is unavailable. WordPress 7.0 or later is required.'
        );
    }

    $provider_preferences = asms_get_ai_provider_preferences();

    if (!$provider_preferences) {
        return new WP_Error(
            'asms_ai_provider_unavailable',
            'Neither rAIven nor OpenAI is configured for this plugin.'
        );
    }

    $errors = [];

    foreach ($provider_preferences as $provider) {
        try {
            $provider_instructions = $instructions;

            if (is_array($schema) && !$provider['native_structured_output']) {
                $provider_instructions .= "\n\nReturn only valid JSON matching this schema. "
                    . "Do not use Markdown or code fences.\n"
                    . wp_json_encode($schema);
            }

            $builder = wp_ai_client_prompt($input)
                ->using_system_instruction($provider_instructions)
                ->using_provider($provider['provider_id'])
                ->using_model_preference($provider['model_preference']);

            if (is_array($schema) && $provider['native_structured_output']) {
                $builder = $builder->as_json_response($schema);
            }

            $result = $builder->generate_text();

            if (is_wp_error($result)) {
                $errors[] = $provider['name'] . ': ' . $result->get_error_message();
                continue;
            }

            $text = trim((string) $result);

            if (is_array($schema)) {
                $text = asms_normalize_ai_json_text($text);

                if (!asms_ai_json_matches_schema($text, $schema)) {
                    $errors[] = $provider['name'] . ': invalid structured response.';
                    continue;
                }
            }

            return $text;
        } catch (Throwable $error) {
            $errors[] = $provider['name'] . ': ' . $error->getMessage();
        }
    }

    return new WP_Error(
        'asms_wordpress_ai_client_error',
        implode(' ', $errors)
    );
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
                'type'     => 'array',
                'minItems' => count($rows),
                'maxItems' => count($rows),
                'items'    => [
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
