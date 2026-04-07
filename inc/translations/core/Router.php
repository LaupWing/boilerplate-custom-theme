<?php

/**
 * Handles language-aware URL routing in WordPress.
 *
 * Registers rewrite rules for language prefixes (/en/, /de/),
 * fixes front page loading, and prevents canonical redirects.
 *
 * URLs use the default (Dutch) slugs with a language prefix:
 *   /en/over-ons/  (not /en/about-us/)
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

class Router
{
    /**
     * Register all WordPress hooks for routing.
     */
    public static function register(): void
    {
        add_filter('query_vars', [self::class, 'registerQueryVars']);
        add_action('init', [self::class, 'registerRewriteRules']);
        add_action('after_switch_theme', 'flush_rewrite_rules');
        add_filter('request', [self::class, 'fixFrontPage']);
        add_filter('redirect_canonical', [self::class, 'preventCanonicalRedirect'], 10, 2);

        // Force flush once after deploy if rules are stale.
        $rules_version = 'snel_rewrite_v4';
        if (get_option($rules_version) !== '1') {
            add_action('init', function () use ($rules_version) {
                flush_rewrite_rules();
                update_option($rules_version, '1');
            }, 99);
        }
    }

    /**
     * Register 'lang' as a query variable so WordPress recognizes it.
     */
    public static function registerQueryVars(array $vars): array
    {
        $vars[] = 'lang';
        return $vars;
    }

    /**
     * Register URL rewrite rules for each non-default language.
     *
     * Creates rules so that:
     *   /en/                    → homepage with lang=en
     *   /en/some-page/          → page with lang=en
     *   /en/page/2/             → blog pagination with lang=en
     *   /en/cpt-slug/           → CPT archive with lang=en (from config)
     *   /en/cpt-slug/some-post/ → CPT single with lang=en (from config)
     */
    public static function registerRewriteRules(): void
    {
        $default   = LocaleManager::default();
        $langs     = LocaleManager::supported();
        $cpt_slugs = UrlGenerator::cptSlugsConfig();

        foreach ($langs as $lang) {
            if ($lang === $default) {
                continue;
            }

            // /en/ → homepage
            add_rewrite_rule(
                "^{$lang}/?$",
                'index.php?lang=' . $lang,
                'top'
            );

            // /en/page/2/ → blog pagination
            add_rewrite_rule(
                "^{$lang}/page/([0-9]+)/?$",
                'index.php?lang=' . $lang . '&paged=$matches[1]',
                'top'
            );

            // CPT rules from config (e.g., /en/products/, /en/products/my-post/)
            foreach ($cpt_slugs as $dutch_slug => $translations) {
                if (! empty($translations[$lang])) {
                    $translated_slug = $translations[$lang];

                    add_rewrite_rule(
                        "^{$lang}/{$translated_slug}/?$",
                        'index.php?lang=' . $lang . '&post_type=' . $dutch_slug,
                        'top'
                    );

                    add_rewrite_rule(
                        "^{$lang}/{$translated_slug}/([^/]+)/?$",
                        'index.php?lang=' . $lang . '&post_type=' . $dutch_slug . '&name=$matches[1]',
                        'top'
                    );

                    add_rewrite_rule(
                        "^{$lang}/{$translated_slug}/page/([0-9]+)/?$",
                        'index.php?lang=' . $lang . '&post_type=' . $dutch_slug . '&paged=$matches[1]',
                        'top'
                    );
                }
            }

            // Catch-all for pages
            add_rewrite_rule(
                "^{$lang}/(.+?)/?$",
                'index.php?lang=' . $lang . '&pagename=$matches[1]',
                'top'
            );
        }
    }

    /**
     * Fix front page loading for non-default languages.
     */
    public static function fixFrontPage(array $query_vars): array
    {
        $lang = $query_vars['lang'] ?? '';

        if (
            $lang &&
            $lang !== LocaleManager::default() &&
            empty($query_vars['pagename']) &&
            empty($query_vars['page_id']) &&
            empty($query_vars['p']) &&
            empty($query_vars['name']) &&
            empty($query_vars['post_type']) &&
            empty($query_vars['s'])
        ) {
            $front_page_id = get_option('page_on_front');
            if ($front_page_id) {
                $query_vars['page_id'] = $front_page_id;
            }
        }

        return $query_vars;
    }

    /**
     * Prevent WordPress from redirecting translated URLs to the canonical (Dutch) URL.
     */
    public static function preventCanonicalRedirect($redirect_url, $requested_url)
    {
        $lang = get_query_var('lang', '');

        if ($lang && $lang !== LocaleManager::default()) {
            return false;
        }

        return $redirect_url;
    }
}
