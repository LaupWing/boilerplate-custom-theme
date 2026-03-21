<?php

/**
 * Multilingual XML Sitemap with hreflang annotations.
 *
 * Generates a sitemap at /sitemap.xml that includes all pages
 * and CPT posts with their language alternates.
 *
 * Disables WordPress's default sitemap to avoid conflicts.
 *
 * @package Boilerplate
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Disable WordPress default sitemap so ours takes over.
 */
add_filter('wp_sitemaps_enabled', '__return_false');

/**
 * Register the sitemap rewrite rule.
 */
function bp_sitemap_rewrite()
{
    add_rewrite_rule('^sitemap\.xml$', 'index.php?bp_sitemap=1', 'top');
}
add_action('init', 'bp_sitemap_rewrite');

/**
 * Register the sitemap query var.
 */
function bp_sitemap_query_var($vars)
{
    $vars[] = 'bp_sitemap';
    return $vars;
}
add_filter('query_vars', 'bp_sitemap_query_var');

/**
 * Serve the sitemap XML when requested.
 */
function bp_sitemap_render()
{
    if (! get_query_var('bp_sitemap')) {
        return;
    }

    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    echo '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    $langs   = bp_get_supported_langs();
    $default = bp_get_default_lang();
    $config  = bp_get_languages_config();
    $cpt_slugs = bp_get_cpt_slugs_config();

    // --- Pages ---
    $pages = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    foreach ($pages as $page) {
        $urls = [];

        foreach ($langs as $lang) {
            $locale   = $config[$lang]['locale'] ?? $lang;
            $hreflang = strtolower(str_replace('_', '-', $locale));

            if ($lang === $default) {
                if ((int) get_option('page_on_front') === $page->ID) {
                    $url = home_url('/');
                } else {
                    $url = home_url('/' . $page->post_name . '/');
                }
            } else {
                if ((int) get_option('page_on_front') === $page->ID) {
                    $url = home_url('/' . $lang . '/');
                } else {
                    $translated_slug = get_post_meta($page->ID, '_slug_' . $lang, true);
                    $slug = $translated_slug ?: $page->post_name;
                    $url = home_url('/' . $lang . '/' . $slug . '/');
                }
            }

            $urls[] = ['hreflang' => $hreflang, 'url' => $url];
        }

        foreach ($urls as $entry) {
            echo "  <url>\n";
            echo '    <loc>' . esc_url($entry['url']) . "</loc>\n";
            foreach ($urls as $alt) {
                echo '    <xhtml:link rel="alternate" hreflang="' . esc_attr($alt['hreflang']) . '" href="' . esc_url($alt['url']) . '" />' . "\n";
            }
            echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . esc_url($urls[0]['url']) . '" />' . "\n";
            echo "  </url>\n";
        }
    }

    // --- Public CPTs ---
    $public_cpts = get_post_types(['_builtin' => false, 'public' => true], 'names');

    foreach ($public_cpts as $cpt) {
        $posts = get_posts([
            'post_type'      => $cpt,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ]);

        foreach ($posts as $post) {
            $urls = [];

            foreach ($langs as $lang) {
                $locale   = $config[$lang]['locale'] ?? $lang;
                $hreflang = strtolower(str_replace('_', '-', $locale));

                if ($lang === $default) {
                    $url = home_url('/' . $cpt . '/' . $post->post_name . '/');
                } else {
                    $archive_slug = $cpt_slugs[$cpt][$lang] ?? $cpt;
                    $url = home_url('/' . $lang . '/' . $archive_slug . '/' . $post->post_name . '/');
                }

                $urls[] = ['hreflang' => $hreflang, 'url' => $url];
            }

            foreach ($urls as $entry) {
                echo "  <url>\n";
                echo '    <loc>' . esc_url($entry['url']) . "</loc>\n";
                foreach ($urls as $alt) {
                    echo '    <xhtml:link rel="alternate" hreflang="' . esc_attr($alt['hreflang']) . '" href="' . esc_url($alt['url']) . '" />' . "\n";
                }
                echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . esc_url($urls[0]['url']) . '" />' . "\n";
                echo "  </url>\n";
            }
        }
    }

    echo '</urlset>';
    exit;
}
add_action('template_redirect', 'bp_sitemap_render');
