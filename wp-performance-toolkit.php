<?php
/**
 * Plugin Name:       WordPress Performance Toolkit
 * Plugin URI:        https://github.com/Rebirth231207/wp-performance-toolkit
 * Description:       A developer-first, zero-overhead performance optimization and diagnostics suite.
 * Version:           1.0.0
 * Author:            Mohammad Hadi Salimi
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
define( 'WPPT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load the Autoloader.
 */
require_once WPPT_PATH . 'includes/class-autoloader.php';

/**
 * Initialize the Plugin components.
 */
function run_wp_performance_toolkit() {
	// 1. Initialize Autoloader.
	new WPPT\Includes\Autoloader();

	// 2. Initialize Internationalization.
	$i18n = new WPPT\Includes\I18n();
	add_action( 'plugins_loaded', [ $i18n, 'load_textdomain' ] );

	// 3. Initialize Module Manager (Discovers all 30 performance modules).
	new WPPT\Includes\Module_Manager();

	// 4. Initialize Admin Dashboard (Crucial Fix: Calling run()).
	if ( is_admin() ) {
		$admin = new WPPT\Admin\Admin_Settings();
		$admin->run(); // This activates the admin_menu hooks.
	}
}

run_wp_performance_toolkit();