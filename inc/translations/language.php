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
