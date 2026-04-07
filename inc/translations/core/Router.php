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
        add_filter('request', [self::class, 'interceptLanguageUrl'], 1);
        add_filter('request', [self::class, 'fixFrontPage']);
        add_filter('request', [self::class, 'resolvePostFromPagename']);
        add_filter('redirect_canonical', [self::class, 'preventCanonicalRedirect'], 10, 2);

        // Force flush once after deploy if rules are stale.
        $rules_version = 'snel_rewrite_v5';
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
     * Intercept language URLs that WordPress mismatched.
     *
     * WordPress sometimes matches /en/slug/ against the attachment rule
     * instead of our language rewrite rules. This filter fires early and
     * overrides the query vars when it detects a language prefix in the URL.
     */
    public static function interceptLanguageUrl(array $query_vars): array
    {
        $default     = LocaleManager::default();
        $langs       = LocaleManager::supported();
        $non_default = array_diff($langs, [$default]);

        if (empty($non_default)) {
            return $query_vars;
        }

        $request = trim($_SERVER['REQUEST_URI'] ?? '', '/');
        $request = strtok($request, '?');

        $pattern = '#^(' . implode('|', $non_default) . ')(/(.*))?$#';
        if (! preg_match($pattern, $request, $matches)) {
            return $query_vars;
        }

        $lang = $matches[1];
        $path = isset($matches[3]) ? trim($matches[3], '/') : '';

        if (! empty($query_vars['lang']) && $query_vars['lang'] === $lang) {
            return $query_vars;
        }

        $new_vars = ['lang' => $lang];

        if (empty($path)) {
            return $new_vars;
        }

        if (preg_match('#^page/(\d+)$#', $path, $page_match)) {
            $new_vars['paged'] = (int) $page_match[1];
            return $new_vars;
        }

        $new_vars['pagename'] = $path;
        return $new_vars;
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
     * When the catch-all rule sets pagename but no page exists with that slug,
     * check if it's a blog post and fix the query vars accordingly.
     */
    public static function resolvePostFromPagename(array $query_vars): array
    {
        $lang = $query_vars['lang'] ?? '';

        if (! $lang || $lang === LocaleManager::default()) {
            return $query_vars;
        }

        if (empty($query_vars['pagename'])) {
            return $query_vars;
        }

        $slug = $query_vars['pagename'];

        if (get_page_by_path($slug)) {
            return $query_vars;
        }

        $post = get_page_by_path($slug, OBJECT, 'post');
        if ($post) {
            unset($query_vars['pagename']);
            $query_vars['name'] = $post->post_name;
            $query_vars['post_type'] = 'post';
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
