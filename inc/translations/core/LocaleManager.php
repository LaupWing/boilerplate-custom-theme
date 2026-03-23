<?php

/**
 * Manages locale detection and language configuration.
 *
 * Responsible for:
 * - Loading the languages config (config/languages.php)
 * - Detecting the current language from the URL
 * - Providing the list of supported languages
 * - Identifying the default language
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

class LocaleManager
{
    /**
     * Cached languages config array.
     *
     * @var array|null
     */
    private static ?array $config = null;

    /**
     * Language override (e.g. for REST API rendering).
     *
     * @var string|null
     */
    private static ?string $override = null;

    /**
     * Temporarily force a language (e.g. for REST API rendering).
     *
     * @param string|null $lang Language code, or null to clear the override.
     */
    public static function setOverride(?string $lang): void
    {
        self::$override = $lang;
    }

    /**
     * Load and cache the languages config.
     *
     * Returns the full array from config/languages.php, e.g.:
     *   ['nl' => ['label' => 'NL', 'locale' => 'nl_NL', 'default' => true], ...]
     *
     * @return array
     */
    public static function config(): array
    {
        if (self::$config === null) {
            self::$config = require get_template_directory() . '/inc/translations/config/languages.php';
        }

        return self::$config;
    }

    /**
     * Get array of supported language codes.
     *
     * @return array e.g. ['nl', 'en', 'de', 'fr', 'es']
     */
    public static function supported(): array
    {
        return array_keys(self::config());
    }

    /**
     * Get the default language code (the one with 'default' => true in config).
     *
     * @return string e.g. 'nl'
     */
    public static function default(): string
    {
        foreach (self::config() as $code => $lang) {
            if (! empty($lang['default'])) {
                return $code;
            }
        }

        // Fallback to first language
        return self::supported()[0];
    }

    /**
     * Get the current language from the URL.
     *
     * Detection order:
     *   1. WordPress query var 'lang' (set by rewrite rules)
     *   2. First segment of the URL path (handles 404s and edge cases)
     *   3. Falls back to the default language
     *
     * @return string e.g. 'en'
     */
    public static function current(): string
    {
        // 0. Check override (e.g. REST API rendering)
        if (self::$override !== null) {
            return self::$override;
        }

        // 1. Check query var (set by rewrite rules)
        $lang = get_query_var('lang', '');

        if ($lang && in_array($lang, self::supported(), true)) {
            return $lang;
        }

        // 2. Fallback: detect language from URL path (e.g., /en/about-us/)
        $path = trim(wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        $first_segment = explode('/', $path)[0] ?? '';

        if ($first_segment && in_array($first_segment, self::supported(), true)) {
            return $first_segment;
        }

        // 3. Default language
        return self::default();
    }

    /**
     * Check if the current language matches a given language code.
     *
     * @param string $lang Language code to check (e.g., 'en')
     * @return bool
     */
    public static function is(string $lang): bool
    {
        return self::current() === $lang;
    }
}
