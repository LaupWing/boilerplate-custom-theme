<?php

/**
 * Open Graph and Twitter Card meta tags with language support.
 *
 * Outputs og: and twitter: meta tags with the correct language
 * and translated content for the current page.
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Output Open Graph and Twitter Card meta tags in the <head>.
 */
function snel_open_graph()
{
    $lang    = snel_get_lang();
    $default = snel_get_default_lang();
    $config  = snel_get_languages_config();
    $locale  = $config[$lang]['locale'] ?? 'en_US';

    $site_name = get_bloginfo('name');
    $url       = snel_lang_url($lang);

    // Determine title
    $title = $site_name;
    if (is_singular() || is_page()) {
        $post = get_queried_object();
        if ($post) {
            $translated_title = ($lang !== $default)
                ? get_post_meta($post->ID, '_title_' . $lang, true)
                : '';
            $title = $translated_title ?: get_the_title($post);
        }
    }

    // Determine description
    $description = get_bloginfo('description');
    if (is_singular() || is_page()) {
        $post = get_queried_object();
        if ($post) {
            $desc = ($lang !== $default)
                ? get_post_meta($post->ID, '_meta_desc_' . $lang, true)
                : get_post_meta($post->ID, '_meta_desc_' . $default, true);

            if (! $desc) {
                $desc = $post->post_excerpt;
            }
            if (! $desc) {
                $desc = wp_trim_words(wp_strip_all_tags($post->post_content), 25, '...');
            }
            if ($desc) {
                $description = $desc;
            }
        }
    }

    // Determine image
    $image     = '';
    $image_w   = '';
    $image_h   = '';
    if (is_singular() || is_page()) {
        $post = get_queried_object();
        if ($post && has_post_thumbnail($post)) {
            $img = wp_get_attachment_image_src(get_post_thumbnail_id($post), 'full');
            if ($img) {
                $image   = $img[0];
                $image_w = $img[1];
                $image_h = $img[2];
            }
        }
    }

    // Determine type
    $type = is_front_page() ? 'website' : 'article';

    // --- Open Graph ---
    echo '<meta property="og:type" content="' . esc_attr($type) . '" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($locale) . '" />' . "\n";

    // Alternate locales for other languages
    foreach ($config as $code => $lang_config) {
        if ($code === $lang) {
            continue;
        }
        $alt_locale = $lang_config['locale'] ?? $code;
        echo '<meta property="og:locale:alternate" content="' . esc_attr($alt_locale) . '" />' . "\n";
    }

    if ($image) {
        echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
        if ($image_w && $image_h) {
            echo '<meta property="og:image:width" content="' . esc_attr($image_w) . '" />' . "\n";
            echo '<meta property="og:image:height" content="' . esc_attr($image_h) . '" />' . "\n";
        }
    }

    // --- Twitter Card ---
    echo '<meta name="twitter:card" content="' . ($image ? 'summary_large_image' : 'summary') . '" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";

    if ($image) {
        echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
    }
}
add_action('wp_head', 'snel_open_graph', 2);
