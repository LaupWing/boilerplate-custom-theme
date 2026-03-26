<?php

/**
 * Handles all translation lookups — theme strings, block attributes,
 * product titles, term names, and CPT fields.
 *
 * Data sources:
 * - Theme strings: DB (snel_theme_translations) → file (translations.php) → original
 * - Term names: term_meta _name_{lang} → original term name
 * - Product titles: post_meta _title_{lang} → post_title
 * - CPT fields: post_meta value as {nl: '...', en: '...'} array → default lang value
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

class Translator
{
    private static ?array $fileTranslations = null;
    private static ?array $dbTranslations = null;

    /**
     * Translate a static theme string.
     * snel__('Zoeken') → 'Search' when lang=en
     */
    public static function translate(string $text): string
    {
        $lang    = LocaleManager::current();
        $default = LocaleManager::default();

        // Default language — check for NL override in DB
        if ($lang === $default) {
            $db  = self::dbTranslations();
            $key = self::findKey($db, $text);
            $override = $key !== null ? ($db[$key]['nl'] ?? '') : '';
            return !empty($override) ? $override : $text;
        }

        // 1. Check database (case-insensitive)
        $db  = self::dbTranslations();
        $key = self::findKey($db, $text);
        if ($key !== null && !empty($db[$key][$lang])) {
            return $db[$key][$lang];
        }

        // 2. Check file defaults (case-insensitive)
        $file = self::fileTranslations();
        $key  = self::findKey($file, $text);
        if ($key !== null && !empty($file[$key][$lang])) {
            return $file[$key][$lang];
        }

        // 3. Fallback — return original
        return $text;
    }

    /**
     * Case-insensitive key lookup in a translations array.
     */
    private static function findKey(array $translations, string $text): ?string
    {
        // Exact match first (fast path).
        if (isset($translations[$text])) {
            return $text;
        }

        // Normalize: decode HTML entities, case-insensitive.
        $normalized = mb_strtolower(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        foreach ($translations as $key => $val) {
            if (mb_strtolower(html_entity_decode($key, ENT_QUOTES | ENT_HTML5, 'UTF-8')) === $normalized) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Extract the current language value from a multilingual array.
     * Input: ['nl' => 'Welkom', 'en' => 'Welcome'] → 'Welcome' when lang=en
     * Plain strings pass through unchanged.
     */
    public static function value($val)
    {
        if (is_array($val) && !isset($val[0])) {
            $lang    = LocaleManager::current();
            $default = LocaleManager::default();
            $translated = $val[$lang] ?? '';
            return (!empty($translated)) ? $translated : ($val[$default] ?? '');
        }
        return $val;
    }

    /**
     * Get a translated block attribute value.
     * snel_attr($attributes, 'heading') → extracts current lang from the attribute
     */
    public static function attr(array $attributes, string $key)
    {
        $val = $attributes[$key] ?? [];
        return self::value($val);
    }

    /**
     * Get a translated taxonomy term name.
     * Checks term_meta _name_{lang}, falls back to original term name.
     */
    public static function termName($term): string
    {
        $lang    = LocaleManager::current();
        $default = LocaleManager::default();

        if ($lang === $default) {
            return $term->name;
        }

        $translated = get_term_meta($term->term_id, '_name_' . $lang, true);
        return (!empty($translated)) ? $translated : $term->name;
    }

    /**
     * Get a translated taxonomy term description.
     * Checks term_meta _desc_{lang}, falls back to original description.
     */
    public static function termDesc($term): string
    {
        $lang    = LocaleManager::current();
        $default = LocaleManager::default();

        if ($lang === $default) {
            return $term->description;
        }

        $translated = get_term_meta($term->term_id, '_desc_' . $lang, true);
        return (!empty($translated)) ? $translated : $term->description;
    }

    /**
     * Get a translated product/CPT title.
     * Checks post_meta _title_{lang}, falls back to post_title.
     */
    public static function productTitle($post_id): string
    {
        $lang    = LocaleManager::current();
        $default = LocaleManager::default();

        if ($lang !== $default) {
            $translated = get_post_meta($post_id, '_title_' . $lang, true);
            if (!empty($translated)) {
                return $translated;
            }
        }

        return get_the_title($post_id);
    }

    /**
     * Get a translated value from a CPT post meta field.
     * Handles both serialized {nl, en, de} arrays and plain values.
     */
    public static function cptField($post_id, string $key)
    {
        $val = get_post_meta($post_id, $key, true);
        return self::value($val);
    }

    /**
     * Save a single theme string translation to the database.
     */
    public static function save(string $key, string $lang, string $text): void
    {
        $translations = get_option('snel_theme_translations', []);
        if (empty($text)) {
            // Empty = remove from DB so file default takes over.
            if (isset($translations[$key][$lang])) {
                unset($translations[$key][$lang]);
                if (empty($translations[$key])) {
                    unset($translations[$key]);
                }
            }
        } else {
            if (!isset($translations[$key])) {
                $translations[$key] = [];
            }
            $translations[$key][$lang] = $text;
        }
        update_option('snel_theme_translations', $translations, false);
    }

    /**
     * Get theme translations grouped by section (merged: file defaults + DB overrides).
     * Used by the admin translations page.
     */
    public static function grouped(): array
    {
        $file    = get_template_directory() . '/inc/translations/translations.php';
        $grouped = file_exists($file) ? require $file : [];
        $db      = self::dbTranslations();

        foreach ($grouped as $group => &$strings) {
            foreach ($strings as $nl_key => &$langs) {
                if (isset($db[$nl_key])) {
                    foreach ($db[$nl_key] as $lang => $text) {
                        if (!empty($text)) {
                            $langs[$lang] = $text;
                        }
                    }
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
            if (!isset($file_keys[$nl_key])) {
                if (!isset($grouped['Other'])) {
                    $grouped['Other'] = [];
                }
                $grouped['Other'][$nl_key] = $translations;
            }
        }

        return $grouped;
    }

    private static function dbTranslations(): array
    {
        if (self::$dbTranslations === null) {
            self::$dbTranslations = get_option('snel_theme_translations', []);
        }
        return self::$dbTranslations;
    }

    private static function fileTranslations(): array
    {
        if (self::$fileTranslations === null) {
            self::$fileTranslations = [];

            $file = get_template_directory() . '/inc/translations/translations.php';
            if (file_exists($file)) {
                $raw = require $file;
                foreach ($raw as $group => $strings) {
                    if (is_array($strings) && !isset($strings['en']) && !isset($strings['de'])) {
                        self::$fileTranslations = array_merge(self::$fileTranslations, $strings);
                    } else {
                        self::$fileTranslations[$group] = $strings;
                    }
                }
            }
        }
        return self::$fileTranslations;
    }
}
