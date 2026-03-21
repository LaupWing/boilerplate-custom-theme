<?php
/**
 * SEO Module — Entry Point.
 *
 * Loads SEO sub-modules and registers the React admin page.
 *
 * @package Boilerplate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// SEO sub-modules
require_once __DIR__ . '/sitemap.php';
require_once __DIR__ . '/structured-data.php';
require_once __DIR__ . '/open-graph.php';

/**
 * Register the SEO top-level admin menu page.
 */
add_action( 'admin_menu', function () {
    add_menu_page(
        __( 'SEO', 'boilerplate' ),
        __( 'SEO', 'boilerplate' ),
        'manage_options',
        'bp-seo',
        'bp_seo_render_page',
        'dashicons-search',
        30
    );
} );

/**
 * Render the SEO admin page container (React mounts here).
 */
function bp_seo_render_page() {
    echo '<div id="bp-seo-root" class="wrap"></div>';
}

/**
 * Enqueue the SEO React app on the SEO admin page only.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'toplevel_page_bp-seo' !== $hook ) {
        return;
    }

    $asset_file = get_template_directory() . '/build/admin/seo/index.asset.php';

    if ( ! file_exists( $asset_file ) ) {
        return;
    }

    $asset = require $asset_file;

    wp_enqueue_script(
        'bp-seo-admin',
        get_template_directory_uri() . '/build/admin/seo/index.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );

    wp_enqueue_style(
        'wp-components'
    );
} );
