<?php

/**
 * Handles language-aware URL routing in WordPress.
 *
 * This is the piece that makes WordPress understand URLs like /en/about-us/.
 * Without this, WordPress only knows about the default Dutch slugs.
 *
 * What it does:
 * 1. Registers 'lang' as a WordPress query variable
 * 2. Creates rewrite rules so /en/... URLs work
 * 3. Resolves translated slugs back to real WordPress slugs
 * 4. Fixes front page loading for non-default languages
 * 5. Prevents WordPress from redirecting translated URLs to Dutch ones
 *
 * How rewrite rules work (simplified):
 *   User visits: /en/about-us/
 *   → WordPress rewrite rule matches: ^en/(.+?)/?$
 *   → Sets query vars: lang=en, pagename=about-us
 *   → resolveSlug() intercepts: looks up _slug_en="about-us" in post meta
 *   → Finds the page with real slug "over-ons"
 *   → Swaps pagename to "over-ons"
 *   → WordPress loads the correct page
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
     *
     * Called once from language.php to wire everything up.
     */
    public static function register(): void
    {
        add_filter('query_vars', [self::class, 'registerQueryVars']);
        add_action('init', [self::class, 'registerRewriteRules']);
        add_action('after_switch_theme', 'flush_rewrite_rules');
        add_filter('request', [self::class, 'fixFrontPage']);
        add_filter('request', [self::class, 'resolveSlug']);
        add_filter('redirect_canonical', [self::class, 'preventCanonicalRedirect']);
    }

    /**
     * Register 'lang' as a query variable so WordPress recognizes it.
     *
     * Without this, get_query_var('lang') would always return empty
     * even if the rewrite rules set it.
     *
     * @param array $vars Existing query vars.
     * @return array
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
     *   /en/some-page/          → page with lang=en (catch-all)
     *   /en/products/           → CPT archive with lang=en (from config)
     *   /en/products/some-post/ → CPT single with lang=en (from config)
     *
     * Rules are registered with 'top' priority so they are checked
     * before WordPress default rules.
     */
    public static function registerRewriteRules(): void
    {
        $default   = LocaleManager::default();
        $langs     = LocaleManager::supported();
        $cpt_slugs = UrlGenerator::cptSlugsConfig();

        foreach ($langs as $lang) {
            // Skip default language — it has no URL prefix
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

                    // /en/products/ → CPT archive
                    add_rewrite_rule(
                        "^{$lang}/{$translated_slug}/?$",
                        'index.php?lang=' . $lang . '&post_type=' . $dutch_slug,
                        'top'
                    );

                    // /en/products/my-post/ → CPT single
                    add_rewrite_rule(
                        "^{$lang}/{$translated_slug}/([^/]+)/?$",
                        'index.php?lang=' . $lang . '&post_type=' . $dutch_slug . '&name=$matches[1]',
                        'top'
                    );

                    // /en/products/page/2/ → CPT archive pagination
                    add_rewrite_rule(
                        "^{$lang}/{$translated_slug}/page/([0-9]+)/?$",
                        'index.php?lang=' . $lang . '&post_type=' . $dutch_slug . '&paged=$matches[1]',
                        'top'
                    );
                }
            }

            // Explicit rules for each page with a translated slug.
            // Uses page_id to avoid WP's pagename→attachment fallback.
            //
            // WHY: When the catch-all rule sets pagename=translated-slug,
            // WordPress's parse_request() looks up the page by that slug.
            // If no page has that actual WP slug (because the real slug is
            // different, e.g. "about" not "about-us"), WP silently converts
            // it to attachment=translated-slug → 404.
            // Using page_id bypasses the slug lookup entirely.
            $pages_with_slugs = get_posts([
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => '_slug_' . $lang,
                'meta_compare'   => 'EXISTS',
            ]);

            foreach ($pages_with_slugs as $page) {
                $translated_slug = get_post_meta($page->ID, '_slug_' . $lang, true);
                if ($translated_slug) {
                    add_rewrite_rule(
                        "^{$lang}/{$translated_slug}/?$",
                        'index.php?lang=' . $lang . '&page_id=' . $page->ID,
                        'top'
                    );
                }
            }

            // Catch-all for pages without explicit translated slugs
            add_rewrite_rule(
                "^{$lang}/(.+?)/?$",
                'index.php?lang=' . $lang . '&pagename=$matches[1]',
                'top'
            );
        }
    }

    /**
     * Fix front page loading for non-default languages.
     *
     * When visiting /en/ the rewrite sets lang=en but no page is specified.
     * WordPress doesn't know to show the front page. This filter injects
     * the front page ID so it loads correctly.
     *
     * @param array $query_vars WordPress query vars.
     * @return array
     */
    public static function fixFrontPage(array $query_vars): array
    {
        $lang    = $query_vars['lang'] ?? '';
        $default = LocaleManager::default();

        if ($lang && $lang !== $default) {
            $has_page = ! empty($query_vars['pagename']) || ! empty($query_vars['page_id']);
            $has_post = ! empty($query_vars['name']) || ! empty($query_vars['p']) || ! empty($query_vars['post_type']);

            if (! $has_page && ! $has_post) {
                $front_page_id = get_option('page_on_front');

                if ($front_page_id) {
                    $query_vars['page_id'] = $front_page_id;
                }
            }
        }

        return $query_vars;
    }

    /**
     * Resolve translated page slugs to their actual WordPress page.
     *
     * When the catch-all rewrite rule sets pagename=about-us (translated slug),
     * WordPress doesn't know that page — the real slug is "over-ons".
     * This filter intercepts the query, looks up the real slug via post meta,
     * and swaps it in.
     *
     * Handles both flat and nested (child) page slugs:
     *   /en/about-us/       → pagename=about-us    → resolves to "over-ons"
     *   /en/about-us/team/  → pagename=about-us/team → resolves segment by segment
     *
     * @param array $query_vars WordPress query vars.
     * @return array
     */
    public static function resolveSlug(array $query_vars): array
    {
        $lang = $query_vars['lang'] ?? '';

        // Only for non-default languages
        if (! $lang || $lang === LocaleManager::default()) {
            return $query_vars;
        }

        // Only if a pagename is set (catch-all rule)
        if (empty($query_vars['pagename'])) {
            return $query_vars;
        }

        $requested_path = $query_vars['pagename'];
        $segments = explode('/', $requested_path);

        // Try the full path first (flat page or blog post, most common case)
        if (count($segments) === 1) {
            $page = self::findPageBySlug($segments[0], $lang);
            if ($page) {
                $query_vars['pagename'] = $page->post_name;
                return $query_vars;
            }

            // Check blog posts too
            $post = self::findPostBySlug($segments[0], $lang);
            if ($post) {
                unset($query_vars['pagename']);
                $query_vars['name'] = $post->post_name;
                $query_vars['post_type'] = 'post';
                return $query_vars;
            }

            return $query_vars;
        }

        // Nested path: translate each segment back to the real slug
        $real_segments = [];
        foreach ($segments as $segment) {
            $page = self::findPageBySlug($segment, $lang);
            if ($page) {
                $real_segments[] = $page->post_name;
            } else {
                $real_segments[] = $segment;
            }
        }

        $query_vars['pagename'] = implode('/', $real_segments);

        return $query_vars;
    }

    /**
     * Prevent WordPress from redirecting translated URLs to the canonical (Dutch) URL.
     *
     * Without this, visiting /en/about-us/ would redirect to /over-ons/
     * because WordPress thinks the "real" URL is the Dutch one.
     *
     * @param string|false $redirect_url The URL WordPress wants to redirect to.
     * @return string|false
     */
    public static function preventCanonicalRedirect($redirect_url)
    {
        $lang = get_query_var('lang', '');

        if ($lang && $lang !== LocaleManager::default()) {
            return false;
        }

        return $redirect_url;
    }

    /**
     * Find a page by its translated slug.
     *
     * Looks in post meta for _slug_{lang} = $slug.
     *
     * @param string $slug The translated slug.
     * @param string $lang The language code.
     * @return WP_Post|null
     */
    private static function findPageBySlug(string $slug, string $lang): ?\WP_Post
    {
        $pages = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'meta_key'       => '_slug_' . $lang,
            'meta_value'     => $slug,
            'posts_per_page' => 1,
        ]);

        return ! empty($pages) ? $pages[0] : null;
    }

    /**
     * Find a blog post by its translated slug.
     *
     * Looks in post meta for _slug_{lang} = $slug.
     *
     * @param string $slug The translated slug.
     * @param string $lang The language code.
     * @return WP_Post|null
     */
    private static function findPostBySlug(string $slug, string $lang): ?\WP_Post
    {
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'meta_key'       => '_slug_' . $lang,
            'meta_value'     => $slug,
            'posts_per_page' => 1,
        ]);

        return ! empty($posts) ? $posts[0] : null;
    }
}
