<?php

/**
 * Theme functions and definitions.
 *
 * @package Boilerplate
 */

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Theme Setup
 */
function boilerplate_setup()
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
        'primary'   => __('Primary Menu', 'boilerplate'),
        'footer'    => __('Footer Menu', 'boilerplate'),
    ));

    // Add support for block styles
    add_theme_support('wp-block-styles');

    // Add support for responsive embeds
    add_theme_support('responsive-embeds');

    // Add support for editor styles
    add_theme_support('editor-styles');
    add_editor_style('build/editor.css');
}
add_action('after_setup_theme', 'boilerplate_setup');

/**
 * Enqueue scripts and styles.
 */
function boilerplate_scripts()
{
    // Google Fonts
    wp_enqueue_style(
        'boilerplate-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    // Tailwind CSS (compiled)
    wp_enqueue_style(
        'boilerplate-tailwind',
        get_template_directory_uri() . '/build/index.css',
        array(),
        filemtime(get_template_directory() . '/build/index.css')
    );

    // Main JS
    wp_enqueue_script(
        'boilerplate-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array(),
        filemtime(get_template_directory() . '/assets/js/main.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'boilerplate_scripts');

/**
 * Register widget areas.
 */
function boilerplate_widgets_init()
{
    register_sidebar(array(
        'name'          => __('Sidebar', 'boilerplate'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here.', 'boilerplate'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Widget Area', 'boilerplate'),
        'id'            => 'footer-1',
        'description'   => __('Add footer widgets here.', 'boilerplate'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'boilerplate_widgets_init');

/**
 * Register custom Gutenberg blocks.
 * Scans build/blocks/ for block.json files and registers them.
 */
function boilerplate_register_blocks()
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
add_action('init', 'boilerplate_register_blocks');

/**
 * Enqueue block editor assets.
 */
function boilerplate_editor_assets()
{
    $editor_css = get_template_directory() . '/build/editor.css';

    if (file_exists($editor_css)) {
        wp_enqueue_style(
            'boilerplate-editor',
            get_template_directory_uri() . '/build/editor.css',
            array(),
            filemtime($editor_css)
        );
    }
}
add_action('enqueue_block_editor_assets', 'boilerplate_editor_assets');
