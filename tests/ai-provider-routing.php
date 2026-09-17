<?php

define('ABSPATH', __DIR__);
define('ASMS_OPENAI_MODEL', 'openai-test-model');

class WP_Error {
    private $message;

    public function __construct($code, $message) {
        $this->message = $message;
    }

    public function get_error_message() {
        return $this->message;
    }
}

function is_wp_error($value) {
    return $value instanceof WP_Error;
}

function wp_json_encode($value) {
    return json_encode($value);
}

$asms_test_raiven_key = 'configured-key';
$asms_test_raiven_model = 'gpt-4';
$asms_test_raiven_models = ['raiven-test-model'];
$asms_test_results = [];
$asms_test_attempts = [];
$asms_test_post_meta = [];

function as329_rai_get_settings() {
    global $asms_test_raiven_model;

    return ['model' => $asms_test_raiven_model];
}

function as329_rai_register_ai_provider() {
}

function as329_rai_get_api_key() {
    global $asms_test_raiven_key;

    return $asms_test_raiven_key;
}

function as329_rai_get_model_ids() {
    global $asms_test_raiven_models;

    return $asms_test_raiven_models;
}

class ASMS_Test_AI_Builder {
    private $instructions = '';
    private $provider = '';
    private $model_preference = [];
    private $structured = false;

    public function using_system_instruction($instructions) {
        $this->instructions = $instructions;

        return $this;
    }

    public function using_model_preference($model_preference) {
        $this->model_preference = $model_preference;

        return $this;
    }

    public function using_provider($provider) {
        $this->provider = $provider;

        return $this;
    }

    public function as_json_response($schema) {
        $this->structured = true;

        return $this;
    }

    public function generate_text() {
        global $asms_test_attempts, $asms_test_results;

        $provider = $this->provider;
        $asms_test_attempts[] = [
            'provider'     => $provider,
            'instructions' => $this->instructions,
            'model'        => $this->model_preference,
            'structured'   => $this->structured,
        ];

        return $asms_test_results[$provider] ?? new WP_Error('missing_result', 'No test result.');
    }
}

function wp_ai_client_prompt($input) {
    return new ASMS_Test_AI_Builder();
}

function sanitize_text_field($value) {
    return trim((string) $value);
}

function current_time($type) {
    return '2026-09-17 10:30:00';
}

function update_post_meta($post_id, $key, $value) {
    global $asms_test_post_meta;

    $asms_test_post_meta[$post_id][$key] = $value;

    return true;
}

require dirname(__DIR__) . '/as-ms-reporting/functions/ms-data-pipeline.php';

function asms_test_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function asms_test_reset($results) {
    global $asms_test_attempts, $asms_test_results;

    $asms_test_attempts = [];
    $asms_test_results = $results;
}

asms_test_reset([
    'raiven' => 'rAIven summary',
    'openai' => 'OpenAI summary',
]);
$result = asms_generate_ai_text('Input', 'Instructions');
asms_test_assert('rAIven summary' === $result, 'Configured rAIven should be preferred.');
asms_test_assert(['raiven'] === array_column($asms_test_attempts, 'provider'), 'OpenAI should not run after rAIven succeeds.');
asms_test_assert(
    ['raiven', 'qwen3.8-flash-next-nvfp4'] === $asms_test_attempts[0]['model'],
    'The verified rAIven model should be selected instead of a stale connector model.'
);
$usage = asms_get_last_ai_request();
asms_test_assert('raiven' === $usage['provider_id'] && !$usage['used_fallback'], 'Successful rAIven usage should be recorded.');
asms_test_assert(asms_store_last_ai_request(42, 'Monthly summary'), 'Successful usage should be persisted to account meta.');
asms_test_assert(
    'Monthly summary' === $asms_test_post_meta[42]['_asms_last_ai_request']['operation'],
    'Persisted usage should include the operation label.'
);

asms_test_reset([
    'raiven' => new WP_Error('raiven_failed', 'rAIven failed.'),
    'openai' => 'OpenAI fallback',
]);
$result = asms_generate_ai_text('Input', 'Instructions');
asms_test_assert('OpenAI fallback' === $result, 'OpenAI should run when rAIven generation fails.');
asms_test_assert(['raiven', 'openai'] === array_column($asms_test_attempts, 'provider'), 'Fallback order should be rAIven then OpenAI.');
$usage = asms_get_last_ai_request();
asms_test_assert('openai' === $usage['provider_id'] && $usage['used_fallback'], 'OpenAI fallback usage should be recorded.');

$schema = [
    'type'                 => 'object',
    'properties'           => [
        'answer' => [
            'type' => 'string',
            'enum' => ['ok'],
        ],
    ],
    'required'             => ['answer'],
    'additionalProperties' => false,
];

asms_test_reset([
    'raiven' => "```json\n{\"answer\":\"ok\"}\n```",
    'openai' => '{"answer":"ok"}',
]);
$result = asms_generate_ai_text('Input', 'Instructions', $schema);
asms_test_assert('{"answer":"ok"}' === $result, 'Valid fenced rAIven JSON should be normalized and accepted.');
asms_test_assert(false === $asms_test_attempts[0]['structured'], 'rAIven must not receive unsupported native structured-output options.');
asms_test_assert(false !== strpos($asms_test_attempts[0]['instructions'], 'Return only valid JSON'), 'rAIven should receive JSON-only instructions.');

asms_test_reset([
    'raiven' => '{"answer":"unsupported"}',
    'openai' => '{"answer":"ok"}',
]);
$result = asms_generate_ai_text('Input', 'Instructions', $schema);
asms_test_assert('{"answer":"ok"}' === $result, 'Invalid rAIven structured data should fall back to OpenAI.');
asms_test_assert(true === $asms_test_attempts[1]['structured'], 'OpenAI should use native structured output.');

$fixed_length_schema = [
    'type'     => 'array',
    'minItems' => 2,
    'maxItems' => 2,
    'items'    => ['type' => 'string'],
];
asms_test_assert(
    !asms_ai_json_matches_schema('["one"]', $fixed_length_schema),
    'Incomplete classification arrays should fail validation and permit fallback.'
);
asms_test_assert(
    asms_ai_json_matches_schema('["one","two"]', $fixed_length_schema),
    'Complete fixed-length classification arrays should pass validation.'
);

$asms_test_raiven_key = '';
asms_test_reset([
    'raiven' => 'Native connector success',
    'openai' => 'Unexpected',
]);
$result = asms_generate_ai_text('Input', 'Instructions');
asms_test_assert(
    'Native connector success' === $result,
    'The native rAIven provider should be tried even when its legacy key helper cannot read the WordPress connector key.'
);
asms_test_assert(['raiven'] === array_column($asms_test_attempts, 'provider'), 'The native rAIven provider should remain authoritative.');
$usage = asms_get_last_ai_request();
asms_test_assert(
    'raiven' === $usage['provider_id'],
    'Successful native rAIven usage should be recorded even when the legacy key helper is empty.'
);

$asms_test_raiven_model = '';
asms_test_reset([
    'raiven' => 'Explicit model success',
    'openai' => 'Unexpected',
]);
$result = asms_generate_ai_text('Input', 'Instructions');
$usage = asms_get_last_ai_request();
asms_test_assert('Explicit model success' === $result, 'rAIven should use its explicitly selected native model.');
asms_test_assert(
    ['raiven', 'qwen3.8-flash-next-nvfp4'] === $asms_test_attempts[0]['model'],
    'Legacy empty model settings must not remove the explicit rAIven model preference.'
);
asms_test_assert(
    'qwen3.8-flash-next-nvfp4' === $usage['model'],
    'The selected rAIven model should be identified in the usage panel.'
);

echo "AI provider routing tests passed.\n";
