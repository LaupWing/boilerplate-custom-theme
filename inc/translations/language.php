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

// Load the LocaleManager class — single source of truth for language config/detection.
require_once get_template_directory() . '/inc/translations/LocaleManager.php';

/**
 * Load and cache the languages config.
 *
 * @return array
 */
function bp_get_languages_config()
{
    return LocaleManager::config();
}

/**
 * Get array of supported language codes.
 *
 * @return array e.g. ['nl', 'en']
 */
function bp_get_supported_langs()
{
    return LocaleManager::supported();
}

/**
 * Get the default language code (the one with 'default' => true).
 *
 * @return string e.g. 'nl'
 */
function bp_get_default_lang()
{
    return LocaleManager::default();
}

/**
 * Get the current language from the URL.
 * Falls back to default language if not set.
 *
 * @return string e.g. 'en'
 */
function bp_get_lang()
{
    return LocaleManager::current();
}

/**
 * Check if the current language matches a given language.
 *
 * @param string $lang Language code to check.
 * @return bool
 */
function bp_is_lang($lang)
{
    return LocaleManager::is($lang);
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

                // /en/products/page/2/ → CPT archive pagination
                add_rewrite_rule(
                    "^{$lang}/{$translated_slug}/page/([0-9]+)/?$",
                    'index.php?lang=' . $lang . '&post_type=' . $dutch_slug . '&paged=$matches[1]',
                    'top'
                );
            }
        }

        // Explicit rules for each page with a translated slug.
        // Uses page_id to avoid WP's pagename→attachment fallback.
        //
        // WHY: When the catch-all rule sets pagename=translated-slug,
        // WordPress's parse_request() looks up the page by that slug.
        // If no page has that actual WP slug (because the real slug is
        // different, e.g. "about" not "about-us"), WP silently converts
        // it to attachment=translated-slug → 404.
        // Using page_id bypasses the slug lookup entirely.
        $pages_with_slugs = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => '_slug_' . $lang,
            'meta_compare'   => 'EXISTS',
        ]);

        foreach ($pages_with_slugs as $page) {
            $translated_slug = get_post_meta($page->ID, '_slug_' . $lang, true);
            if ($translated_slug) {
                add_rewrite_rule(
                    "^{$lang}/{$translated_slug}/?$",
                    'index.php?lang=' . $lang . '&page_id=' . $page->ID,
                    'top'
                );
            }
        }

        // Catch-all for pages without explicit translated slugs
        add_rewrite_rule(
            "^{$lang}/(.+?)/?$",
            'index.php?lang=' . $lang . '&pagename=$matches[1]',
            'top'
        );
    }
}
add_action('init', 'bp_lang_rewrite_rules');

// Flush rewrite rules on theme activation so language rules are registered.
add_action('after_switch_theme', 'flush_rewrite_rules');

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
 * Resolve a single translated slug to its real WordPress page.
 *
 * @param string $slug The translated slug.
 * @param string $lang The language code.
 * @return WP_Post|null The page if found, null otherwise.
 */
function bp_find_page_by_translated_slug($slug, $lang)
{
    $pages = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'meta_key'       => '_slug_' . $lang,
        'meta_value'     => $slug,
        'posts_per_page' => 1,
    ]);

    return ! empty($pages) ? $pages[0] : null;
}

/**
 * Resolve a single translated slug to its real WordPress blog post.
 *
 * @param string $slug The translated slug.
 * @param string $lang The language code.
 * @return WP_Post|null The post if found, null otherwise.
 */
function bp_find_post_by_translated_slug($slug, $lang)
{
    $posts = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'meta_key'       => '_slug_' . $lang,
        'meta_value'     => $slug,
        'posts_per_page' => 1,
    ]);

    return ! empty($posts) ? $posts[0] : null;
}

/**
 * Resolve translated page slugs to their actual WordPress page.
 *
 * Handles both flat and nested (child) page slugs:
 *   /en/about-us/          → pagename=about-us   → resolves to "over-ons"
 *   /en/about-us/team/     → pagename=about-us/team → resolves to "over-ons/team"
 *
 * For nested paths, each segment is checked individually:
 *   - "about-us" → found via _slug_en meta → real slug is "over-ons"
 *   - "team" → checked for _slug_en meta → if found, swap; if not, keep as-is
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

    $requested_path = $query_vars['pagename'];
    $segments = explode('/', $requested_path);

    // Try the full path first (flat page or blog post, most common case)
    if (count($segments) === 1) {
        $page = bp_find_page_by_translated_slug($segments[0], $lang);
        if ($page) {
            $query_vars['pagename'] = $page->post_name;
            return $query_vars;
        }

        // Check blog posts too
        $post = bp_find_post_by_translated_slug($segments[0], $lang);
        if ($post) {
            unset($query_vars['pagename']);
            $query_vars['name'] = $post->post_name;
            $query_vars['post_type'] = 'post';
            return $query_vars;
        }

        return $query_vars;
    }

    // Nested path: translate each segment back to the real slug
    $real_segments = [];
    foreach ($segments as $segment) {
        $page = bp_find_page_by_translated_slug($segment, $lang);
        if ($page) {
            $real_segments[] = $page->post_name;
        } else {
            // No translation found — keep the original segment
            $real_segments[] = $segment;
        }
    }

    $query_vars['pagename'] = implode('/', $real_segments);

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
    $lang    = bp_get_lang();
    $default = bp_get_default_lang();

    if ($lang === $default) {
        return $url;
    }

    $langs       = bp_get_supported_langs();
    $non_default = array_diff($langs, [$default]);
    $parsed      = parse_url($url);
    $path        = $parsed['path'] ?? '/';

    // Avoid double-prefixing.
    $pattern = '#^/(' . implode('|', $non_default) . ')(/|$)#';
    if (preg_match($pattern, $path)) {
        return $url;
    }

    $new_path = '/' . $lang . $path;

    // Rebuild full URL if it had scheme + host.
    if (isset($parsed['scheme'], $parsed['host'])) {
        $host = $parsed['host'];
        if (isset($parsed['port'])) {
            $host .= ':' . $parsed['port'];
        }
        return $parsed['scheme'] . '://' . $host . $new_path;
    }

    return $new_path;
}

/**
 * Build a translated page URL for the current language.
 *
 * Usage in templates:
 *   <a href="<?php echo bp_page_url('over-ons'); ?>">About</a>
 *   // Returns /over-ons/ for NL, /en/about-us/ for EN
 *
 * @param string $default_slug The default language (Dutch) page slug.
 * @return string
 */
function bp_page_url($default_slug)
{
    $lang    = bp_get_lang();
    $default = bp_get_default_lang();

    if ($lang === $default) {
        return '/' . $default_slug . '/';
    }

    // Look up translated slug from post meta
    $page = get_page_by_path($default_slug);
    if ($page) {
        $translated_slug = get_post_meta($page->ID, '_slug_' . $lang, true);
        if ($translated_slug) {
            return '/' . $lang . '/' . $translated_slug . '/';
        }
    }

    // Fallback: use default slug with language prefix
    return '/' . $lang . '/' . $default_slug . '/';
}

/**
 * Build a translated URL for a nav menu item.
 *
 * Menu items store absolute URLs (e.g. http://localhost/products/).
 * For page items, this uses bp_page_url() for proper translated slugs.
 * For CPT singles, uses bp_cpt_single_url(). For everything else,
 * falls back to bp_url() which now handles absolute URLs.
 *
 * @param WP_Post $item Nav menu item object.
 * @return string Translated URL.
 */
function bp_nav_item_url($item)
{
    // Page menu items → use translated slug.
    if ('post_type' === $item->type && 'page' === $item->object) {
        $page = get_post($item->object_id);
        if ($page) {
            if ((int) get_option('page_on_front') === $page->ID) {
                return bp_url('/');
            }
            return bp_page_url($page->post_name);
        }
    }

    // CPT single menu items → use translated archive + post slug.
    if ('post_type' === $item->type && 'page' !== $item->object) {
        $post = get_post($item->object_id);
        if ($post) {
            return bp_cpt_single_url($post, $post->post_type);
        }
    }

    // Everything else → bp_url handles absolute URLs now.
    return bp_url($item->url);
}

/**
 * Build a translated CPT archive URL for the current language.
 *
 * Usage in templates:
 *   <a href="<?php echo bp_cpt_url('diensten'); ?>">Services</a>
 *   // Returns /diensten/ for NL, /en/services/ for EN
 *
 * @param string $cpt_slug The default-language CPT slug.
 * @return string
 */
function bp_cpt_url($cpt_slug)
{
    $lang    = bp_get_lang();
    $default = bp_get_default_lang();

    if ($lang === $default) {
        return '/' . $cpt_slug . '/';
    }

    $cpt_slugs  = bp_get_cpt_slugs_config();
    $translated = $cpt_slugs[$cpt_slug][$lang] ?? $cpt_slug;

    return '/' . $lang . '/' . $translated . '/';
}

/**
 * Build a translated CPT single post URL for the current language.
 *
 * Translates the archive segment via slugs-cpt.php config and
 * optionally translates the post slug via _slug_{lang} meta.
 *
 * Usage:
 *   bp_cpt_single_url($post, 'diensten')
 *   // NL: /diensten/mijn-dienst/
 *   // EN: /en/services/my-service/
 *
 * @param int|WP_Post $post     Post ID or object.
 * @param string      $cpt_slug The default-language CPT archive slug.
 * @return string
 */
function bp_cpt_single_url($post, $cpt_slug)
{
    $post = get_post($post);
    if (! $post) {
        return '';
    }

    $lang    = bp_get_lang();
    $default = bp_get_default_lang();

    // Translate the post slug if meta exists.
    $post_slug = $post->post_name;
    if ($lang !== $default) {
        $translated_post_slug = get_post_meta($post->ID, '_slug_' . $lang, true);
        if ($translated_post_slug) {
            $post_slug = $translated_post_slug;
        }
    }

    if ($lang === $default) {
        return '/' . $cpt_slug . '/' . $post_slug . '/';
    }

    $cpt_slugs     = bp_get_cpt_slugs_config();
    $archive_slug  = $cpt_slugs[$cpt_slug][$lang] ?? $cpt_slug;

    return '/' . $lang . '/' . $archive_slug . '/' . $post_slug . '/';
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

    // Single blog post
    if (is_single() && get_post_type() === 'post') {
        $post = get_queried_object();

        if ($target_lang === $default) {
            return home_url('/' . $post->post_name . '/');
        }

        $translated_slug = get_post_meta($post->ID, '_slug_' . $target_lang, true);
        $slug = $translated_slug ?: $post->post_name;

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

// Load the Translator class — handles theme string translations and multilingual values.
require_once get_template_directory() . '/inc/translations/Translator.php';

/**
 * Save a single theme string translation to the database.
 *
 * @param string $key  The Dutch source text (translation key).
 * @param string $lang Language code.
 * @param string $text Translated text.
 */
function bp_save_translation($key, $lang, $text)
{
    Translator::save($key, $lang, $text);
}

/**
 * Get all theme string translations grouped by section, merging file defaults with DB overrides.
 *
 * @return array ['Section' => ['nl_key' => ['nl' => '...', 'en' => '...', ...]]]
 */
function bp_get_grouped_theme_translations()
{
    return Translator::grouped();
}

/**
 * Translate a static theme string.
 *
 * Usage in templates:
 *   <h1><?php echo bp__('Welkom'); ?></h1>
 *   // Outputs "Welcome" if lang=en
 *
 * @param string $text The default-language (Dutch) text.
 * @return string Translated text, or original if no translation found.
 */
function bp__($text)
{
    return Translator::translate($text);
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
    return Translator::value($val);
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
    return Translator::attr($attributes, $key);
}

// ---------------------------------------------------------------------------
// SEO: <html lang=""> Attribute
// ---------------------------------------------------------------------------

/**
 * Override the <html lang=""> attribute to match the current visitor language.
 *
 * Without this, WordPress always outputs the site's default locale (e.g., nl-NL)
 * even when the visitor is viewing /en/about-us/. This is important for:
 * - Screen readers (accessibility)
 * - Search engine language detection
 * - Browser translation prompts
 */
function bp_override_language_attributes($output)
{
    $lang   = bp_get_lang();
    $config = bp_get_languages_config();
    $locale = $config[$lang]['locale'] ?? $lang;

    // Convert locale format: nl_NL → nl-NL (HTML uses hyphens)
    $html_lang = str_replace('_', '-', $locale);

    return preg_replace('/lang="[^"]*"/', 'lang="' . esc_attr($html_lang) . '"', $output);
}
add_filter('language_attributes', 'bp_override_language_attributes');

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
        // Use full locale with hyphen (e.g., fr-BE, nl-NL) so Google can
        // distinguish regional variants. Google supports both "fr" and "fr-BE".
        $hreflang = strtolower(str_replace('_', '-', $locale));

        echo '<link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url($url) . '" />' . "\n";
    }

    // x-default points to the default language version
    $default_url = bp_lang_url($default);
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
}
add_action('wp_head', 'bp_hreflang_tags');

// ---------------------------------------------------------------------------
// SEO: Canonical URL
// ---------------------------------------------------------------------------

/**
 * Remove WordPress's default canonical tag.
 *
 * WordPress generates a canonical URL based on the page's real slug,
 * which ignores the language prefix. For example, on /en/about-us/
 * it would output <link rel="canonical" href="https://site.com/over-ons/" />
 * — pointing to the default language version instead of the English one.
 *
 * We replace it with our own language-aware canonical below.
 */
remove_action('wp_head', 'rel_canonical');

/**
 * Output a language-aware canonical URL tag.
 *
 * Ensures each language version of a page has a canonical pointing to itself,
 * not to the default language version. This prevents Google from treating
 * translated pages as duplicates.
 */
function bp_canonical_tag()
{
    $lang = bp_get_lang();
    $url  = bp_lang_url($lang);

    echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
}
add_action('wp_head', 'bp_canonical_tag');

// ---------------------------------------------------------------------------
// Page Slug Meta Box
// ---------------------------------------------------------------------------

/**
 * Register the SEO & translations meta box on the page editor.
 */
function bp_seo_meta_box()
{
    $post_types = array_merge(['page'], array_keys(get_post_types(['_builtin' => false, 'public' => true])));

    foreach ($post_types as $pt) {
        add_meta_box(
            'bp_seo_translations',
            'SEO & Translations',
            'bp_seo_meta_box_html',
            $pt,
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'bp_seo_meta_box');

/**
 * Render the SEO & translations meta box.
 *
 * Shows per non-default language:
 * - URL slug
 * - Title tag
 * - Meta description
 */
function bp_seo_meta_box_html($post)
{
    $langs   = bp_get_supported_langs();
    $default = bp_get_default_lang();
    $config  = bp_get_languages_config();

    wp_nonce_field('bp_save_seo_meta', 'bp_seo_nonce');

    echo '<p class="description">Leave fields empty to use the default language values.</p>';

    echo '<table class="widefat" style="border:0;box-shadow:none;">';
    echo '<thead><tr>';
    echo '<th style="width:50px;">Lang</th>';
    echo '<th>URL Slug</th>';
    echo '<th>Title Tag</th>';
    echo '<th>Meta Description</th>';
    echo '</tr></thead><tbody>';

    foreach ($langs as $lang) {
        if ($lang === $default) {
            continue;
        }

        $label     = $config[$lang]['label'] ?? strtoupper($lang);
        $slug_val  = get_post_meta($post->ID, '_slug_' . $lang, true);
        $title_val = get_post_meta($post->ID, '_title_' . $lang, true);
        $desc_val  = get_post_meta($post->ID, '_meta_desc_' . $lang, true);

        echo '<tr>';
        echo '<td><strong>' . esc_html($label) . '</strong></td>';

        echo '<td>';
        echo '<input type="text" name="bp_slug_' . esc_attr($lang) . '" ';
        echo 'value="' . esc_attr($slug_val) . '" ';
        echo 'placeholder="' . esc_attr($post->post_name) . '" ';
        echo 'style="width:100%;" />';
        echo '</td>';

        echo '<td>';
        echo '<input type="text" name="bp_title_' . esc_attr($lang) . '" ';
        echo 'value="' . esc_attr($title_val) . '" ';
        echo 'placeholder="' . esc_attr(get_the_title($post)) . '" ';
        echo 'style="width:100%;" maxlength="60" />';
        echo '</td>';

        echo '<td>';
        echo '<textarea name="bp_meta_desc_' . esc_attr($lang) . '" ';
        echo 'placeholder="Meta description..." ';
        echo 'style="width:100%;height:40px;resize:vertical;" maxlength="155">';
        echo esc_textarea($desc_val);
        echo '</textarea>';
        echo '</td>';

        echo '</tr>';
    }

    echo '</tbody></table>';
}

/**
 * Save the SEO & translation meta when the post is saved.
 */
function bp_save_seo_meta($post_id)
{
    if (! isset($_POST['bp_seo_nonce']) || ! wp_verify_nonce($_POST['bp_seo_nonce'], 'bp_save_seo_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $langs   = bp_get_supported_langs();
    $default = bp_get_default_lang();

    foreach ($langs as $lang) {
        if ($lang === $default) {
            continue;
        }

        $slug_key = 'bp_slug_' . $lang;
        if (isset($_POST[$slug_key])) {
            $slug = sanitize_title($_POST[$slug_key]);
            if ($slug) {
                update_post_meta($post_id, '_slug_' . $lang, $slug);
            } else {
                delete_post_meta($post_id, '_slug_' . $lang);
            }
        }

        $title_key = 'bp_title_' . $lang;
        if (isset($_POST[$title_key])) {
            $title = sanitize_text_field($_POST[$title_key]);
            if ($title) {
                update_post_meta($post_id, '_title_' . $lang, $title);
            } else {
                delete_post_meta($post_id, '_title_' . $lang);
            }
        }

        $desc_key = 'bp_meta_desc_' . $lang;
        if (isset($_POST[$desc_key])) {
            $desc = sanitize_text_field($_POST[$desc_key]);
            if ($desc) {
                update_post_meta($post_id, '_meta_desc_' . $lang, $desc);
            } else {
                delete_post_meta($post_id, '_meta_desc_' . $lang);
            }
        }
    }
}
add_action('save_post', 'bp_save_seo_meta');

// ---------------------------------------------------------------------------
// SEO: Translated Title Tag
// ---------------------------------------------------------------------------

/**
 * Override the document title for non-default languages.
 */
function bp_translate_document_title($title)
{
    $lang = bp_get_lang();

    if ($lang === bp_get_default_lang()) {
        return $title;
    }

    if (is_singular() || is_page()) {
        $post = get_queried_object();
        if ($post) {
            $translated = get_post_meta($post->ID, '_title_' . $lang, true);
            if ($translated) {
                $title['title'] = $translated;
            }
        }
    }

    return $title;
}
add_filter('document_title_parts', 'bp_translate_document_title');

// ---------------------------------------------------------------------------
// SEO: Translated Meta Description
// ---------------------------------------------------------------------------

/**
 * Output a meta description tag based on the current language.
 */
function bp_meta_description()
{
    if (! is_singular() && ! is_page()) {
        return;
    }

    $post = get_queried_object();
    if (! $post) {
        return;
    }

    $lang    = bp_get_lang();
    $default = bp_get_default_lang();

    if ($lang !== $default) {
        $desc = get_post_meta($post->ID, '_meta_desc_' . $lang, true);
    } else {
        $desc = get_post_meta($post->ID, '_meta_desc_' . $default, true);
    }

    if (! $desc) {
        $desc = $post->post_excerpt;
    }
    if (! $desc) {
        $desc = wp_trim_words(wp_strip_all_tags($post->post_content), 25, '...');
    }

    if ($desc) {
        echo '<meta name="description" content="' . esc_attr($desc) . '" />' . "\n";
    }
}
add_action('wp_head', 'bp_meta_description', 1);
