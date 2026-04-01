<?php

/**
 * Core language routing and translation helpers.
 *
 * DO NOT EDIT per project — configure via config/ files instead.
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

// Load the LocaleManager class — single source of truth for language config/detection.
require_once get_template_directory() . '/inc/translations/core/LocaleManager.php';

/**
 * Load and cache the languages config.
 *
 * @return array
 */
function snel_get_languages_config()
{
    return LocaleManager::config();
}

/**
 * Get array of supported language codes.
 *
 * @return array e.g. ['nl', 'en']
 */
function snel_get_supported_langs()
{
    return LocaleManager::supported();
}

/**
 * Get the default language code (the one with 'default' => true).
 *
 * @return string e.g. 'nl'
 */
function snel_get_default_lang()
{
    return LocaleManager::default();
}

/**
 * Get the current language from the URL.
 * Falls back to default language if not set.
 *
 * @return string e.g. 'en'
 */
function snel_get_lang()
{
    return LocaleManager::current();
}

/**
 * Check if the current language matches a given language.
 *
 * @param string $lang Language code to check.
 * @return bool
 */
function snel_is_lang($lang)
{
    return LocaleManager::is($lang);
}

// Load the UrlGenerator class — builds language-aware URLs.
require_once get_template_directory() . '/inc/translations/urls/UrlGenerator.php';

// Load and register the Router class — rewrite rules, slug resolution, redirects.
require_once get_template_directory() . '/inc/translations/core/Router.php';
Router::register();

/**
 * Load and cache the CPT slug translations config.
 *
 * @return array
 */
function snel_get_cpt_slugs_config()
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
function snel_url($url)
{
    return UrlGenerator::url($url);
}

/**
 * Build a translated page URL for the current language.
 *
 * @param string $default_slug The default language (Dutch) page slug.
 * @return string
 */
function snel_page_url($default_slug)
{
    return UrlGenerator::pageUrl($default_slug);
}

/**
 * Build a translated URL for a nav menu item.
 *
 * @param WP_Post $item Nav menu item object.
 * @return string Translated URL.
 */
function snel_nav_item_url($item)
{
    return UrlGenerator::navItemUrl($item);
}

/**
 * Build a translated CPT archive URL for the current language.
 *
 * @param string $cpt_slug The default-language CPT slug.
 * @return string
 */
function snel_cpt_url($cpt_slug)
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
function snel_cpt_single_url($post, $cpt_slug)
{
    return UrlGenerator::cptSingleUrl($post, $cpt_slug);
}

/**
 * Get the URL for switching to a different language on the current page.
 *
 * @param string $target_lang Language code to switch to (e.g., 'en')
 * @return string Full URL for that language
 */
function snel_lang_url($target_lang)
{
    return UrlGenerator::langUrl($target_lang);
}

// ---------------------------------------------------------------------------
// Translation Helpers
// ---------------------------------------------------------------------------

// Load the Translator class — handles theme string translations and multilingual values.
require_once get_template_directory() . '/inc/translations/core/Translator.php';

/**
 * Save a single theme string translation to the database.
 *
 * @param string $key  The Dutch source text (translation key).
 * @param string $lang Language code.
 * @param string $text Translated text.
 */
function snel_save_translation($key, $lang, $text)
{
    Translator::save($key, $lang, $text);
}

/**
 * Get all theme string translations grouped by section, merging file defaults with DB overrides.
 *
 * @return array ['Section' => ['nl_key' => ['nl' => '...', 'en' => '...', ...]]]
 */
function snel_get_grouped_theme_translations()
{
    return Translator::grouped();
}

/**
 * Translate a static theme string.
 *
 * Usage in templates:
 *   <h1><?php echo snel__('Welkom'); ?></h1>
 *   // Outputs "Welcome" if lang=en
 *
 * @param string $text The default-language (Dutch) text.
 * @return string Translated text, or original if no translation found.
 */
function snel__($text)
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
function snel_val($val)
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
 *   $heading = snel_attr($attributes, 'heading');
 *
 * @param array  $attributes Block attributes array.
 * @param string $key        The attribute key.
 * @return string The translated value.
 */
function snel_attr($attributes, $key)
{
    return Translator::attr($attributes, $key);
}

/**
 * Get a translated taxonomy term name.
 *
 * @param WP_Term $term Term object.
 * @return string Translated name or original.
 */
function snel_term_name($term)
{
    return Translator::termName($term);
}

/**
 * Get a translated taxonomy term description.
 *
 * @param WP_Term $term Term object.
 * @return string Translated description or original.
 */
function snel_term_desc($term)
{
    return Translator::termDesc($term);
}

/**
 * Get a translated product/CPT title.
 *
 * @param int $post_id Post ID.
 * @return string Translated title or original.
 */
function snel_product_title($post_id)
{
    return Translator::productTitle($post_id);
}

/**
 * Get a translated value from a CPT post meta field.
 * Handles both {nl, en, de} arrays and plain values.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return mixed Translated value.
 */
function snel_cpt_field($post_id, $key)
{
    return Translator::cptField($post_id, $key);
}

// Load and register SEO manager — hreflang, canonical, meta description, html lang.
require_once get_template_directory() . '/inc/translations/seo/SeoManager.php';
SeoManager::register();

// Register slug meta fields for REST API (used by Snel Stack editor sidebar).
add_action('init', function () {
    $langs = LocaleManager::supported();
    $default = LocaleManager::default();
    $post_types = get_post_types(['public' => true]);

    foreach ($post_types as $pt) {
        foreach ($langs as $lang) {
            if ($lang === $default) continue;
            register_post_meta($pt, '_slug_' . $lang, [
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'auth_callback' => function () { return current_user_can('edit_posts'); },
            ]);
        }
    }
});

// Load AI translation AJAX handler.
require_once get_template_directory() . '/inc/translations/admin/translate.php';

// Auto-slugify: translate slugs on post publish when API key is set.
require_once get_template_directory() . '/inc/translations/auto-slug.php';

// ─── Snel SEO Integration ─────────────────────────────────────────────────

/**
 * Provide available languages to Snel SEO plugin.
 */
add_filter( 'snel_seo_languages', function () {
    $config = include get_template_directory() . '/inc/translations/config/languages.php';
    $result = array();
    foreach ( $config as $code => $lang ) {
        $result[] = array(
            'code'    => $code,
            'label'   => $lang['label'],
            'default' => ! empty( $lang['default'] ),
        );
    }
    return $result;
} );

/**
 * Tell Snel SEO what language the current visitor is viewing.
 */
add_filter( 'snel_seo_current_language', function () {
    return LocaleManager::current();
} );
