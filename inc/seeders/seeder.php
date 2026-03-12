<?php

/**
 * Page Seeder — creates starter pages for a new project.
 *
 * Admin UI under Tools > Seed Pages.
 * Creates default pages with translated slugs pre-filled.
 *
 * Edit seed-pages.php to customize which pages are created.
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
        'Seed Pages',
        'Seed Pages',
        'manage_options',
        'bp-seed-pages',
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

    echo '<div class="wrap">';
    echo '<h1>Seed Pages</h1>';
    echo '<p>Creates starter pages for your project with translated URL slugs pre-filled.</p>';

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
            echo '<li style="color:' . $color . ';padding:2px 0;">• ' . esc_html($log['message']) . '</li>';
        }
        echo '</ul></div>';
    }

    // Show current pages
    $existing = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    if (! empty($existing)) {
        echo '<h3>Existing Pages (' . count($existing) . ')</h3>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>Title</th><th>Slug</th><th>Status</th>';

        $langs   = bp_get_supported_langs();
        $default = bp_get_default_lang();
        foreach ($langs as $lang) {
            if ($lang !== $default) {
                echo '<th>' . esc_html(strtoupper($lang)) . ' Slug</th>';
            }
        }
        echo '</tr></thead><tbody>';

        foreach ($existing as $page) {
            echo '<tr>';
            echo '<td>' . esc_html($page->post_title) . '</td>';
            echo '<td><code>' . esc_html($page->post_name) . '</code></td>';
            echo '<td>' . esc_html($page->post_status) . '</td>';
            foreach ($langs as $lang) {
                if ($lang !== $default) {
                    $slug = get_post_meta($page->ID, '_slug_' . $lang, true);
                    echo '<td>' . ($slug ? '<code>' . esc_html($slug) . '</code>' : '<span style="color:#888;">—</span>') . '</td>';
                }
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    // Seed form
    echo '<form method="post" style="margin-top:20px;">';
    wp_nonce_field('bp_seed_action');
    echo '<p><label>';
    echo '<input type="checkbox" name="bp_seed_reset" value="1" /> ';
    echo '<strong>Reset & Reseed</strong> — Delete all existing pages first';
    echo '</label></p>';
    echo '<p><input type="submit" class="button button-primary" value="Seed Pages" /></p>';
    echo '</form>';

    echo '</div>';
}

/**
 * Run the seeder.
 *
 * @param bool $reset Whether to delete existing pages first.
 */
function bp_run_seeder($reset = false)
{
    $pages_config = require get_template_directory() . '/inc/seeders/seed-pages.php';

    if ($reset) {
        $existing = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        foreach ($existing as $page_id) {
            wp_delete_post($page_id, true);
        }

        bp_seed_log('Deleted ' . count($existing) . ' existing pages', 'info');
    }

    $default_lang = bp_get_default_lang();
    $front_page_id = null;

    foreach ($pages_config as $page) {
        // Check if page already exists
        $existing = get_page_by_path($page['slug']);
        if ($existing) {
            bp_seed_log($page['title'] . ' — already exists, skipping', 'skip');

            if (! empty($page['is_front_page'])) {
                $front_page_id = $existing->ID;
            }
            continue;
        }

        // Create the page
        $post_id = wp_insert_post([
            'post_title'   => $page['title'],
            'post_name'    => $page['slug'],
            'post_content' => $page['content'] ?? '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);

        if (is_wp_error($post_id)) {
            bp_seed_log($page['title'] . ' — ERROR: ' . $post_id->get_error_message(), 'error');
            continue;
        }

        // Save translated slugs
        if (! empty($page['slugs'])) {
            foreach ($page['slugs'] as $lang => $translated_slug) {
                update_post_meta($post_id, '_slug_' . $lang, sanitize_title($translated_slug));
            }
        }

        // Apply page template if set
        if (! empty($page['template'])) {
            update_post_meta($post_id, '_wp_page_template', $page['template']);
        }

        if (! empty($page['is_front_page'])) {
            $front_page_id = $post_id;
        }

        $slug_info = '';
        if (! empty($page['slugs'])) {
            $parts = [];
            foreach ($page['slugs'] as $lang => $s) {
                $parts[] = strtoupper($lang) . ': ' . $s;
            }
            $slug_info = ' (' . implode(', ', $parts) . ')';
        }

        bp_seed_log($page['title'] . ' — created' . $slug_info, 'ok');
    }

    // Set front page
    if ($front_page_id) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $front_page_id);
        bp_seed_log('Set "' . get_the_title($front_page_id) . '" as front page', 'ok');
    }

    // Flush rewrite rules so language URLs work
    flush_rewrite_rules();
    bp_seed_log('Flushed rewrite rules', 'info');
}
