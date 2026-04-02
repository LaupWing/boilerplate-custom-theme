<?php
/**
 * Auto-slugify — Automatically translate slugs on post publish.
 *
 * When a post/page is published and _slug_{lang} meta fields are empty,
 * this translates the post title via OpenAI and saves slugified versions.
 *
 * Requires an OpenAI API key configured via Snelstack Settings.
 * If no API key is set, this does nothing (original slug is used as fallback).
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('save_post', 'snel_auto_slugify', 20, 2);

function snel_auto_slugify($post_id, $post)
{
    // Skip autosaves, revisions, and non-published posts.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if ($post->post_status !== 'publish') return;

    // Only run on public post types.
    if (! is_post_type_viewable($post->post_type)) return;

    // Get API key — skip if not configured.
    $api_key = function_exists('snelstack_get_openai_key') ? snelstack_get_openai_key() : '';
    if (empty($api_key)) return;

    $langs   = LocaleManager::supported();
    $default = LocaleManager::default();
    $title   = $post->post_title;

    if (empty($title)) return;

    $model     = function_exists('snelstack_get_openai_model') ? snelstack_get_openai_model() : 'gpt-4o-mini';
    $lang_names = [
        'nl' => 'Dutch', 'en' => 'English', 'de' => 'German',
        'fr' => 'French', 'es' => 'Spanish', 'it' => 'Italian',
    ];

    foreach ($langs as $lang) {
        if ($lang === $default) continue;

        // Skip if slug already set.
        $existing = get_post_meta($post_id, '_slug_' . $lang, true);
        if (! empty($existing)) continue;

        // Translate title via OpenAI.
        $source_name = $lang_names[$default] ?? $default;
        $target_name = $lang_names[$lang] ?? $lang;

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode([
                'model'    => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Translate the given page title. Return ONLY the translation, nothing else.'],
                    ['role' => 'user', 'content' => "Translate from {$source_name} to {$target_name}: {$title}"],
                ],
                'temperature' => 0.3,
            ]),
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            continue;
        }

        $body       = json_decode(wp_remote_retrieve_body($response), true);
        $translated = trim($body['choices'][0]['message']['content'] ?? '');

        if (empty($translated)) continue;

        // Slugify: lowercase, strip accents, replace non-alphanumeric with hyphens.
        $slug = strtolower($translated);
        $slug = remove_accents($slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if (! empty($slug)) {
            update_post_meta($post_id, '_slug_' . $lang, $slug);
        }

        // Also save the translated title if not already set.
        $existing_title = get_post_meta($post_id, '_title_' . $lang, true);
        if (empty($existing_title) && ! empty($translated)) {
            update_post_meta($post_id, '_title_' . $lang, $translated);
        }
    }
}
