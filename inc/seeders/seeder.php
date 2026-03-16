<?php

/**
 * Content Seeder — creates starter pages and blog posts.
 *
 * Admin UI under Tools > Seed Content.
 * Creates default pages and blog posts with translated slugs
 * and content-section blocks pre-filled.
 *
 * Edit seed-pages.php and seed-posts.php to customize.
 *
 * @package Boilerplate
 */

if (! defined('ABSPATH')) {
    exit;
}

// Global log collector
$bp_seed_logs = [];

function bp_seed_log($message, $type = 'ok')
{
    global $bp_seed_logs;
    $bp_seed_logs[] = ['message' => $message, 'type' => $type];
}

/**
 * Register the seeder admin page under Tools.
 */
function bp_seeder_menu()
{
    add_management_page(
        'Seed Content',
        'Seed Content',
        'manage_options',
        'bp-seed-content',
        'bp_seeder_page'
    );
}
add_action('admin_menu', 'bp_seeder_menu');

/**
 * Render the seeder admin page.
 */
function bp_seeder_page()
{
    global $bp_seed_logs;

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('bp_seed_action')) {
        $reset = ! empty($_POST['bp_seed_reset']);
        bp_run_seeder($reset);
    }

    $langs   = bp_get_supported_langs();
    $default = bp_get_default_lang();

    echo '<div class="wrap">';
    echo '<h1>Seed Content</h1>';
    echo '<p>Creates starter pages and blog posts with translated slugs and content-section blocks pre-filled.</p>';
    echo '<p><strong>Languages:</strong> ' . esc_html(implode(', ', array_map('strtoupper', $langs))) . ' (default: ' . esc_html(strtoupper($default)) . ')</p>';

    // Show logs if we just ran
    if (! empty($bp_seed_logs)) {
        echo '<div style="background:#fff;border:1px solid #ccd0d4;padding:12px 16px;margin:16px 0;border-radius:4px;">';
        echo '<h3 style="margin-top:0;">Results</h3>';
        echo '<ul style="margin:0;padding:0;list-style:none;">';
        foreach ($bp_seed_logs as $log) {
            $color = match ($log['type']) {
                'ok'    => 'green',
                'error' => 'red',
                'skip'  => '#888',
                default => '#333',
            };
            echo '<li style="color:' . $color . ';padding:2px 0;">&bull; ' . esc_html($log['message']) . '</li>';
        }
        echo '</ul></div>';
    }

    // Show current pages
    bp_seeder_show_table('Pages', 'page', $langs, $default);

    // Show current posts
    bp_seeder_show_table('Blog Posts', 'post', $langs, $default);

    // Seed form
    echo '<form method="post" style="margin-top:20px;">';
    wp_nonce_field('bp_seed_action');
    echo '<p><label>';
    echo '<input type="checkbox" name="bp_seed_reset" value="1" /> ';
    echo '<strong>Reset &amp; Reseed</strong> — Delete all existing pages and posts first';
    echo '</label></p>';
    echo '<p><input type="submit" class="button button-primary" value="Seed Content" /></p>';
    echo '</form>';

    echo '</div>';
}

/**
 * Show an admin table for a post type.
 */
function bp_seeder_show_table($label, $post_type, $langs, $default)
{
    $existing = get_posts([
        'post_type'      => $post_type,
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    if (empty($existing)) {
        echo '<h3>' . esc_html($label) . ' (0)</h3>';
        echo '<p style="color:#888;">No ' . esc_html(strtolower($label)) . ' found.</p>';
        return;
    }

    echo '<h3>' . esc_html($label) . ' (' . count($existing) . ')</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>Title</th><th>Slug</th><th>Status</th>';

    foreach ($langs as $lang) {
        if ($lang !== $default) {
            echo '<th>' . esc_html(strtoupper($lang)) . ' Slug</th>';
        }
    }
    echo '</tr></thead><tbody>';

    foreach ($existing as $item) {
        echo '<tr>';
        echo '<td>' . esc_html($item->post_title) . '</td>';
        echo '<td><code>' . esc_html($item->post_name) . '</code></td>';
        echo '<td>' . esc_html($item->post_status) . '</td>';
        foreach ($langs as $lang) {
            if ($lang !== $default) {
                $slug = get_post_meta($item->ID, '_slug_' . $lang, true);
                echo '<td>' . ($slug ? '<code>' . esc_html($slug) . '</code>' : '<span style="color:#888;">&mdash;</span>') . '</td>';
            }
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

/**
 * Run the seeder.
 *
 * @param bool $reset Whether to delete existing content first.
 */
function bp_run_seeder($reset = false)
{
    $pages_config = require get_template_directory() . '/inc/seeders/seed-pages.php';
    $posts_config = require get_template_directory() . '/inc/seeders/seed-posts.php';

    if ($reset) {
        foreach (['page', 'post'] as $pt) {
            $existing = get_posts([
                'post_type'      => $pt,
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);

            foreach ($existing as $pid) {
                wp_delete_post($pid, true);
            }

            bp_seed_log('Deleted ' . count($existing) . ' existing ' . $pt . 's', 'info');
        }
    }

    // Seed pages
    $front_page_id = null;

    foreach ($pages_config as $page) {
        $existing = get_page_by_path($page['slug']);
        if ($existing) {
            bp_seed_log('[Page] ' . $page['title'] . ' — already exists, skipping', 'skip');

            if (! empty($page['is_front_page'])) {
                $front_page_id = $existing->ID;
            }
            continue;
        }

        $post_id = wp_insert_post([
            'post_title'   => $page['title'],
            'post_name'    => $page['slug'],
            'post_content' => $page['content'] ?? '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);

        if (is_wp_error($post_id)) {
            bp_seed_log('[Page] ' . $page['title'] . ' — ERROR: ' . $post_id->get_error_message(), 'error');
            continue;
        }

        if (! empty($page['slugs'])) {
            foreach ($page['slugs'] as $lang => $translated_slug) {
                update_post_meta($post_id, '_slug_' . $lang, sanitize_title($translated_slug));
            }
        }

        if (! empty($page['template'])) {
            update_post_meta($post_id, '_wp_page_template', $page['template']);
        }

        if (! empty($page['is_front_page'])) {
            $front_page_id = $post_id;
        }

        $slug_info = bp_seed_slug_info($page['slugs'] ?? []);
        bp_seed_log('[Page] ' . $page['title'] . ' — created' . $slug_info, 'ok');
    }

    // Set front page
    if ($front_page_id) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $front_page_id);
        bp_seed_log('Set "' . get_the_title($front_page_id) . '" as front page', 'ok');
    }

    // Seed blog posts
    foreach ($posts_config as $post_data) {
        $existing = get_posts([
            'post_type' => 'post',
            'name'      => $post_data['slug'],
            'posts_per_page' => 1,
        ]);

        if (! empty($existing)) {
            bp_seed_log('[Post] ' . $post_data['title'] . ' — already exists, skipping', 'skip');
            continue;
        }

        $post_id = wp_insert_post([
            'post_title'   => $post_data['title'],
            'post_name'    => $post_data['slug'],
            'post_content' => $post_data['content'] ?? '',
            'post_excerpt' => $post_data['excerpt'] ?? '',
            'post_status'  => 'publish',
            'post_type'    => 'post',
        ]);

        if (is_wp_error($post_id)) {
            bp_seed_log('[Post] ' . $post_data['title'] . ' — ERROR: ' . $post_id->get_error_message(), 'error');
            continue;
        }

        if (! empty($post_data['slugs'])) {
            foreach ($post_data['slugs'] as $lang => $translated_slug) {
                update_post_meta($post_id, '_slug_' . $lang, sanitize_title($translated_slug));
            }
        }

        $slug_info = bp_seed_slug_info($post_data['slugs'] ?? []);
        bp_seed_log('[Post] ' . $post_data['title'] . ' — created' . $slug_info, 'ok');
    }

    // Flush rewrite rules so language URLs work
    flush_rewrite_rules();
    bp_seed_log('Flushed rewrite rules', 'info');
}

/**
 * Format slug info string for log output.
 */
function bp_seed_slug_info($slugs)
{
    if (empty($slugs)) {
        return '';
    }
    $parts = [];
    foreach ($slugs as $lang => $s) {
        $parts[] = strtoupper($lang) . ': ' . $s;
    }
    return ' (' . implode(', ', $parts) . ')';
}
