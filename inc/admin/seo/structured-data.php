<?php

/**
 * Structured Data (JSON-LD) with language support.
 *
 * Outputs WebPage, BreadcrumbList, and Organization schema
 * with the correct inLanguage value for the current page.
 *
 * @package Boilerplate
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Output JSON-LD structured data in the <head>.
 */
function bp_structured_data()
{
    $lang    = bp_get_lang();
    $config  = bp_get_languages_config();
    $locale  = $config[$lang]['locale'] ?? $lang;
    $in_lang = str_replace('_', '-', $locale);

    $all_locales = [];
    foreach ($config as $l) {
        $all_locales[] = str_replace('_', '-', $l['locale'] ?? '');
    }

    $schema = [];

    // --- WebPage schema ---
    $webpage = [
        '@context'    => 'https://schema.org',
        '@type'       => 'WebPage',
        'url'         => bp_lang_url($lang),
        'inLanguage'  => $in_lang,
        'isPartOf'    => [
            '@type' => 'WebSite',
            'url'   => home_url('/'),
            'name'  => get_bloginfo('name'),
            'inLanguage' => $all_locales,
        ],
    ];

    if (is_singular()) {
        $post = get_queried_object();
        if ($post) {
            $webpage['name'] = get_the_title($post);
            $webpage['datePublished'] = get_the_date('c', $post);
            $webpage['dateModified']  = get_the_modified_date('c', $post);

            if (has_post_thumbnail($post)) {
                $webpage['primaryImageOfPage'] = [
                    '@type' => 'ImageObject',
                    'url'   => get_the_post_thumbnail_url($post, 'full'),
                ];
            }
        }
    }

    $schema[] = $webpage;

    // --- Organization schema (on homepage only) ---
    if (is_front_page()) {
        $schema[] = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Organization',
            'name'        => get_bloginfo('name'),
            'url'         => home_url('/'),
            'description' => get_bloginfo('description'),
        ];
    }

    // Output
    foreach ($schema as $item) {
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
}
add_action('wp_head', 'bp_structured_data');
