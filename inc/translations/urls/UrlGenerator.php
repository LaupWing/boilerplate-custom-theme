<?php

/**
 * Builds language-aware URLs.
 *
 * Adds language prefixes for non-default languages.
 * Default language (Dutch) has no prefix.
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
     */
    private static ?array $cptSlugs = null;

    /**
     * Load and cache the CPT slug translations config.
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
     * Get the URL for switching to a different language on the current page.
     * Strips existing prefix and adds the target language prefix.
     */
    public static function langUrl(string $target_lang): string
    {
        $default     = LocaleManager::default();
        $langs       = LocaleManager::supported();
        $current_url = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip any existing language prefix
        $non_default = array_diff($langs, [$default]);
        if (! empty($non_default)) {
            $pattern  = '#^/(' . implode('|', $non_default) . ')(/|$)#';
            $current_url = preg_replace($pattern, '/', $current_url);
        }
        if (empty($current_url)) {
            $current_url = '/';
        }

        if ($target_lang === $default) {
            return home_url($current_url);
        }

        return home_url('/' . $target_lang . $current_url);
    }

    /**
     * Add the current language prefix to any internal URL.
     *
     * If current lang is default ('nl'): returns the URL unchanged.
     * If the URL already has a language prefix: returns unchanged.
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
}
