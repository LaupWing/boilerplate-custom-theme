<?php

/**
 * Theme functions and definitions.
 *
 * @package Snel
 */

if (! defined('ABSPATH')) {
    exit;
}


// Translation system
require get_template_directory() . '/inc/translations/language.php';

if (is_admin()) {
    require get_template_directory() . '/inc/translations/admin/admin-translations.php';
    require get_template_directory() . '/inc/translations/admin-health-check.php';
    require get_template_directory() . '/inc/seeders/seeder.php';
}

// Auto-load modules: scans inc/admin/*, inc/api/*, and inc/*/index.php.
// Skip 'translations' and 'seeders' — they're loaded explicitly above.
$snel_skip_modules = array( 'translations', 'seeders' );
$snel_module_paths = array(
    get_template_directory() . '/inc/*/index.php',
    get_template_directory() . '/inc/admin/*/index.php',
    get_template_directory() . '/inc/api/*/index.php',
);
foreach ( $snel_module_paths as $snel_pattern ) {
    foreach ( glob( $snel_pattern ) as $snel_module_file ) {
        $snel_module_name = basename( dirname( $snel_module_file ) );
        if ( ! in_array( $snel_module_name, $snel_skip_modules, true ) ) {
            require_once $snel_module_file;
        }
    }
}

/**
 * Theme Setup
 */
function snel_setup()
{
    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable featured images
    add_theme_support('post-thumbnails');

    // HTML5 markup support
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));

    // Register navigation menus
    register_nav_menus(array(
        'primary'   => __('Primary Menu', 'snel'),
        'footer'    => __('Footer Menu', 'snel'),
    ));

    // Add support for block styles
    add_theme_support('wp-block-styles');

    // Add support for responsive embeds
    add_theme_support('responsive-embeds');

    // Add support for editor styles
    add_theme_support('editor-styles');
    add_editor_style('build/editor.css');
}
add_action('after_setup_theme', 'snel_setup');

/**
 * Enqueue scripts and styles.
 */
function snel_scripts()
{
    // Google Fonts
    wp_enqueue_style(
        'snel-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    // Tailwind CSS (compiled)
    wp_enqueue_style(
        'snel-tailwind',
        get_template_directory_uri() . '/build/index.css',
        array(),
        filemtime(get_template_directory() . '/build/index.css')
    );

    // Main JS
    wp_enqueue_script(
        'snel-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array(),
        filemtime(get_template_directory() . '/assets/js/main.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'snel_scripts');


/**
 * Register custom Gutenberg blocks.
 * Scans build/blocks/ for block.json files and registers them.
 */
function snel_register_blocks()
{
    $blocks_dir = get_template_directory() . '/build/blocks';

    if (! is_dir($blocks_dir)) {
        return;
    }

    $block_dirs = glob($blocks_dir . '/*/block.json');

    foreach ($block_dirs as $block_json) {
        register_block_type(dirname($block_json));
    }
}
add_action('init', 'snel_register_blocks');

/**
 * Enqueue block editor assets.
 */
function snel_editor_assets()
{
    $editor_css = get_template_directory() . '/build/editor.css';

    if (file_exists($editor_css)) {
        wp_enqueue_style(
            'snel-editor',
            get_template_directory_uri() . '/build/editor.css',
            array(),
            filemtime($editor_css)
        );
    }
}
add_action('enqueue_block_editor_assets', 'snel_editor_assets');

/**
 * Enqueue translation sidebar plugin in the block editor.
 */
function snel_enqueue_editor_plugins()
{
    $asset_file = get_template_directory() . '/build/editor/translator/index.asset.php';
    if (! file_exists($asset_file)) {
        return;
    }

    $asset = require $asset_file;

    wp_enqueue_script(
        'snel-translation-sidebar',
        get_template_directory_uri() . '/build/editor/translator/index.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );
}
add_action('enqueue_block_editor_assets', 'snel_enqueue_editor_plugins');

/**
 * Hide unused admin menu items (Customizer, Site Editor).
 */
function snel_clean_admin_menu()
{
    remove_submenu_page('themes.php', 'customize.php');
    remove_submenu_page('themes.php', 'site-editor.php');
}
add_action('admin_menu', 'snel_clean_admin_menu');
