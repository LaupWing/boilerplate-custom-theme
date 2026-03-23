<?php

/**
 * AI Translation — AJAX endpoint.
 *
 * Translates text via OpenAI API. Powers the "Translate" button
 * in the EditorWrapper block component.
 *
 * Requires SNEL_OPENAI_API_KEY defined in wp-config.php,
 * or set via the database option 'snel_openai_api_key'.
 *
 * DO NOT EDIT per project.
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Pass translation config to the block editor JS.
 *
 * Makes the AJAX URL, nonce, and language config available
 * to the EditorWrapper component via the global `snelTranslate` object.
 */
function snel_translate_editor_assets()
{
    wp_localize_script('wp-blocks', 'snelTranslate', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('snel_translate_nonce'),
        'langs'   => snel_get_supported_langs(),
        'default' => snel_get_default_lang(),
    ]);
}
add_action('enqueue_block_editor_assets', 'snel_translate_editor_assets');

/**
 * AJAX handler: translate an array of strings via OpenAI.
 *
 * POST params:
 *   texts[]  — array of source strings
 *   source   — source language code (e.g. 'nl')
 *   target   — target language code (e.g. 'en')
 *   nonce    — security nonce
 *
 * Returns: { success: true, data: { translations: [...] } }
 */
function snel_translate_ajax()
{
    check_ajax_referer('snel_translate_nonce', 'nonce');

    if (! current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $texts  = $_POST['texts'] ?? [];
    $target = sanitize_text_field($_POST['target'] ?? 'en');
    $source = sanitize_text_field($_POST['source'] ?? 'nl');

    if (empty($texts) || ! is_array($texts)) {
        wp_send_json_error('No texts provided');
    }

    // Sanitize — wp_kses_post preserves safe HTML like <strong>, <em>, <a>
    $texts = array_map('wp_kses_post', $texts);
    $texts = array_values(array_filter($texts, function ($t) {
        return trim($t) !== '';
    }));

    if (empty($texts)) {
        wp_send_json_error('No non-empty texts provided');
    }

    // Get API key from unified Snelstack settings
    $api_key = function_exists('snelstack_get_openai_key') ? snelstack_get_openai_key() : '';
    if (empty($api_key)) {
        wp_send_json_error('API key not configured. Go to Snelstack Settings to add your OpenAI API key.');
    }

    $model = function_exists('snelstack_get_openai_model') ? snelstack_get_openai_model() : 'gpt-4o-mini';

    // Language names for the prompt
    $lang_names = [
        'nl' => 'Dutch',
        'en' => 'English',
        'de' => 'German',
        'fr' => 'French',
        'es' => 'Spanish',
    ];
    $source_name = $lang_names[$source] ?? $source;
    $target_name = $lang_names[$target] ?? $target;

    // Build numbered list for the prompt
    $numbered = [];
    foreach ($texts as $i => $text) {
        $numbered[] = ($i + 1) . '. ' . $text;
    }

    $prompt = "Translate the following texts from {$source_name} to {$target_name}. "
            . "Return ONLY the translations, numbered the same way (1. 2. 3. etc). "
            . "Keep HTML tags intact. Keep the same tone, style, and formatting.\n\n"
            . implode("\n", $numbered);

    // Call OpenAI API
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => wp_json_encode([
            'model'    => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional translator. Translate accurately and naturally. Preserve HTML tags.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
        ]),
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('API request failed: ' . $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_error('API returned status ' . $status_code . ': ' . $body);
    }

    $body   = json_decode(wp_remote_retrieve_body($response), true);
    $output = $body['choices'][0]['message']['content'] ?? '';

    // Parse numbered translations back into array
    $translations = [];
    $lines = explode("\n", trim($output));
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        // Remove numbering: "1. Translated text" → "Translated text"
        $cleaned = preg_replace('/^\d+\.\s*/', '', $line);
        if ($cleaned !== '') {
            $translations[] = $cleaned;
        }
    }

    // Ensure we have the same number of translations as inputs
    if (count($translations) !== count($texts)) {
        wp_send_json_error('Translation count mismatch. Expected ' . count($texts) . ', got ' . count($translations));
    }

    wp_send_json_success(['translations' => $translations]);
}
add_action('wp_ajax_snel_translate', 'snel_translate_ajax');
