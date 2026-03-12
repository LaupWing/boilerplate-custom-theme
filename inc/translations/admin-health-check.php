<?php

/**
 * Translation Health Check admin page.
 *
 * Scans the theme for missing translations and shows a report.
 * Checks: theme strings (bp__), CPT slugs, and page slug meta.
 *
 * @package Boilerplate
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register the health check admin page under Tools.
 */
function bp_health_check_menu()
{
    add_management_page(
        'Translation Health Check',
        'Translation Check',
        'manage_options',
        'bp-translation-check',
        'bp_health_check_page'
    );
}
add_action('admin_menu', 'bp_health_check_menu');

/**
 * Scan all theme PHP files for bp__('...') calls.
 *
 * @return array List of unique Dutch strings found in templates.
 */
function bp_health_check_scan_theme_strings()
{
    $theme_dir = get_template_directory();
    $strings   = [];

    // Scan root PHP files + inc/ + template-parts/
    $patterns = [
        $theme_dir . '/*.php',
        $theme_dir . '/inc/**/*.php',
        $theme_dir . '/template-parts/**/*.php',
    ];

    foreach ($patterns as $pattern) {
        $files = glob($pattern, GLOB_BRACE);
        if (! $files) {
            continue;
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Match bp__('...') and bp__("...")
            if (preg_match_all("/bp__\(\s*['\"](.+?)['\"]\s*\)/", $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $strings[$match] = str_replace($theme_dir . '/', '', $file);
                }
            }
        }
    }

    return $strings;
}

/**
 * Get all translations from the file (flattened).
 *
 * @return array ['Dutch text' => ['en' => 'English', ...]]
 */
function bp_health_check_get_file_translations()
{
    $file = get_template_directory() . '/inc/translations/translations.php';

    if (! file_exists($file)) {
        return [];
    }

    $grouped = require $file;
    $flat    = [];

    foreach ($grouped as $section => $strings) {
        foreach ($strings as $key => $translations) {
            $flat[$key] = $translations;
        }
    }

    return $flat;
}

/**
 * Render the health check admin page.
 */
function bp_health_check_page()
{
    $langs          = bp_get_supported_langs();
    $default        = bp_get_default_lang();
    $non_default    = array_filter($langs, fn($l) => $l !== $default);
    $db_translations = get_option('bp_theme_translations', []);
    $file_translations = bp_health_check_get_file_translations();

    echo '<div class="wrap">';
    echo '<h1>Translation Health Check</h1>';

    // ----- Theme Strings -----
    echo '<h2>Theme Strings</h2>';
    $theme_strings = bp_health_check_scan_theme_strings();

    if (empty($theme_strings)) {
        echo '<p>No <code>bp__()</code> calls found in theme files.</p>';
    } else {
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>Status</th><th>String</th><th>Found in</th>';
        foreach ($non_default as $l) {
            echo '<th>' . esc_html(strtoupper($l)) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($theme_strings as $text => $file) {
            $all_good = true;
            $lang_status = [];

            foreach ($non_default as $l) {
                $has_db   = ! empty($db_translations[$text][$l]);
                $has_file = ! empty($file_translations[$text][$l]);

                if ($has_db || $has_file) {
                    $lang_status[$l] = '<span style="color:green;">&#10003;</span>';
                } else {
                    $lang_status[$l] = '<span style="color:red;font-weight:bold;">MISSING</span>';
                    $all_good = false;
                }
            }

            $status = $all_good
                ? '<span style="color:green;">&#10003;</span>'
                : '<span style="color:red;">&#10007;</span>';

            echo '<tr>';
            echo '<td>' . $status . '</td>';
            echo '<td><code>' . esc_html($text) . '</code></td>';
            echo '<td>' . esc_html($file) . '</td>';
            foreach ($non_default as $l) {
                echo '<td>' . $lang_status[$l] . '</td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    // ----- CPT Slugs -----
    echo '<h2 style="margin-top:2rem;">CPT Slugs</h2>';
    $cpt_slugs = bp_get_cpt_slugs_config();
    $custom_post_types = get_post_types(['_builtin' => false, 'public' => true], 'objects');

    if (empty($custom_post_types)) {
        echo '<p>No custom post types registered.</p>';
    } else {
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>Status</th><th>CPT</th><th>Dutch Slug</th>';
        foreach ($non_default as $l) {
            echo '<th>' . esc_html(strtoupper($l)) . ' Slug</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($custom_post_types as $cpt) {
            $slug     = $cpt->name;
            $all_good = true;
            $lang_status = [];

            foreach ($non_default as $l) {
                if (! empty($cpt_slugs[$slug][$l])) {
                    $lang_status[$l] = '<code>' . esc_html($cpt_slugs[$slug][$l]) . '</code>';
                } else {
                    $lang_status[$l] = '<span style="color:red;font-weight:bold;">MISSING</span>';
                    $all_good = false;
                }
            }

            $status = $all_good
                ? '<span style="color:green;">&#10003;</span>'
                : '<span style="color:red;">&#10007;</span>';

            echo '<tr>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . esc_html($cpt->label) . '</td>';
            echo '<td><code>' . esc_html($slug) . '</code></td>';
            foreach ($non_default as $l) {
                echo '<td>' . $lang_status[$l] . '</td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    // ----- Page Slugs -----
    echo '<h2 style="margin-top:2rem;">Page Slugs</h2>';
    $pages = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    if (empty($pages)) {
        echo '<p>No published pages found.</p>';
    } else {
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>Status</th><th>Page</th><th>Dutch Slug</th>';
        foreach ($non_default as $l) {
            echo '<th>' . esc_html(strtoupper($l)) . ' Slug</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($pages as $page) {
            $all_good = true;
            $lang_status = [];

            foreach ($non_default as $l) {
                $translated_slug = get_post_meta($page->ID, '_slug_' . $l, true);

                if ($translated_slug) {
                    $lang_status[$l] = '<code>' . esc_html($translated_slug) . '</code>';
                } else {
                    $lang_status[$l] = '<span style="color:red;font-weight:bold;">MISSING</span>';
                    $all_good = false;
                }
            }

            $status = $all_good
                ? '<span style="color:green;">&#10003;</span>'
                : '<span style="color:red;">&#10007;</span>';

            echo '<tr>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . esc_html($page->post_title) . '</td>';
            echo '<td><code>' . esc_html($page->post_name) . '</code></td>';
            foreach ($non_default as $l) {
                echo '<td>' . $lang_status[$l] . '</td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}
