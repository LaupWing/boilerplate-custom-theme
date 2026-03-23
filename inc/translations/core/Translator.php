<?php

/**
 * Handles theme string translations and multilingual value extraction.
 *
 * Responsible for:
 * - Translating static theme strings (header, footer, UI text)
 * - Extracting the current language value from multilingual arrays
 * - Saving/loading translations from the database and file defaults
 *
 * Data flow:
 * - Theme strings are stored in two places:
 *   1. File defaults: inc/translations/translations.php (grouped by section)
 *      Format: ['Section' => ['Dutch text' => ['en' => 'English', 'de' => 'German']]]
 *   2. Database overrides: wp_option 'snel_theme_translations'
 *      Format: ['Dutch text' => ['en' => 'English', 'de' => 'German']]
 *   Database takes priority over file defaults.
 *
 * - Multilingual values (block attributes, custom fields) are arrays like:
 *   ['nl' => 'Welkom', 'en' => 'Welcome', 'de' => 'Willkommen']
 *   The value() method picks the right language from these arrays.
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

class Translator
{
    /**
     * Cached file translations (flattened from grouped format).
     *
     * @var array|null
     */
    private static ?array $fileTranslations = null;

    /**
     * Translate a static theme string.
     *
     * Used in templates like: snel__('Welkom') → returns 'Welcome' if lang=en
     *
     * Lookup order:
     *   1. Database (wp_option 'snel_theme_translations') — admin overrides
     *   2. File (inc/translations/translations.php) — developer defaults
     *   3. Original text — returns the Dutch key as-is
     *
     * @param string $text The default-language (Dutch) text.
     * @return string Translated text, or original if no translation found.
     */
    public static function translate(string $text): string
    {
        $lang = LocaleManager::current();

        // Default language — return as-is, no lookup needed
        if ($lang === LocaleManager::default()) {
            return $text;
        }

        // 1. Check database overrides
        $db = get_option('snel_theme_translations', []);
        if (! empty($db[$text][$lang])) {
            return $db[$text][$lang];
        }

        // 2. Check file defaults
        $file = self::fileTranslations();
        if (! empty($file[$text][$lang])) {
            return $file[$text][$lang];
        }

        // 3. Fallback — return original Dutch text
        return $text;
    }

    /**
     * Extract the current language value from a multilingual array.
     *
     * Input example:  ['nl' => 'Welkom', 'en' => 'Welcome', 'de' => 'Willkommen']
     * If lang=en:     returns 'Welcome'
     * If lang=fr:     falls back to 'Welkom' (default language)
     *
     * If $val is a plain string (not an array), returns it as-is.
     *
     * @param mixed $val A multilingual array or a plain string.
     * @return string The value for the current language.
     */
    public static function value(mixed $val): string
    {
        if (! is_array($val)) {
            return $val ?: '';
        }

        $lang    = LocaleManager::current();
        $default = LocaleManager::default();

        // Try current language, fall back to default
        return $val[$lang] ?? $val[$default] ?? '';
    }

    /**
     * Get a translated block attribute value.
     *
     * Shorthand for reading a key from a block's $attributes array
     * and extracting the current language value.
     *
     * Input example:
     *   $attributes = ['heading' => ['nl' => 'Welkom', 'en' => 'Welcome']]
     *   attr($attributes, 'heading') → 'Welcome' (if lang=en)
     *
     * @param array  $attributes Block attributes array.
     * @param string $key        The attribute key.
     * @return string The translated value.
     */
    public static function attr(array $attributes, string $key): string
    {
        $val = $attributes[$key] ?? '';
        return self::value($val);
    }

    /**
     * Save a single theme string translation to the database.
     *
     * @param string $key  The Dutch source text (translation key).
     * @param string $lang Language code (e.g., 'en').
     * @param string $text Translated text.
     */
    public static function save(string $key, string $lang, string $text): void
    {
        $translations = get_option('snel_theme_translations', []);

        if (! isset($translations[$key])) {
            $translations[$key] = [];
        }

        $translations[$key][$lang] = $text;

        update_option('snel_theme_translations', $translations, false);
    }

    /**
     * Get all theme string translations grouped by section,
     * merging file defaults with database overrides.
     *
     * Returns format:
     *   ['Navigation' => ['Home' => ['en' => 'Home', 'de' => 'Startseite'], ...], ...]
     *
     * Database overrides are merged on top of file defaults.
     * Any DB-only strings (not in file) appear under 'Other'.
     *
     * @return array
     */
    public static function grouped(): array
    {
        $file    = get_template_directory() . '/inc/translations/translations.php';
        $grouped = file_exists($file) ? require $file : [];

        $db = get_option('snel_theme_translations', []);

        // Merge DB overrides into the grouped structure
        foreach ($grouped as $section => &$strings) {
            foreach ($strings as $nl_key => &$translations) {
                if (isset($db[$nl_key])) {
                    $translations = array_merge($translations, $db[$nl_key]);
                }
            }
        }

        // Add any DB-only strings (not in file) under "Other"
        $file_keys = [];
        foreach ($grouped as $section => $strings) {
            foreach ($strings as $nl_key => $translations) {
                $file_keys[$nl_key] = true;
            }
        }

        foreach ($db as $nl_key => $translations) {
            if (! isset($file_keys[$nl_key])) {
                if (! isset($grouped['Other'])) {
                    $grouped['Other'] = [];
                }
                $grouped['Other'][$nl_key] = $translations;
            }
        }

        return $grouped;
    }

    /**
     * Load and cache the file-based translations (flattened).
     *
     * The file stores translations grouped by section:
     *   ['Navigation' => ['Home' => ['en' => 'Home']], 'General' => [...]]
     *
     * This flattens it to:
     *   ['Home' => ['en' => 'Home'], ...]
     *
     * @return array
     */
    private static function fileTranslations(): array
    {
        if (self::$fileTranslations === null) {
            $file    = get_template_directory() . '/inc/translations/translations.php';
            $grouped = file_exists($file) ? require $file : [];

            self::$fileTranslations = [];
            foreach ($grouped as $section => $strings) {
                foreach ($strings as $key => $translations) {
                    self::$fileTranslations[$key] = $translations;
                }
            }
        }

        return self::$fileTranslations;
    }
}
