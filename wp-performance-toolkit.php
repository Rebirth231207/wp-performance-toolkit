<?php
/**
 * Plugin Name:       WordPress Performance Toolkit
 * Plugin URI:        https://github.com/Rebirth231207/wp-performance-toolkit
 * Description:       A developer-first, zero-overhead performance optimization and diagnostics suite.
 * Version:           1.0.0
 * Author:            Mohammad Hadi Salimi
 * Author URI:        https://t.me/Salimi_Developer
 * Text Domain:       wp-performance-toolkit
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 5.8
 * License:           GPLv3
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define Plugin Constants.
 */
define( 'WPPT_VERSION', '1.0.0' );
define( 'WPPT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPPT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load and Initialize the PSR-4 Autoloader.
 */
require_once WPPT_PATH . 'includes/class-autoloader.php';
new WPPT\Includes\Autoloader();

/**
 * Run the Toolkit.
 * We hook into plugins_loaded to ensure dependencies are available.
 */
add_action( 'plugins_loaded', function() {
	// Initialize the Module Manager.
	// This will handle the discovery and loading of all performance modules.
	new WPPT\Includes\Module_Manager();
});