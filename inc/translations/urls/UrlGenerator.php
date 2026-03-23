<?php

/**
 * Builds language-aware URLs.
 *
 * Every URL on the site needs to include the language prefix for non-default
 * languages. This class handles that for all URL types:
 * - Internal URLs: /contact/ → /en/contact/
 * - Page URLs with translated slugs: /over-ons/ → /en/about-us/
 * - CPT archive URLs: /diensten/ → /en/services/
 * - CPT single URLs: /diensten/my-post/ → /en/services/my-post/
 * - Language switcher URLs: current page in a different language
 * - Nav menu item URLs
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

class UrlGenerator
{
    /**
     * Cached CPT slug translations config.
     *
     * @var array|null
     */
    private static ?array $cptSlugs = null;

    /**
     * Load and cache the CPT slug translations config.
     *
     * Returns the array from config/slugs-cpt.php, e.g.:
     *   ['diensten' => ['en' => 'services', 'de' => 'dienstleistungen']]
     *
     * @return array
     */
    public static function cptSlugsConfig(): array
    {
        if (self::$cptSlugs === null) {
            self::$cptSlugs = require get_template_directory() . '/inc/translations/config/slugs-cpt.php';
        }

        return self::$cptSlugs;
    }

    /**
     * Add the current language prefix to any internal URL.
     *
     * Examples (if current lang is 'en'):
     *   url('/contact/')           → /en/contact/
     *   url('/producten/my-item/') → /en/producten/my-item/
     *
     * If current lang is default ('nl'): returns the URL unchanged.
     * If the URL already has a language prefix: returns unchanged (no double-prefix).
     *
     * @param string $url Relative or absolute URL.
     * @return string
     */
    public static function url(string $url): string
    {
        $lang    = LocaleManager::current();
        $default = LocaleManager::default();

        if ($lang === $default) {
            return $url;
        }

        $langs       = LocaleManager::supported();
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
     * Looks up the page's translated slug from post meta (_slug_{lang}).
     *
     * Examples (if current lang is 'en'):
     *   pageUrl('over-ons') → /en/about-us/  (if _slug_en = 'about-us')
     *   pageUrl('contact')  → /en/contact/   (no translation found, uses original)
     *
     * @param string $default_slug The default language (Dutch) page slug.
     * @return string
     */
    public static function pageUrl(string $default_slug): string
    {
        $lang    = LocaleManager::current();
        $default = LocaleManager::default();

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
     * Build a translated CPT archive URL for the current language.
     *
     * Uses the slugs-cpt.php config to translate the archive slug.
     *
     * Examples (if current lang is 'en'):
     *   cptUrl('diensten') → /en/services/  (from config)
     *   cptUrl('producten') → /en/producten/ (no translation in config)
     *
     * @param string $cpt_slug The default-language CPT slug.
     * @return string
     */
    public static function cptUrl(string $cpt_slug): string
    {
        $lang    = LocaleManager::current();
        $default = LocaleManager::default();

        if ($lang === $default) {
            return '/' . $cpt_slug . '/';
        }

        $cpt_slugs  = self::cptSlugsConfig();
        $translated = $cpt_slugs[$cpt_slug][$lang] ?? $cpt_slug;

        return '/' . $lang . '/' . $translated . '/';
    }

    /**
     * Build a translated CPT single post URL for the current language.
     *
     * Translates both the archive segment (from config) and optionally
     * the post slug (from _slug_{lang} meta).
     *
     * Examples (if current lang is 'en'):
     *   cptSingleUrl($post, 'diensten') → /en/services/my-service/
     *
     * @param int|WP_Post $post     Post ID or object.
     * @param string      $cpt_slug The default-language CPT archive slug.
     * @return string
     */
    public static function cptSingleUrl($post, string $cpt_slug): string
    {
        $post = get_post($post);
        if (! $post) {
            return '';
        }

        $lang    = LocaleManager::current();
        $default = LocaleManager::default();

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

        $cpt_slugs    = self::cptSlugsConfig();
        $archive_slug = $cpt_slugs[$cpt_slug][$lang] ?? $cpt_slug;

        return '/' . $lang . '/' . $archive_slug . '/' . $post_slug . '/';
    }

    /**
     * Build a translated URL for a nav menu item.
     *
     * Handles pages (uses translated slug), CPT singles, and fallback.
     *
     * @param WP_Post $item Nav menu item object.
     * @return string Translated URL.
     */
    public static function navItemUrl($item): string
    {
        // Page menu items → use translated slug.
        if ('post_type' === $item->type && 'page' === $item->object) {
            $page = get_post($item->object_id);
            if ($page) {
                if ((int) get_option('page_on_front') === $page->ID) {
                    return self::url('/');
                }
                return self::pageUrl($page->post_name);
            }
        }

        // CPT single menu items → use translated archive + post slug.
        if ('post_type' === $item->type && 'page' !== $item->object) {
            $post = get_post($item->object_id);
            if ($post) {
                return self::cptSingleUrl($post, $post->post_type);
            }
        }

        // Everything else → add language prefix.
        return self::url($item->url);
    }

    /**
     * Get the URL for switching to a different language on the current page.
     *
     * Used in the language switcher. Figures out what the current page is
     * and builds the equivalent URL for the target language.
     *
     * @param string $target_lang Language code to switch to (e.g., 'en').
     * @return string Full URL for that language.
     */
    public static function langUrl(string $target_lang): string
    {
        $default = LocaleManager::default();

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
                return home_url('/' . $page->post_name . '/');
            }

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
            $post      = get_queried_object();
            $cpt_slug  = $post->post_type;
            $cpt_slugs = self::cptSlugsConfig();

            if ($target_lang === $default) {
                return home_url('/' . $cpt_slug . '/' . $post->post_name . '/');
            }

            $archive_slug = $cpt_slugs[$cpt_slug][$target_lang] ?? $cpt_slug;

            return home_url('/' . $target_lang . '/' . $archive_slug . '/' . $post->post_name . '/');
        }

        // CPT archive
        if (is_post_type_archive()) {
            $cpt_slug  = get_queried_object()->name;
            $cpt_slugs = self::cptSlugsConfig();

            if ($target_lang === $default) {
                return home_url('/' . $cpt_slug . '/');
            }

            $archive_slug = $cpt_slugs[$cpt_slug][$target_lang] ?? $cpt_slug;

            return home_url('/' . $target_lang . '/' . $archive_slug . '/');
        }

        // Fallback: just prefix the current path
        $path = wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip existing language prefix if present
        $current_lang = LocaleManager::current();
        if ($current_lang !== $default) {
            $path = preg_replace('#^/' . $current_lang . '/#', '/', $path);
        }

        if ($target_lang === $default) {
            return home_url($path);
        }

        return home_url('/' . $target_lang . rtrim($path, '/') . '/');
    }
}
