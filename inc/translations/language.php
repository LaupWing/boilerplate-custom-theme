<?php

/**
 * Core language routing and translation helpers.
 *
 * DO NOT EDIT per project — configure via config/ files instead.
 *
 * @package Boilerplate
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Load and cache the languages config.
 *
 * @return array
 */
function bp_get_languages_config()
{
    static $config = null;

    if ($config === null) {
        $config = require get_template_directory() . '/inc/translations/config/languages.php';
    }

    return $config;
}

/**
 * Get array of supported language codes.
 *
 * @return array e.g. ['nl', 'en']
 */
function bp_get_supported_langs()
{
    return array_keys(bp_get_languages_config());
}

/**
 * Get the default language code (the one with 'default' => true).
 *
 * @return string e.g. 'nl'
 */
function bp_get_default_lang()
{
    foreach (bp_get_languages_config() as $code => $lang) {
        if (! empty($lang['default'])) {
            return $code;
        }
    }

    // Fallback to first language
    return bp_get_supported_langs()[0];
}

/**
 * Get the current language from the URL.
 * Falls back to default language if not set.
 *
 * @return string e.g. 'en'
 */
function bp_get_lang()
{
    $lang = get_query_var('lang', '');

    if ($lang && in_array($lang, bp_get_supported_langs(), true)) {
        return $lang;
    }

    return bp_get_default_lang();
}

/**
 * Check if the current language matches a given language.
 *
 * @param string $lang Language code to check.
 * @return bool
 */
function bp_is_lang($lang)
{
    return bp_get_lang() === $lang;
}

// ---------------------------------------------------------------------------
// URL Rewrite Rules
// ---------------------------------------------------------------------------

/**
 * Register 'lang' as a query variable so WordPress recognizes it.
 *
 * Without this, get_query_var('lang') would always return empty
 * even if the rewrite rules set it.
 */
function bp_lang_query_vars($vars)
{
    $vars[] = 'lang';
    return $vars;
}
add_filter('query_vars', 'bp_lang_query_vars');

/**
 * Load and cache the CPT slug translations config.
 *
 * @return array
 */
function bp_get_cpt_slugs_config()
{
    static $config = null;

    if ($config === null) {
        $config = require get_template_directory() . '/inc/translations/config/slugs-cpt.php';
    }

    return $config;
}

/**
 * Register URL rewrite rules for each non-default language.
 *
 * Creates rules so that:
 *   /en/                    → homepage with lang=en
 *   /en/some-page/          → page with lang=en (catch-all)
 *   /en/products/           → CPT archive with lang=en (from config)
 *   /en/products/some-post/ → CPT single with lang=en (from config)
 *
 * Rules are registered with 'top' priority so they are checked
 * before WordPress default rules.
 */
function bp_lang_rewrite_rules()
{
    $default   = bp_get_default_lang();
    $langs     = bp_get_supported_langs();
    $cpt_slugs = bp_get_cpt_slugs_config();

    foreach ($langs as $lang) {
        // Skip default language — it has no URL prefix
        if ($lang === $default) {
            continue;
        }

        // /en/ → homepage
        add_rewrite_rule(
            "^{$lang}/?$",
            'index.php?lang=' . $lang,
            'top'
        );

        // CPT rules from config (e.g., /en/products/, /en/products/my-post/)
        foreach ($cpt_slugs as $dutch_slug => $translations) {
            if (! empty($translations[$lang])) {
                $translated_slug = $translations[$lang];

                // /en/products/ → CPT archive
                add_rewrite_rule(
                    "^{$lang}/{$translated_slug}/?$",
                    'index.php?lang=' . $lang . '&post_type=' . $dutch_slug,
                    'top'
                );

                // /en/products/my-post/ → CPT single
                add_rewrite_rule(
                    "^{$lang}/{$translated_slug}/([^/]+)/?$",
                    'index.php?lang=' . $lang . '&post_type=' . $dutch_slug . '&name=$matches[1]',
                    'top'
                );
            }
        }

        // /en/some-page/ → catch-all for pages
        add_rewrite_rule(
            "^{$lang}/(.+?)/?$",
            'index.php?lang=' . $lang . '&pagename=$matches[1]',
            'top'
        );
    }
}
add_action('init', 'bp_lang_rewrite_rules');

/**
 * Fix front page loading for non-default languages.
 *
 * When visiting /en/ the rewrite sets lang=en but no page is specified.
 * WordPress doesn't know to show the front page. This filter injects
 * the front page ID so it loads correctly.
 */
function bp_lang_fix_front_page($query_vars)
{
    $lang    = $query_vars['lang'] ?? '';
    $default = bp_get_default_lang();

    if ($lang && $lang !== $default) {
        // Only if no specific page/post is requested
        $has_page = ! empty($query_vars['pagename']) || ! empty($query_vars['page_id']);
        $has_post = ! empty($query_vars['name']) || ! empty($query_vars['p']) || ! empty($query_vars['post_type']);

        if (! $has_page && ! $has_post) {
            $front_page_id = get_option('page_on_front');

            if ($front_page_id) {
                $query_vars['page_id'] = $front_page_id;
            }
        }
    }

    return $query_vars;
}
add_filter('request', 'bp_lang_fix_front_page');

/**
 * Prevent WordPress from redirecting translated URLs to the canonical (Dutch) URL.
 *
 * Without this, visiting /en/about-us/ would redirect to /over-ons/
 * because WordPress thinks the "real" URL is the Dutch one.
 */
function bp_lang_prevent_canonical_redirect($redirect_url)
{
    $lang = get_query_var('lang', '');

    if ($lang && $lang !== bp_get_default_lang()) {
        return false;
    }

    return $redirect_url;
}
add_filter('redirect_canonical', 'bp_lang_prevent_canonical_redirect');

// ---------------------------------------------------------------------------
// Dynamic Page Slug Translations (from post meta)
// ---------------------------------------------------------------------------

/**
 * Resolve translated page slugs to their actual WordPress page.
 *
 * When a user visits /en/about-us/, the catch-all rewrite rule sets
 * pagename=about-us. But the actual WordPress page slug is "over-ons".
 *
 * This hook checks: is "about-us" a translated slug stored in post meta?
 * If yes, swap the pagename to the real slug so WordPress finds the page.
 *
 * Post meta key format: _slug_{lang} (e.g., _slug_en = "about-us")
 */
function bp_resolve_translated_page_slug($query_vars)
{
    $lang = $query_vars['lang'] ?? '';

    // Only for non-default languages
    if (! $lang || $lang === bp_get_default_lang()) {
        return $query_vars;
    }

    // Only if a pagename is set (catch-all rule)
    if (empty($query_vars['pagename'])) {
        return $query_vars;
    }

    $requested_slug = $query_vars['pagename'];

    // Look up: is there a page with _slug_{lang} = this slug?
    $pages = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'meta_key'       => '_slug_' . $lang,
        'meta_value'     => $requested_slug,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    if (! empty($pages)) {
        // Found it — swap pagename to the real WordPress slug
        $real_page = get_post($pages[0]);
        $query_vars['pagename'] = $real_page->post_name;
    }

    return $query_vars;
}
add_filter('request', 'bp_resolve_translated_page_slug');

// ---------------------------------------------------------------------------
// URL Helpers
// ---------------------------------------------------------------------------

/**
 * Add the current language prefix to any internal URL.
 *
 * Usage in templates:
 *   <a href="<?php echo bp_url('/contact/'); ?>">Contact</a>
 *
 * If current lang is 'en': returns /en/contact/
 * If current lang is 'nl' (default): returns /contact/ (no prefix)
 *
 * @param string $url Relative URL (e.g., '/contact/' or '/producten/my-item/')
 * @return string
 */
function bp_url($url)
{
    $lang = bp_get_lang();

    if ($lang === bp_get_default_lang()) {
        return $url;
    }

    // Ensure leading slash
    $url = '/' . ltrim($url, '/');

    return '/' . $lang . $url;
}

/**
 * Get the URL for switching to a different language on the current page.
 *
 * Used in the language switcher. Figures out what the current page is
 * and builds the URL for the target language.
 *
 * @param string $target_lang Language code to switch to (e.g., 'en')
 * @return string Full URL for that language
 */
function bp_lang_url($target_lang)
{
    $default = bp_get_default_lang();

    // Front page
    if (is_front_page()) {
        if ($target_lang === $default) {
            return home_url('/');
        }
        return home_url('/' . $target_lang . '/');
    }

    // Single page
    if (is_page()) {
        $page = get_queried_object();

        if ($target_lang === $default) {
            // Default language — use the real WordPress slug
            return home_url('/' . $page->post_name . '/');
        }

        // Check if this page has a translated slug
        $translated_slug = get_post_meta($page->ID, '_slug_' . $target_lang, true);
        $slug = $translated_slug ?: $page->post_name;

        return home_url('/' . $target_lang . '/' . $slug . '/');
    }

    // Single CPT post
    if (is_singular()) {
        $post     = get_queried_object();
        $cpt_slug = $post->post_type;
        $cpt_slugs = bp_get_cpt_slugs_config();

        if ($target_lang === $default) {
            return home_url('/' . $cpt_slug . '/' . $post->post_name . '/');
        }

        // Use translated CPT slug if available
        $archive_slug = $cpt_slugs[$cpt_slug][$target_lang] ?? $cpt_slug;

        return home_url('/' . $target_lang . '/' . $archive_slug . '/' . $post->post_name . '/');
    }

    // CPT archive
    if (is_post_type_archive()) {
        $cpt_slug  = get_queried_object()->name;
        $cpt_slugs = bp_get_cpt_slugs_config();

        if ($target_lang === $default) {
            return home_url('/' . $cpt_slug . '/');
        }

        $archive_slug = $cpt_slugs[$cpt_slug][$target_lang] ?? $cpt_slug;

        return home_url('/' . $target_lang . '/' . $archive_slug . '/');
    }

    // Fallback: just prefix the current path
    $path = wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Strip existing language prefix if present
    $current_lang = bp_get_lang();
    if ($current_lang !== $default) {
        $path = preg_replace('#^/' . $current_lang . '/#', '/', $path);
    }

    if ($target_lang === $default) {
        return home_url($path);
    }

    return home_url('/' . $target_lang . rtrim($path, '/') . '/');
}

// ---------------------------------------------------------------------------
// Translation Helpers
// ---------------------------------------------------------------------------

/**
 * Translate a static theme string.
 *
 * Usage in templates:
 *   <h1><?php echo bp__('Welkom'); ?></h1>
 *   // Outputs "Welcome" if lang=en
 *
 * Lookup order:
 *   1. Database (wp_option 'bp_theme_translations') — admin can override
 *   2. File (inc/translations/translations.php) — developer defaults
 *   3. Original text — returns the Dutch key as-is
 *
 * @param string $text The default-language (Dutch) text.
 * @return string Translated text, or original if no translation found.
 */
function bp__($text)
{
    $lang = bp_get_lang();

    // Default language — return as-is
    if ($lang === bp_get_default_lang()) {
        return $text;
    }

    // 1. Check database
    $db_translations = get_option('bp_theme_translations', []);
    if (! empty($db_translations[$text][$lang])) {
        return $db_translations[$text][$lang];
    }

    // 2. Check file
    static $file_translations = null;
    if ($file_translations === null) {
        $file = get_template_directory() . '/inc/translations/translations.php';
        $grouped = file_exists($file) ? require $file : [];

        // Flatten grouped array: ['Section' => ['key' => ['en' => 'val']]] → ['key' => ['en' => 'val']]
        $file_translations = [];
        foreach ($grouped as $section => $strings) {
            foreach ($strings as $key => $translations) {
                $file_translations[$key] = $translations;
            }
        }
    }

    if (! empty($file_translations[$text][$lang])) {
        return $file_translations[$text][$lang];
    }

    // 3. Fallback — return original
    return $text;
}

/**
 * Extract the current language value from a multilingual value.
 *
 * Used for data that's stored as {nl: '...', en: '...'}
 * like block attributes or custom field values.
 *
 * @param mixed $val A multilingual array or a plain string.
 * @return string The value for the current language.
 */
function bp_val($val)
{
    if (! is_array($val)) {
        return $val ?: '';
    }

    $lang    = bp_get_lang();
    $default = bp_get_default_lang();

    // Try current language, fall back to default
    return $val[$lang] ?? $val[$default] ?? '';
}

/**
 * Get a translated block attribute value.
 *
 * Shorthand for reading a key from the block's $attributes array
 * and extracting the current language.
 *
 * Usage in render.php:
 *   $heading = bp_attr($attributes, 'heading');
 *
 * @param array  $attributes Block attributes array.
 * @param string $key        The attribute key.
 * @return string The translated value.
 */
function bp_attr($attributes, $key)
{
    $val = $attributes[$key] ?? '';
    return bp_val($val);
}

// ---------------------------------------------------------------------------
// SEO: hreflang Tags
// ---------------------------------------------------------------------------

/**
 * Output hreflang tags in the <head> for all supported languages.
 *
 * Tells search engines: "this page exists in these languages at these URLs."
 *
 * Outputs something like:
 *   <link rel="alternate" hreflang="nl" href="https://example.com/over-ons/" />
 *   <link rel="alternate" hreflang="en" href="https://example.com/en/about-us/" />
 *   <link rel="alternate" hreflang="x-default" href="https://example.com/over-ons/" />
 */
function bp_hreflang_tags()
{
    $langs   = bp_get_supported_langs();
    $default = bp_get_default_lang();
    $config  = bp_get_languages_config();

    foreach ($langs as $lang) {
        $url    = bp_lang_url($lang);
        $locale = $config[$lang]['locale'] ?? $lang;
        // hreflang uses short codes like "nl", "en", or full like "en-US"
        $hreflang = substr($locale, 0, 2);

        echo '<link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url($url) . '" />' . "\n";
    }

    // x-default points to the default language version
    $default_url = bp_lang_url($default);
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
}
add_action('wp_head', 'bp_hreflang_tags');
