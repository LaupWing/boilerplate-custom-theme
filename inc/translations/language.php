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

// Load the UrlGenerator class — builds language-aware URLs.
require_once get_template_directory() . '/inc/translations/UrlGenerator.php';

// Load and register the Router class — rewrite rules, slug resolution, redirects.
require_once get_template_directory() . '/inc/translations/Router.php';
Router::register();

/**
 * Load and cache the CPT slug translations config.
 *
 * @return array
 */
function bp_get_cpt_slugs_config()
{
    return UrlGenerator::cptSlugsConfig();
}

// ---------------------------------------------------------------------------
// URL Helpers
// ---------------------------------------------------------------------------

/**
 * Add the current language prefix to any internal URL.
 *
 * @param string $url Relative URL (e.g., '/contact/' or '/producten/my-item/')
 * @return string
 */
function bp_url($url)
{
    return UrlGenerator::url($url);
}

/**
 * Build a translated page URL for the current language.
 *
 * @param string $default_slug The default language (Dutch) page slug.
 * @return string
 */
function bp_page_url($default_slug)
{
    return UrlGenerator::pageUrl($default_slug);
}

/**
 * Build a translated URL for a nav menu item.
 *
 * @param WP_Post $item Nav menu item object.
 * @return string Translated URL.
 */
function bp_nav_item_url($item)
{
    return UrlGenerator::navItemUrl($item);
}

/**
 * Build a translated CPT archive URL for the current language.
 *
 * @param string $cpt_slug The default-language CPT slug.
 * @return string
 */
function bp_cpt_url($cpt_slug)
{
    return UrlGenerator::cptUrl($cpt_slug);
}

/**
 * Build a translated CPT single post URL for the current language.
 *
 * @param int|WP_Post $post     Post ID or object.
 * @param string      $cpt_slug The default-language CPT archive slug.
 * @return string
 */
function bp_cpt_single_url($post, $cpt_slug)
{
    return UrlGenerator::cptSingleUrl($post, $cpt_slug);
}

/**
 * Get the URL for switching to a different language on the current page.
 *
 * @param string $target_lang Language code to switch to (e.g., 'en')
 * @return string Full URL for that language
 */
function bp_lang_url($target_lang)
{
    return UrlGenerator::langUrl($target_lang);
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
