<?php

/**
 * Manages SEO output for multilingual pages.
 *
 * Responsible for:
 * - Setting the correct <html lang=""> attribute
 * - Outputting hreflang tags (tells Google about language variants)
 * - Outputting a language-aware canonical URL
 * - Translating the document <title> tag
 * - Outputting a translated meta description
 *
 * @package Boilerplate
 */

if (! defined('ABSPATH')) {
    exit;
}

class SeoManager
{
    /**
     * Register all WordPress hooks for SEO.
     */
    public static function register(): void
    {
        add_filter('language_attributes', [self::class, 'htmlLangAttribute']);
        add_action('wp_head', [self::class, 'hreflangTags']);

        // Replace WordPress's default canonical with our language-aware one.
        remove_action('wp_head', 'rel_canonical');
        add_action('wp_head', [self::class, 'canonicalTag']);

        add_filter('document_title_parts', [self::class, 'translateDocumentTitle']);
        add_action('wp_head', [self::class, 'metaDescription'], 1);
    }

    /**
     * Override the <html lang=""> attribute to match the current visitor language.
     *
     * Without this, WordPress always outputs the site's default locale (e.g., nl-NL)
     * even when the visitor is viewing /en/about-us/.
     *
     * @param string $output The current language_attributes output.
     * @return string
     */
    public static function htmlLangAttribute(string $output): string
    {
        $lang   = LocaleManager::current();
        $config = LocaleManager::config();
        $locale = $config[$lang]['locale'] ?? $lang;

        // Convert locale format: nl_NL → nl-NL (HTML uses hyphens)
        $html_lang = str_replace('_', '-', $locale);

        return preg_replace('/lang="[^"]*"/', 'lang="' . esc_attr($html_lang) . '"', $output);
    }

    /**
     * Output hreflang tags in the <head> for all supported languages.
     *
     * Tells search engines: "this page exists in these languages at these URLs."
     *
     * Output example:
     *   <link rel="alternate" hreflang="nl-nl" href="https://example.com/over-ons/" />
     *   <link rel="alternate" hreflang="en-us" href="https://example.com/en/about-us/" />
     *   <link rel="alternate" hreflang="x-default" href="https://example.com/over-ons/" />
     */
    public static function hreflangTags(): void
    {
        $langs   = LocaleManager::supported();
        $default = LocaleManager::default();
        $config  = LocaleManager::config();

        foreach ($langs as $lang) {
            $url    = UrlGenerator::langUrl($lang);
            $locale = $config[$lang]['locale'] ?? $lang;
            $hreflang = strtolower(str_replace('_', '-', $locale));

            echo '<link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url($url) . '" />' . "\n";
        }

        // x-default points to the default language version
        $default_url = UrlGenerator::langUrl($default);
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
    }

    /**
     * Output a language-aware canonical URL tag.
     *
     * Each language version of a page gets a canonical pointing to itself,
     * not to the default language version. This prevents Google from treating
     * translated pages as duplicates.
     */
    public static function canonicalTag(): void
    {
        $lang = LocaleManager::current();
        $url  = UrlGenerator::langUrl($lang);

        echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
    }

    /**
     * Override the document title for non-default languages.
     *
     * Looks up _title_{lang} in post meta. If found, replaces the
     * title part of the document title.
     *
     * @param array $title The document title parts.
     * @return array
     */
    public static function translateDocumentTitle(array $title): array
    {
        $lang = LocaleManager::current();

        if ($lang === LocaleManager::default()) {
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

    /**
     * Output a meta description tag based on the current language.
     *
     * Checks _meta_desc_{lang} in post meta first, then falls back to
     * the post excerpt, then to a trimmed version of the content.
     */
    public static function metaDescription(): void
    {
        if (! is_singular() && ! is_page()) {
            return;
        }

        $post = get_queried_object();
        if (! $post) {
            return;
        }

        $lang    = LocaleManager::current();
        $default = LocaleManager::default();

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
}
