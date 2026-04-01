<?php
/**
 * Snel Media Folders — Entry Point.
 *
 * Adds a folder sidebar to the WordPress Media Library and media modals.
 *
 * @package Snelstack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Install.php';
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/Rest.php';
require_once __DIR__ . '/QueryFilter.php';

// Initialize REST routes and query filters.
new \Snel\MediaFolders\Rest();
new \Snel\MediaFolders\QueryFilter();

// Create tables if they don't exist (runs once, checks version).
add_action( 'after_switch_theme', [ '\\Snel\\MediaFolders\\Install', 'create_tables' ] );

// Also run on init if tables don't exist yet (for development).
add_action( 'init', function() {
	if ( get_option( 'snel_media_folders_db_version' ) !== '1.0' ) {
		\Snel\MediaFolders\Install::create_tables();
		update_option( 'snel_media_folders_db_version', '1.0' );
	}


} );

/**
 * Enqueue the media folders React app on all admin pages.
 */
function snel_media_folders_enqueue( $hook ) {
	$asset_file = get_template_directory() . '/build/admin/media-folders/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	// Only load Tailwind CSS on pages that use the media folders UI.
	// Loading it globally breaks WP default admin list tables.
	$css_pages = array( 'upload.php', 'media-new.php', 'post.php', 'post-new.php' );
	if ( in_array( $hook, $css_pages, true ) || wp_script_is( 'media-editor' ) ) {
		wp_enqueue_style(
			'snel-media-folders',
			get_template_directory_uri() . '/build/admin/media-folders/index.css',
			[],
			$asset['version']
		);
	}

	wp_enqueue_script( 'jquery-ui-draggable' );
	wp_enqueue_script( 'jquery-ui-droppable' );

	wp_enqueue_script(
		'snel-media-folders',
		get_template_directory_uri() . '/build/admin/media-folders/index.js',
		array_merge( $asset['dependencies'], [ 'jquery-ui-draggable', 'jquery-ui-droppable', 'wp-data', 'wp-notices' ] ),
		$asset['version'],
		true
	);

	wp_localize_script( 'snel-media-folders', 'snelMediaFolders', [
		'isUploadScreen' => ( 'upload.php' === $hook ) ? '1' : '0',
		'restUrl'        => rest_url( 'snel/v1' ),
		'nonce'          => wp_create_nonce( 'wp_rest' ),
	] );
}
add_action( 'admin_enqueue_scripts', 'snel_media_folders_enqueue' );
